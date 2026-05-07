<x-admin::layouts>
    <x-slot:title>
        {{ __('reorder::app.page.title') }}
    </x-slot>

    @push('styles')
        <style>
            /* Desktop: flex layout w/ draggable splitter between grid + clip.
               --reorder-clip-w is updated by JS as the user drags;
               persisted to localStorage. Default 38% of the container. */
            .reorder-grid {
                display: flex; gap: 0;
                /* Constrain to viewport height so children can independently
                   scroll. Each pane (grid/clipboard) gets its own scrollbar
                   instead of riding the page scroll — easier to navigate
                   long product lists while reviewing the clipboard. */
                height: calc(100vh - 160px);
                --reorder-clip-w: 420px;
            }
            .reorder-grid > .rg-grid {
                flex: 1 1 auto; min-width: 0; padding-right: 8px;
                display: flex; flex-direction: column;
                overflow: hidden; /* let .rg-grid-scroll own the scroll */
            }
            /* Toolbar above the grid (search + history) stays pinned;
               the table scrolls inside .rg-grid-scroll. */
            .rg-grid-scroll {
                flex: 1 1 auto; overflow-y: auto; overflow-x: auto;
                border: 1px solid #e5e7eb; border-radius: 6px; background: #fff;
            }
            .rg-grid-scroll table { margin: 0; border: 0; border-radius: 0; }
            .rg-grid-scroll thead th {
                position: sticky; top: 0; z-index: 2;
                /* Keep solid bg so rows can't bleed through under the header. */
                background: #f8fafc;
            }
            .reorder-grid > .rg-splitter {
                flex: 0 0 6px; cursor: col-resize; background: transparent;
                position: relative;
            }
            .reorder-grid > .rg-splitter::after {
                content: ''; position: absolute; left: 2px; top: 0;
                width: 2px; height: 100%; background: #e5e7eb;
                transition: background .15s;
            }
            .reorder-grid > .rg-splitter:hover::after,
            .reorder-grid > .rg-splitter.dragging::after { background: #3b82f6; }
            .reorder-grid > .rg-clip {
                flex: 0 0 var(--reorder-clip-w); min-width: 280px; padding-left: 8px;
                display: flex; flex-direction: column;
                overflow: hidden;
            }
            /* Clipboard internals: the .reorder-clip card fills the pane,
               and its scrollable middle section holds the items table. */
            .reorder-grid > .rg-clip .reorder-clip {
                display: flex; flex-direction: column; height: 100%;
                margin: 0;
            }
            .rg-clip-scroll {
                flex: 1 1 auto; overflow-y: auto; min-height: 0;
                /* faint top/bottom borders so user sees scroll boundary */
                border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;
            }
            .rg-clip-scroll table thead th {
                position: sticky; top: 0; z-index: 2; background: #f8fafc;
            }

            /* Mobile: only the reorder grid renders; the clipboard collapses
               to a sticky-bottom mini bar. The full clipboard panel toggles
               open as a bottom sheet via #rg-clip.rg-clip-open. The desktop
               nested-scroll / fixed-height rules above are reset here so the
               page scrolls naturally. */
            @media (max-width: 1023px) {
                .reorder-grid { display: block; height: auto; }
                .reorder-grid > .rg-grid { overflow: visible; display: block; }
                .rg-grid-scroll { overflow: visible; max-height: none; border: 0; }
                .reorder-grid > .rg-splitter { display: none; }
                .reorder-grid > .rg-clip {
                    position: fixed; left: 0; right: 0; bottom: 0;
                    z-index: 60; background: #fff; padding: 0;
                    transform: translateY(calc(100% - 56px));
                    transition: transform .25s ease;
                    border-top: 2px solid #2563eb;
                    box-shadow: 0 -8px 24px rgba(0,0,0,.10);
                    max-height: 80vh; overflow-y: auto;
                    display: block;
                }
                .reorder-grid > .rg-clip.rg-clip-open { transform: translateY(0); }
                .reorder-grid > .rg-clip .reorder-clip { padding: 12px; height: auto; display: block; }
                .rg-clip-scroll { overflow: visible; }
                .rg-clip-handle { display: flex !important; }
            }
            .rg-clip-handle {
                display: none;
                cursor: pointer; padding: 12px 16px;
                background: #2563eb; color: #fff; font-weight: 600;
                font-size: 14px; align-items: center; justify-content: space-between;
                position: sticky; top: 0; z-index: 1;
            }

            .reorder-table th, .reorder-table td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
            .reorder-table thead th { background: #f8fafc; font-weight: 600; text-align: left; }

            /* Bigger images + zoom button */
            .reorder-img { width: 64px; height: 64px; object-fit: cover; border-radius: 4px; cursor: zoom-in; background: #f3f4f6; }
            .reorder-clip-img { width: 56px; height: 56px; object-fit: cover; border-radius: 4px; background: #f3f4f6; cursor: zoom-in; }
            .reorder-img-wrap { position: relative; display: inline-block; }
            .reorder-img-zoom {
                position: absolute; bottom: 2px; right: 2px;
                width: 18px; height: 18px; border-radius: 50%;
                background: rgba(0,0,0,.55); color: #fff; font-size: 11px;
                line-height: 18px; text-align: center; cursor: zoom-in;
                opacity: 0; transition: opacity .15s;
            }
            .reorder-img-wrap:hover .reorder-img-zoom { opacity: 1; }

            .reorder-chip { font-size: 11px; padding: 2px 6px; border-radius: 999px; background: #eef2ff; color: #3730a3; }
            /* @deprecated 2026-04-27 — Wish-qty modal removed (direct insert flow).
               No DOM elements use these rules. Scheduled for removal after 2026-05-11. */
            .reorder-modal { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; z-index: 999; }
            .reorder-modal .panel { background: #fff; padding: 1.25rem; border-radius: 8px; width: 360px; }
            .reorder-clip { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; }
            @media (min-width: 1024px) {
                .reorder-clip { position: sticky; top: 16px; }
            }
            .zoom-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.85); display: flex; align-items: center; justify-content: center; z-index: 1000; }
            .zoom-overlay img { max-width: 90vw; max-height: 90vh; }

            .reorder-table th[data-sort] { cursor: pointer; user-select: none; }
            .reorder-table th[data-sort]:hover { background: #eef2ff; }
            .reorder-table .sort-arr { color: #2563eb; font-size: 10px; }

            /* Original-name subtext: subdued, italic. Empty when null. */
            .reorder-orig-name { font-size: 11px; color: #6b7280; font-style: italic; }

            /* Layout swap (desktop only): when the user clicks the swap
               button, the root gets .rg-swapped and we use flex `order` to
               flip the panes. The splitter sits between them in either
               configuration. Mobile is unaffected (clip is fixed-position).
               Padding flips so the active gutter remains between the two
               panes, not on the outside edge. */
            @media (min-width: 1024px) {
                #rg-root.rg-swapped > .rg-clip     { order: 1; padding-left: 0; padding-right: 8px; }
                #rg-root.rg-swapped > .rg-splitter { order: 2; }
                #rg-root.rg-swapped > .rg-grid     { order: 3; padding-right: 0; padding-left: 8px; }
            }

            /* ─── Bigger, friendlier checkboxes (desktop + mobile) ───
               Default browser checkbox is ~13px; bumping to 22px gives a
               solid tap target and is still readable on a desk. accent-color
               recolors the native check on supporting browsers without
               requiring a custom-styled control. */
            .reorder-table input[type="checkbox"],
            #reorder-clip input[type="checkbox"],
            #reorder-clip-allhead,
            #reorder-all,
            #reorder-clip-all {
                width: 22px;
                height: 22px;
                cursor: pointer;
                accent-color: #2563eb;
                vertical-align: middle;
            }

            /* ─── Mobile row layout: image → above, name/stock/etc → below ───
               On screens <768px, turn each grid <tr> into a CSS grid: image
               cell occupies the top "row" full-width, the rest of the data
               cells flow as wrapped chips below it. Same DOM, no JS — purely
               a presentation layer change so server payload + sort logic stay
               unchanged. */
            @media (max-width: 767px) {
                #reorder-grid-table thead { display: none; }
                #reorder-grid-table,
                #reorder-grid-table tbody,
                #reorder-grid-table tr,
                #reorder-grid-table td { display: block; width: 100%; box-sizing: border-box; }
                #reorder-grid-table tr {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 4px 8px;
                    padding: 10px;
                    border-bottom: 1px solid #e5e7eb;
                    background: #fff;
                }
                #reorder-grid-table td {
                    border-bottom: none;
                    padding: 0;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 13px;
                }
                /* The image cell becomes a centered banner above the rest. */
                #reorder-grid-table td:nth-child(2) {
                    justify-content: center;
                }
                #reorder-grid-table .reorder-img {
                    width: 96px;
                    height: 96px;
                }
                /* Prefix data-label so users know what each line is. */
                #reorder-grid-table td[data-label]::before {
                    content: attr(data-label) ":";
                    color: #6b7280;
                    font-size: 11px;
                    font-weight: 600;
                    min-width: 56px;
                }
                /* Checkbox cell: top-left corner, no prefix. */
                #reorder-grid-table td:first-child { justify-content: flex-start; }
            }
        </style>
    @endpush

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="py-1 text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
            {{ __('reorder::app.page.title') }}
            <button type="button" id="rg-swap"
                    class="hidden lg:inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700"
                    title="Swap reorder grid and clipboard left/right">
                <span id="rg-swap-icon">⇄</span>
                <span class="hidden sm:inline">Swap</span>
            </button>
        </p>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            <label class="flex items-center gap-1 text-gray-600 dark:text-gray-300">
                {{ __('reorder::app.page.channel') }}:
                <select id="reorder-channel" class="px-2 py-1 border rounded text-sm">
                    @foreach ($channels as $ch)
                        <option value="{{ $ch['code'] }}" @selected($ch['code'] === $channel->code)>
                            {{ $ch['name'] }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="flex items-center gap-1 text-gray-600 dark:text-gray-300">
                Source:
                <select id="reorder-source" class="px-2 py-1 border rounded text-sm">
                    <option value="">All</option>
                    @foreach ($sourceOptions as $opt)
                        <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                    @endforeach
                </select>
            </label>

            <span class="text-gray-500 dark:text-gray-400">
                {{ __('reorder::app.page.lead-time') }}: {{ $leadTimeDays }}d
                &middot; {{ __('reorder::app.page.window') }}: {{ $salesWindowDays }}d
            </span>
        </div>
    </div>

    <div id="reorder-app" v-pre class="mt-4"
         data-channel-id="{{ $channelId }}"
         data-channel-code="{{ $channel->code }}"
         data-products-url="{{ route('admin.inventory.reorder.products') }}"
         data-store-url="{{ route('admin.inventory.reorder.store') }}"
         data-batches-url="{{ route('admin.inventory.reorder.batches.index') }}"
         data-batches-store-url="{{ route('admin.inventory.reorder.batches.store') }}"
         data-labels-url="{{ route('admin.inventory.reorder.labels') }}"
         data-arrive-url="{{ url('admin/inventory/reorder') }}"
         data-preselect="{{ json_encode($preselectIds ?? []) }}"
         data-csrf="{{ csrf_token() }}">
        @include('reorder::admin.partials.app')
    </div>

    @pushOnce('scripts')
        @include('reorder::admin.partials.script')
    @endPushOnce
</x-admin::layouts>
