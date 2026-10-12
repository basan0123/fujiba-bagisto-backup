"""WS-L fabric digital twin — shared constants + deterministic image helpers.

HARD RULE: every fabric pixel in a tile or render comes from a real photo pixel through
deterministic geometry only (crop, rotation, affine/homography, scaling with standard
interpolation). No inpainting, synthesis, AI upscaling, median-stacking or de-wrinkling.
Illumination flattening changes pixel values: allowed only as a labelled, gated colour
correction (FLATTEN_GAIN_MAX), recorded in fi_twin_attempts, and it caps the tier at B.

PRE-REGISTERED THRESHOLDS — written down and committed BEFORE any seam/lattice measurement
was run on catalog photos (2026-10-12). They mirror config/fujiba_intel.php fabric_gate
(CIEDE2000 mean 2.0 / p95 5.0 on sigma-1.5 blurred pixels) so a tile that passes its seam
check is held to the same colour tolerance as a published render. Do not tune them on the
data; change them only with a new VERSION and a written reason.

  Lattice (is this really one repeat?)
    LATTICE_NCC_MIN 0.80   zero-mean NCC (L*a*b*, multichannel) between the photo region and
                           itself shifted by each lattice basis vector, over the overlap.
  Seam (does the tile wrap without a visible join?) — measured at SOURCE resolution on bands
  of SEAM_BAND px: tile edge band vs the real fabric continuation one period away.
    SEAM_DE00_MEAN_MAX 2.0 CIEDE2000 mean, both bands Gaussian-blurred sigma 1.5 px
    SEAM_DE00_P95_MAX  5.0 CIEDE2000 95th percentile (same pixels)
    SEAM_DISSIM_MAX    0.10 1 - NCC(L*) across the wrap boundary (pattern continuity)
    Both seams (u and w direction) must pass every limit.
  Artefacts (watermark / sticker / glare inside the tile) — tile vs its lattice copies in
  the same photo:
    ARTEFACT_DE00 10.0, ARTEFACT_SHARE_MAX 0.005: at most 0.5 % of tile pixels may differ by
    more than dE00 10 from EVERY available copy (non-periodic content = not the print).
  Fixes
    ROTATE_FREE_DEG 0.5    |rotation| and |skew| of the repeat up to this = plain crop (A);
                           more = deterministic rotate/deskew fix (B).
    FLATTEN_GAIN_MAX 0.15  illumination flattening only if the gain field stays within ±15 %.
  Audit / tier
    SHARP_MIN 150          Laplacian variance at max side 800 px (cat-1.1 'sharp' band)
    CAST_MAX 6.0           neutral-pixel a*b* magnitude (cat-1.1 grayworld) for colour 'reliable'
    EVEN_MIN 0.60          lighting evenness = 1 - illumination range / 20 L*  (range <= 8 L*)

TIERS
  A  own photo (not supplier), sharp, flat, >=1 full repeat both axes, tile passes lattice +
     seam + artefact gates with crop-only geometry (rotation/skew <= 0.5 deg), no colour
     change, cast <= 6, evenness >= 0.60. Colour is "as photographed": no product has a
     colour card, so A never means colorimetrically calibrated.
  B  same gates pass only after a deterministic fix of an existing own photo (rotate/deskew,
     homography from fabric edges, labelled illumination flattening), or colour not reliable
     (cast > 6 / evenness < 0.60) while geometry passes.
  C  no own photo yields a gated tile -> new shoot (or not yardage: applicable = 0).
Supplier images are INTERNAL reference only (matching/colour): never a tile source.
"""
import json
import os
import subprocess
import sys
import tempfile

import cv2
import numpy as np

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
from fabric_gate import ciede2000  # noqa: E402  (same CIEDE2000 as the publish gate)

VERSION = 'twin-1.0'

