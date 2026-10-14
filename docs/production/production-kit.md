# Fujiba one-day production kit

Prepared 2026-10-15 for one shoot day: 3 products, worn-style photos on the dress form plus flat-panel photos and short vertical videos.

**Status.** Everything in this kit is **PROPOSED**. Nothing has been shot, published or scheduled. Only the product checks and the free preview concepts are **DONE**.

## 1. The three products (verified)

All three were checked against the live catalogue on 2026-10-15 at 08:09 JST, using the same fail-closed rules as the nightly batch (`bin/v3/batch_scope.php`). All three passed every check: enabled in en and ja, no active special price, available = stock − reserved > 0, primary photo on disk has the same sha256 as `fi_catalog_images` with no duplicate flag, not blocked by DuplicateImageGuard, public code only (no supplier code in the name), and the primary photo is clean in the watermark scan (`watermark_scan_enabled.csv`).

| Use | SKU | product_id | Public name | Price (product_flat) | Available | Primary photo | Size | sha256 match | Checked at |
|---|---|---|---|---|---|---|---|---|---|
| Myanmar htamein (bold florals, temari balls) | KIMONO-2545 | 1972 | FU-90-A | ¥1,595 | 5 | product/1972/EjHCcx…qC.webp | 1496×2000 | yes (3159fba5…) | 2026-10-15 08:09 JST |
| Nepali lungi (white cranes, gold waves) | KIMONO-2625 | 2052 | FV-73-B | ¥1,595 | 11 | product/2052/93XbXA…9.webp | 1500×2000 | yes (11436508…) | 2026-10-15 08:09 JST |
| Japanese fabric-detail campaign (dragons, gold line work) | KIMONO-2773 | 2191 | FV-59-A | ¥1,595 | 13 | product/2191/CNwFZR…80.webp | 1496×2000 | yes (4af5d86f…) | 2026-10-15 08:09 JST |

Catalogue material: FU-90-A "Square (100% cotton)"; FV-73-B and FV-59-A "Hosonuno (100% cotton)". Width and length are **not confirmed** for any of them (there is no `owner_fill_in` value). Do not state a size.

**Check again on the morning of the shoot.** Stock and price can change. Run `batch_scope.php` again and `prod_ctx.py`; it exits 2 if a product now fails.

## 2. Equipment

**Already have:** phone (main camera, 4K/30 video), dress form, a plain light wall, a window with daylight, an iron, a table for flat shots.

**To buy (PROPOSED, about ¥12,000 in total):**

| Item | Why | Approx. cost |
|---|---|---|
| Phone tripod with Bluetooth remote | Same height and angle in every shot, no shake | ¥2,500 |
| Handheld garment steamer | Removes fold lines on the dress form (ironing a wrapped cloth is not possible) | ¥4,000 |
| White foam board 90×60 cm, ×2 | Bounces window light into the shadow side; also a flat-shot surface | ¥1,600 |
| Large clips / spring clamps ×6 | Hold the fabric at the back of the form, out of sight | ¥600 |
| Safety pins (large) + glass-head pins | Fix the tuck and the overlap panel | ¥400 |
| Lint roller | Removes dust and threads before close-ups | ¥300 |
| Grey card (18%) | One reference frame per product so colour can be matched later | ¥800 |
| Neutral backdrop paper or cloth, light grey, 1.35 m | Plain background if the wall has marks | ¥1,800 |

## 3. Dress-form preparation and mounting

1. Put the form 1.5 m in front of the background and 1 m to the side of the window, so the light comes from the side at about 45°.
2. Set the form height so its waist is at your chest height. This makes the camera height easy to repeat.
3. Wipe the form. Put a thin plain slip or a tight light tube on it if the form's colour shows through thin cotton.
4. Steam each fabric flat **before** wrapping. Steam again once it is wrapped. Never stretch the fabric to remove a wrinkle.
5. Keep the printed side facing out. Make sure the pattern direction is correct: cranes and dragons point upward or across, never upside down.

