# Outstanding TODOs in Bagisto-PWA source

Generated 2026-04-26 after the 14-bug screenshot review.

Scope: code we own — `packages/Fujiba/*`, the writable PWA source at
`storage/pwa-build/source/src/`, and any vendor files we forked
(`vendor/bagisto/pwa/src`, `vendor/bagisto/rest-api/src/Http/Resources/V1/Shop`).
Upstream Webkul / Bagisto / vendored package TODOs are intentionally excluded;
they're not ours to act on.

## Code TODOs

| File | Line | Description |
|------|------|-------------|
| [src/Resources/assets/js/components/checkout/onepage/index.vue](source/src/Resources/assets/js/components/checkout/onepage/index.vue#L24) | 24 | Reorder one-page checkout so delivery/shipping appears before billing, with billing collapsed and a "same as delivery" toggle. |
| [src/Resources/assets/js/components/checkout/onepage/index.vue](source/src/Resources/assets/js/components/checkout/onepage/index.vue#L29) | 29 | Replace the panel-based one-page flow with the same chat-bubble thread used on mobile. Most of this is already implemented in [chat/index.vue](source/src/Resources/assets/js/components/checkout/chat/index.vue) — only desktop wiring remains. |

Both TODOs sit in the **desktop one-page checkout** view. Mobile/PWA already
uses `chat/index.vue` (auto-selected for `window.innerWidth <= 1023`), so the
above only matters when we re-target tablet/desktop users. Low priority.

## Doc TODOs

| File | Description |
|------|-------------|
| [src/Resources/assets/js/components/checkout/onepage/TODO_time_slot.md](source/src/Resources/assets/js/components/checkout/onepage/TODO_time_slot.md) | Original spec for delivery time-slot validation UI. **Superseded** — the chat checkout now renders slots via `/sagawa/delivery-slots`. Doc should be deleted; kept for now as historical reference. |

## Notes

- `TODO_time_slot.md` is **stale**. The functionality it describes ships in
  [chat/index.vue](source/src/Resources/assets/js/components/checkout/chat/index.vue)
  via `fetchSlots()` / `submitSlot()`. Safe to delete after final QA.
- No `FIXME`, `XXX`, or `HACK` markers in our code.
- Build is clean (zero webpack warnings; only Sass `@import` deprecation
  notices from upstream Bagisto-PWA core SCSS — not actionable here).
