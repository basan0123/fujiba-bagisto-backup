# Fujiba Intelligence Agent — 00 Current State

Inspection only (Rule 0.2). **No code was written for Phase 1.** Build is blocked
on a stop condition — see §6.

Inspected: 2026-10-08 ~23:55 JST, by session `root-a8`.

---

## 1. Git

| | |
|---|---|
| Current branch | `feat/autoposter-v2` (**not** `feature/intelligence-agent`) |
| Last 3 commits | `2b0cb66` v2 dry-run renderers + fabric quality gate · `4035006` v2 phase 1 pattern knowledge DB · `d89cdb2` Content Engine v2 inspection report + plan |
| Older | `34f3e5c` original AutoPoster package |
| Stashes | 2, both unrelated (`fix/gateway-provisioning…`, `leftover-v2-fragments-2026-05-11`) — not touched |
| Uncommitted in AutoPoster | **40 entries** (23 modified, 17 untracked) |

Those three v2 commits were made by **another session**, not this one.

### Uncommitted work (DO NOT TOUCH — Rule 0.1)

Modified: `config/autoposter.php`, all 13 `resources/views/admin/*.blade.php`,
`routes/admin.php`, `PlanPostsCommand.php`, `PostController.php`,
`AutoPosterQueue.php`, `AutoPosterServiceProvider.php`, `CaptionGenerator.php`,
`PostPublisher.php`, `PreflightChecker.php`, `ProductSelectionService.php`,
`QueueBuilder.php`.

Untracked: `bin/post_photo.py`, `bin/post_reel.py`, `knowledge/IMAGE-STYLE.md`,
`knowledge/prompts/`, `_fb_preview.blade.php`,
`2026_10_04_000001_add_instagram_to_autoposter_queue.php`.