LATTICE_NCC_MIN = 0.80
SEAM_DE00_MEAN_MAX = 2.0
SEAM_DE00_P95_MAX = 5.0
SEAM_DISSIM_MAX = 0.10
SEAM_BLUR_SIGMA = 1.5
ARTEFACT_DE00 = 10.0
ARTEFACT_SHARE_MAX = 0.005
ROTATE_FREE_DEG = 0.5
FLATTEN_GAIN_MAX = 0.15
SHARP_MIN = 150.0
CAST_MAX = 6.0
EVEN_MIN = 0.60

DETECT_MAX_SIDE = 640      # lattice search resolution (FFT size stays bounded); vectors refined at full res
WORK_MAX_SIDE = 2400       # tile extraction resolution cap (12 MP phone photos are downscaled)
MIN_TILE_PX = 24

cv2.setNumThreads(2)
cv2.setRNGSeed(1234)


def guard(name):
    """Mandatory resource guard: shared heavy flock + 3 GB address-space cap."""
    sys.path.insert(0, '/var/www/bagisto/packages/Fujiba/AutoPoster/bin/intel')
    try:
        __import__('heavy_guard').acquire(name)
    except ImportError:          # worktree copy (identical file)
        sys.path.insert(0, HERE)
        __import__('heavy_guard').acquire(name)


# ----------------------------------------------------------------------------- IO

def load_bgr(path):
    """BGR uint8 with EXIF orientation applied. HEIC/HEIF via ImageMagick (read-only)."""
    img = cv2.imread(path, cv2.IMREAD_COLOR)
    if img is not None:
        return img
    with tempfile.TemporaryDirectory() as td:
        out = os.path.join(td, 'x.png')
        r = subprocess.run(['magick', path + '[0]', '-auto-orient', out], capture_output=True, timeout=120)
        if r.returncode == 0:
            return cv2.imread(out, cv2.IMREAD_COLOR)
    return None


def resize_max(img, max_side, interp=cv2.INTER_AREA):
    h, w = img.shape[:2]
    s = max_side / float(max(h, w))
    if s >= 1:
        return img, 1.0
    return cv2.resize(img, (max(1, int(round(w * s))), max(1, int(round(h * s)))), interpolation=interp), s


def lab32(bgr):
    """Real-valued CIELAB (L 0..100), float32, sRGB companding handled by OpenCV."""
    return cv2.cvtColor(bgr.astype(np.float32) / 255.0, cv2.COLOR_BGR2LAB)


def de00(bgr1, bgr2, sigma=SEAM_BLUR_SIGMA):
    """Per-pixel CIEDE2000 of two same-size BGR uint8 patches after Gaussian blur."""
    if sigma:
        bgr1 = cv2.GaussianBlur(bgr1, (0, 0), sigma)
        bgr2 = cv2.GaussianBlur(bgr2, (0, 0), sigma)
    return ciede2000(lab32(bgr1).astype(np.float64), lab32(bgr2).astype(np.float64))


def ncc(a, b, mask=None):
    a = a.astype(np.float64).ravel()
    b = b.astype(np.float64).ravel()
    if mask is not None:
        m = mask.ravel().astype(bool)
        a, b = a[m], b[m]
    if a.size < 16:
        return None
    a = a - a.mean()
    b = b - b.mean()
    den = np.sqrt((a * a).sum() * (b * b).sum())
    if den < 1e-9:
        return None
    return float((a * b).sum() / den)


# ------------------------------------------------------------- lattice detection

