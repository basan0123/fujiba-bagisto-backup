# Prompt template — lifestyle drape

Variables: `{product_name}` `{pattern_desc}` `{dominant_color}` `{width}`
`{length}` `{price}` `{code}`

Target share: 40% of posts. Spec: `../IMAGE-STYLE.md` §1b.
This is the series template — the layout stays fixed, only the accent colour and
the fabric change, so a week of posts reads as one set.

## Image prompt (right-hand photograph only)

```
Warm lifestyle product photograph of Japanese kimono cotton fabric —
{pattern_desc}, dominant colour {dominant_color}.

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

## Layout composed afterwards (1080×1350)

Left panel, over the quiet third:

- Title (EN, serif): `{product_name}`
- Subtitle (JA / MY, script): 和柄コットン / ဂျပန် ကီမိုနို အထည်
- Spec badge: `{width}cm × {length}` · ¥{price}
- Icon bullets, in this order:
  1. 和柄 Japanese pattern
  2. Made in Japan
  3. Gift for family
  4. Easy to sew longyi & blouse
  5. Soft & breathable
  6. {length} cut
  7. Ships nationwide
- Circular zoom inset: swirled close-up of the same fabric.
- Product code `{code}`.
- Fixed CTA bar at the base → m.me link.

Accent colour (panel rule, badge fill, icon tint) = `{dominant_color}`, taken
from the cached photo-meta value, never guessed.

## Rejection criteria

Reject if the pattern is altered, if the left third is too busy to carry text,
if generated text appears anywhere, or if the Burmese subtitle renders as tofu
(use Padauk, not DejaVu).
