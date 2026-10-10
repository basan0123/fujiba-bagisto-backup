#!/usr/bin/env python3
"""Workstream B: measured-only fabric facts for catalog images.

Usage: catalog_analyse.py <input.json> <output.json>
input:  [{"image_id": int, "path": "/abs/file"}]
output: [{"image_id", "ok", "error", "width", "height", "dhash", "facts": {fact: {...}}}]

Every fact is {"status": "measured"|"unknown", "value": ..., "method": str,
"confidence": float|None}. Nothing is inferred or guessed: if a measurement
cannot be made reliably the fact is 'unknown' with a reason.
Dependencies: cv2 + numpy only (no scipy/skimage, no network).
"""
import json
import sys

import cv2
import numpy as np

VERSION = "cat-1.0"
MAX_SIDE = 800  # analysis resolution (keeps sharpness comparable across photos)

cv2.setRNGSeed(1234)
cv2.setNumThreads(1)


def unknown(method, reason):
    return {"status": "unknown", "value": {"reason": reason}, "method": method, "confidence": None}


def measured(method, value, conf):
    return {"status": "measured", "value": value, "method": method,
            "confidence": None if conf is None else round(float(max(0.0, min(1.0, conf))), 3)}


def to_lab(img_bgr):
    lab = cv2.cvtColor(img_bgr.astype(np.float32) / 255.0, cv2.COLOR_BGR2LAB)
    return lab  # L 0..100, a/b approx -127..127


def lab_to_hex(L, a, b):
    px = np.array([[[L, a, b]]], dtype=np.float32)
    bgr = np.clip(cv2.cvtColor(px, cv2.COLOR_LAB2BGR)[0, 0] * 255, 0, 255).astype(int)
    return "#%02x%02x%02x" % (bgr[2], bgr[1], bgr[0])


def dhash(gray):
    small = cv2.resize(gray, (9, 8), interpolation=cv2.INTER_AREA)
    bits = (small[:, 1:] > small[:, :-1]).flatten()
    v = 0
    for bit in bits:
        v = (v << 1) | int(bit)
    return "%016x" % v


def fabric_mask(lab):
    """Background = pixels close (dE<12) to the median colour of a 4% border,
    only when that border is itself uniform. Returns (mask, info)."""
    h, w = lab.shape[:2]
    bw = max(2, int(round(min(h, w) * 0.04)))
    border = np.concatenate([lab[:bw].reshape(-1, 3), lab[-bw:].reshape(-1, 3),
                             lab[:, :bw].reshape(-1, 3), lab[:, -bw:].reshape(-1, 3)])
    med = np.median(border, axis=0)
    d_border = np.linalg.norm(border - med, axis=1)
    uniform_frac = float((d_border < 12).mean())
    if uniform_frac < 0.6:
        # No uniform backdrop: fabric (or a busy scene) touches the frame edges.
        return np.ones((h, w), np.uint8), {"backdrop": "none_uniform", "border_uniform_frac": round(uniform_frac, 3)}
    d = np.linalg.norm(lab - med, axis=2)
    mask = (d >= 12).astype(np.uint8)
    k = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (7, 7))
    mask = cv2.morphologyEx(mask, cv2.MORPH_OPEN, k)
    mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, k)
    return mask, {"backdrop": "uniform", "border_uniform_frac": round(uniform_frac, 3),
                  "backdrop_lab": [round(float(x), 1) for x in med]}


def largest_box(mask):
    n, lbl, stats, _ = cv2.connectedComponentsWithStats(mask, 8)
    if n <= 1:
        return None
    i = 1 + int(np.argmax(stats[1:, cv2.CC_STAT_AREA]))
    x, y, w, h, a = stats[i]
    return int(x), int(y), int(w), int(h), int(a)


def f_quality(img, gray, W, H):
    m = "laplacian_var@max800 + resolution"
    lap = float(cv2.Laplacian(gray, cv2.CV_64F).var())
    mp = W * H / 1e6
    return measured(m, {"width": W, "height": H, "megapixels": round(mp, 3),
                        "sharpness_laplacian_var": round(lap, 1),
                        "sharp_band": "blurry" if lap < 50 else ("soft" if lap < 150 else "sharp")}, 1.0)