def _masked_ncc_map(chans, m):
    """Masked normalised auto-correlation (Padfield 2012) of a multichannel image with itself.

    chans: list of float32 HxW arrays (zero-mean-ish); m: HxW {0,1} float32.
    Returns (ncc, overlap) for every shift v=(dy,dx) in fftshifted layout of size (2H-1, 2W-1),
    centre index (H-1, W-1) = zero shift. NCC is pooled over channels.
    """
    H, W = m.shape
    sh = (2 * H, 2 * W)
    Mf = np.fft.rfft2(m, sh)
    N = np.fft.irfft2(np.conj(Mf) * Mf, sh)
    num = np.zeros(sh)
    d1 = np.zeros(sh)
    d2 = np.zeros(sh)
    for f in chans:
        fm = f * m
        FMf = np.fft.rfft2(fm, sh)
        F2f = np.fft.rfft2(fm * f, sh)
        Sa = np.fft.irfft2(np.conj(FMf) * Mf, sh)
        Sb = np.fft.irfft2(np.conj(Mf) * FMf, sh)
        Sab = np.fft.irfft2(np.conj(FMf) * FMf, sh)
        Saa = np.fft.irfft2(np.conj(F2f) * Mf, sh)
        Sbb = np.fft.irfft2(np.conj(Mf) * F2f, sh)
        Nn = np.maximum(N, 1.0)
        num += Sab - Sa * Sb / Nn
        d1 += Saa - Sa * Sa / Nn
        d2 += Sbb - Sb * Sb / Nn
    den = np.sqrt(np.maximum(d1, 0) * np.maximum(d2, 0))
    out = np.where(den > 1e-6, num / np.maximum(den, 1e-6), 0.0)
    out = np.fft.fftshift(out)[1:, 1:]
    N = np.fft.fftshift(N)[1:, 1:]
    return out.astype(np.float32), N.astype(np.float32)


def _prep_chans(bgr, mask=None):
    lab = lab32(bgr)
    chans = []
    for c in range(3):
        f = lab[..., c]
        if mask is not None and mask.any():
            f = f - float(f[mask > 0].mean())
        else:
            f = f - float(f.mean())
        chans.append(f.astype(np.float32))
    return chans


def _gauss_reduce(b1, b2):
    b1, b2 = np.array(b1, float), np.array(b2, float)
    for _ in range(20):
        if np.dot(b2, b2) < np.dot(b1, b1):
            b1, b2 = b2, b1
        mu = round(np.dot(b1, b2) / np.dot(b1, b1))
        if mu == 0:
            break
        b2 = b2 - mu * b1
    return b1, b2


def _ncc_int(lab, mask, dx, dy, stride=2):
    """Multichannel zero-mean NCC of lab vs lab shifted by integer (dx,dy), over the masked overlap."""
    H, W = mask.shape
    xa0, xa1 = max(0, -dx), min(W, W - dx)
    ya0, ya1 = max(0, -dy), min(H, H - dy)
    if xa1 - xa0 < 16 or ya1 - ya0 < 16:
        return None
    A = lab[ya0:ya1:stride, xa0:xa1:stride]
    B = lab[ya0 + dy:ya1 + dy:stride, xa0 + dx:xa1 + dx:stride]
    m = (mask[ya0:ya1:stride, xa0:xa1:stride] > 0) & (mask[ya0 + dy:ya1 + dy:stride, xa0 + dx:xa1 + dx:stride] > 0)
    if m.sum() < 256:
        return None
    x = A[m].astype(np.float64)
    y = B[m].astype(np.float64)
    x -= x.mean(0)
    y -= y.mean(0)
    den = np.sqrt((x * x).sum() * (y * y).sum())
    return float((x * y).sum() / den) if den > 1e-9 else None


def ncc_at(bgr, mask, v, lab=None):
    """NCC of the region vs itself shifted by the (rounded) vector v=(dx,dy)."""
    lab = lab32(bgr) if lab is None else lab
    return _ncc_int(lab, mask, int(round(v[0])), int(round(v[1]))), None


