# Prompt template — worn style

Variables: `{product_name}` `{pattern_desc}` `{dominant_color}` `{width}`
`{length}` `{price}` `{code}`

Target share: 40% of posts. Spec: `../IMAGE-STYLE.md` §1a.

## Prompt

```
Full-body editorial product photograph of a woman in her late twenties wearing a
traditional Myanmar two-piece outfit sewn from Japanese kimono cotton.

The outfit: a fitted three-quarter-sleeve blouse and an ankle-length wrapped
htamein skirt, both cut from the SAME fabric — {pattern_desc}, dominant colour
{dominant_color}. The print must be reproduced faithfully: same motif, same
scale, same colours. Do not restyle, recolour, or reinterpret the pattern.

Setting: soft natural daylight in a quiet garden courtyard with greenery and
weathered stone, background gently out of focus. The subject stands relaxed,
three-quarter turn to camera, hands at her side.

Framing: vertical 4:5, full body in frame, fabric drape clearly visible from
shoulder to hem so the pattern reads at phone size.

Styling: optional rattan handbag and simple heels. No logos, no text overlays,
no jewellery that competes with the print.

Photographic, natural skin tones, soft shadows. Not illustration, not 3D render.
```

## Compose afterwards (not in the prompt)

- Small inset, lower corner: flat shot of the real fabric.
- Product code `{code}` beneath the inset.
- Spec line: `{width}cm × {length}` · ¥{price}
- CTA bar → m.me link.

## Rejection criteria

Reject and re-run if the pattern differs from the real product in motif, scale,
or colour; if the fabric reads as a different material; if any text, logo, or
watermark is generated into the frame; or if the outfit silhouette is not the
Myanmar blouse + wrap skirt requested.

Set `caption_meta.ai_image = true` on anything generated here.