def f_cast(lab):
    m = "grayworld_on_low_chroma_bright_pixels(C<15,L>p50)"
    L, a, b = lab[..., 0], lab[..., 1], lab[..., 2]
    C = np.hypot(a, b)
    sel = (C < 15) & (L > np.percentile(L, 50))
    frac = float(sel.mean())
    if frac < 0.02:
        return unknown(m, "too few neutral pixels (%.3f)" % frac)
    ma, mb = float(a[sel].mean()), float(b[sel].mean())
    mag = float(np.hypot(ma, mb))
    if mag < 2.5:
        label = "neutral"
    else:
        ang = np.degrees(np.arctan2(mb, ma)) % 360
        label = ("red/magenta" if ang < 45 or ang >= 315 else "yellow/warm" if ang < 135
                 else "green" if ang < 225 else "blue/cool")
    return measured(m, {"mean_a": round(ma, 2), "mean_b": round(mb, 2), "magnitude": round(mag, 2),
                        "label": label, "neutral_pixel_frac": round(frac, 3)}, min(1.0, frac / 0.15))


def f_colours(lab, mask):
    m = "kmeans_k6_Lab_on_fabric_mask, merge dE<8"
    px = lab[mask > 0].reshape(-1, 3)
    if len(px) < 500:
        return unknown(m, "fabric mask too small")
    rng = np.random.default_rng(1234)
    if len(px) > 20000:
        px = px[rng.choice(len(px), 20000, replace=False)]
    crit = (cv2.TERM_CRITERIA_EPS + cv2.TERM_CRITERIA_MAX_ITER, 50, 0.2)
    _, lbl, cen = cv2.kmeans(px.astype(np.float32), 6, None, crit, 3, cv2.KMEANS_PP_CENTERS)
    lbl = lbl.flatten()
    clusters = [[cen[i], int((lbl == i).sum()), [i]] for i in range(6)]
    merged = True
    while merged:
        merged = False
        for i in range(len(clusters)):
            for j in range(i + 1, len(clusters)):
                if np.linalg.norm(clusters[i][0] - clusters[j][0]) < 8:
                    ni, nj = clusters[i][1], clusters[j][1]
                    clusters[i] = [(clusters[i][0] * ni + clusters[j][0] * nj) / (ni + nj), ni + nj,
                                   clusters[i][2] + clusters[j][2]]
                    clusters.pop(j)
                    merged = True
                    break
            if merged:
                break
    total = float(len(px))
    out = []
    for c, n, ids in sorted(clusters, key=lambda c: -c[1]):
        share = n / total
        if share < 0.02:
            continue
        L, a, b = [float(x) for x in c]
        out.append({"lab": [round(L, 1), round(a, 1), round(b, 1)], "hex": lab_to_hex(L, a, b),
                    "share": round(share, 3), "chroma": round(float(np.hypot(a, b)), 1)})
    # confidence: how well 6 centres explain the pixels (RMS distance to centre)
    rms = float(np.sqrt(np.mean(np.sum((px - cen[lbl]) ** 2, axis=1))))
    return measured(m, {"colours": out, "rms_dE": round(rms, 2), "pixels": int(total)}, 1 - rms / 30.0)


def crop_fabric(gray, mask):
    box = largest_box(mask)
    h, w = gray.shape
    if box is None:
        return None, None
    x, y, bw, bh, area = box
    # central 70% of the fabric bbox to avoid edges/background spill
    cx, cy = x + bw * 0.15, y + bh * 0.15
    cw, ch = bw * 0.7, bh * 0.7
    if cw < 96 or ch < 96:
        return None, box
    return gray[int(cy):int(cy + ch), int(cx):int(cx + cw)], box


def f_dots(crop, scale):
    m = "SimpleBlobDetector(circ>=0.75,conv>=0.85,inertia>=0.5) dark+light on fabric crop"
    if crop is None:
        return unknown(m, "no fabric crop >=96px")
    area_px = crop.shape[0] * crop.shape[1]
    p = cv2.SimpleBlobDetector_Params()
    p.filterByArea = True
    p.minArea = 12
    p.maxArea = max(13.0, area_px * 0.004)
    p.filterByCircularity = True
    p.minCircularity = 0.75
    p.filterByConvexity = True
    p.minConvexity = 0.85
    p.filterByInertia = True
    p.minInertiaRatio = 0.5
    p.minThreshold, p.maxThreshold, p.thresholdStep = 20, 235, 15
    best = None
    for colour, inv in (("dark", False), ("light", True)):
        p.filterByColor = True
        p.blobColor = 0
        det = cv2.SimpleBlobDetector_create(p)
        kps = det.detect(255 - crop if inv else crop)
        if best is None or len(kps) > len(best[1]):
            best = (colour, kps)
    colour, kps = best
    n = len(kps)
    density = n / (area_px / 1e4)  # per 100x100 analysis px
    if n < 5:
        return measured(m, {"dots_detected": False, "count": n, "per_100x100px": round(density, 2)}, 0.7)
    sizes = np.array([k.size for k in kps])
    cv = float(sizes.std() / sizes.mean())
    # spacing regularity: nearest-neighbour distance CV
    pts = np.array([k.pt for k in kps])
    if n > 1:
        d = np.sqrt(((pts[:, None, :] - pts[None, :, :]) ** 2).sum(-1))
        np.fill_diagonal(d, np.inf)
        nn = d.min(1)
        nn_cv = float(nn.std() / nn.mean())
    else:
        nn_cv = 1.0
    is_dot = n >= 30 and cv < 0.45 and nn_cv < 0.6
    conf = max(0.0, 1 - (cv + nn_cv) / 2) if is_dot else 0.6
    return measured(m, {"dots_detected": bool(is_dot), "dot_polarity": colour, "count": n,
                        "per_100x100px": round(density, 2),
                        "median_diameter_px_orig": round(float(np.median(sizes)) * scale, 1),
                        "size_cv": round(cv, 3), "spacing_cv": round(nn_cv, 3)}, conf)