def refine_vector(bgr, mask, v, radius=None, lab=None, coarse_px=1.0):
    """Integer search around v (radius ~ detection quantum), then parabolic sub-pixel peak."""
    lab = lab32(bgr) if lab is None else lab
    r = int(radius if radius is not None else max(2, int(np.ceil(coarse_px)) + 1))
    cx, cy = int(round(v[0])), int(round(v[1]))
    grid = {}
    for dy in range(-r, r + 1):
        for dx in range(-r, r + 1):
            c = _ncc_int(lab, mask, cx + dx, cy + dy)
            if c is not None:
                grid[(dx, dy)] = c
    if not grid:
        return np.array(v, float), -1.0
    (bx, by), bc = max(grid.items(), key=lambda kv: kv[1])
    # if the max sits on the window edge, walk once more
    if abs(bx) == r or abs(by) == r:
        return refine_vector(bgr, mask, (cx + bx, cy + by), r, lab)
    def para(m1, c0, p1):
        d = m1 - 2 * c0 + p1
        return 0.0 if abs(d) < 1e-9 else float(np.clip(0.5 * (m1 - p1) / d, -0.5, 0.5))
    sx = para(grid.get((bx - 1, by), bc), bc, grid.get((bx + 1, by), bc))
    sy = para(grid.get((bx, by - 1), bc), bc, grid.get((bx, by + 1), bc))
    return np.array([cx + bx + sx, cy + by + sy], float), float(bc)


def detect_lattice(bgr, mask=None, min_overlap=0.15):
    """Find the print repeat lattice of a region.

    Returns dict(found, b1, b2 (px, region coords, (dx,dy)), ncc1, ncc2, peaks, reason).
    Detection runs at <= DETECT_MAX_SIDE; vectors are scaled back to region pixels and refined
    with direct NCC at region resolution.
    """
    H0, W0 = bgr.shape[:2]
    if mask is None:
        mask = np.ones((H0, W0), np.uint8)
    small, s = resize_max(bgr, DETECT_MAX_SIDE)
    msmall = cv2.resize(mask.astype(np.uint8), (small.shape[1], small.shape[0]), interpolation=cv2.INTER_NEAREST)
    H, W = msmall.shape
    if H < 48 or W < 48:
        return {'found': False, 'reason': 'region too small for lattice search'}
    chans = _prep_chans(small, msmall)
    if float(np.std(chans[0][msmall > 0])) < 2.0:
        return {'found': False, 'reason': 'flat region (no texture)'}
    ncc_map, N = _masked_ncc_map(chans, msmall.astype(np.float32))
    cy, cx = H - 1, W - 1
    n0 = float(N[cy, cx])
    valid = N >= min_overlap * n0
    # half-width of the zero peak -> minimum lag (avoid the central blob)
    ys, xs = np.indices(ncc_map.shape)
    r = np.hypot(ys - cy, xs - cx)
    prof = np.bincount(r.astype(int).ravel(), ncc_map.ravel()) / np.maximum(1, np.bincount(r.astype(int).ravel()))
    below = np.where(prof < 0.5)[0]
    rhalf = int(below[0]) if below.size else 4
    min_lag = max(6, 2 * rhalf)
    dil = cv2.dilate(ncc_map, np.ones((5, 5), np.uint8))
    pk = (ncc_map >= dil - 1e-7) & (ncc_map >= 0.30) & valid & (r >= min_lag)
    # half plane only (autocorrelation symmetry)
    pk &= (ys > cy) | ((ys == cy) & (xs > cx))
    py, px = np.where(pk)
    if py.size == 0:
        return {'found': False, 'reason': 'no autocorrelation peak >= 0.30 (repeat larger than view or non-repeating)',
                'min_lag': min_lag}
    vals = ncc_map[py, px]
    order = np.argsort(-vals)[:60]
    peaks = [((float(px[i] - cx)) / s, (float(py[i] - cy)) / s, float(vals[i])) for i in order]
    best = peaks[0][2]
    if best < 0.45:
        return {'found': False, 'reason': f'only weak periodicity (best autocorrelation peak {best:.2f} < 0.45)',
                'best_peak': round(best, 3), 'peaks': [[round(a, 1), round(b, 1), round(c, 3)] for a, b, c in peaks[:8]]}
    labr = lab32(bgr)
    # shortest vector within 5 % of the best peak: sub-periods (same motif shape, different colour)
    # score clearly lower; multiples of the true period score about the same but are longer
    strong = [p for p in peaks if p[2] >= 0.95 * best]
    strong.sort(key=lambda p: np.hypot(p[0], p[1]))
    v1 = strong[0]
    v2 = None
    second = sorted([p for p in peaks if p[2] >= 0.90 * best], key=lambda p: np.hypot(p[0], p[1]))
    for p in second:
        cosang = abs(p[0] * v1[0] + p[1] * v1[1]) / (np.hypot(*p[:2]) * np.hypot(*v1[:2]) + 1e-9)
        if cosang <= np.cos(np.radians(20)):
            v2 = p
            break
    res = {'found': False, 'min_lag_px': round(min_lag / s, 1), 'best_peak': round(best, 3),
           'peaks': [[round(a, 1), round(b, 1), round(c, 3)] for a, b, c in peaks[:8]]}
    if v2 is None:
        # 1-D periodic: stripes along v1-perp are constant if NCC stays high along the perpendicular
        perp = np.array([-v1[1], v1[0]]) / (np.hypot(v1[0], v1[1]) + 1e-9)
        L = 0.25 * min(H0, W0)
        c, _ = ncc_at(bgr, mask, perp * L, lab=labr)
        if c is not None and c >= 0.9:
            v2 = (perp[0] * L, perp[1] * L, c)
            res['stripe'] = True
        else:
            res['reason'] = 'only one repeat direction visible (other axis larger than view)'
            res['b1'] = [round(v1[0], 2), round(v1[1], 2)]
            res['ncc1'] = round(v1[2], 3)
            return res
    b1, b2 = _gauss_reduce(v1[:2], v2[:2])
    b1, n1 = refine_vector(bgr, mask, b1, lab=labr, coarse_px=1.0 / s)
    b2, n2 = refine_vector(bgr, mask, b2, lab=labr, coarse_px=1.0 / s)
    res.update(b1=[round(float(b1[0]), 3), round(float(b1[1]), 3)],
               b2=[round(float(b2[0]), 3), round(float(b2[1]), 3)],
               ncc1=round(float(n1), 4), ncc2=round(float(n2), 4))
    res['found'] = True
    res['passes'] = bool(n1 >= LATTICE_NCC_MIN and n2 >= LATTICE_NCC_MIN)
    if not res['passes']:
        res['reason'] = f'lattice NCC {min(n1, n2):.3f} < {LATTICE_NCC_MIN}'
    return res


