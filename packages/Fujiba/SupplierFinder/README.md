# Fujiba Supplier Finder

Finds other Japanese sources for fabric that matches (or looks like) our kimono
fabrics. It crawls shops, decodes their product codes to find the real
maker/wholesaler, and scores each item against our kimono catalog
(channel 1 / `default`, kimono tenant only).

Admin-only data. Supplier codes are kept out of every customer-facing field
(see the Naka-code secrecy rule).

## Pipeline

```
supplier-finder:crawl   → sf_crawled_products   (+ raw HTML/JSON + private image copy per item)
supplier-finder:match   → Grouper: codes → sf_source_fabrics (same base code in 2+ shops = one fabric)
                          Matcher: each item vs our products → sf_matches (exact | similar)
supplier-finder:report  → families, cross-shop groups, EXACT list, top SIMILAR
```

Everything runs inside the kimono tenant (`TenantContext::enter()`), so no `tenants:run` is needed.

## Commands

```bash
php artisan supplier-finder:seed                      # insert missing shops/rules/suppliers from config (never overwrites)
php artisan supplier-finder:crawl                     # all enabled shops, inline
php artisan supplier-finder:crawl yamayo yamatomi -v  # chosen shops, print each item
php artisan supplier-finder:crawl yamayo --limit=5    # test run (never marks items as gone)
php artisan supplier-finder:crawl --queue             # one CrawlShopJob per shop on the redis queue
php artisan supplier-finder:match                     # regroup + rescore (takes seconds; reference image features are cached)
php artisan supplier-finder:report --similar=30 --json=/path/report.json
```

First install on a tenant:

```bash
php artisan tenants:migrate --tenants=1bd1181f-fcd5-4238-a12d-a207d6ea4006 \
  --path=packages/Fujiba/SupplierFinder/database/migrations/tenant --realpath
php artisan supplier-finder:seed
```

## Politeness rules (enforced in `Services/PoliteHttp.php`)

- robots.txt is read per host and its `Disallow` rules are applied, for the `*` group or our own token. A robots.txt that answers 401/403 is treated as "disallow everything".
- At most 1 request every 3 s per domain (`http.min_interval`).
- User-Agent: `FujibaSupplierFinder/1.0 (+https://kimono.fujiba.co.jp; support-f@fujiba.shop)` (`SUPPLIER_FINDER_UA`).
- On 429/403 we wait `max(Retry-After, 120 s)` once. A second 429/403 marks the shop `blocked:429` (or `blocked:403`) in `sf_source_shops.last_status`, and the crawl skips it. We never change the User-Agent or use other tricks to get around a block.
- Public pages only, with no login. Images go to `storage/app/supplier-finder/images/` and are used **only** for matching: never published, never hotlinked.
- Snapshots are written to `storage/app/supplier-finder/raw/{shop}/{Ymd}/{id}.html|.json`, so you can diff two crawl dates.

## Add a shop adapter

1. Create `src/Adapters/{Name}Adapter.php` extending `ShopAdapter` and implement
   `crawl(SourceShop $shop): \Generator`. Yield one array per product: `external_id, url, title, codes[], series, material, width_cm, colorways[], price, price_unit (10cm|50cm|1m|tan), tan_m, min_order, stock, image_urls[], raw, raw_ext`.
   Fetch only through `$this->http->get()`, so the throttle and robots rules always apply.
   The helpers `codes()`, `material()`, `width()`, `series()` and `norm()` are already in the base class.
2. In `sf_source_shops`, set `adapter` to the class basename, fill in `start_urls` and `strip_prefixes` (the shop's own code prefixes, e.g. `["F1-"]`), set `price_type` (retail/wholesale) and `enabled = 1`.
   Also add the shop to `Config/supplier-finder.php` → `shops`, so a fresh install seeds it.
3. Test with `supplier-finder:crawl {code} --limit=5 -v`, then run the full crawl and `supplier-finder:match`.

Existing adapters: `YamayoAdapter` (Color Me Shop, `Colorme` JSON), `YamatomiAdapter` (EC-CUBE listing + detail),
`TomatoAdapter` (Shopify `products.json`; it finds the 和調/和柄 collections itself when `start_urls` is empty).

## Add a code rule

Each row in `sf_code_rules` is a PHP regex. It runs against the **normalised** code: half-width, upper-case, with the shop prefix stripped.
The named group `(?<base>…)` becomes the grouping key `family:base`. Rules run in `priority` order (lower first), and the first match wins.

```sql
INSERT INTO sf_code_rules (name, pattern, family, series, supplier_id, confidence, priority, enabled, created_at, updated_at)
VALUES ('夢色百景', '/^(?<base>(?:45000|75500)-\\d+)/', 'yumeiro', '夢色百景', NULL, 0.6, 40, 1, NOW(), NOW());
```

After that, run `supplier-finder:match`; no re-crawl is needed, because grouping re-reads codes from the stored titles.
Codes no rule recognises still get grouped (`unknown:<code minus colour suffix>`), so a family two shops share shows up as a `cross_shop` group with "maker unknown". When you learn the maker (for example from the selvage 耳 on a sample), set `supplier_id` and `decode_method = 'manual'` on the `sf_source_fabrics` row; a regroup keeps manual rows.

Confidence: rule = the rule's own `confidence` (+0.1 when 2+ shops carry it); cross-shop without a rule = 0.5; single shop with no rule = 0.2; manual = whatever the admin sets.

## Matching

| type    | when |
|---------|------|
| EXACT   | Decoded design key equals one of ours (`naka:1750-67` ≡ our `1750_67_A`; Naka's chirimen `N` is ignored), **or** the pHash distance is ≤ 10/64 |
| SIMILAR | score ≥ 0.45, where score = 0.55·image + 0.25·text + 0.20·material/width |

- image = 0.5·LAB palette similarity + 0.35·texture (edge density, saturation, gold-pixel share) + 0.15·pHash. Pure GD; no extra dependencies.
- text = 0.6 when the shop title has any 和柄 motif/style word, plus up to 0.4 for words shared with our design. Our alias names (FV-67-A) have no motifs, so each EXACT match gives our design the motif words from the shop's title.
- Reference image features are cached in `storage/app/supplier-finder/ref-features.json` (keyed by path + mtime).

Price: `price_per_m` = the listed price converted to ¥/m (10cm × 10, 50cm × 2, tan ÷ metres).
Wholesale estimate = retail × `wholesale_estimate_factor` (0.6), and is always shown as an estimate.