### Myanmar htamein (FU-90-A): side tuck, overlap panel, ankle length

1. Hold the fabric horizontally, with the printed side facing out and the top edge at the waist.
2. Set the hem at "ankle" height on the form. On most forms that is the bottom of the stand pole collar. Measure it, and use the same height for every product.
3. Wrap from the form's left side across the front to the right side and round the back.
4. Bring the remaining end round to the front again, so the **overlap panel** lies on top at the front-left. The overlap edge should run straight down.
5. At the left hip, fold the top corner over itself and tuck it inside the waistband (the **side tuck**). Pin it from the inside so no pin shows.
6. Fold down a 3–4 cm waistband if you want the tuck line to be visible.
7. Check that the hem is level all the way round, and that the overlap edge is vertical with soft, natural folds.

### Nepali lungi (FV-73-B): straight wrap

1. Wrap the fabric straight round the form like a tube, printed side out, with the top at the waist.
2. Pull the two ends to the front centre, then fold both together towards the right.
3. Roll the top edge down twice (a 2–3 cm roll) to lock it. Clip the back if needed.
4. The hem should be straight and level at about ankle height, with vertical folds and no diagonal pull lines.

**What every wrap must show:** the waist (top edge or roll), the tuck or roll point, the overlap edge, and natural vertical folds. The motif must not be cut off at the hip.

## 4. Light, phone position, background

- **Light:** daylight from the side window only. Do not mix it with ceiling lights, because colour then shifts. Shoot between 10:00 and 15:00 and avoid direct sun. Put the white foam board on the shadow side.
- **Phone:** on the tripod, lens at waist height of the form, 1.8–2.2 m away for full-length shots, lens level (not tilted). Use the 1× lens only, because the ultra-wide lens bends the pattern. Lock exposure and focus (long press). Turn off beauty and HDR effects and portrait mode.
- **Background:** a plain light wall or grey paper with nothing else in the frame: no plants, cables or other stock.
- **Close-ups:** 20–30 cm from the fabric, with the phone parallel to the cloth so the pattern is not distorted. Tap to focus on the gold line work. Take one shot with the grey card beside the fabric.
- **Vertical framing (Reels/TikTok/FB, 9:16, 1080×1920):** keep the product and any text inside the centre area. Leave about 250 px clear at the top and about 420 px at the bottom (captions and buttons), and about 120 px on the right side (like and share icons).

## 5. One-day timetable (realistic)

| Time | Task |
|---|---|
| 09:00–09:45 | Set up form, backdrop, tripod. Steam all 3 fabrics. Take a test shot and check it on a computer |
| 09:45–10:00 | Recheck stock and price (`batch_scope.php`); grey-card frame |
| 10:00–11:15 | Product 1 FU-90-A (htamein wrap): photos then video |
| 11:15–12:30 | Product 2 FV-73-B (lungi wrap): photos then video |
| 12:30–13:15 | Lunch (keep the light setup untouched) |
| 13:15–14:15 | Product 3 FV-59-A: flat panel and close-ups, plus a short wrap |
| 14:15–14:45 | Reshoots from a quick QC on a laptop |
| 14:45–15:30 | Rename and upload files to the folders below |
| 15:30–17:30 | Editing: crops, brand mark, captions (drafts only) |
| 17:30–18:00 | Final QC; hand drafts to the owner for approval (**nothing is published**) |

## 6. Shot lists

The standard for every product is 5 photos: front full-length on the form, side (tuck), back, flat panel, and motif close-up. Worn-video shots: front 3 s, walk/turn on the form 4 s, turn 3 s, back 2 s, waist close-up 2 s, hand close-up 2 s, flat 2 s (18 s total).

### FU-90-A: Myanmar htamein