def supercell(b1, b2, max_mult=4):
    """Rectangular super-cell (u ~ horizontal, w ~ perpendicular) from a reduced basis.

    Returns u, w (dx,dy), rotation_deg (u vs x axis), skew_deg (angle(u,w) - 90)."""
    b1, b2 = np.array(b1, float), np.array(b2, float)
    combos = []
    for i in range(-max_mult, max_mult + 1):
        for j in range(-max_mult, max_mult + 1):
            if i == 0 and j == 0:
                continue
            v = i * b1 + j * b2
            combos.append(v)
    combos = np.array(combos)
    lens = np.hypot(combos[:, 0], combos[:, 1])
    ang = np.degrees(np.arctan2(combos[:, 1], combos[:, 0]))
    horiz = np.minimum(np.abs(ang), 180 - np.abs(ang))
    u = None
    for lim in (10, 30, 90):
        sel = np.where((horiz <= lim) & (combos[:, 0] > 0))[0]
        if sel.size:
            u = combos[sel[np.argmin(lens[sel])]]
            break
    un = u / np.hypot(*u)
    cosw = np.abs(combos @ un) / np.maximum(lens, 1e-9)
    devdeg = 90 - np.degrees(np.arccos(np.clip(cosw, 0, 1)))
    w = None
    for lim in (0.6, 1.5, 4.0, 90):
        sel = np.where((devdeg <= lim) & (combos[:, 1] > 0))[0]
        if sel.size:
            w = combos[sel[np.argmin(lens[sel])]]
            break
    rot = float(np.degrees(np.arctan2(u[1], u[0])))
    skew = float(np.degrees(np.arccos(np.clip(np.dot(u, w) / (np.hypot(*u) * np.hypot(*w)), -1, 1))) - 90)
    return u, w, rot, skew


