<x-admin::layouts>
    <x-slot:title>{{ __('waste::app.title') }}</x-slot:title>

    @push('meta')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    @push('styles')
    <style>
        /* Desktop: leave the sidebar (286px / 85px when collapsed) uncovered;
           below lg the layout stacks so the modal goes full-screen. */
        @media (min-width: 1024px) {
            html[dir="ltr"] .wm-content-modal { top: 62px; left: 286px; }
            html[dir="rtl"] .wm-content-modal { top: 62px; right: 286px; }
            html[dir="ltr"] .sidebar-collapsed .wm-content-modal { left: 85px; }
            html[dir="rtl"] .sidebar-collapsed .wm-content-modal { right: 85px; }
        }

        /* ── Mobile bottom-sheet (≤640px) ──
           Old layout used items-end on a fixed inset-0 + max-h-[95vh] which on
           iPhone made the inner card scroll independently of the page; the
           Bagisto sticky header (62px) plus the modal header could occlude the
           first input. New layout: card is a true bottom-sheet (rounded top,
           pinned to viewport bottom, body grows to fill). */
        @media (max-width: 640px) {
            #waste-modal { padding: 0 !important; align-items: stretch !important; }
            #waste-modal > div {
                width: 100% !important;
                max-width: none !important;
                border-radius: 16px 16px 0 0 !important;
                margin-top: auto !important;
                /* dvh accounts for the iOS dynamic toolbar; vh fallback below */
                max-height: 88vh !important;
                max-height: 88dvh !important;
                box-shadow: 0 -8px 24px rgba(0,0,0,.18) !important;
            }
            /* Drag handle for the bottom-sheet */
            #waste-modal > div::before {
                content: '';
                display: block;
                width: 40px; height: 4px;
                background: #cbd5e1;
                border-radius: 2px;
                margin: 8px auto 0;
                flex-shrink: 0;
            }
            /* Make sure inputs are ≥16px so iOS doesn't zoom-on-focus */
            #waste-modal input,
            #waste-modal select,
            #waste-modal textarea { font-size: 16px !important; }

            /* Confirm dialog — keep centered card style on mobile too */
            #wm-confirm-modal { padding: 16px !important; }
        }

        /* Camera viewport — caps height so live video + scan overlay don't
           push everything else below the fold. */
        #wm-camera-container video {
            max-height: 240px;
            object-fit: cover;
            background: #000;
        }
    </style>
    @endpush

    {{-- ── Compact header strip: title + add button + 3 KPIs all in one row ── --}}
    <div class="flex flex-wrap items-center gap-3 mb-4 box-shadow rounded-lg bg-white dark:bg-gray-900 px-4 py-3">
        <p class="text-lg font-bold text-gray-800 dark:text-white shrink-0">{{ __('waste::app.title') }}</p>
        <button onclick="WM.openModal()"
            class="px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition flex items-center gap-1 shrink-0">
            <span class="leading-none">+</span> {{ __('waste::app.add') }}
        </button>

        {{-- KPIs inline --}}
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 ml-auto text-sm">
            <div class="flex items-baseline gap-1.5">
                <span class="text-gray-500 dark:text-gray-400 text-xs">Amount</span>
                <span id="kpi-amount" class="text-lg font-bold text-red-600">¥0</span>
                <span class="text-[11px] text-gray-400" id="kpi-records">0</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-gray-500 dark:text-gray-400 text-xs">Qty</span>
                <span id="kpi-qty" class="text-lg font-bold text-orange-500">0</span>
                <span class="text-[11px] text-gray-400">units</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-gray-500 dark:text-gray-400 text-xs">Year</span>
                <span class="text-lg font-bold text-gray-700 dark:text-gray-200">{{ date('Y') }}</span>
                <span class="text-[11px] text-gray-400">{{ date('M') }}</span>
            </div>
        </div>
    </div>

    {{-- Charts row — taller canvas so the bars are readable. Chart.js uses
         the canvas height attribute when responsive:false; we keep responsive
         on but force a fixed-height container. --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-5">
            <p class="font-semibold text-gray-700 dark:text-gray-200 mb-3">Monthly Waste ({{ date('Y') }})</p>
            <div class="relative" style="height:320px"><canvas id="chart-monthly"></canvas></div>
        </div>
        <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-5">
            <p class="font-semibold text-gray-700 dark:text-gray-200 mb-3">Yearly Waste (5 years)</p>
            <div class="relative" style="height:320px"><canvas id="chart-yearly"></canvas></div>
        </div>
    </div>

    {{-- ── Filters ──────────────────────────────────────────────────────────────── --}}
    <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-4 mb-4">
        <form method="GET" action="{{ route('admin.catalog.waste.index') }}" class="flex flex-wrap gap-3 items-end">

            {{-- Month --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Month</label>
                <select name="month" class="border rounded px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 min-w-[110px]">
                    <option value="">All Months</option>
                    @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" @selected(request('month') == $m)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Year --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Year</label>
                <select name="year" class="border rounded px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 min-w-[100px]">
                    <option value="">All Years</option>
                    @foreach(range(date('Y'), date('Y')-4, -1) as $y)
                        <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Channel --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Channel</label>
                <select name="channel_id" id="filter-channel" class="border rounded px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 min-w-[140px]">
                    <option value="">All Channels</option>
                    @foreach($channels as $ch)
                        <option value="{{ $ch->id }}" @selected(request('channel_id') == $ch->id)>{{ $ch->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div class="flex flex-col gap-1 flex-1 min-w-[180px]">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Search Product</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU…"
                    class="border rounded px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 w-full">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">{{ __('waste::app.filter') }}</button>
                <a href="{{ route('admin.catalog.waste.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300">{{ __('waste::app.reset') }}</a>
            </div>
        </form>
    </div>

    {{-- Totals row --}}
    @if($records->total() > 0)
    <div class="flex gap-4 mb-3 text-sm text-gray-600 dark:text-gray-300">
        <span>{{ __('waste::app.filtered-total') }}: <strong class="text-red-600">¥{{ number_format($totals->total_amount ?? 0, 0) }}</strong></span>
        <span>·</span>
        <span>{{ __('waste::app.total-qty') }}: <strong>{{ number_format($totals->total_qty ?? 0, 2) }}</strong></span>
        <span>·</span>
        <span>{{ $records->total() }} {{ __('waste::app.records') }}</span>
    </div>
    @endif

    {{-- ── Records Table ────────────────────────────────────────────────────────── --}}
    <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('waste::app.columns.date') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('waste::app.columns.product') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('waste::app.columns.sku') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('waste::app.columns.channel') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('waste::app.columns.qty') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('waste::app.columns.amount') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('waste::app.columns.reason') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('waste::app.columns.notes') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('waste::app.columns.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($records as $rec)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                    <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                        {{ $rec->created_at->format('Y-m-d') }}
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $rec->product_name }}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $rec->product_sku ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                        {{ $rec->channel?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono">{{ number_format($rec->quantity, 2) }}</td>
                    <td class="px-4 py-3 text-right font-mono text-red-600">¥{{ number_format($rec->amount, 0) }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 text-xs rounded-full
                            @if($rec->reason === 'Expired') bg-orange-100 text-orange-700
                            @elseif($rec->reason === 'Damaged') bg-red-100 text-red-700
                            @elseif($rec->reason === 'Theft') bg-purple-100 text-purple-700
                            @else bg-gray-100 text-gray-600
                            @endif">
                            {{ $rec->reason }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-[150px] truncate" title="{{ $rec->notes }}">{{ $rec->notes ?: '—' }}</td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <button onclick="WM.editRecord({{ $rec->id }})"
                            class="text-indigo-600 hover:text-indigo-800 text-xs px-2 py-1 rounded hover:bg-indigo-50 mr-1">
                            {{ __('waste::app.actions.edit') }}
                        </button>
                        <button onclick="WM.deleteRecord({{ $rec->id }})"
                            class="text-red-500 hover:text-red-700 text-xs px-2 py-1 rounded hover:bg-red-50">
                            {{ __('waste::app.actions.delete') }}
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-400">{{ __('waste::app.empty') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($records->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $records->links() }}
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    {{-- Add Waste Modal                                                          --}}
    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    {{-- Backdrop is positioned to cover only the inner content area on desktop,
         not the sidebar — Bagisto's sidebar lives at lg:left-0 z-10, 286px wide
         (85px when collapsed), and the page header is 62px tall. Matching those
         offsets keeps the side nav clickable while the modal is open. We use
         a custom CSS class instead of arbitrary Tailwind utilities because the
         JIT pass for this package isn't producing left-[286px] / left-[85px]. --}}
    <div id="waste-modal"
         class="wm-content-modal fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/50 p-2 sm:p-4 overflow-y-auto">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-md flex flex-col max-h-[95vh] sm:max-h-[90vh] overflow-hidden">

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-3 sm:px-4 py-2 sm:py-3 border-b border-gray-100 dark:border-gray-700 shrink-0">
                <h2 id="wm-modal-title" class="text-base sm:text-lg font-bold text-gray-800 dark:text-white">{{ __('waste::app.add-record') }}</h2>
                <button onclick="WM.closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none px-1">&times;</button>
            </div>

            {{-- Modal body --}}
            <div class="px-3 sm:px-4 py-3 sm:py-4 space-y-3 overflow-y-auto flex-1">

                {{-- Barcode scan --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        🔍 Search / Scan Barcode
                    </label>
                    <div class="flex gap-2">
                        <input type="text" id="wm-search" placeholder="Type product name, SKU, or scan barcode…"
                            class="flex-1 border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600"
                            oninput="WM.debounceSearch(this.value)"
                            onkeydown="if(event.key==='Enter'){event.preventDefault(); WM.triggerSearch(this.value);}">
                        <button onclick="WM.startBarcodeCamera()"
                            title="Scan barcode with camera"
                            class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-lg">
                            📷
                        </button>
                    </div>

                    {{-- Video stream for camera scan --}}
                    <div id="wm-camera-container" class="mt-2 hidden">
                        <video id="wm-video" class="w-full rounded border" autoplay muted playsinline></video>
                        <button onclick="WM.stopCamera()" class="mt-1 text-xs text-red-500 hover:underline">Stop camera</button>
                    </div>

                    {{-- Dropdown results --}}
                    <div id="wm-results" class="relative mt-1 hidden">
                        <ul id="wm-results-list"
                            class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        </ul>
                    </div>
                </div>

                {{-- Product info (read-only). Sell price intentionally hidden —
                     waste records value is the cost-side number, not the sell
                     price, so showing it confuses the operator. --}}
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hidden" id="wm-product-info">
                    <p class="font-semibold text-gray-800 dark:text-white text-sm" id="wm-product-name-display">—</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        SKU: <span id="wm-product-sku-display">—</span>
                        · Stock: <span id="wm-product-stock-display">—</span>
                    </p>
                </div>

                {{-- Hidden product fields --}}
                <input type="hidden" id="wm-product-id" value="">
                <input type="hidden" id="wm-product-name" value="">
                <input type="hidden" id="wm-product-sku-val" value="">
                <input type="hidden" id="wm-product-price" value="">

                {{-- Buy price (editable) — prefilled from product cost; falls back
                     to sell price when cost is missing. Operator can override. --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('waste::app.form.buy-price') }}
                    </label>
                    <input type="number" id="wm-buy-price" min="0" step="1" value="0"
                        class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600"
                        oninput="WM.calcAmount()">
                    <p class="text-xs text-gray-400 mt-0.5">{{ __('waste::app.form.buy-price-hint') }}</p>
                </div>

                {{-- Quantity & Amount --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('waste::app.form.qty') }} <span class="text-red-500">*</span></label>
                        <input type="number" id="wm-quantity" min="1" step="1" value="1"
                            class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600"
                            oninput="WM.calcAmount()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('waste::app.form.amount') }}</label>
                        <input type="number" id="wm-amount" min="0" step="1" value="0"
                            class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-xs text-gray-400 mt-0.5">{{ __('waste::app.form.amount-hint') }}</p>
                    </div>
                </div>

                {{-- Channel & Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('waste::app.form.channel') }}</label>
                        <select id="wm-channel" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="">— Select —</option>
                            @foreach($channels as $ch)
                                <option value="{{ $ch->id }}">{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('waste::app.form.date') }}</label>
                        <input type="date" id="wm-date" value="{{ date('Y-m-d') }}"
                            class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    </div>
                </div>

                {{-- Reason --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('waste::app.form.reason') }} <span class="text-red-500">*</span></label>
                    <select id="wm-reason" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <option value="Expired" selected>{{ __('waste::app.reasons.expired') }}</option>
                        <option value="Damaged">{{ __('waste::app.reasons.damaged') }}</option>
                        <option value="Theft">{{ __('waste::app.reasons.theft') }}</option>
                        <option value="Spoilage">{{ __('waste::app.reasons.other') }}</option>
                        <option value="Other">{{ __('waste::app.reasons.other') }}</option>
                    </select>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('waste::app.form.notes') }}</label>
                    <textarea id="wm-notes" rows="2" placeholder="Additional details…"
                        class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600"></textarea>
                </div>

                {{-- Error message --}}
                <div id="wm-error" class="hidden p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm rounded-lg"></div>
            </div>

            {{-- Modal footer (sticks at bottom; buttons full-width on phones) --}}
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-3 px-3 sm:px-4 py-2 sm:py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 shrink-0">
                <button onclick="WM.closeModal()" class="w-full sm:w-auto px-4 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-white rounded-lg hover:bg-gray-50">{{ __('waste::app.actions.cancel') }}</button>
                <button onclick="WM.save()" id="wm-save-btn"
                    class="w-full sm:w-auto px-5 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    {{ __('waste::app.form.save-btn') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Delete confirm modal --}}
    <div id="wm-confirm-modal"
         class="wm-content-modal fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/50 p-2 sm:p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-5 sm:p-6 w-full max-w-sm">
            <p class="text-gray-800 dark:text-white font-semibold mb-3">{{ __('waste::app.confirm.delete-title') }}</p>
            <p class="text-xs text-gray-500 mb-4">{{ __('waste::app.confirm.delete-text') }}</p>
            <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 sm:justify-end">
                <button onclick="document.getElementById('wm-confirm-modal').classList.add('hidden');document.getElementById('wm-confirm-modal').classList.remove('flex')"
                    class="w-full sm:w-auto px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg">{{ __('waste::app.actions.cancel') }}</button>
                <button id="wm-confirm-delete-btn" class="w-full sm:w-auto px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">{{ __('waste::app.actions.delete') }}</button>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    {{-- ZXing — universal barcode/QR scanner. Loaded lazily on first scan
         attempt so it doesn't add weight to the initial page load. --}}

    <script>
    const WM = (() => {
        // ── Config ────────────────────────────────────────────────────────────────
        const ROUTES = {
            store:         '{{ route("admin.catalog.waste.store") }}',
            dashboard:     '{{ route("admin.catalog.waste.dashboard") }}',
            search:        '{{ route("admin.catalog.waste.products.search") }}',
            destroyBase:   '{{ url(config("app.admin_url") . "/catalog/waste") }}',
        };
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const I18N = {
            addTitle:    @json(__('waste::app.add-record')),
            editTitle:   @json(__('waste::app.edit-record')),
            saveBtn:     @json(__('waste::app.form.save-btn')),
            updateBtn:   @json(__('waste::app.form.update-btn')),
            error:       @json(__('waste::app.messages.error')),
            deleteFailed:@json(__('waste::app.messages.error')),
        };

        // ── State ─────────────────────────────────────────────────────────────────
        let selectedProduct = null;
        let searchTimer     = null;
        let cameraStream    = null;
        let monthlyChart    = null;
        let yearlyChart     = null;
        let editingId       = null;

        // ── Helpers ───────────────────────────────────────────────────────────────
        function $id(id) { return document.getElementById(id); }

        function fmtCurrency(v) {
            return '¥' + Number(v).toLocaleString('ja-JP', {maximumFractionDigits: 0});
        }

        async function apiFetch(url, opts = {}) {
            const res = await fetch(url, {
                headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', ...(opts.headers ?? {})},
                ...opts,
            });
            return res.json();
        }

        // ── Modal ─────────────────────────────────────────────────────────────────
        function openModal() {
            resetForm();
            editingId = null;
            $id('wm-modal-title').textContent = I18N.addTitle;
            $id('wm-save-btn').textContent    = I18N.saveBtn;
            const m = $id('waste-modal');
            m.classList.remove('hidden');
            m.classList.add('flex');
            setTimeout(() => $id('wm-search').focus(), 100);
        }

        function closeModal() {
            stopCamera();
            editingId = null;
            const m = $id('waste-modal');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        function resetForm() {
            selectedProduct = null;
            editingId = null;
            $id('wm-search').value        = '';
            $id('wm-product-id').value    = '';
            $id('wm-product-name').value  = '';
            $id('wm-product-sku-val').value = '';
            $id('wm-product-price').value = '';
            $id('wm-buy-price').value     = '0';
            $id('wm-quantity').value      = '1';
            $id('wm-amount').value        = '0';
            $id('wm-reason').value        = 'Expired';
            $id('wm-notes').value         = '';
            $id('wm-date').value          = new Date().toISOString().split('T')[0];
            $id('wm-product-info').classList.add('hidden');
            hideResults();
            hideError();
        }

        // ── Product search ────────────────────────────────────────────────────────
        function debounceSearch(val) {
            clearTimeout(searchTimer);
            if (val.length < 1) { hideResults(); return; }
            searchTimer = setTimeout(() => doSearch(val), 300);
        }

        function triggerSearch(val) {
            clearTimeout(searchTimer);
            doSearch(val);
        }

        async function doSearch(q) {
            if (!q) return;
            const data = await apiFetch(ROUTES.search + '?q=' + encodeURIComponent(q));
            renderResults(data.products ?? []);
        }

        function renderResults(products) {
            const list = $id('wm-results-list');
            const box  = $id('wm-results');
            list.innerHTML = '';

            if (!products.length) {
                list.innerHTML = '<li class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500">No products found</li>';
            } else {
                products.forEach(p => {
                    const li = document.createElement('li');
                    li.className = 'px-4 py-3 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/30 text-sm border-b border-gray-100 dark:border-gray-700 last:border-0';
                    li.innerHTML = `<span class="font-medium text-gray-800 dark:text-white">${esc(p.name)}</span>
                        <span class="text-gray-400 text-xs ml-2">${esc(p.sku || '')}</span>
                        <span class="float-right text-gray-500 text-xs">¥${Number(p.price||0).toLocaleString()} · Stock: ${p.stock||0}</span>`;
                    li.onclick = () => selectProduct(p);
                    list.appendChild(li);
                });
            }

            box.classList.remove('hidden');
        }

        function hideResults() {
            $id('wm-results').classList.add('hidden');
            $id('wm-results-list').innerHTML = '';
        }

        function selectProduct(p) {
            selectedProduct = p;
            $id('wm-product-id').value     = p.product_id;
            $id('wm-product-name').value   = p.name;
            $id('wm-product-sku-val').value = p.sku || '';
            $id('wm-product-price').value  = p.price || 0;
            // Prefer cost; fall back to sell price so the field is never zero
            // when the operator picks a product that has no cost set.
            const buy = (p.cost && p.cost > 0) ? p.cost : (p.price || 0);
            $id('wm-buy-price').value      = Number(buy).toFixed(0);
            $id('wm-search').value         = p.name;
            hideResults();

            $id('wm-product-name-display').textContent  = p.name;
            $id('wm-product-sku-display').textContent   = p.sku || '—';
            $id('wm-product-stock-display').textContent = p.stock ?? '—';
            $id('wm-product-info').classList.remove('hidden');

            calcAmount();
        }

        function calcAmount() {
            const buy = parseFloat($id('wm-buy-price').value) || 0;
            const qty = parseFloat($id('wm-quantity').value) || 0;
            $id('wm-amount').value = (buy * qty).toFixed(0);
        }

        // ── Camera barcode scan ───────────────────────────────────────────────────
        // Strategy:
        //   1. Try the native BarcodeDetector (fast, Android Chrome).
        //   2. If missing OR detection fails, lazy-load ZXing-js from CDN
        //      (works on iOS Safari, Firefox, etc.) and use it.
        // The video element is shared between both paths; cameraStream owns
        // the MediaStream so stopCamera() always releases the camera even if
        // ZXing's reader didn't finish initializing.
        let zxingReader = null;

        function loadZXingOnce() {
            if (window.ZXingBrowser) return Promise.resolve(window.ZXingBrowser);
            if (window.__zxingLoading) return window.__zxingLoading;
            window.__zxingLoading = new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.5/umd/index.min.js';
                s.async = true;
                s.onload = () => {
                    if (window.ZXingBrowser) resolve(window.ZXingBrowser);
                    else reject(new Error('ZXing failed to attach to window'));
                };
                s.onerror = () => reject(new Error('ZXing CDN load failed'));
                document.head.appendChild(s);
            });
            return window.__zxingLoading;
        }

        async function startBarcodeCamera() {
            const container = $id('wm-camera-container');
            const video     = $id('wm-video');
            container.classList.remove('hidden');

            // Acquire camera stream first — same call works for both detection
            // backends. facingMode 'environment' selects the rear camera on phones.
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false,
                });
                video.srcObject = cameraStream;
                await video.play().catch(() => {}); // iOS sometimes needs an explicit play()
            } catch (err) {
                container.classList.add('hidden');
                showError('Camera not available: ' + err.message);
                return;
            }

            // Try native BarcodeDetector first (Chrome/Edge on Android & desktop).
            if ('BarcodeDetector' in window) {
                try {
                    const supported = await BarcodeDetector.getSupportedFormats?.() ?? [];
                    const formats = supported.length
                        ? supported.filter(f => ['ean_13','ean_8','code_128','code_39','upc_a','upc_e','qr_code','itf'].includes(f))
                        : undefined;
                    const detector = new BarcodeDetector(formats?.length ? { formats } : undefined);
                    let nativeFailed = false;
                    const scanLoop = async () => {
                        if (!cameraStream) return;
                        try {
                            const barcodes = await detector.detect(video);
                            if (barcodes.length) {
                                const code = barcodes[0].rawValue;
                                handleScannedCode(code);
                                return;
                            }
                        } catch (e) {
                            // detect() can throw on some platforms — fall through to ZXing.
                            nativeFailed = true;
                        }
                        if (nativeFailed) { startZXing(video); return; }
                        requestAnimationFrame(scanLoop);
                    };
                    requestAnimationFrame(scanLoop);
                    return;
                } catch (_) {
                    // Constructor or detect() blew up — fall through to ZXing.
                }
            }

            // Fallback: ZXing
            startZXing(video);
        }

        async function startZXing(video) {
            try {
                const ZXing = await loadZXingOnce();
                zxingReader = new ZXing.BrowserMultiFormatReader();
                // decodeFromVideoElement runs continuously; callback fires per detection.
                zxingReader.decodeFromVideoElement(video, (result, err) => {
                    if (result) handleScannedCode(result.getText());
                    // err is a NotFoundException between detections — ignore.
                });
            } catch (e) {
                showError('Camera scanner failed to load: ' + (e?.message || e));
                stopCamera();
            }
        }

        function handleScannedCode(code) {
            stopCamera();
            $id('wm-search').value = code;
            doSearch(code);
        }

        function stopCamera() {
            if (zxingReader) {
                try { zxingReader.reset(); } catch (_) {}
                zxingReader = null;
            }
            if (cameraStream) {
                cameraStream.getTracks().forEach(t => t.stop());
                cameraStream = null;
            }
            const v = $id('wm-video');
            if (v) v.srcObject = null;
            $id('wm-camera-container').classList.add('hidden');
        }

        // ── Save ──────────────────────────────────────────────────────────────────
        async function save() {
            hideError();

            if (!editingId && !$id('wm-product-id').value) {
                return showError('Please select a product first.');
            }
            const qty = parseFloat($id('wm-quantity').value);
            if (!qty || qty <= 0) {
                return showError('Quantity must be greater than 0.');
            }

            const btn = $id('wm-save-btn');
            btn.disabled = true;
            btn.textContent = 'Saving…';

            const body = {
                product_id:   $id('wm-product-id').value,
                product_name: $id('wm-product-name').value,
                product_sku:  $id('wm-product-sku-val').value,
                channel_id:   $id('wm-channel').value || null,
                quantity:     qty,
                amount:       parseFloat($id('wm-amount').value) || 0,
                reason:       $id('wm-reason').value,
                notes:        $id('wm-notes').value,
                waste_date:   $id('wm-date').value,
            };

            const isEdit = !!editingId;
            const url    = isEdit ? ROUTES.destroyBase + '/' + editingId : ROUTES.store;
            const method = isEdit ? 'PUT' : 'POST';

            try {
                const data = await apiFetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                });

                if (data.success) {
                    closeModal();
                    window.location.reload();
                } else {
                    showError(data.message || JSON.stringify(data.errors || I18N.error));
                }
            } catch (e) {
                showError('Network error: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.textContent = isEdit ? I18N.updateBtn : I18N.saveBtn;
            }
        }

        // ── Edit ──────────────────────────────────────────────────────────────────
        async function editRecord(id) {
            try {
                const data = await apiFetch(ROUTES.destroyBase + '/' + id);
                if (!data.success || !data.record) {
                    alert(I18N.error);
                    return;
                }
                const r = data.record;

                resetForm();
                editingId = id;

                $id('wm-modal-title').textContent = I18N.editTitle;
                $id('wm-save-btn').textContent    = I18N.updateBtn;

                $id('wm-product-id').value       = r.product_id;
                $id('wm-product-name').value     = r.product_name;
                $id('wm-product-sku-val').value  = r.product_sku || '';
                $id('wm-product-name-display').textContent = r.product_name;
                $id('wm-product-sku-display').textContent  = r.product_sku || '—';
                $id('wm-product-info').classList.remove('hidden');

                $id('wm-channel').value  = r.channel_id || '';
                $id('wm-quantity').value = r.quantity;
                $id('wm-amount').value   = r.amount;
                // Back-derive buy price from stored amount so editing doesn't
                // silently change the recorded total.
                const editQty = parseFloat(r.quantity) || 1;
                $id('wm-buy-price').value = (parseFloat(r.amount) / editQty).toFixed(0);
                $id('wm-reason').value   = r.reason;
                $id('wm-notes').value    = r.notes || '';
                $id('wm-date').value     = (r.created_at || '').substring(0, 10);

                const m = $id('waste-modal');
                m.classList.remove('hidden');
                m.classList.add('flex');
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        // ── Delete ────────────────────────────────────────────────────────────────
        function deleteRecord(id) {
            const modal = $id('wm-confirm-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            $id('wm-confirm-delete-btn').onclick = async () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');

                try {
                    const data = await apiFetch(ROUTES.destroyBase + '/' + id, {
                        method: 'DELETE',
                    });
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Delete failed');
                    }
                } catch (e) {
                    alert('Error: ' + e.message);
                }
            };
        }

        // ── Charts + KPIs ─────────────────────────────────────────────────────────
        async function loadDashboard() {
            const channelId = document.getElementById('filter-channel')?.value ?? '';
            const url = ROUTES.dashboard + (channelId ? '?channel_id=' + channelId : '');
            const data = await apiFetch(url);

            // KPIs
            $id('kpi-amount').textContent  = fmtCurrency(data.totals.amount);
            $id('kpi-qty').textContent     = Number(data.totals.qty).toLocaleString();
            $id('kpi-records').textContent = data.totals.records + ' records';

            // Monthly chart
            const mLabels = data.monthly.map(m => m.label);
            const mAmount = data.monthly.map(m => m.total_amount);
            const mQty    = data.monthly.map(m => m.total_qty);

            if (monthlyChart) monthlyChart.destroy();
            monthlyChart = new Chart($id('chart-monthly'), {
                type: 'bar',
                data: {
                    labels: mLabels,
                    datasets: [
                        {
                            label: 'Amount (¥)',
                            data: mAmount,
                            backgroundColor: 'rgba(239,68,68,0.7)',
                            yAxisID: 'yAmount',
                        },
                        {
                            label: 'Qty',
                            data: mQty,
                            type: 'line',
                            borderColor: 'rgba(249,115,22,0.9)',
                            backgroundColor: 'rgba(249,115,22,0.1)',
                            tension: 0.4,
                            yAxisID: 'yQty',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        yAmount: { type: 'linear', position: 'left',  title: { display: true, text: '¥' } },
                        yQty:    { type: 'linear', position: 'right', title: { display: true, text: 'Qty' }, grid: { drawOnChartArea: false } },
                    },
                },
            });

            // Yearly chart
            const yLabels = data.yearly.map(y => y.year);
            const yAmount = data.yearly.map(y => y.total_amount);
            const yQty    = data.yearly.map(y => y.total_qty);

            if (yearlyChart) yearlyChart.destroy();
            yearlyChart = new Chart($id('chart-yearly'), {
                type: 'bar',
                data: {
                    labels: yLabels,
                    datasets: [
                        {
                            label: 'Amount (¥)',
                            data: yAmount,
                            backgroundColor: 'rgba(99,102,241,0.7)',
                        },
                        {
                            label: 'Qty',
                            data: yQty,
                            type: 'line',
                            borderColor: 'rgba(16,185,129,0.9)',
                            backgroundColor: 'rgba(16,185,129,0.1)',
                            tension: 0.4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                },
            });
        }

        // ── Utilities ─────────────────────────────────────────────────────────────
        function showError(msg) {
            const el = $id('wm-error');
            el.textContent = msg;
            el.classList.remove('hidden');
        }

        function hideError() {
            $id('wm-error').classList.add('hidden');
        }

        function esc(str) {
            return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#wm-search') && !e.target.closest('#wm-results')) {
                hideResults();
            }
        });

        // Close modal on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
                const cm = $id('wm-confirm-modal');
                if (!cm.classList.contains('hidden')) {
                    cm.classList.add('hidden');
                    cm.classList.remove('flex');
                }
            }
        });

        // Auto-load dashboard on page ready
        document.addEventListener('DOMContentLoaded', loadDashboard);

        return { openModal, closeModal, save, deleteRecord, editRecord, debounceSearch, triggerSearch, startBarcodeCamera, stopCamera, calcAmount };
    })();
    </script>
    @endpush

</x-admin::layouts>