1. **Clean product photo:** fabric folded flat on white board, from directly overhead, with the whole repeat visible.
2. **Full-length garment:** form front, htamein wrapped to ankle length, overlap panel at front-left.
3. **Close-up A:** the side tuck at the left hip (shows how it is worn).
4. **Close-up B:** a temari ball and plum flowers with the gold outline, filling the frame.
5. **Close-up C (extra):** the hem edge and overlap, showing the folds.
6. **Vertical video (18 s):** 0–1 s hook, a fast push-in on the bright red florals. Then front 3 s, slow turn 3 s, back 2 s, waist and tuck close-up 2 s, a hand smoothing the overlap 2 s, flat 2 s. End card 3 s: code, price, Messenger CTA. Audio: only tracks from the platform's own licensed library.
7. **Campaign layout:** concept 2 "traditional" (Burmese headline). Preview: `F10-traditional-FU-90-A.jpg`.

### FV-73-B: Nepali lungi

1. **Clean product photo:** flat, overhead, with the cranes facing the right way up.
2. **Full-length garment:** form front, straight lungi wrap, rolled waist.
3. **Close-up A:** the rolled waist and the fold point.
4. **Close-up B:** one crane wing and the gold seigaiha waves.
5. **Vertical video (18 s):** hook on a crane flying across the frame (a slow pan), front, turn, back, waist roll, hand on the fold, flat. End card with the Messenger CTA. Platform audio only.
6. **Campaign layout:** concept 2 "traditional" (Nepali headline). Preview: `F10-traditional-FV-73-B.jpg`.

### FV-59-A: Japanese fabric detail

1. **Clean product photo:** flat, overhead.
2. **Full-length garment:** short straight wrap on the form, or a long drape from the shoulder of the form (no cutting or pinning through the print).
3. **Close-up A:** a dragon head with the gold line work (macro distance, sharp).
4. **Close-up B:** the wave crest and cloud.
5. **Close-up C:** a chrysanthemum roundel.
6. **Vertical video (15 s):** a dark, slow sequence of the three close-ups (2–3 s each, gentle slide), then the full drape, then the end card. Platform audio only.
7. **Campaign layout:** concept 3 "cinematic" or concept 1 "editorial". Previews: `F11-cinematic-FV-59-A.jpg`, `F9-editorial-FV-59-A.jpg`.

## 7. File naming and upload folders

Name every file like this: `<SKU>_<YYYYMMDD>_<shot-type>_<nn>.<ext>`

Shot types: `product`, `full`, `side`, `back`, `flat`, `closeup`, `tuck`, `waist`, `video`, `greycard`, `endcard`.

Example: `KIMONO-2545_20261016_closeup_02.jpg`, `KIMONO-2625_20261016_video_01.mp4`

| What | Folder |
|---|---|
| Form or wrap photos and videos | `/root/incoming/worn-shoots/<SKU>/` |
| Flat panel and close-ups used for twin images | `/root/incoming/twin-shoots/<SKU>/` |

Keep the original files from the phone (no edits) in these folders. Edited versions go into a subfolder `edited/`.

## 8. QC checklist (every photo and video)

- [ ] No wrinkles or crease lines from folding
- [ ] Exposure is correct: gold is not blown out white, and the dark ground is not crushed black
- [ ] Sharp: zoom to 100% on the gold line work
- [ ] Pattern not distorted (no ultra-wide lens, phone parallel for close-ups)
- [ ] Correct garment shape: htamein has a side tuck and overlap at ankle length; lungi has a straight wrap and rolled waist
- [ ] Colour matches the product photo (compare side by side; use the grey-card frame)
- [ ] No fingers, pins, clips, cables or other stock in the frame (a hand is allowed only in the "hand" shot)
- [ ] Supplier labels, selvedge printing and tags are hidden
- [ ] The pattern is the right way up

## 9. Editing workflow and caption template