# ------------------------------------------------------------------ tile sampling

def grid_maps(origin, u, w, nu, nw, i0=0, j0=0, ni=None, nj=None):
    """Sampling maps for tile pixels (i,j): p = origin + (i/nu) u + (j/nw) w."""
    ni = nu if ni is None else ni
    nj = nw if nj is None else nj
    ii, jj = np.meshgrid(np.arange(i0, i0 + ni, dtype=np.float64), np.arange(j0, j0 + nj, dtype=np.float64))
    x = origin[0] + ii / nu * u[0] + jj / nw * w[0]
    y = origin[1] + ii / nu * u[1] + jj / nw * w[1]
    return x.astype(np.float32), y.astype(np.float32)


def sample(img, mx, my):
    return cv2.remap(img, mx, my, interpolation=cv2.INTER_LINEAR, borderMode=cv2.BORDER_REFLECT)


def inside(mask, mx, my):
    h, w = mask.shape
    xi, yi = np.round(mx).astype(int), np.round(my).astype(int)
    ok = (xi >= 0) & (yi >= 0) & (xi < w) & (yi < h)
    out = np.zeros(mx.shape, bool)
    out[ok] = mask[yi[ok], xi[ok]] > 0
    return out


def seam_check(img, mask, origin, u, w, nu, nw, band=None):
    """Seam gate: tile edge band vs the real fabric one period away (source px).

    For the u-seam, tile column i is compared with source column i+nu (side +1, after the far
    edge) or tile column nu-band..nu with columns -band..0 (side -1, before the start); same for
    the w-seam with rows. If the tile is exactly one repeat these pixels are the same print.
    """
    band = band or int(np.clip(round(0.02 * min(nu, nw)), 4, 16))
    pad = 6
    out = {'band_px': band}
    worst = {'de_mean': 0.0, 'de_p95': 0.0, 'dissim': 0.0}
    ok_all = True
    for name in ('u', 'w'):
        n_d = nu if name == 'u' else nw
        n_o = nw if name == 'u' else nu
        best = None
        for side in (+1, -1):
            d0 = -pad if side > 0 else n_d - band - pad
            shift = n_d if side > 0 else -n_d
            if name == 'u':
                A = grid_maps(origin, u, w, nu, nw, d0, -pad, band + 2 * pad, n_o + 2 * pad)
                B = grid_maps(origin, u, w, nu, nw, d0 + shift, -pad, band + 2 * pad, n_o + 2 * pad)
            else:
                A = grid_maps(origin, u, w, nu, nw, -pad, d0, n_o + 2 * pad, band + 2 * pad)
                B = grid_maps(origin, u, w, nu, nw, -pad, d0 + shift, n_o + 2 * pad, band + 2 * pad)
            if inside(mask, *A).mean() < 0.98 or inside(mask, *B).mean() < 0.98:
                continue
            pa, pb = sample(img, *A), sample(img, *B)
            d = de00(pa, pb)[pad:-pad, pad:-pad]
            la = lab32(pa)[pad:-pad, pad:-pad, 0]
            lb = lab32(pb)[pad:-pad, pad:-pad, 0]
            c = ncc(la, lb)
            flat = c is None
            if flat:                      # both bands plain: continuity judged by colour alone
                c = 1.0 if float(d.mean()) <= SEAM_DE00_MEAN_MAX else 0.0
            r = {'side': side, 'de_mean': round(float(d.mean()), 3), 'de_p95': round(float(np.percentile(d, 95)), 3),
                 'ncc': round(float(c), 4), 'dissim': round(1 - float(c), 4), 'plain_bands': flat}
            if best is None or (r['de_mean'] + 10 * r['dissim']) < (best['de_mean'] + 10 * best['dissim']):
                best = r
        if best is None:
            out[name] = {'available': False}
            ok_all = False
            continue
        best['pass'] = (best['de_mean'] <= SEAM_DE00_MEAN_MAX and best['de_p95'] <= SEAM_DE00_P95_MAX
                        and best['dissim'] <= SEAM_DISSIM_MAX)
        ok_all &= best['pass']
        out[name] = best
        for k in worst:
            worst[k] = max(worst[k], best[k])
    out.update({'pass': bool(ok_all), 'de_mean': round(worst['de_mean'], 3), 'de_p95': round(worst['de_p95'], 3),
                'dissim': round(worst['dissim'], 4), 'ncc': round(1 - worst['dissim'], 4)})
    return out


