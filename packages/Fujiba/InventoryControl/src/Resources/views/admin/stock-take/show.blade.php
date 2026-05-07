<x-admin::layouts>
    <x-slot:title>{{ $session->name }} — Stock Take</x-slot:title>

    @push('meta')
    <script>
    (function(){
      var m=document.querySelector('meta[name=viewport]');
      if(m){m.setAttribute('content','width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no');}
      function tag(){if(document.body)document.body.classList.add('st-fullbleed');}
      if(document.body){tag();}else{document.addEventListener('DOMContentLoaded',tag);}
    })();
    </script>
    @endpush

    @push('styles')
    <style>
    /* ── Sort buttons (pos-view style) ── */
    .st-sort-btn{display:inline-flex;align-items:center;gap:4px;background:#fff;color:#374151;transition:all .15s;}
    .st-sort-btn:hover{background:#f3f4f6;color:#1f2937;}
    .st-sort-btn.is-active{background:#2563eb;color:#fff;border-color:#1d4ed8;}
    .dark .st-sort-btn{background:#1f2937;color:#d1d5db;}
    .dark .st-sort-btn:hover{background:#111827;color:#fff;}
    .dark .st-sort-btn.is-active{background:#1d4ed8;border-color:#1e40af;color:#fff;}

    /* ── Row layout ── */
    .st-row{display:flex;align-items:center;padding:10px 8px;background:#fff;gap:8px;border-bottom:1px solid #f3f4f6;}
    .dark .st-row{background:#030712;border-bottom-color:#1f2937;}
    .st-row:hover{background:#eff6ff;}
    .dark .st-row:hover{background:rgba(30,58,138,0.15);}

    .st-thumb{width:48px;height:48px;flex-shrink:0;border-radius:8px;overflow:hidden;background:#f3f4f6;}
    .st-thumb img{width:100%;height:100%;object-fit:cover;}
    .st-thumb-empty{display:flex;align-items:center;justify-content:center;height:100%;color:#9ca3af;font-size:11px;}

    .st-body{flex:1;min-width:0;display:flex;flex-direction:column;justify-content:center;padding:0 4px;}
    .st-name{font-size:13px;font-weight:600;color:#1f2937;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word;}
    .dark .st-name{color:#f9fafb;}
    .st-variant{font-size:11px;color:#1d4ed8;}
    .st-sku{font-size:11px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px;}

    .st-sys{display:flex;flex-direction:column;align-items:flex-end;flex-shrink:0;min-width:36px;text-align:right;line-height:1.1;}
    .st-sys .lbl{font-size:9px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;}
    .st-sys .val{font-size:18px;font-weight:700;color:#1f2937;}
    .dark .st-sys .val{color:#f9fafb;}

    .st-cnt-input{
      width:64px;padding:7px 4px;flex-shrink:0;text-align:center;
      border:2px solid #d1d5db;border-radius:8px;
      font-family:ui-monospace,SFMono-Regular,monospace;
      font-weight:700;background:#fff;
    }
    .dark .st-cnt-input{background:#1f2937;color:#f9fafb;border-color:#4b5563;}
    .st-cnt-input:focus{border-color:#2563eb;outline:none;}
    .st-cnt-input.is-saving{border-color:#60a5fa;}
    .st-cnt-input.is-saved{border-color:#16a34a;}
    .st-cnt-input.is-error{border-color:#ef4444;}

    .st-diff{flex-shrink:0;min-width:36px;text-align:right;font-size:13px;font-weight:700;}

    .st-chk-box{
      width:22px;height:22px;flex-shrink:0;border-radius:4px;border:2px solid #d1d5db;
      display:flex;align-items:center;justify-content:center;cursor:pointer;background:transparent;
    }
    .st-chk-box.is-checked{background:#2563eb;border-color:#2563eb;}
    .st-chk-box.is-checked svg{display:block;}
    .st-chk-box svg{display:none;}

    .st-nobc{display:inline-block;padding:1px 4px;border-radius:4px;background:#fef3c7;color:#92400e;font-size:9px;font-weight:700;vertical-align:middle;margin-left:4px;}

    /* ── Chips ── */
    .st-chip{
      display:inline-flex;align-items:center;padding:5px 11px;
      border-radius:9999px;border:1px solid #d1d5db;background:#fff;
      font-size:12px;font-weight:600;color:#374151;
      cursor:pointer;user-select:none;white-space:nowrap;
    }
    .st-chip.is-on{background:#fef3c7;border-color:#f59e0b;color:#92400e;}

    /* ── iOS zoom prevention ── */
    #st-search,#st-scan,.st-cnt-input,#st-channel,#st-stock-min{font-size:16px !important;}

    /* ── Edge-to-edge mobile, sidebar offset on desktop ── */
    body.st-fullbleed [class*="min-h-[calc(100vh-62px)]"]{padding-left:0 !important;padding-right:0 !important;}
    @media(min-width:1024px){
      body.st-fullbleed [class*="min-h-[calc(100vh-62px)]"]{padding-left:290px !important;padding-right:16px !important;}
    }

    /* ── Sticky topbar ── */
    body.st-fullbleed,
    body.st-fullbleed [class*="min-h-[calc(100vh-62px)]"],
    body.st-fullbleed .w-full.overflow-x-hidden{overflow:visible !important;}
    #st-topbar{position:sticky;top:0;z-index:30;background:#fff;}
    .dark #st-topbar{background:#030712;}

    /* ── Scan flash ── */
    @keyframes st-flash-ok{0%{background:#dcfce7;}100%{background:transparent;}}
    @keyframes st-flash-err{0%{background:#fee2e2;}100%{background:transparent;}}
    .st-flash-ok{animation:st-flash-ok .6s ease-out;}
    .st-flash-err{animation:st-flash-err .6s ease-out;}

    /* ── Bulk bar ── */
    #st-bulk-bar{
      position:fixed;left:0;right:0;bottom:0;z-index:9500;
      background:#f59e0b;color:#fff;border-top:4px solid #b45309;
      display:flex;align-items:center;justify-content:center;gap:12px;
      padding:12px 16px;box-shadow:0 -4px 20px rgba(0,0,0,.15);
    }
    </style>
    @endpush

    @php
        $isOpen = $session->status === 'open';

        $channelInfo = null;
        if ($session->channel_id) {
            $loc = app()->getLocale();
            $channelInfo = \DB::table('channels as c')
                ->leftJoin('channel_translations as ct', function ($j) use ($loc) {
                    $j->on('ct.channel_id', '=', 'c.id')->where('ct.locale', '=', $loc);
                })
                ->leftJoin('channel_translations as ct_any', 'ct_any.channel_id', '=', 'c.id')
                ->where('c.id', $session->channel_id)
                ->groupBy('c.id', 'c.code')
                ->first(['c.code', \DB::raw('COALESCE(MAX(ct.name), MAX(ct_any.name), c.code) as name')]);
        }

        $statusBadge = match ($session->status) {
            'open'      => 'bg-blue-100 text-blue-800',
            'applied'   => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-gray-200 text-gray-700',
            default     => 'bg-gray-100 text-gray-700',
        };

        // Build URLs relative to the request host so AJAX never crosses tenants.
        $baseUrl = url(config('app.admin_url') . '/catalog/stock-take/' . $session->id);
    @endphp

    {{-- ═══ HEADER ═══ --}}
    <div class="px-3 sm:px-4 pt-3">
        <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
                <p class="text-base sm:text-lg font-bold text-gray-800 dark:text-white truncate">{{ $session->name }}</p>
                <p class="text-xs text-gray-500 mt-0.5 truncate">
                    {{ $session->inventorySource->name ?? '—' }} ·
                    <span class="inline-block px-2 py-0.5 text-xs rounded {{ $statusBadge }}">{{ ucfirst($session->status) }}</span>
                    @if ($channelInfo)
                        <span class="inline-block ml-1 px-2 py-0.5 text-xs rounded bg-indigo-100 text-indigo-800">{{ $channelInfo->name }}</span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ $baseUrl }}/export" class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded">CSV</a>
                <a href="{{ route('admin.inventory_control.stock_take.index') }}" class="text-sm text-blue-600 hover:underline">←</a>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-2 p-2 text-xs bg-green-50 text-green-800 border border-green-200 rounded">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-2 p-2 text-xs bg-red-50 text-red-800 border border-red-200 rounded">{{ session('error') }}</div>
        @endif
    </div>

    {{-- ═══ TOPBAR (pos-view inspired) ═══ --}}
    @if ($isOpen)
    <div id="st-topbar" class="border-b border-gray-200 dark:border-gray-800 mt-2 shadow-sm">

        {{-- Scan input ── always-focused for hardware scanners --}}
        <div class="px-3 sm:px-4 pt-2">
            <div class="relative">
                <input type="text" id="st-scan" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                       placeholder="📷 Scan barcode or type SKU then press Enter"
                       class="w-full pl-3 pr-3 py-2.5 rounded-lg border-2 border-blue-500 dark:border-blue-400 bg-white dark:bg-gray-800 text-gray-900 dark:text-white outline-none focus:border-blue-700">
                <p id="st-scan-msg" class="text-[11px] mt-1 hidden"></p>
            </div>
        </div>

        {{-- Search --}}
        <div class="px-3 sm:px-4 pt-2 pb-2">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="st-search" placeholder="Search by name or SKU…"
                       autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                       class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-white outline-none focus:border-blue-500">
            </div>
        </div>

        {{-- Filter row: channel + stock-greater + no-barcode --}}
        <div class="flex items-center gap-2 px-3 sm:px-4 pb-2 flex-wrap">
            @if (! $session->channel_id)
                <select id="st-channel"
                        class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1.5 pr-7 text-sm font-medium text-gray-700 dark:text-white">
                    <option value="">All channels</option>
                </select>
            @endif

            <select id="st-stock-min"
                    class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1.5 pr-7 text-sm font-medium text-gray-700 dark:text-white"
                    title="Stock greater than">
                <option value="">Any stock</option>
                @for ($i = 0; $i <= 20; $i++)
                    <option value="{{ $i }}">Stock &gt; {{ $i }}</option>
                @endfor
            </select>

            <button type="button" id="st-nobarcode" class="st-chip">No barcode</button>

            <span class="ml-auto text-xs text-gray-500 whitespace-nowrap">
                <span id="st-count">…</span>
            </span>
        </div>

        {{-- Sort row --}}
        <div class="flex items-center gap-2 px-3 sm:px-4 pb-2 flex-wrap">
            <span class="text-xs text-gray-500 dark:text-gray-400">Sort:</span>
            <button type="button" class="st-sort-btn text-xs font-semibold px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600" data-col="name">Name</button>
            <button type="button" class="st-sort-btn text-xs font-semibold px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600" data-col="sys">Sys qty</button>
            <button type="button" class="st-sort-btn text-xs font-semibold px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600" data-col="cnt">Counted</button>
            <button type="button" class="st-sort-btn text-xs font-semibold px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600" data-col="diff">Diff</button>
        </div>
    </div>
    @endif

    {{-- ═══ SUMMARY ═══ --}}
    <div class="grid grid-cols-4 gap-2 px-3 sm:px-4 mt-3">
        <div class="bg-white dark:bg-gray-900 border rounded-lg p-2 sm:p-3 text-center">
            <p class="text-[9px] sm:text-[10px] uppercase text-gray-500 tracking-wider">Up</p>
            <p class="text-lg sm:text-xl font-bold text-green-600" id="st-sum-inc">{{ $summary['increased_count'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 border rounded-lg p-2 sm:p-3 text-center">
            <p class="text-[9px] sm:text-[10px] uppercase text-gray-500 tracking-wider">Down</p>
            <p class="text-lg sm:text-xl font-bold text-red-600" id="st-sum-dec">{{ $summary['decreased_count'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 border rounded-lg p-2 sm:p-3 text-center">
            <p class="text-[9px] sm:text-[10px] uppercase text-gray-500 tracking-wider">Same</p>
            <p class="text-lg sm:text-xl font-bold text-gray-700 dark:text-gray-200" id="st-sum-unc">{{ $summary['unchanged_count'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 border rounded-lg p-2 sm:p-3 text-center">
            <p class="text-[9px] sm:text-[10px] uppercase text-gray-500 tracking-wider">Δ</p>
            <p class="text-lg sm:text-xl font-bold text-blue-700" id="st-sum-delta">{{ ($summary['total_delta'] ?? 0) > 0 ? '+' : '' }}{{ $summary['total_delta'] ?? 0 }}</p>
        </div>
    </div>

    {{-- ═══ PRODUCT LIST ═══ --}}
    @if ($isOpen)
    <div class="px-3 sm:px-4 mt-3">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-24">
            <div id="st-list-loading" class="p-8 text-center text-sm text-gray-400">Loading products…</div>
            <div id="st-list" class="hidden"></div>
            <div id="st-list-empty" class="hidden p-8 text-center text-sm text-gray-500">No products match the current filter.</div>
            <div id="st-list-error" class="hidden p-8 text-center text-sm text-red-600 break-words"></div>
        </div>
    </div>
    @endif

    {{-- ═══ APPLY / CANCEL (above bulk bar when bulk-bar appears) ═══ --}}
    @if ($isOpen)
    <div class="flex gap-3 px-3 sm:px-4 pb-6">
        <form method="POST" action="{{ $baseUrl }}/cancel" class="flex-1"
              onsubmit="return confirm('Cancel this session?');">
            @csrf
            <button type="submit" class="w-full py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-semibold rounded-md">
                Cancel session
            </button>
        </form>
        <form method="POST" action="{{ $baseUrl }}/apply" class="flex-[2]" id="st-apply-form">
            @csrf
            <button type="submit" id="st-apply-btn"
                    class="w-full py-3 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white text-base font-bold rounded-md">
                Apply (<span id="st-apply-count">{{ $summary['total_count'] ?? 0 }}</span>)
            </button>
        </form>
    </div>
    @endif

    {{-- ═══ BULK BAR (appears when items are selected) ═══ --}}
    @if ($isOpen)
    <div id="st-bulk-bar" class="hidden flex-wrap">
        <span class="font-bold text-sm">
            <span id="st-bulk-count">0</span> selected
        </span>

        {{-- Select Action — Bagisto datagrid-style dropdown with nested submenus --}}
        <div class="relative">
            <div class="flex select-none">
                <button type="button" id="st-bulk-action-btn"
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 focus:ring-black dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400">
                    <span>Select Action</span>
                    <span class="icon-sort-down text-2xl"></span>
                </button>
            </div>

            {{-- Top-level menu --}}
            <ul id="st-bulk-action-menu"
                class="hidden absolute right-0 bottom-full mb-1 z-[9600] min-w-[220px] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-[0_5px_20px_rgba(0,0,0,0.15)] py-0">

                {{-- Set count (leaf action — no submenu) --}}
                <li>
                    <a class="whitespace-no-wrap flex gap-1.5 rounded-t px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950 cursor-pointer"
                       data-st-act="set-count">
                        <i class="icon-pencil text-xl"></i>
                        Set count
                    </a>
                </li>

                {{-- Assign channel (parent — submenu) --}}
                <li class="group/item relative overflow-visible">
                    <a class="whitespace-no-wrap flex justify-between gap-1.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950 cursor-pointer"
                       href="javascript:void(0);">
                        <i class="icon-add text-xl"></i>
                        <span>Assign channel</span>
                        <i class="icon-arrow-left rtl:icon-arrow-right -mt-px text-xl"></i>
                    </a>
                    <ul id="st-bulk-submenu-assign"
                        class="absolute top-0 z-10 hidden w-max min-w-[180px] rounded border bg-white shadow-[0_5px_20px_rgba(0,0,0,0.15)] group-hover/item:block dark:border-gray-800 dark:bg-gray-900 ltr:right-full rtl:left-full">
                        {{-- Populated from JS --}}
                    </ul>
                </li>

                {{-- Remove channel (parent — submenu) --}}
                <li class="group/item relative overflow-visible">
                    <a class="whitespace-no-wrap flex justify-between gap-1.5 rounded-b px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950 cursor-pointer"
                       href="javascript:void(0);">
                        <i class="icon-delete text-xl"></i>
                        <span>Remove channel</span>
                        <i class="icon-arrow-left rtl:icon-arrow-right -mt-px text-xl"></i>
                    </a>
                    <ul id="st-bulk-submenu-remove"
                        class="absolute top-0 z-10 hidden w-max min-w-[180px] rounded border bg-white shadow-[0_5px_20px_rgba(0,0,0,0.15)] group-hover/item:block dark:border-gray-800 dark:bg-gray-900 ltr:right-full rtl:left-full">
                        {{-- Populated from JS --}}
                    </ul>
                </li>
            </ul>
        </div>

        <button type="button" id="st-bulk-clear"
                class="bg-amber-700 text-white px-4 py-2 rounded hover:bg-amber-800 text-sm">
            Clear
        </button>
    </div>
    @endif

    {{-- ═══ JS ═══ --}}
    @if ($isOpen)
    @push('scripts')
    <script>
    (function () {
        'use strict';

        const SESSION_ID   = {{ (int) $session->id }};
        const CSRF         = "{{ csrf_token() }}";
        const BASE         = @json($baseUrl);
        const URL_PRODUCTS = BASE + '/products';
        const URL_SAVE     = BASE + '/items';
        const URL_SCAN     = BASE + '/scan';
        const URL_QUICK    = BASE + '/quick-increment';
        const URL_BULK_CH  = BASE + '/bulk-channel';
        const SESSION_CHANNEL_SCOPED = {{ $session->channel_id ? 'true' : 'false' }};
        const SAVE_DEBOUNCE_MS = 250;

        // ── State ─────────────────────────────────────────────────
        let allProducts = [];
        let filtered    = [];
        let rowByPid    = new Map();
        let selected    = new Set();
        let channelCode = SESSION_CHANNEL_SCOPED ? '' : (localStorage.getItem('st-ch-' + SESSION_ID) || '');
        let stockMin    = '';
        let noBarcode   = localStorage.getItem('st-nobc-' + SESSION_ID) === '1';
        let searchQ     = '';
        let sortCol     = null;
        let sortDir     = 'asc';

        const saveTimers   = new Map();
        const pendingSaves = new Map();
        const inflight     = new Set();

        // ── DOM ────────────────────────────────────────────────────
        const $ = (id) => document.getElementById(id);
        const elList    = $('st-list');
        const elLoading = $('st-list-loading');
        const elEmpty   = $('st-list-empty');
        const elError   = $('st-list-error');
        const elCount   = $('st-count');
        const elSearch  = $('st-search');
        const elScan    = $('st-scan');
        const elScanMsg = $('st-scan-msg');
        const elChannel = $('st-channel');
        const elStockMin= $('st-stock-min');
        const elNoBc    = $('st-nobarcode');
        const elApplyBtn= $('st-apply-btn');
        const elApplyCnt= $('st-apply-count');
        const elSumInc  = $('st-sum-inc');
        const elSumDec  = $('st-sum-dec');
        const elSumUnc  = $('st-sum-unc');
        const elSumDelta= $('st-sum-delta');
        const elBulkBar       = $('st-bulk-bar');
        const elBulkCnt       = $('st-bulk-count');
        const elBulkActBtn    = $('st-bulk-action-btn');
        const elBulkActMenu   = $('st-bulk-action-menu');
        const elBulkSubAssign = $('st-bulk-submenu-assign');
        const elBulkSubRemove = $('st-bulk-submenu-remove');

        // Channel data (populated by loadProducts)
        let channelsData = []; // [{id, code, name}]

        const esc = (s) => { const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML; };

        function diffMeta(diff) {
            if (diff === null || diff === undefined || isNaN(diff)) return { cls: 'st-diff text-gray-400', txt: '—' };
            if (diff > 0) return { cls: 'st-diff text-green-600', txt: '+' + diff };
            if (diff < 0) return { cls: 'st-diff text-red-600',   txt: String(diff) };
            return { cls: 'st-diff text-gray-500', txt: '0' };
        }

        // ── Toast (pos-view style) ─────────────────────────────────
        function toast(msg, type) {
            const t = document.createElement('div');
            t.style.cssText = 'position:fixed;bottom:80px;right:20px;z-index:99999;padding:10px 18px;border-radius:10px;color:#fff;font-size:14px;font-weight:500;background:' + (type === 'error' ? '#dc2626' : '#16a34a') + ';box-shadow:0 4px 14px rgba(0,0,0,.2);';
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 2500);
        }

        // ── Render ─────────────────────────────────────────────────
        function buildRow(p) {
            const div = document.createElement('div');
            div.className = 'st-row';
            div.dataset.pid = p.product_id;

            const photo = p.photo_url
                ? '<img src="' + esc(p.photo_url) + '" alt="" loading="lazy">'
                : '<div class="st-thumb-empty">N/A</div>';
            const variant = p.variant_label
                ? '<p class="st-variant">' + esc(p.variant_label) + '</p>'
                : '';
            const nobc = !p.has_barcode ? '<span class="st-nobc">NO BC</span>' : '';
            const counted = (p.counted_qty === null || p.counted_qty === undefined) ? '' : p.counted_qty;
            const dm = diffMeta(p.difference);
            const isSel = selected.has(String(p.product_id));
            const chkCls = 'st-chk-box' + (isSel ? ' is-checked' : '');

            div.innerHTML =
                '<div class="' + chkCls + '" data-st-chk>' +
                  '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' +
                '</div>' +
                '<div class="st-thumb">' + photo + '</div>' +
                '<div class="st-body">' +
                  '<p class="st-name">' + esc(p.name) + nobc + '</p>' +
                  variant +
                  '<p class="st-sku">' + esc(p.sku) + '</p>' +
                '</div>' +
                '<div class="st-sys"><span class="lbl">Sys</span><span class="val" data-st-sys>' + p.system_qty + '</span></div>' +
                '<input type="number" inputmode="numeric" min="0" data-st-input ' +
                  'class="st-cnt-input" value="' + counted + '" placeholder="0">' +
                '<span class="' + dm.cls + '" data-st-diff>' + dm.txt + '</span>';
            return div;
        }

        function render() {
            rowByPid.clear();
            elList.innerHTML = '';
            if (!filtered.length) {
                elList.classList.add('hidden');
                elEmpty.classList.remove('hidden');
                elCount.textContent = '0 / ' + allProducts.length;
                return;
            }
            elEmpty.classList.add('hidden');
            const frag = document.createDocumentFragment();
            for (const p of filtered) {
                const row = buildRow(p);
                rowByPid.set(p.product_id, row);
                frag.appendChild(row);
            }
            elList.appendChild(frag);
            elList.classList.remove('hidden');
            elCount.textContent = filtered.length + ' / ' + allProducts.length;
        }

        // ── Filter + sort ──────────────────────────────────────────
        function getRowVal(p) {
            if (sortCol === 'name') return (p.name || '').toLowerCase();
            if (sortCol === 'sys')  return p.system_qty || 0;
            if (sortCol === 'cnt')  return (p.counted_qty === null || p.counted_qty === undefined) ? -1 : p.counted_qty;
            if (sortCol === 'diff') return (p.difference === null || p.difference === undefined) ? -9999 : p.difference;
            return 0;
        }

        function applyFilter() {
            const q = searchQ.toLowerCase();
            const minStock = stockMin === '' ? null : parseInt(stockMin, 10);
            filtered = allProducts.filter((p) => {
                if (q) {
                    const hay = (p.name || '').toLowerCase()
                        + ' ' + (p.sku || '').toLowerCase()
                        + ' ' + (p.variant_label || '').toLowerCase();
                    if (hay.indexOf(q) === -1) return false;
                }
                if (channelCode && (p.channel_codes || []).indexOf(channelCode) === -1) return false;
                if (noBarcode && p.has_barcode) return false;
                if (minStock !== null && (p.system_qty || 0) <= minStock) return false;
                return true;
            });
            if (sortCol) {
                filtered.sort((a, b) => {
                    const av = getRowVal(a), bv = getRowVal(b);
                    if (av < bv) return sortDir === 'asc' ? -1 : 1;
                    if (av > bv) return sortDir === 'asc' ? 1 : -1;
                    return 0;
                });
            }
            render();
        }

        function paintNoBc() { elNoBc.classList.toggle('is-on', noBarcode); }

        function paintSummary(s) {
            if (!s) return;
            elSumInc.textContent = s.increased_count ?? 0;
            elSumDec.textContent = s.decreased_count ?? 0;
            elSumUnc.textContent = s.unchanged_count ?? 0;
            const d = s.total_delta ?? 0;
            elSumDelta.textContent = (d > 0 ? '+' : '') + d;
            if (elApplyCnt) elApplyCnt.textContent = s.total_count ?? 0;
        }

        function updateRowAfterSave(pid, item) {
            const row = rowByPid.get(pid);
            if (row) {
                const sysEl  = row.querySelector('[data-st-sys]');
                const diffEl = row.querySelector('[data-st-diff]');
                if (sysEl) sysEl.textContent = item.system_qty;
                const dm = diffMeta(item.difference);
                if (diffEl) { diffEl.className = dm.cls; diffEl.textContent = dm.txt; }
                row.classList.add('st-flash-ok');
                setTimeout(() => row.classList.remove('st-flash-ok'), 700);
            }
            const p = allProducts.find((x) => x.product_id === pid);
            if (p) {
                p.counted_qty  = item.counted_qty;
                p.baseline_qty = item.baseline_qty;
                p.difference   = item.difference;
                p.system_qty   = item.system_qty;
                p.item_id      = item.id;
            }
        }

        function setInputState(input, state) {
            if (!input) return;
            input.classList.remove('is-saving', 'is-saved', 'is-error');
            if (state) input.classList.add(state);
        }

        // ── Save (single product) ──────────────────────────────────
        async function saveCount(pid, qty, inputEl) {
            pendingSaves.delete(pid);
            inflight.add(pid);
            setInputState(inputEl, 'is-saving');
            try {
                const fd = new FormData();
                fd.append('product_id',  String(pid));
                fd.append('counted_qty', String(qty));
                fd.append('matched_by',  'manual_table');
                fd.append('_token', CSRF);
                const res = await fetch(URL_SAVE, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: fd,
                });
                const json = await res.json();
                if (!res.ok || !json.success) throw new Error(json.message || ('HTTP ' + res.status));
                updateRowAfterSave(pid, json.item);
                paintSummary(json.summary);
                setInputState(inputEl, 'is-saved');
                setTimeout(() => setInputState(inputEl, null), 800);
                return true;
            } catch (err) {
                setInputState(inputEl, 'is-error');
                console.error('[stock-take] save failed pid=' + pid + ':', err);
                return false;
            } finally {
                inflight.delete(pid);
            }
        }

        function scheduleSave(pid, qty, inputEl) {
            pendingSaves.set(pid, { qty, inputEl });
            if (saveTimers.has(pid)) clearTimeout(saveTimers.get(pid));
            saveTimers.set(pid, setTimeout(() => {
                saveTimers.delete(pid);
                const p = pendingSaves.get(pid);
                if (p) saveCount(pid, p.qty, p.inputEl);
            }, SAVE_DEBOUNCE_MS));
        }

        function flushNow(pid, qty, inputEl) {
            if (saveTimers.has(pid)) { clearTimeout(saveTimers.get(pid)); saveTimers.delete(pid); }
            return saveCount(pid, qty, inputEl);
        }

        async function flushAllPending() {
            const entries = Array.from(pendingSaves.entries());
            for (const [, t] of saveTimers) clearTimeout(t);
            saveTimers.clear();
            await Promise.all(entries.map(([pid, p]) => saveCount(pid, p.qty, p.inputEl)));
            let waited = 0;
            while (inflight.size > 0 && waited < 5000) {
                await new Promise((r) => setTimeout(r, 100));
                waited += 100;
            }
        }

        // ── Bulk actions ───────────────────────────────────────────
        function paintBulkBar() {
            const n = selected.size;
            elBulkCnt.textContent = n;
            elBulkBar.classList.toggle('hidden', n === 0);
        }

        function toggleSelect(pid) {
            const s = String(pid);
            if (selected.has(s)) selected.delete(s);
            else selected.add(s);
            const row = rowByPid.get(pid);
            if (row) {
                const chk = row.querySelector('[data-st-chk]');
                if (chk) chk.classList.toggle('is-checked', selected.has(s));
            }
            paintBulkBar();
        }

        async function bulkSetCount() {
            if (selected.size === 0) return;
            const raw = window.prompt('Set counted qty for ' + selected.size + ' selected products:', '');
            if (raw === null || raw === '') return;
            const qty = Math.max(0, parseInt(raw, 10) || 0);
            if (isNaN(qty)) { toast('Invalid number', 'error'); return; }
            const ids = Array.from(selected).map((x) => parseInt(x, 10));
            let ok = 0, fail = 0;
            // Save sequentially to avoid hammering the server.
            for (const pid of ids) {
                const row = rowByPid.get(pid);
                const inp = row ? row.querySelector('[data-st-input]') : null;
                if (inp) inp.value = String(qty);
                const success = await saveCount(pid, qty, inp);
                if (success) ok++; else fail++;
            }
            toast('Updated ' + ok + (fail ? ' (' + fail + ' failed)' : ''), fail ? 'error' : 'ok');
            selected.clear();
            paintBulkBar();
            applyFilter();
        }

        // ── Event delegation on the list ───────────────────────────
        elList.addEventListener('click', (e) => {
            const chk = e.target.closest('[data-st-chk]');
            if (chk) {
                const row = chk.closest('[data-pid]');
                if (row) toggleSelect(parseInt(row.dataset.pid, 10));
                return;
            }
        });

        elList.addEventListener('input', (e) => {
            const inp = e.target.closest('[data-st-input]'); if (!inp) return;
            const row = inp.closest('[data-pid]'); const pid = parseInt(row.dataset.pid, 10);
            const raw = inp.value.trim(); if (raw === '') return;
            const qty = Math.max(0, parseInt(raw, 10) || 0);
            scheduleSave(pid, qty, inp);
        });
        elList.addEventListener('blur', (e) => {
            const inp = e.target.closest('[data-st-input]'); if (!inp) return;
            const row = inp.closest('[data-pid]'); const pid = parseInt(row.dataset.pid, 10);
            const raw = inp.value.trim(); if (raw === '') return;
            const qty = Math.max(0, parseInt(raw, 10) || 0);
            flushNow(pid, qty, inp);
        }, true);
        elList.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter') return;
            const inp = e.target.closest('[data-st-input]'); if (!inp) return;
            e.preventDefault(); inp.blur();
            const row = inp.closest('[data-pid]');
            const next = row && row.nextElementSibling && row.nextElementSibling.querySelector('[data-st-input]');
            if (next) next.focus();
        });

        // ── Search ────────────────────────────────────────────────
        elSearch.addEventListener('input', () => { searchQ = elSearch.value || ''; applyFilter(); });

        // ── Channel filter ────────────────────────────────────────
        if (elChannel) {
            const onCh = () => {
                channelCode = elChannel.value || '';
                localStorage.setItem('st-ch-' + SESSION_ID, channelCode);
                applyFilter();
            };
            elChannel.addEventListener('change', onCh);
            elChannel.addEventListener('input',  onCh);
        }

        // ── Stock-greater filter ──────────────────────────────────
        elStockMin.addEventListener('change', () => { stockMin = elStockMin.value; applyFilter(); });

        // ── No-barcode chip ───────────────────────────────────────
        elNoBc.addEventListener('click', () => {
            noBarcode = !noBarcode;
            localStorage.setItem('st-nobc-' + SESSION_ID, noBarcode ? '1' : '0');
            paintNoBc();
            applyFilter();
        });

        // ── Sort buttons ──────────────────────────────────────────
        document.querySelectorAll('.st-sort-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const col = btn.dataset.col;
                if (sortCol === col) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                else { sortCol = col; sortDir = 'asc'; }
                document.querySelectorAll('.st-sort-btn').forEach((b) => b.classList.toggle('is-active', b.dataset.col === sortCol));
                applyFilter();
            });
        });

        // ── Bulk bar ──────────────────────────────────────────────
        $('st-bulk-clear').addEventListener('click', () => {
            selected.clear();
            paintBulkBar();
            elList.querySelectorAll('[data-st-chk]').forEach((c) => c.classList.remove('is-checked'));
        });

        // ── Scan input ────────────────────────────────────────────
        function flashScan(ok, msg) {
            elScanMsg.textContent = msg || '';
            elScanMsg.className = 'text-[11px] mt-1 ' + (ok ? 'text-green-600' : 'text-red-600');
            if (msg) elScanMsg.classList.remove('hidden'); else elScanMsg.classList.add('hidden');
            if (msg) setTimeout(() => elScanMsg.classList.add('hidden'), 2000);
        }

        async function doScan(code) {
            code = (code || '').trim();
            if (!code) return;
            try {
                const fd = new FormData();
                fd.append('code', code);
                fd.append('_token', CSRF);
                const res = await fetch(URL_SCAN, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: fd,
                });
                if (res.status === 404) { flashScan(false, 'Not found: ' + code); return; }
                const json = await res.json();
                if (!res.ok || !json.success) { flashScan(false, json.message || 'Scan failed'); return; }
                const pid = json.product.product_id;
                const qfd = new FormData();
                qfd.append('product_id', String(pid));
                qfd.append('delta', '1');
                qfd.append('_token', CSRF);
                const qres = await fetch(URL_QUICK, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: qfd,
                });
                const qjson = await qres.json();
                if (!qres.ok || !qjson.success) { flashScan(false, qjson.message || 'Increment failed'); return; }
                updateRowAfterSave(pid, qjson.item);
                paintSummary(qjson.summary);
                const row = rowByPid.get(pid);
                if (row) {
                    const inp = row.querySelector('[data-st-input]');
                    if (inp) inp.value = qjson.item.counted_qty;
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                flashScan(true, '+1 ' + (json.product.name || '#' + pid) + ' = ' + qjson.item.counted_qty);
            } catch (err) {
                console.error('[stock-take] scan failed:', err);
                flashScan(false, 'Network error');
            }
        }

        elScan.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const code = elScan.value;
            elScan.value = '';
            doScan(code);
            setTimeout(() => elScan.focus(), 50);
        });

        // ── Channel select population ─────────────────────────────
        function populateChannels(channels) {
            channelsData = channels || [];
            // Filter dropdown
            if (elChannel && channelsData.length) {
                for (const c of channelsData) {
                    const opt = document.createElement('option');
                    opt.value = c.code;
                    opt.textContent = c.name + ' (' + c.code + ')';
                    elChannel.appendChild(opt);
                }
                try { elChannel.value = channelCode || ''; } catch (e) {}
                if (elChannel.value !== (channelCode || '')) {
                    channelCode = '';
                    localStorage.setItem('st-ch-' + SESSION_ID, '');
                    elChannel.value = '';
                }
            }
            // Submenus: one row per channel for assign and remove.
            const channelLi = (action, c) =>
                '<li>' +
                  '<a class="whitespace-no-wrap block rounded px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950 cursor-pointer" ' +
                     'href="javascript:void(0);" ' +
                     'data-st-channel-action="' + action + '" ' +
                     'data-channel-id="' + c.id + '" ' +
                     'data-channel-name="' + esc(c.name) + '">' +
                    esc(c.name) + ' (' + esc(c.code) + ')' +
                  '</a>' +
                '</li>';
            if (elBulkSubAssign) {
                elBulkSubAssign.innerHTML = channelsData.map((c) => channelLi('assign', c)).join('')
                    || '<li class="px-4 py-2 text-xs text-gray-400">No channels</li>';
            }
            if (elBulkSubRemove) {
                elBulkSubRemove.innerHTML = channelsData.map((c) => channelLi('remove', c)).join('')
                    || '<li class="px-4 py-2 text-xs text-gray-400">No channels</li>';
            }
        }

        // ── Bulk action: assign/remove channel ────────────────────
        function closeBulkMenu() { elBulkActMenu.classList.add('hidden'); }

        async function bulkChannelAction(action, channelId, channelName) {
            if (selected.size === 0) return;
            const verb = action === 'assign' ? 'Assign' : 'Remove';
            const ids = Array.from(selected).map((x) => parseInt(x, 10));
            if (!confirm(verb + ' "' + channelName + '" channel for ' + ids.length + ' product(s)?')) return;
            closeBulkMenu();
            elBulkActBtn.disabled = true;
            try {
                const fd = new FormData();
                fd.append('action', action);
                fd.append('channel_id', String(channelId));
                fd.append('_token', CSRF);
                for (const pid of ids) fd.append('product_ids[]', String(pid));
                const res = await fetch(URL_BULK_CH, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: fd,
                });
                const json = await res.json();
                if (!res.ok || !json.success) throw new Error(json.message || ('HTTP ' + res.status));
                toast(json.message || (verb + ' done'));
                // Update local channel_codes on each affected product so the
                // filter chip and badges reflect the new state without reload.
                const ch = channelsData.find((c) => c.id === channelId);
                const code = ch ? ch.code : null;
                if (code) {
                    for (const p of allProducts) {
                        if (selected.has(String(p.product_id))) {
                            const codes = new Set(p.channel_codes || []);
                            if (action === 'assign') codes.add(code);
                            else codes.delete(code);
                            p.channel_codes = Array.from(codes);
                        }
                    }
                }
                selected.clear();
                paintBulkBar();
                applyFilter();
            } catch (err) {
                console.error('[stock-take] bulk channel failed:', err);
                toast('Bulk channel failed: ' + err.message, 'error');
            } finally {
                elBulkActBtn.disabled = false;
            }
        }

        if (elBulkActBtn) {
            elBulkActBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                elBulkActMenu.classList.toggle('hidden');
            });
        }
        // Top-level menu — handles "Set count" leaf + delegated submenu clicks.
        if (elBulkActMenu) {
            elBulkActMenu.addEventListener('click', (e) => {
                // Set count
                const setBtn = e.target.closest('[data-st-act="set-count"]');
                if (setBtn) {
                    closeBulkMenu();
                    bulkSetCount();
                    return;
                }
                // Channel submenu items (assign / remove)
                const chBtn = e.target.closest('[data-st-channel-action]');
                if (chBtn) {
                    const action = chBtn.dataset.stChannelAction;
                    const channelId = parseInt(chBtn.dataset.channelId, 10);
                    const channelName = chBtn.dataset.channelName || '';
                    closeBulkMenu();
                    bulkChannelAction(action, channelId, channelName);
                }
            });
        }
        // Close menu when clicking outside.
        document.addEventListener('click', (e) => {
            if (!elBulkActMenu || elBulkActMenu.classList.contains('hidden')) return;
            if (e.target.closest('#st-bulk-action-menu') || e.target.closest('#st-bulk-action-btn')) return;
            closeBulkMenu();
        });

        // ── Initial fetch ─────────────────────────────────────────
        async function loadProducts() {
            try {
                console.log('[stock-take] fetching', URL_PRODUCTS);
                const res = await fetch(URL_PRODUCTS, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status + ' ' + res.statusText);
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'load failed');
                allProducts = json.products || [];
                populateChannels(json.channels || []);
                paintNoBc();
                elLoading.classList.add('hidden');
                applyFilter();
                console.log('[stock-take] loaded', allProducts.length, 'products');
            } catch (err) {
                elLoading.classList.add('hidden');
                elError.textContent = 'Failed to load products: ' + err.message + ' (URL: ' + URL_PRODUCTS + ')';
                elError.classList.remove('hidden');
                console.error('[stock-take] loadProducts failed:', err);
            }
        }

        // ── Apply form: flush pending first ───────────────────────
        const applyForm = $('st-apply-form');
        if (applyForm) {
            let submitting = false;
            applyForm.addEventListener('submit', async (e) => {
                if (submitting) return;
                e.preventDefault();
                if (!confirm('Apply counted quantities to inventory?')) return;
                if (elApplyBtn) elApplyBtn.disabled = true;
                try { await flushAllPending(); } catch (err) { console.error(err); }
                submitting = true;
                applyForm.submit();
            });
        }

        // Auto-focus scan field for hardware scanners.
        setTimeout(() => elScan.focus(), 100);

        loadProducts();
    })();
    </script>
    @endpush
    @endif
</x-admin::layouts>
