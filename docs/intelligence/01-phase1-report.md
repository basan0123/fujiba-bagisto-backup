# Fujiba Intelligence Agent — Phase 1 report

Branch `feature/intelligence-agent`, cut from `2b0cb66`.
Written 2026-10-09. Every number below came from a command that was actually
run; anything unverified is marked `capability_limited`, not `complete`.

---

## 1. Architecture

```
product (Bagisto, live)
  │
  ├─ PhotoAuditService ──► bin/intel/photo_audit.py      (local cv2; a FACT)
  │                        cache: image_sha256 + metadata_hash
  │
  ├─ VisualEvidenceService ──► bin/intel/fabric_match.py (5 signals)
  │                            WebVisualSearchProvider = capability_limited
  │
  ├─ fi_facts  ◄── provenance for every claim used in content
  ├─ fi_marketing_memory  (hypothesis → … → confirmed_learning, evidence-gated)
  ├─ fi_creative_components + links   (creative DNA, recombined)
  ├─ fi_events                        (unresearched dates = zero influence)
  │
  ├─ ProfitGuard ──► allow/block + fi_profit_guard_log
  ├─ CostGuard   ──► budget check BEFORE call, fi_ai_usage after
  │
  ├─ provider (null in Phase 1) ──► UNTRUSTED output
  │        │
  │        ▼
  │   bin/intel/compositor.py   ← discards every provider pixel in the mask,
  │        │                       re-inserts the REAL photograph
  │        ▼
  │   bin/intel/fabric_gate.py  ← CIEDE2000 / SSIM / ORB / coverage
  │        │
  │        ▼
  │   QA ──► HUMAN APPROVAL ──► (publish: NOT implemented in Phase 1)
  │
  └─ fi_performance / fi_order_attribution ──► learning
```

v1 publishing (`PostPublisher` → ChatPion → Graph) is untouched and remains the
only path that can post.

## 2. Migrations / tables

Two additive migrations, both with a working `down()`. No drops, renames or
data rewrites.

`2026_10_09_000001` — fi_product_profiles, fi_facts, fi_research_sources,
fi_marketing_memory, fi_creatives, fi_ai_usage, fi_profit_guard_log,
fi_performance, fi_order_attribution.

`2026_10_09_000002` — fi_experiments, fi_experiment_arms,
fi_creative_components, fi_creative_component_links, fi_events,
fi_product_visual_evidence, fi_visual_patterns, fi_product_visual_profiles.

**Verified: 17/17 present on the fujiba tenant.**

## 3. Product analysis + caching

`fujiba:intel:photo-audit --limit=120 --report`

```
run 1   analysed=120  cache_hits=0    reshoot_flagged=55
run 2   analysed=0    cache_hits=120  reshoot_flagged=0
AI cost ¥0
```

Cache key is `image_sha256 + metadata_hash + analysis_version`. The second run
did zero work, which is the requirement.

## 4. Research storage + reuse

`fi_research_sources` holds URL, publisher, retrieval time, our own-words
analysis and confidence, keyed by topic. Patterns are researched once and reused
across every product sharing them; TTL default 180 days.

`CreativeResearchService::storeFinding()` **rejects** payloads longer than 200
characters per field or containing linked/embedded media — copied advertising
cannot enter the store even by accident. Verified by tests L2 and L3.

## 5. Fabric gate — checks, thresholds, results

| Check | Threshold | Implementation |
|---|---|---|
| Colour | CIEDE2000 mean ≤ 2.0, p95 ≤ 5.0 | full ΔE00, not CIE76 |
| Structure | SSIM ≥ 0.95 (luma, in mask) | gaussian-weighted |
| Pattern | ORB+RANSAC inliers ≥ 0.6 | OpenCV 4.10 |
| Pattern | Canny edge diff ≤ 0.15 | |
| Coverage | mask area ≥ planned | |

`bin/intel/test_fabric_integrity.py` — **7/7**:

```
honest composite → compositor → gate            PASS
altered provider → compositor → gate            PASS   (fabric restored)
altered provider → NO compositor → gate         REJECT (ΔE mean 48.7)
recoloured fabric                               REJECT (colour)
inpainted motif                                 REJECT
declared shadow excluded from ΔE                PASS   (ΔE mean 0.004)
undeclared shadow                               REJECT
```

The last case was added beyond the spec: without it the shadow exclusion would
be a loophole for silently darkening fabric.

**No threshold was ever relaxed to make a test pass.** The shadow fixture first
failed at p95 5.025 vs 5.0; the cause was the fixture declaring a smaller shadow
region than the blur actually darkened. The fixture was fixed.

The existing `bin/v2/gate.py` (another session's file, ΔE 3.0 / SSIM 0.80,
CIE76) is deliberately **untouched**; the stricter values live in
`config/fujiba_intel.php` and in the new gate.

## 6. Provider interfaces

`VisionProvider`, `LLMProvider`, `ResearchProvider`, `ImageGenerationProvider`,
`VideoGenerationProvider`, `SpeechProvider`, `MusicProvider`,
`WebVisualSearchProvider` — one file per class, all null in Phase 1.

Image and video generation signatures **require** a fabric mask and expose no
path to generating fabric. Provider output is untrusted regardless:
`MaliciousMockImageProvider` returns altered fabric on purpose, and the
compositor discards it.

## 7. Cost control

`CostGuard` checks the budget *before* a call and logs after. Default
`FUJIBA_AI_MONTHLY_BUDGET_JPY=0`, so every priced operation is refused and the
refusal is itself logged. There is no method to raise the budget.

**Total AI spend in Phase 1: ¥0.** All measurement is local (cv2/numpy).

## 8. Performance / attribution

`fi_performance` (reach, impressions, video views, watch time, engagement,
clicks, product views, add-to-cart, orders, revenue, profit, source) and
`fi_order_attribution` (order → creative, method ref/utm/coupon/time-window,
confidence). `fi_creatives.tracking_ref` carries the per-creative
`m.me/<page>?ref=fi_<id>` and UTM.

Importers are **not built** — see §10.

## 9. Safety: what blocks a bad asset

1. Provider output untrusted; fabric pixels discarded by the compositor.
2. Fabric Integrity Gate — any failing check ⇒ rejected, unpublishable.
3. An undecidable pattern check **fails** rather than passes.
4. ProfitGuard blocks out-of-stock, negative EV, low ROI, over-budget, duplicate.
5. CostGuard throws rather than silently skipping.
6. Nothing in this layer can write to `autoposter_queue`; publish is not implemented.
7. Marketing memory cannot be created as `confirmed_learning`.
8. Experiments refuse a winner below threshold, and likes never decide one.

## 10. Remaining work

| # | Item | Status | Needs | Est. cost |
|---|---|---|---|---|
| 1 | Open-web visual search (O/P/Z) | `capability_limited` | A visual-search API (e.g. SerpAPI/Bing Visual). Scraping is forbidden. | ~¥3-8k/mo |
| 2 | Watermark / packaging detection | `capability_limited` | Vision model (`ANTHROPIC_API_KEY`) | ~¥1-3k/mo |
| 3 | AI captions | `capability_limited` | `ANTHROPIC_API_KEY` — absent, so v1 captions are template-only | ~¥1-2k/mo |
| 4 | Meta insights importer | not built | Token already has `read_insights` — **no new permission needed** | ¥0 |
| 5 | Bagisto order → creative attribution | not built | ref/UTM plumbing into checkout | ¥0 |
| 6 | Creative planner (E) | not built | depends on 4 + 5 for real scoring | ¥0 |
| 7 | Owner-review CLI for inferences | not built | — | ¥0 |
| 8 | Event date research | 11 of 14 unresearched | owner or research provider | ¥0 |

Items 4-7 need no external service and are the natural next phase.

## 11. Git

Branch `feature/intelligence-agent`. Commits by this session:

```
2c9b86e  foundation — config + 9 fi_ tables
b247092  fabric compositor + CIEDE2000 gate + test #9
0580aa7  marketing memory (H) + profit guard (N)
46ed667  photography intelligence (M) + explainability
fb3f3e9  experiments, creative DNA, events, competitor research
faac02b  visual fabric matching, providers, cost guard
```

`5b59f92` (order-status Messenger matrix) is **another session's** commit on the
same branch; it touches no Intel or `fi_` file.

Left uncommitted on purpose: the ~40 pre-existing AutoPoster entries (admin UI
09-29, collections 10-03, Instagram 10-04/07, knowledge files 10-07) and the
two-line command registration in `AutoPosterServiceProvider.php`, whose other
diffs belong to that older work. Every commit used an explicit pathspec — the
git index holds ~145 unrelated pre-staged files, so a bare `git commit` would
sweep them in.

## 12-20. Addendum sections

**12. Marketing memory.** Four kinds; AI ideas enter only as `hypothesis`.
Promotion needs ≥2 experiments, ≥30 clicks/arm, ≥5 attributed orders, win
probability ≥0.9 — all configurable, never auto-raised. Contradicting evidence
demotes. 180-day TTL.

**13. Experiments.** One variable each; design recorded (organic cannot be
randomised). Winner by profit > revenue > orders > clicks; likes excluded.
Beta-binomial win probability, 20k samples, fixed seed.
*A real bug was found and fixed here:* money metrics are not binomial — clamping
yen into a success count saturated both arms at n/n and scored a 4.5× winner at
p=0.4996. Money is now modelled as conversion rate × measured value per order;
the same fixture gives p=0.9991.

**14. Creative DNA.** 13 component types stored apart from assets. No exact
combination within 30 days; no component above 40% of recent use; 70/30
exploit/explore. Attribution is full-credit-per-component — this over-credits
passengers, so `uses` is always reported beside any rate. Fabric is never a
component (`source_fabric:<product_id>`).

**15. Events.** 14 seeded for 2026 across JP/MM/NP; **11 are variable/lunar and
deliberately have no date**, confidence 0, and return a zero planner boost until
researched. Owner checklist via `unresearched(year)`.

**16. Public creative research.** Analysis and principles only; verbatim
captions and media rejected at the API boundary. A principle stays
`observed_visual_pattern` until OUR OWN creatives produce ≥5 attributed orders.
No scraping, login, paywall or rate-limit bypass.

**17. Photography intelligence.** Measured locally. 120 audited, 55 flagged.
Top: **KIMONO-2561 — in stock 15, 48 sales/12mo, source only 800×800.** Lists at
`docs/intelligence/reshoot-list.{md,csv}`.

**18. Profit Guard.** `EV = reach × ctr × cvr × aov × margin − cost`. Blocks on
negative EV, ROI < 3.0, out of stock, over budget, duplicate concept.
Conservative priors flag `low_data`. Every decision logged.

**19. Self-improvement limits.** Learning may update marketing memory, component
stats, experiment results and recommendation weights — nothing else. No
self-modification of code, thresholds, budgets or safety rules.

**20. Explainability.** `fujiba:intel:explain {creative_id}` prints product,
audience, language, story, components, CTA, time, the `fi_facts` and memory rows
used, the Profit Guard decision with inputs, and risk/confidence. Unrecorded
fields print "not recorded". A sample run showed the motif labelled
`ai_inference` (0.72) and the gift framing an unproven `hypothesis` (n=0).

---

## Test totals (all executed)

| Suite | Result |
|---|---|
| Fabric integrity (#9) | 7/7 |
| Fabric matching (O/P) | 9/9 |
| Marketing memory + Profit Guard (H, N) | 14/14 |
| Experiments, DNA, events, research (I, J, K, L) | 21/21 |
| Visual evidence, providers, cost guard | 15/15 |
| **Total** | **66/66** |

## v1 isolation — verified after every commit

```
published 73   scheduled 60   failed 0   Instagram 22
scheduler: 3 autoposter entries intact
```