def artefact_check(img, mask, origin, u, w, nu, nw, tile):
    """Tile vs its lattice copies in the same photo (source px).

    A tile pixel is an artefact when it differs by more than ARTEFACT_DE00 from EVERY copy that
    covers it (non-periodic content: watermark, sticker, glare, crease). Copies may be partial;
    >= 90 % of tile pixels must be covered by at least one copy, otherwise 'unverifiable'.
    """
    dmin = np.full(tile.shape[:2], np.inf, np.float64)
    stats = []
    for m in range(-2, 3):
        for n in range(-2, 3):
            if m == 0 and n == 0:
                continue
            o = (origin[0] + m * u[0] + n * w[0], origin[1] + m * u[1] + n * w[1])
            mx, my = grid_maps(o, u, w, nu, nw)
            ins = inside(mask, mx, my)
            if ins.mean() < 0.25:
                continue
            cp = sample(img, mx, my)
            d = de00(tile, cp)
            d = np.where(ins, d, np.inf)
            dmin = np.minimum(dmin, d)
            fin = np.isfinite(d)
            c = ncc(lab32(tile)[..., 0], lab32(cp)[..., 0], fin)
            stats.append({'mn': [m, n], 'covered': round(float(ins.mean()), 3),
                          'de_mean': round(float(d[fin].mean()), 3), 'ncc': None if c is None else round(c, 4)})
    covered = np.isfinite(dmin)
    cov = float(covered.mean())
    if not stats or cov < 0.90:
        return {'copies': len(stats), 'verified_share': round(cov, 3), 'pass': False,
                'reason': f'unverifiable: only {cov:.0%} of tile pixels have a lattice copy in the clean region'}
    share = float((dmin[covered] > ARTEFACT_DE00).mean())
    full = [x for x in stats if x['covered'] >= 0.97]
    ref = full or stats
    return {'copies': len(stats), 'full_copies': len(full), 'verified_share': round(cov, 3),
            'artefact_share': round(share, 5), 'pass': share <= ARTEFACT_SHARE_MAX, 'copy_stats': stats[:6],
            'copy_de_mean': round(float(np.mean([x['de_mean'] for x in ref])), 3),
            'copy_ncc_mean': round(float(np.mean([x['ncc'] for x in ref if x['ncc'] is not None] or [0])), 4)}


# ------------------------------------------------------------- illumination field

def srgb_to_lin(x):
    x = x.astype(np.float32) / 255.0
    return np.where(x <= 0.04045, x / 12.92, ((x + 0.055) / 1.055) ** 2.4)


def lin_to_srgb(x):
    x = np.clip(x, 0, 1)
    return np.clip(np.where(x <= 0.0031308, x * 12.92, 1.055 * np.power(x, 1 / 2.4) - 0.055) * 255.0 + 0.5, 0, 255).astype(np.uint8)


def illumination_field(bgr, mask, win):
    """Luminance averaged over one repeat window (pattern-invariant for a periodic print).

    win = (wx, wy) px. Returns Y_lin field (float32) using mask-normalised box filtering."""
    lin = srgb_to_lin(bgr)
    Y = (0.0722 * lin[..., 0] + 0.7152 * lin[..., 1] + 0.2126 * lin[..., 2]).astype(np.float32)
    m = (mask > 0).astype(np.float32)
    # exact repeat-size window; only windows fully inside the clean mask average out the print
    k = (max(3, int(round(win[0]))), max(3, int(round(win[1]))))
    num = cv2.boxFilter(Y * m, -1, k, normalize=False, borderType=cv2.BORDER_CONSTANT)
    den = cv2.boxFilter(m, -1, k, normalize=False, borderType=cv2.BORDER_CONSTANT)
    return np.where(den >= 0.98 * k[0] * k[1], num / np.maximum(den, 1e-6), np.nan).astype(np.float32), Y


