# Caption-style research: method, sources, limits (WS-K, rubric cs-1.0)

Goal: learn how real human sellers write post text in Burmese, Nepali, Japanese and English so captions sound like a shop owner. Staging only: v1 captions, templates and CaptionGenerator are untouched; nothing is posted or queued.

## Sources (only legitimate ones)

| source | rows | included | what it is |
|---|---|---|---|
| own_page_phrases | 3 | 3 | paragraphs of our published captions that CollectionPostBuilder attributes to the page's own past posts |
| owner_screenshot | 20 | 0 | owner Instagram screenshots in fi_competitor_posts: image-only crops, no caption text |
| tiktok_oembed | 12 | 12 | captions already in fi_competitor_posts, fetched earlier via TikTok official oEmbed |

Excluded: no_caption_text 20 (2 of them also flagged as AI-generated images; 0 captions excluded as AI-written). No new network calls were made: every TikTok URL in the DB already had its oEmbed caption and the owner gave no new URLs (any future call: official oEmbed only, ≤1 request/5 s, UA FujibaResearch/1.0). No Facebook/Instagram scraping, no profile enumeration. Inbox `/root/incoming/caption-screens/{my,ne,ja,en}/` (mode 700, README.txt) had 0 files at analysis time.

Our captions: last 50 published autoposter_queue rows (ids 137–186), split into my / ne / ja / en / shared segments.

## Script detection

- Language of a caption = script with most letters in the body (hashtags, @mentions, URLs removed); Latin text is Nepali when it has ≥2 strong romanized-Nepali tokens (lagi, cha/xa, garnus, hola, matra, …) or 1 strong + 1 weak (ko, ma, …). Romaji tokens (onegaisamasu, nippori, …) are counted separately.
- English-mix ratio = Latin English word units ÷ all word units (Burmese syllables via the sylbreak rule, Japanese characters ÷ 2, Devanagari/Latin words).
- **Zawgyi heuristic** (Burmese): Zawgyi stores glyphs in visual order and reuses Myanmar code points, so some sequences are impossible in Unicode. Zawgyi signals: Z1 ေ (U+1031) not after a consonant/medial; Z2 U+103B before a consonant; Z3 any U+1060–U+1097; Z4 U+1039 not followed by a consonant; Z5 U+1004/U+101A + U+1039; Z6 U+1033/U+1034. Unicode signals: U1 kinzi U+1004 U+103A U+1039 (×2); U2 U+103A after a consonant that never takes medial ya; U3 U+1037 next to U+103A; U4 medial then ေ (×0.5). p = Z/(Z+U): ≥0.8 zawgyi, ≤0.2 unicode, else mixed; <4 Myanmar letters → none; no signal → uncertain.
- Self-test: 16/16 (4 Unicode strings, 4 hand-encoded Zawgyi strings, emoji, syllables, copy guard, romanized Nepali).
- Limits: Shan/Mon/Karen text uses U+1060–U+109F legitimately (false Zawgyi); very short strings or hashtags carry no signal; mixed copy-pastes come out "mixed"; a screenshot cannot reveal the encoding (only rendering damage such as a dotted circle before ေ hints at Zawgyi), so screenshot-read Burmese is stored as transcribed Unicode with read_confidence < 1. Copied text (owner_paste) keeps the real bytes.

## AI-likeness rubric

Score = clamp(sum of weights, 0, 1). AI-looking at ≥ 0.4 (sample excluded / segment ai_sounding); 0.25–0.4 = borderline. Calibration: the 15 collected human captions score at most 0.05; our v1 segments score my 0.00–0.00, ne 0.45–0.85, ja 0.45–0.60, en 0.45–0.45, shared 0.00–0.20.

