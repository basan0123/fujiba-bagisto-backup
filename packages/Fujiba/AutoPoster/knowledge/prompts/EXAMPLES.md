# Filled example prompts — real catalogue products

Pulled from the live fujiba tenant on 2026-10-07. Prices and colours are real;
width/length follow the values the posting code already uses (110cm × 2m, or
×3m where `length === '3m'`).

Dominant colours come from `storage/app/autoposter/photo-meta-<tenant>.json`.

> **Known data issue — `{dominant_color}` is unreliable at scale.**
> Measured 2026-10-07: of 168 products whose description carries an explicit
> `Color: X (Name)`, **97 (58%) disagree with the cached photo-meta colour.**
> Samples: pid 1905 cache=red/desc=black · 1912 cache=pink/desc=black ·
> 1918 cache=red/desc=purple · 1919 cache=red/desc=black.
>
> The cache is sampled from the photo; the description is hand-entered. A
> patterned kimono print genuinely has several colours, so "dominant" is
> partly a judgement call and some disagreement is expected — but a 58% rate
> means neither source can be trusted blind for a creative's accent colour.
>
> Until this is reconciled: **verify the colour against the real product photo
> before generating**, and prefer the description where one exists (it is the
> human-checked value). A wrong accent propagates through the whole creative.
> This affects only the accent/colour-set decision — it does not affect the
> `colour` grouping used by existing flat-fabric posts, which has been running
> on the cached value all along.

---

## Example 1 — worn style · FV-21-D (pid 5466)

Facts: price ¥1,595 · colour `brown` (ရွှေ… → 茶 / Brown) · 110cm × 2m · code FV-21-D

```
Full-body editorial product photograph of a woman in her late twenties wearing a
traditional Myanmar two-piece outfit sewn from Japanese kimono cotton.

The outfit: a fitted three-quarter-sleeve blouse and an ankle-length wrapped
htamein skirt, both cut from the SAME fabric — traditional Japanese pattern on
100% cotton, dominant colour brown. The print must be reproduced faithfully:
same motif, same scale, same colours. Do not restyle, recolour, or reinterpret
the pattern.

Setting: soft natural daylight in a quiet garden courtyard with greenery and
weathered stone, background gently out of focus. The subject stands relaxed,
three-quarter turn to camera, hands at her side.

Framing: vertical 4:5, full body in frame, fabric drape clearly visible from
shoulder to hem so the pattern reads at phone size.

Styling: optional rattan handbag and simple heels. No logos, no text overlays,
no jewellery that competes with the print.

Photographic, natural skin tones, soft shadows. Not illustration, not 3D render.
```

Overlay afterwards: flat-fabric inset · code **FV-21-D** · `110cm × 2m · ¥1,595`
· m.me CTA bar.

---

## Example 2 — lifestyle drape · FT-2-C (pid 5382)

Facts: price ¥1,595 · colour `navy` (အပြာရင့်ရောင် / 紺 / Navy) · described as
*Ultramarine, traditional Japanese pattern, 100% cotton* · 110cm × 2m · code FT-2-C

```
Warm lifestyle product photograph of Japanese kimono cotton fabric —
traditional Japanese pattern in ultramarine, dominant colour navy.

Arrangement: one length draped loosely over the back and seat of a wooden chair
so the pattern falls in soft folds, with a neatly folded stack of the same
fabric resting on the seat beside it.

Props: a small bundle of dried flowers, a potted green plant just in frame, a
rattan basket, bare wood surfaces. Warm late-afternoon light from the side,
gentle shadows, calm and homely.

Framing: vertical, fabric occupying the right two-thirds, left third quieter so
a text panel can sit over it later. Pattern must be sharp and readable.

Reproduce the print exactly — same motif, scale, and colours. Photographic, not
illustration.
```

Panel afterwards (1080×1350), accent **navy**:
title `FT-2-C` · subtitle 和柄コットン / ဂျပန် ကီမိုနို အထည် ·
badge `110cm × 2m · ¥1,595` · the seven standard bullets · zoom inset ·
code **FT-2-C** · CTA bar.

---

## Example 3 — catalog stack · FO-2-C (pid 5384) + colour-matched set

Facts: price ¥1,595 · 110cm × 2m · code FO-2-C ·
*Hosonuno (100% cotton), traditional Japanese pattern* ·
colour **unresolved** — cache says `pink`, description says *C (Brown)*. Confirm
from the photo before generating; the prompt below assumes brown per the
description, which is the hand-entered, human-checked value.

Bundle terms: the real 11-for-10 offer (buy 11, get 1 free). No other badge.

```
Clean e-commerce catalog photograph of folded Japanese kimono cotton fabric.

Arrangement: 11 folded pieces stacked and fanned so each print is partly
visible, grouped as a colour-coordinated set around brown. Leading piece shows a
traditional Japanese pattern on fine hosonuno cotton clearly.

Background: plain white studio sweep, soft even lighting, minimal contact
shadow. No props, no hands, no packaging.

Framing: square, stack centred with generous margin for badges at the corners.
Each fold crisp, prints sharp and true to colour.

Photographic product shot. Reproduce every print exactly — no invented motifs.
```

Overlay afterwards (1080×1080): badge `11 pcs · buy 11 get 1 free` ·
`¥1,595 each` · `110cm × 2m per piece` · code **FO-2-C** · CTA bar.
No ratings, no review count, no strike-through price — we hold no such data.