1. Pick the best take of each shot type. Correct only exposure and white balance, using the grey card. **Do not** change the fabric's colour, saturation or pattern.
2. Crops: Feed 4:5 (1080×1350), Reels/TikTok/Stories 9:16 (1080×1920) inside the safe zone, Messenger or catalogue 1:1 (1080×1080).
3. Brand mark: "Fujiba Elegance Emporium" in the Mali font, placed on the background, never on top of the fabric.
4. Code and price: the public code and the verified product_flat price (for example "FU-90-A · ¥1,595"). Put them at the bottom-left, off the fabric. Check the price again on the day you publish.
5. Captions: Burmese first, in Burmese **Unicode** (never Zawgyi), then the Nepali or Japanese variant. Follow `storage/app/intel/captions/caption-style-my.md`, `-ne.md` and `-ja.md`: short (about 3 lines), friendly "ရှင့်" tone, few emoji, no hashtag spam.
6. CTA: always `https://m.me/103196179075087`.
7. Not allowed: "Made in Japan", any size or width unless an `owner_fill_in` value exists, shipping prices, and any claim that is not in the catalogue.

**Caption template (PROPOSED drafts; owner approves the wording):**

```
[my] {code} ထမီအတွက် ပိတ်စ လှလှလေး ရပါပြီရှင့် 🌸
ချည်သား ၁၀၀% · ¥{price}
Messenger မှ မှာယူနိုင်ပါတယ်ရှင့် 👉 https://m.me/103196179075087

[ne] {code} लुंगीका लागि सुन्दर कपडा · १००% कपास · ¥{price}
Messenger मा अर्डर गर्नुहोस् 👉 https://m.me/103196179075087

[ja] {code} 和柄コットン生地 · 綿100% · ¥{price}
ご注文はMessengerから 👉 https://m.me/103196179075087
```

Use "100% cotton" only because the catalogue says it for these 3 products.

## 10. Fabric fidelity (non-negotiable)

- Fabric pixels always come from real photos. They are never redrawn, recoloured, mirrored, stretched or AI-generated.
- Crops may only be scaled uniformly. Every composite goes through `fabric_gate.py --master`. Any gate fail means it is not used.
- The gate rule `min_text_px ≥ 26` and the other gate thresholds stay as they are.

## 11. Free preview concepts (DONE, preview only)

Folder: `storage/app/autoposter/preview/2026-10-15-production-concepts/`. Contact sheet: `contact-sheet.jpg`.

1. **Editorial** (family 9): asymmetric magazine layout with a large fabric crop and an inset detail.
2. **Traditional** (family 10): htamein or lungi styling layout with a waist band and two panels, and a Burmese or Nepali headline. It is labelled "flat fabric shown, not a worn photo". No AI model was used.
3. **Cinematic** (family 11): dark film strip of 3 real motif crops.

All 8 renders pass the gate: deltaE 0, SSIM 1.0, maximum zoom 0.8× (crops are never enlarged). Families 9–11 are `enabled=False`, so the nightly batch never renders them. The vision check (motif/kanji/faces) is still a manual owner review.

## 12. Remaining manual tasks (PROPOSED)

- Buy the equipment in section 2.
- Owner confirms the htamein and lungi wraps on the form look right, and ideally checks them with a Myanmar or Nepali customer.
- Owner fills in width and length (`owner_fill_in`) if size should appear.
- Shoot, QC, upload, edit, and get approval for captions.
- Owner reviews the 3 concept directions.

## 13. Risks

- Stock may sell out before or after the shoot (FV-59-A 13, FV-73-B 11, FU-90-A 5). Recheck on the day.
- A wrong wrap shape would look unprofessional to Myanmar and Nepali buyers.
- Mixed light causes a colour mismatch with the product photo.
- Commercial music in Reels risks takedown. Use platform library audio only.
- The ¥ sign is drawn by the serif font. Check that it reads clearly at phone size.

## 14. Estimated API costs

None are needed. The kit and the concepts are free. **Optional** AI styling concepts would use about ¥22 per image (Nano Banana Pro, `gemini-3-pro-image`: average ¥21.9 over 5 successful calls in `fi_ai_usage`), plus free colour correction. For example, 3 products × 2 concepts ≈ ¥130. Any such image must be labelled "Styling concept – actual fabric may differ". None were made in this task.