| signal | weight |
|---|---|
| S1_em_dash | +0.20 |
| S2_middle_dot_chain | +0.15 |
| S3_telegraphic | +0.15 |
| S4_formal_register | +0.25 |
| S5_generic_praise_1 | +0.10 |
| S5_generic_praise_2plus | +0.20 |
| S6a_symmetric_emoji_frame | +0.10 |
| S6b_emoji_bullets | +0.10 |
| S6c_flag_language_labels | +0.10 |
| S7_parallel_translation | +0.10 |
| S8_template_repetition | +0.10 |
| S9_rhetorical_question_hook | +0.10 |
| S11_ai_image_suspected | +0.60 |
| S12_devanagari_body_vs_measured | +0.10 |
| H1_informal_spelling_or_grammar | -0.15 |
| H2_colloquial_1_2 | -0.10 |
| H2_colloquial_3plus | -0.20 |
| H3_natural_code_mixing | -0.10 |
| H4_short_direct | -0.05 |
| H5_spacing_quirks | -0.05 |
| H6_specific_local_detail | -0.05 |

S = AI/template signals, H = human signals (typos, colloquial particles, natural code-mixing, hand-typed spacing, local detail). S8 template repetition = ≥50% of a segment's lines recur (codes/numbers/colours masked) in ≥80% of the 50 posts. S12 applies only because measured Nepali-audience captions use no Devanagari body text. Analyst (Claude-coded) human markers on competitor posts: hashtag typos (#swostikgaesha), non-native grammar, "Orginal".

## Copy guard and fact guard

- No generated text (openings, CTAs, drafts, rewrites) may share a run of ≥6 words (URLs, codes and prices masked), ≥20 Burmese characters or ≥12 Japanese characters with any collected text (25 texts incl. cover overlays). Openings, CTAs and drafts are additionally checked against every published v1 caption (122 texts in total) so they are genuinely fresh. Result: 140/140 passed.
- Drafts: no 細布, no width/length words, no supplier codes or supplier name, price ¥1,595 only, Messenger link present, sold-out drafts start with [DO NOT POST until restocked], EN material only "Hosonuno (100% cotton)", JA only 綿100%, Burmese must classify as Unicode. Result: 60/60 clean.
- Rewrites of our 50 captions use only facts parsed from that row's published caption text (codes, price lines, sold-out codes, colour, tax/offer wording, Messenger link); a template needing a fact the row does not state is skipped. No width/length is used.

## Measured vs model knowledge

- `measured (n=…)` = counted from included human samples in fi_caption_samples; style rows in fi_caption_style with method=measured. A language with fewer than 10 included samples with body text is **provisional**.
- `model_knowledge (not measured)` = typical small-seller practice from general knowledge, stored as method=model_knowledge rows (sample_size 0). Never mixed into measured counts.

## Re-run (after screenshots arrive)

```bash
cd /var/www/bagisto-wt/ws-k   # or the live tree once merged
D=/var/www/bagisto/storage/app/intel/captions
python3 -I packages/Fujiba/AutoPoster/bin/intel/caption_style.py inbox --dir /root/incoming/caption-screens --out $D/inbox.json [--ocr]
php packages/Fujiba/AutoPoster/bin/intel/caption_style_db.php export $D/bundle.json
python3 -I packages/Fujiba/AutoPoster/bin/intel/caption_style.py analyse --bundle $D/bundle.json \
    --content docs/intelligence/caption-style-content.json --inbox $D/inbox.json --out $D/analysis.json
python3 -I packages/Fujiba/AutoPoster/bin/intel/caption_style.py render --analysis $D/analysis.json \
    --content docs/intelligence/caption-style-content.json --outdir docs/intelligence
php packages/Fujiba/AutoPoster/bin/intel/caption_style_db.php load $D/analysis.json [--dry-run]
```
Burmese/Nepali screenshots: Claude reads each image and writes `<image>.claude.txt` (first lines `# source: claude_read` and `# confidence: 0.x`) before `inbox` is run; en/ja screenshots get a tesseract draft `<image>.ocr.txt` (`--ocr`, heavy_guard) that must be checked and marked `# verified: yes`.

## Limits (honest)

- English and Nepali samples are short TikTok captions from Nepali-audience sellers of the same fabric; Facebook posts are longer by nature, so length comparisons are platform-skewed.
- Burmese: zero competitor captions (Facebook not scrapable, no screenshots yet); the measured Burmese is our own page (1 seller). Japanese: zero samples.
- Engagement is not exposed by oEmbed, so nothing here says which style sells better; that needs fi_performance / insights data later.