def evenness_from_field(field, mask):
    """Range (p95-p5) of the illumination field in L* units -> evenness 0..1."""
    v = field[(mask > 0) & np.isfinite(field)]
    if v.size < 64:
        return None, None
    Lf = 116 * np.cbrt(np.clip(v, 1e-6, 1)) - 16
    rng = float(np.percentile(Lf, 95) - np.percentile(Lf, 5))
    return round(float(np.clip(1 - rng / 20.0, 0, 1)), 3), round(rng, 2)


def quadfit_evenness(bgr, mask):
    """Fallback without a lattice: quadratic surface fitted to 16x16 block medians of L*."""
    L = lab32(bgr)[..., 0]
    h, w = L.shape
    pts = []
    for by in range(16):
        for bx in range(16):
            y0, y1 = by * h // 16, (by + 1) * h // 16
            x0, x1 = bx * w // 16, (bx + 1) * w // 16
            mm = mask[y0:y1, x0:x1] > 0
            if mm.mean() > 0.7:
                pts.append(((x0 + x1) / 2 / w, (y0 + y1) / 2 / h, float(np.median(L[y0:y1, x0:x1][mm]))))
    if len(pts) < 12:
        return None, None
    P = np.array(pts)
    x, y, z = P[:, 0], P[:, 1], P[:, 2]
    A = np.stack([np.ones_like(x), x, y, x * x, y * y, x * y], 1)
    coef, *_ = np.linalg.lstsq(A, z, rcond=None)
    fit = A @ coef
    rng = float(np.percentile(fit, 95) - np.percentile(fit, 5))
    return round(float(np.clip(1 - rng / 20.0, 0, 1)), 3), round(rng, 2)


def wrinkle_score(bgr, mask, b1, b2):
    """Shading that is NOT periodic: low-pass of L(x) - L(x+v) for each lattice vector.

    A flat, evenly lit periodic print gives ~0. RMS (L*) / sqrt(2), mapped 0..1 by /10."""
    L = lab32(bgr)[..., 0]
    h, w = L.shape
    vals = []
    sig = max(3.0, 0.015 * max(h, w))
    for v in (b1, b2):
        M = np.float32([[1, 0, v[0]], [0, 1, v[1]]])
        Ls = cv2.warpAffine(L, M, (w, h), flags=cv2.INTER_LINEAR | cv2.WARP_INVERSE_MAP, borderMode=cv2.BORDER_CONSTANT)
        ms = cv2.warpAffine(mask.astype(np.uint8), M, (w, h), flags=cv2.INTER_NEAREST | cv2.WARP_INVERSE_MAP, borderMode=cv2.BORDER_CONSTANT)
        both = ((mask > 0) & (ms > 0)).astype(np.float32)
        if both.sum() < 1000:
            continue
        D = (L - Ls) * both
        num = cv2.GaussianBlur(D, (0, 0), sig)
        den = cv2.GaussianBlur(both, (0, 0), sig)
        lp = np.where(den > 0.5, num / np.maximum(den, 1e-6), np.nan)
        lp = lp[(both > 0) & np.isfinite(lp)]
        lp = lp - np.nanmedian(lp)
        vals.append(float(np.sqrt(np.nanmean(lp ** 2)) / np.sqrt(2)))
    if not vals:
        return None, None
    rms = float(np.mean(vals))
    return round(float(np.clip(rms / 10.0, 0, 1)), 3), round(rms, 3)


def jdump(obj):
    return json.dumps(obj, ensure_ascii=False, default=lambda o: o.tolist() if hasattr(o, 'tolist') else str(o))
