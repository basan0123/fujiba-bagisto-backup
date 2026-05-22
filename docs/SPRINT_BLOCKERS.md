# Sprint Blockers — 2026-05-18 onwards

Items I deferred because they exceeded the time budget or needed a
decision I shouldn't make unsupervised.

## A2 — Catalog name JP fallback (partial)

**Why deferred:** The product `name` returned by `getOutletProducts`
is already the active-locale string from `product_flat`. For mmfood
products that only have a Burmese translation, the API returns the
Burmese string and there's nothing to fall back to client-side.

**True fix:** add a `translations: [{locale, name}]` field to
`OutletProductResult` so the client can pick `ja → en → first`. Needs
a small additive schema change + resolver method; deferred until
Phase B since Phase A is scoped to frontend-only.

**Frontend shipped instead:** `pickName(p)` utility used at every
render site — returns `name` if non-empty, else `sku`, else `(no
name)`. Doesn't fix the Burmese-vs-Japanese case but prevents blank
cells when a product has no JA translation at all (rare today, will
matter as we accept more imported catalogs).
