# Fujiba Kimono — post & creative knowledge

Reference for AutoPoster images and ad creatives. Rules here are **descriptive of
intent**; the code that currently implements any of them is named inline so the
two never drift silently.

Audience: Myanmar and Nepali customers living in Japan, roughly 20–35, buying
Japanese kimono cotton by the 2m cut — mostly to sew a longyi/htamein set or as
a gift (ကိုယ်တိုင်ချုပ် / कोसेली) to carry home.

---

## 1. Image types and weighting

Five posts a day. Target mix:

| Type | Share | Why |
|---|---|---|
| Worn style | 40% | Buyers respond to fabric shown as finished wear, not as flat goods |
| Lifestyle drape | 40% | Carries the specs and makes the feed look like one branded series |
| Catalog stack / bundle | 20% | Sets and gift bundles; the 11-for-10 offer lives here |

The weighting is a target over a week, not a strict per-day quota. Current code
(`CollectionPostBuilder::fill()`) rotates `new` / `colour` / `pattern`
collections and renders flat fabric only — worn and drape types are **not yet
implemented**; this file defines them so the generator can be built to match.

### 1a. Worn style
- Full-body model or mannequin wearing an outfit sewn from our kimono fabric.
- Myanmar silhouette: fitted blouse + ankle-length wrap skirt (htamein/longyi).
  Nepali variants: kurta or sari-style drape.
- Background: clean outdoor, temple, garden, or plain studio.
- Accessories (rattan bag, heels) optional; never let them outshine the fabric.
- The fabric pattern must be clearly readable in the frame.
- Include a small inset of the flat fabric plus the product code.

### 1b. Lifestyle drape
- 1080×1350.
- Left panel: title in EN, subtitle in JA/MY, a spec badge, and icon bullets.
- Badge carries the **real** width (110–115cm) and length (2m).
- Right: fabric draped on a wooden chair, a folded stack, and a circular zoom
  inset of the swirled fabric.
- Props: dried flowers, plant, rattan, wood, warm light.
- Accent colour is taken from the fabric's dominant colour (see §4).
- Bullets, kimono-specific:
  和柄 Japanese pattern · Made in Japan · Gift for family ·
  Easy to sew longyi & blouse · Soft & breathable · 2m cut · Ships nationwide

### 1c. Catalog stack / bundle
- 1080×1080, white background.
- Folded fabric piles grouped into colour-themed sets.
- Badge for the bundle (e.g. 11pcs gift set) and the price.
- Only real bundles and real prices — see §5.

---

## 2. Always

- **CTA bar → Messenger.** Every creative carries the m.me link (ref routes into
  the ChatPion flow). In captions the CTA sits near the top, above Facebook's
  "See more" fold — a CTA at the bottom of a 25-line caption is invisible.
  Implemented in `CollectionPostBuilder::caption()`.
- **Trilingual caption** EN / JA / MY. Burmese is written for Myanmar customers
  living in Japan, not translated word-for-word from English.
- **Product code on the image**, so a customer can order by code in chat.
  Implemented in `bin/post_photo.py` (bottom band) and `bin/post_reel.py`.

## 3. Series consistency

One template family per week; swap only the accent colour per fabric. The feed
should read as one brand, not a scrapbook.

## 4. Accent / dominant colour

Do **not** invent a colour. The pipeline already computes and caches one per
product in `storage/app/autoposter/photo-meta-<tenant>.json` as
`{"<product_id>": {"colour": "navy", ...}}`, and
`CollectionPostBuilder::COLOURS` maps each key to its Burmese / Japanese /
English name. Reuse both.

Keys: black, white, grey, red, orange, gold, brown, green, blue, navy, purple,
pink.

**Caveat:** the cached value and the product description disagree for 58% of
products that carry an explicit colour (97 of 168, measured 2026-10-07). For a
creative's accent colour, verify against the real photo and prefer the
description where one exists. Details and samples in `prompts/EXAMPLES.md`.

## 5. Honesty rules (hard limits)

- **Never** copy a competitor's text, images, or layout pixel-for-pixel. Study
  the pattern, draw our own.
- **No fake ratings, reviews, discounts, or urgency.** Star ratings and
  "20PCS/10pcs" badges are common in competitor creatives; we only ever show
  numbers that come from Bagisto — real price, real stock, real bundle terms.
- No competitor branding anywhere in frame.

## 6. AI-generated worn images

- Label internally (e.g. `caption_meta.ai_image = true`) so AI creatives can
  always be told apart from photographs.
- The fabric pattern must stay faithful to the real product. A generated image
  that alters the print, its scale, or its colours is **rejected** — it would
  misrepresent what the customer receives.
- Review every output against the real product photo before it can be queued.

---

## 7. Quality checklist

Before a creative is allowed to post:

- [ ] Pattern matches the real product (no altered print, scale, or colour)
- [ ] Text readable on a phone at feed size
- [ ] CTA present and above the fold
- [ ] Correct width / length / price, taken from Bagisto
- [ ] Burmese text renders with the correct font (Padauk — DejaVu drops Myanmar
      glyphs and silently renders tofu)
- [ ] No competitor branding
- [ ] Product code visible

---

## 8. Related

Prompt templates for each image type: `knowledge/prompts/`.
Caption house style lives in `CaptionGenerator::systemPrompt()` — it already
forbids inventing fabric, age, provenance, condition or price, and that rule
applies to images too.
