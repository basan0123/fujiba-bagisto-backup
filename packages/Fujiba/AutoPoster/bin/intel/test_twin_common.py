#!/usr/bin/env python3
"""Synthetic tests for twin_common (no catalog photos): a known repeat must be found and pass the
seam gate; a wrong period must fail it; a stamped 'watermark' inside the tile must trip the
artefact check; a half-drop lattice must give a rectangular super-cell."""
import os
import sys

import cv2
import numpy as np

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import twin_common as T  # noqa: E402


def motif(w, h, seed):
    rng = np.random.default_rng(seed)
    t = np.full((h, w, 3), (40, 90, 160), np.uint8)
    for _ in range(14):
        c = tuple(int(x) for x in rng.integers(0, 255, 3))
        cv2.circle(t, (int(rng.integers(0, w)), int(rng.integers(0, h))), int(rng.integers(4, 18)), c, -1)
    cv2.putText(t, 'K', (w // 3, h // 2), cv2.FONT_HERSHEY_SIMPLEX, 1.2, (250, 240, 200), 3)
    return t


def periodic(tile, H, W, drop=0):
    th, tw = tile.shape[:2]
    out = np.zeros((H, W, 3), np.uint8)
    for y in range(H):
        for x in range(W):
            col = x // tw
            out[y, x] = tile[(y + col * drop) % th, x % tw]
    return out


def test_rect_lattice():
    tile = motif(61, 47, 1)
    img = np.ascontiguousarray(np.tile(tile, (9, 7, 1))[:400, :400])
    mask = np.ones(img.shape[:2], np.uint8)
    lat = T.detect_lattice(img, mask)
    assert lat['found'] and lat['passes'], lat
    u, w, rot, skew = T.supercell(lat['b1'], lat['b2'])
    assert abs(np.hypot(*u) - 61) < 0.6 and abs(np.hypot(*w) - 47) < 0.6, (u, w)
    assert abs(rot) < 0.5 and abs(skew) < 0.5
    nu, nw = int(round(np.hypot(*u))), int(round(np.hypot(*w)))
    s = T.seam_check(img, mask, (100.0, 100.0), u, w, nu, nw)
    assert s['pass'], s
    bad = T.seam_check(img, mask, (100.0, 100.0), (u[0] + 4, u[1]), w, nu + 4, nw)
    assert not bad['pass'], bad
    mx, my = T.grid_maps((100.0, 100.0), u, w, nu, nw)
    tl = T.sample(img, mx, my)
    a = T.artefact_check(img, mask, (100.0, 100.0), u, w, nu, nw, tl)
    assert a['pass'] and a['copies'] >= 4, a
    stamped = img.copy()
    cv2.putText(stamped, 'Fujiba', (102, 130), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 255), 2)
    tl2 = T.sample(stamped, mx, my)
    a2 = T.artefact_check(stamped, mask, (100.0, 100.0), u, w, nu, nw, tl2)
    assert not a2['pass'], a2


def test_half_drop():
    tile = motif(50, 40, 2)
    img = periodic(tile, 360, 360, drop=20)
    lat = T.detect_lattice(img)
    assert lat['found'] and lat['passes'], lat
    u, w, rot, skew = T.supercell(lat['b1'], lat['b2'])
    assert abs(np.hypot(*u) - 100) < 1.0 or abs(np.hypot(*u) - 50) < 1.0, u
    assert abs(skew) < 1.0, (u, w, skew)


def test_wrinkle_and_evenness():
    tile = motif(40, 40, 3)
    img = np.ascontiguousarray(np.tile(tile, (8, 8, 1)))
    mask = np.ones(img.shape[:2], np.uint8)
    ws, _ = T.wrinkle_score(img, mask, (40, 0), (0, 40))
    assert ws is not None and ws < 0.05, ws
    f, _ = T.illumination_field(img, mask, (40, 40))
    ev, rng = T.evenness_from_field(f, mask)
    assert ev > 0.9, (ev, rng)
    grad = (img.astype(np.float32) * np.linspace(0.6, 1.0, img.shape[1])[None, :, None]).astype(np.uint8)
    f2, _ = T.illumination_field(grad, mask, (40, 40))
    ev2, rng2 = T.evenness_from_field(f2, mask)
    assert ev2 < ev, (ev2, ev)


if __name__ == '__main__':
    for fn in (test_rect_lattice, test_half_drop, test_wrinkle_and_evenness):
        fn()
        print('ok', fn.__name__)