def _first_peak(profile, min_lag=6):
    # profile normalised so profile[0] == 1
    for i in range(min_lag, len(profile) - 1):
        if profile[i] > profile[i - 1] and profile[i] >= profile[i + 1] and profile[i] > 0.25:
            return i, float(profile[i])
    return None, None


def f_repeat(crop, scale):
    m = "FFT autocorrelation (hann window) axis profiles on fabric crop"
    if crop is None:
        return unknown(m, "no fabric crop >=96px")
    g = crop.astype(np.float32)
    s = min(512, min(g.shape))
    g = cv2.resize(g, (s, s), interpolation=cv2.INTER_AREA) if max(g.shape) > s else g[:s, :s]
    k = g.shape[0] / crop.shape[0]  # resize factor crop->g
    g = g - g.mean()
    if g.std() < 2:
        return unknown(m, "flat crop (no texture)")
    win = np.outer(np.hanning(g.shape[0]), np.hanning(g.shape[1]))
    F = np.fft.fft2(g * win)
    ac = np.real(np.fft.ifft2(np.abs(F) ** 2))
    ac /= ac[0, 0]
    # normalise for window overlap shrinkage
    wa = np.real(np.fft.ifft2(np.abs(np.fft.fft2(win)) ** 2))
    wa /= wa[0, 0]
    half = g.shape[0] // 2
    res = {}
    confs = []
    for axis, prof, wprof in (("x", ac[0, :half], wa[0, :half]), ("y", ac[:half, 0], wa[:half, 0])):
        prof = prof / np.maximum(wprof, 0.05)
        lag, h = _first_peak(prof)
        if lag is None:
            res[axis] = None
        else:
            res[axis] = {"period_px_orig": round(lag / k * scale, 1), "peak": round(h, 3)}
            confs.append(h)
    if not confs:
        return unknown(m, "no autocorrelation peak > 0.25 within half-crop")
    return measured(m, {"x": res["x"], "y": res["y"], "crop_px_orig": [int(crop.shape[1] * scale), int(crop.shape[0] * scale)],
                        "note": "pixels in source photo, not cm (no scale reference)"}, float(np.mean(confs)))


def analyse(path):
    raw = cv2.imread(path, cv2.IMREAD_COLOR)
    if raw is None:
        raise ValueError("cv2 could not decode image")
    H, W = raw.shape[:2]
    scale = max(H, W) / MAX_SIDE if max(H, W) > MAX_SIDE else 1.0
    img = cv2.resize(raw, (int(round(W / scale)), int(round(H / scale))), interpolation=cv2.INTER_AREA) if scale > 1 else raw
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    lab = to_lab(img)
    mask, minfo = fabric_mask(lab)
    cov = float(mask.mean())
    facts = {
        "quality": f_quality(img, gray, W, H),
        "colour_cast": f_cast(lab),
        "fabric_coverage": measured("border-backdrop dE>=12 segmentation", dict(minfo, coverage=round(cov, 3)),
                                    0.8 if minfo["backdrop"] == "uniform" else 0.4),
        "dominant_colours": f_colours(lab, mask),
    }
    crop, _ = crop_fabric(gray, mask)
    facts["dots"] = f_dots(crop, scale)
    facts["repeat_period"] = f_repeat(crop, scale)
    return W, H, dhash(cv2.cvtColor(raw, cv2.COLOR_BGR2GRAY)), facts


def main():
    items = json.load(open(sys.argv[1]))
    out = []
    for it in items:
        r = {"image_id": it["image_id"], "ok": False, "version": VERSION}
        try:
            W, H, dh, facts = analyse(it["path"])
            r.update(ok=True, width=W, height=H, dhash=dh, facts=facts)
        except Exception as e:  # noqa: BLE001 — record, never crash the batch
            r["error"] = "%s: %s" % (type(e).__name__, e)
        out.append(r)
    json.dump(out, open(sys.argv[2], "w"))


if __name__ == "__main__":
    main()