This mixes work from at least three efforts (admin UI redesign 09-29, collection
rotation 10-03, Instagram 10-04/07, and this session's knowledge files 10-07).

## 2. Live v1 pipeline (must stay untouched)

| Piece | Location | State |
|---|---|---|
| Scheduler | `bootstrap/app.php` ~L190–232 | `autoposter:plan` hourly, `autoposter:dispatch` */5, `autoposter:instagram` */5 — all under `tenants:run`, gated by `AUTOPOSTER_SCHEDULE_ENABLED=true` |
| Settings (fujiba ch1) | `autoposter_settings` | 10 posts/day, 08:00–22:00 Asia/Tokyo, `auto_posting_enabled=1` |
| Queue | `autoposter_queue` | **63 published, 60 scheduled, 9 cancelled, 0 failed** |
| Instagram | same table, `instagram_*` cols | **22 published** to @fujiba_kimono since 2026-10-07 |
| Publish path | `PostPublisher` → `ChatpionPostClient` → bot.snssale.com → Graph v25 | FB + IG both live |
| Photo render | `bin/post_photo.py` | crop 4:5 → 1440×1800, mild grade, FUJIBA + code bands |
| Video render | `bin/post_reel.py` | single template, xfade only, silent, 20.5–24s |
| Captions | `CaptionGenerator` | template-only — no `ANTHROPIC_API_KEY` in `.env` |

Production is healthy and posting. Nothing in this inspection changed it.

## 3. Tables present

`autoposter_settings`, `autoposter_templates`, `autoposter_queue`,
`autoposter_post_products`, `autoposter_product_history`,
`autoposter_pattern_knowledge` (new, from the other session).

**No `fi_*` table exists yet** — verified `fi_product_profiles`, `fi_facts`,
`fi_creatives` all absent.

## 4. Env / credentials relevant to Phase 1

- `AUTOPOSTER_SCHEDULE_ENABLED=true`, `AUTOPOSTER_INSTAGRAM_ENABLED=true`.
- **No** `ANTHROPIC_API_KEY` → every LLM feature must use the template fallback,
  exactly as the spec requires.
- **No** `FUJIBA_INTEL_MODE` and no `FUJIBA_AI_MONTHLY_BUDGET_JPY` yet.
- Page token (via ChatPion, `facebook_rx_fb_page_info`) carries `read_insights`
  and `pages_read_engagement` — so Meta insight import is possible **without**
  requesting any new permission, satisfying Rule F.

## 5. Findings that shape Phase 1

- **Colour labels unreliable.** 97 of 168 products (58%) have a description
  colour disagreeing with the photo-sampled cache. Confirms the spec's decision
  to store *measured* Lab/hex in `fi_product_profiles` rather than trust either.
  (Measured 2026-10-07; see `packages/Fujiba/AutoPoster/knowledge/IMAGE-STYLE.md`.)
- **Out-of-stock dominates top sellers**, per the other session's PLAN.md: 4 of
  the top 5 sellers have stock 0. Reinforces `fi_` priority = in-stock first.
- **Sold-out posts now self-heal.** `PostPublisher::publishViaChatpion()` rebuilds
  a fully-sold-out post onto a fresh in-stock collection instead of failing
  (added 10-07). Relevant to Profit Guard's "product out of stock → block".

## 5b. Existing fabric gate vs spec (read-only finding)

`bin/v2/gate.py` (committed by the other session in `2b0cb66`) already implements
a numeric fabric gate. Its thresholds are **looser** than the master command's:

| Check | Existing `gate.py` | Spec (Rule 1) | Resolution |
|---|---|---|---|
| Colour | mean **ΔE ≤ 3.0**, **CIE76**, after σ1.5 blur | **CIEDE2000** mean ≤ 2.0, p95 ≤ 5.0 | spec is stricter → **adopt spec**, document both |
| Structure | **SSIM ≥ 0.80** (luma) | **SSIM ≥ 0.95** (luma, in mask) | spec is stricter → **adopt spec** |
| Pattern | pHash distance ≤ 8/64 | ORB/SIFT inlier ratio ≥ 0.6 + edge-map diff | different method; keep pHash **and** add feature-match |
| Coverage | fabric coverage + `excluded ≤ 0.5` | mask area ≥ planned | compatible, align naming |
| Text | min text size ≥ 26px | text overflow/overlap QA | complementary |

Two implementation notes:

1. **CIE76 ≠ CIEDE2000.** The existing code computes a plain Euclidean Lab
   distance. CIEDE2000 needs its own implementation — it is not a threshold
   change.
2. **ORB/SIFT needs OpenCV, which is not installed.** Verified available:
   `PIL 11.1.0`, `numpy 2.2.3`. **Missing:** `cv2`, `skimage`, `scipy`,
   `imagehash`, `sklearn`. The existing gate is hand-rolled numpy for exactly
   this reason.

So the Rule 1 gate cannot be fully implemented without either installing OpenCV
(a system change needing approval) or hand-rolling feature matching in numpy.
**Owner decision required** — listed in §7.

Local-only photo metrics for Rule M (resolution, Laplacian sharpness, colour
cast, exposure) are all feasible with numpy + PIL alone, no new dependency.

## 6. STOP CONDITION — why Phase 1 was not built

Master command **Rule 6**: *"If a file is being changed by another session
(recent mtime, uncommitted diff), stop and report instead of editing."*
Stop conditions also list *"any edit to a file another session is working on."*

Both triggers are met:

1. **`root-a7` is an active peer session, status `busy`**, started ~52 min ago.
   It authored the three `feat/autoposter-v2` commits.
2. **`AutoPosterServiceProvider.php` was modified at 2026-10-08 23:02** (~50 min
   before inspection) and is uncommitted. Phase 1 must edit this file to register
   new commands and providers.
3. 23 AutoPoster source files carry uncommitted diffs from that session.

Additional conflicts with the spec as written:

- Spec requires branch `feature/intelligence-agent`; HEAD is
  `feat/autoposter-v2` with another session's unpushed work. Branching now would
  either strand or drag along 40 uncommitted entries that are not mine to commit.
- Rule 0.5 forbids `git add -A` and committing others' work — so the uncommitted
  backlog cannot simply be swept into a new branch.

**Nothing was created beyond this file.** No migrations, no tables, no code, no
branch, no commits.

## 7. To unblock

Any one of these lets Phase 1 start cleanly:

1. **Wait** for `root-a7` to finish and commit, then branch from a clean tree.
2. **Tell that session to stop**, and confirm who owns the 40 uncommitted entries.
3. **Explicitly authorise** this session to proceed and to work around the
   shared files — overriding Rule 6 for this case.

Recommended: option 1. The two efforts overlap heavily (that session's v2 plan
already consumes this session's `knowledge/` files), so serialising them avoids
two agents writing the same provider and config.

**Chosen 2026-10-08: option 1 — wait.** A monitor is watching for new commits on
`feat/autoposter-v2` and for the working tree to go quiet. No AutoPoster file,
migration, branch, or commit has been touched.

## 8. Owner decisions needed before Phase 1 can fully meet Rule 1

| # | Decision | Options | Recommendation |
|---|---|---|---|
| 1 | ORB/SIFT pattern matching needs OpenCV; not installed | (a) `pip install opencv-python-headless` (system change, free, ~60MB) · (b) hand-roll a numpy feature matcher · (c) keep pHash + edge-map only and document the deviation | **(a)** — (b) is slow and error-prone for a safety gate; (c) weakens Rule 1 |
| 2 | CIEDE2000 implementation | (a) hand-roll in numpy (well-defined formula, ~40 lines) · (b) `pip install colormath`/`colour-science` | **(a)** — no new dependency, fully testable |
| 3 | Gate threshold conflict with live v2 renderers | spec ΔE≤2.0/SSIM≥0.95 may reject assets the current renderers produce | adopt spec thresholds for `fi_` assets only; leave `bin/v2/gate.py` defaults alone so the other session's work is untouched |

None involve paid APIs. Item 1 is the only one that changes the server.
