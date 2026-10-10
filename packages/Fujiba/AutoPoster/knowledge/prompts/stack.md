# Prompt template — catalog stack / bundle

Variables: `{product_name}` `{pattern_desc}` `{dominant_color}` `{width}`
`{length}` `{price}` `{code}` `{bundle_qty}` `{bundle_terms}`

Target share: 20% of posts. Spec: `../IMAGE-STYLE.md` §1c.

## Image prompt

```
Clean e-commerce catalog photograph of folded Japanese kimono cotton fabric.

Arrangement: {bundle_qty} folded pieces stacked and fanned so each print is
partly visible, grouped as a colour-coordinated set around {dominant_color}.
Leading piece shows {pattern_desc} clearly.

Background: plain white studio sweep, soft even lighting, minimal contact
shadow. No props, no hands, no packaging.

Framing: square, stack centred with generous margin for badges at the corners.
Each fold crisp, prints sharp and true to colour.

Photographic product shot. Reproduce every print exactly — no invented motifs.
```

## Layout composed afterwards (1080×1080)

- Bundle badge: `{bundle_qty}` · `{bundle_terms}`
- Price: ¥{price}
- Spec: `{width}cm × {length}` per piece
- Product code `{code}`
- CTA bar → m.me link

## Honesty — read before using this type

This is the format where competitor creatives lean hardest on fake signals.
Ours carry **only** what Bagisto holds:

- `{bundle_qty}` and `{bundle_terms}` must describe a real offer (e.g. the
  genuine 11-for-10 — buy 11, get 1 free). Never invent a bundle.
- `{price}` is the live catalogue price.
- **No star ratings, no review counts, no "was ¥X" strike-throughs, no countdown
  or scarcity badges** unless every one of those is a real, verifiable fact in
  our own data. We currently hold none of them, so in practice: omit.

## Rejection criteria

Reject if any print is altered or invented, if the background is not clean
white, or if the generator adds text, badges, ratings, or branding into the
image itself (all overlays are composed by us afterwards).
