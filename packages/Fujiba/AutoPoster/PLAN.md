# Fujiba AI Content Engine v2: Plan

Status: **plan and first-run prototype only.** Live daily posting (v1) is untouched and stays on until v2 is verified.

## 0. Inspection: what exists today

| Piece | Where | Facts |
|---|---|---|
| Scheduler | `bootstrap/app.php` ~L190–221 | `autoposter:plan` (7 days ahead) and `autoposter:dispatch` (every 5 min), gated by `AUTOPOSTER_SCHEDULE_ENABLED` (on). `autoposter:instagram` runs every 5 min. |
| Settings | `autoposter_settings` (fujiba, channel 1) | 10 posts/day, 08:00–22:00 Asia/Tokyo, 7-day product cooldown, default `multi_photo`, `en_ja`. |
| Queue | `autoposter_queue` (+ `autoposter_post_products`, `autoposter_product_history`) | 63 published, 60 scheduled (10/day to 2026-10-14), 0 failed. No metrics columns. |
| Content selection | `Services/CollectionPostBuilder.php` | Rotates `new` / `colour` / `pattern` groups. Every 3rd post is a reel (`VIDEO_EVERY=3`). Grade A/B arm (`ENHANCE_EVERY=2`). |
| Photo render | `bin/post_photo.py` | Picks the most "fabric-only" source, crops 4:5 at 1440×1800, mild autocontrast + sharpen, then stamps a FUJIBA band and a code band. **This is why edits look like the originals: it is a crop + light grade by design.** |
| Video render | `bin/post_reel.py` (ffmpeg + ImageMagick pango) | **One template**: blurred background, slow zoom, `xfade=fade` only, size label, FUJIBA end card, **silent track**. 20–24 s. |
| Captions | `CaptionGenerator` / `ClaudeCaptionClient` | Claude when `ANTHROPIC_API_KEY` is set, otherwise rotating templates. **The key is not in `.env`, so captions are template-only.** |
| Publishing | `PostPublisher` → `ChatpionPostClient` → bot.snssale.com → Graph v25 | FB live. IG cross-post live since 2026-10-07. The Page token has `read_insights` + `pages_read_engagement`. |
| Knowledge | `knowledge/IMAGE-STYLE.md`, `prompts/*.md` | Descriptive only. The worn/drape/stack types are **not implemented**. |
| Tools on server | | ffmpeg 7.1, ImageMagick, Python 3.13 + Pillow 11 + numpy 2.2. **No** OpenCV / scikit-image / rembg. Fonts: IPA (JA), Myanmar (several). **No Devanagari font, so Nepali text can't be drawn on images yet.** |
| Gen/vision APIs | `.env` | **None.** No Anthropic, OpenAI, Gemini, fal, Replicate, Runway, Luma, Kling or ElevenLabs key. |

Data findings that shape the plan:
- **Our best sellers are often out of stock.** 4 of the top 5 sellers (12 months) have stock 0, so v2 selection must key on in-stock items × demand, not demand alone.
- **Source photo quality varies.** Some products only have packaged/watermarked shots (e.g. FV-10-A: label plus a "Fujiba Elegance Emporium" watermark across the fabric), so the clean close-up area is small. The knowledge record flags these as `photo_needs`.
- **Colour labels are unreliable.** 58% of description colours disagree with the photo-sampled cache (see knowledge/IMAGE-STYLE.md). v2 stores **measured** hex colours (grade A) instead of trusting either.

## 1. Architecture: extend v1, no parallel system

```
v1 (live)                                v2 additions (same package)
CollectionPostBuilder.fill() ───────────► ContentPlanner picks template+scenario+product (calendar, rotation, festival boost)
bin/post_photo.py                        bin/v2/compose.py   template renderers (real fabric pixels only)
bin/post_reel.py                         bin/v2/reel.py      story/motion engine (varied transitions, music bed)
                                         bin/v2/gate.py      quality gate (fabric-region match + checks)
PostPublisher (unchanged) ◄──────────── queue rows carry caption_meta.v2 = {template, scenario, combo, gate}
```

The switch is a per-channel setting `content_engine = v1|v2|shadow`. In `shadow` mode v2 renders next to v1 into the preview folder and nothing it makes is posted. v1 keeps running until you approve.

## 2. Storage: MySQL tenant tables (decision)

MySQL rather than JSON files, because the data has to join with `product_flat`, `autoposter_queue` and stock for selection. The admin UI needs to read and edit it. Rotation rules ("no repeat in 7 days") are SQL queries. Per-tenant isolation then comes for free.
Long analyst notes and source images stay on disk, and the table points to them.

| Table | Purpose |
|---|---|
| `autoposter_pattern_knowledge` | One row per product: image hash, close-up crop, measured colours, motifs, category, repeat, scale, background, texture, meanings / seasons / occasions (each claim graded A–D with source), `fingerprint` (what must never change), `photo_needs`, hooks EN/JA/MY/NE, `analysed_by`, `analysed_at`. Re-analysis happens only when `image_hash` changes. |
| `autoposter_creative_log` | One row per rendered asset: queue id, product ids, template, scenario, character, motion/transition/music combo, duration, gate results, file path, cost. Drives the 7-day no-repeat rule. |
| `autoposter_post_metrics` (Phase 7) | Daily insight snapshots per post: reach, 3s views, avg watch, completion, saves, shares, comments, link clicks. |
| `autoposter_scenarios` / `autoposter_characters` / `autoposter_festivals` (Phases 4–5) | The story pool, persistent characters and festival calendar, all editable in admin. |

Confidence grades: **A** = seen/measured in our photo; **B** = reliable published source (named in `source`); **C** = interpretation (marketing angle, never stated as fact); **D** = unknown, so not used in captions.

## 3. Phases

| Phase | Deliverable | Needs |
|---|---|---|
| 1 Knowledge DB | Table + `autoposter:knowledge {import\|status\|stale}`, measured palette + crop via `bin/v2/measure.py`. **Done for 5 products in this first run.** Scaling to ~380 kimono products needs a vision model, i.e. `ANTHROPIC_API_KEY` (Claude vision, about 1 call per product, once). |
| 2 Image lock | `compose.py` uses only real pixels: crop, perspective warp, fold shading, shadows. A generator can only supply backgrounds/people, never fabric. Close-ups get exposure/sharpen only (ΔE gate ≤ 2). | — |
| 3 Templates | 10 renderers A–J. The first run has 3: **H colour set**, **J folded stack**, **B/G motif zoom + meaning callout**. | Devanagari font for Nepali text on images |
| 4 Video engine | Story beats (hook → reveal → story → brand → CTA), 15/30/45 s, a pool of motion/transition/text styles, a combo log. The first run has 2 reels. | Music: licensed library or generator (see §4) |
| 5 Calendar | ContentPlanner: 10/day mix, festival boost T−14 days (Thingyan, Thadingyut, Tazaungdaing, Dashain, Tihar, Teej, JP New Year…), Asia/Tokyo slots. Festival dates are stored per year, never computed by guess (lunar calendars). | — |
| 6 Quality gate | `gate.py`: fabric-region pHash + mean ΔE00 vs the source crop, sharpness, text-in-safe-area, mobile legibility. A vision check (motif unchanged, no fake kanji, hands/faces) needs a vision model. Max 2 retries, then skip + log. | `ANTHROPIC_API_KEY` for the vision part |
| 7 Feedback | `autoposter:insights` daily via the ChatPion page token (already has `read_insights`); a weekly re-weighting report. | ChatPion endpoint for insights (small addition) |

## 4. APIs: what we have and what to add

| Need | Without a key (works now) | Recommended key (`.env` only) |
|---|---|---|
| Vision analysis + gate vision check + captions MY/NE | Manual analysis (this first run); template captions | `ANTHROPIC_API_KEY` (one key covers analysis, the gate check and multilingual hooks) |
| People / scenes (Myanmar wedding, mother receiving a parcel…) | Motion graphics + composited real fabric, text-led storytelling, no people | An image model for **backgrounds/people only**: `FAL_KEY` (Flux) or `OPENAI_API_KEY` (gpt-image). The fabric is always composited in afterwards. |
| AI video scenes | Ken Burns / parallax / unfold / zoom-to-motif from stills | Optional: `RUNWAY_API_KEY` or `KLING`/`LUMA`. Same rule: scene only, fabric composited. |
| Music | Silent (as today) | A licensed royalty-free library dropped into `storage/app/autoposter/music/<mood>/`. Avoids per-track API cost and copyright risk. |
| Nepali on images | — | `apt install fonts-noto-core` (adds Noto Sans Devanagari) |

Cost control: analysis is cached by image hash, a post never triggers re-research, and every API call writes its cost to `autoposter_creative_log.cost_jpy`. A daily cap is set in config.

## 5. Safety rules carried into the code
- Fabric pixels come only from our product photos. No model ever receives "redraw this fabric".
- Factory/process content is captioned "How Japanese fabric is made", never as Fujiba's own factory.
- Supplier codes never appear on images or in captions (Naka-code secrecy rule); use the public alias (FV-10-A).
- Meanings with grade C are framed as feelings ("a gift that says…"), grade D is never used, and Myanmar/Nepal festival links are always C unless sourced.
- Dry-run writes to `storage/app/autoposter/preview/<date>/` only.

## 6. First run (this delivery)
1. This inspection + plan.
2. Phase 1 for 5 in-stock best sellers: FV-10-A, FV-73-C, FV-59-A, FV-81-A, FV-61-C.
3. Dry-run: 3 images + 2 reels with different templates, each with gate results, in the preview folder. **Nothing is queued or posted.**
