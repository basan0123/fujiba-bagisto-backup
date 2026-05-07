{{-- ════════════════════════════════════════════════════════════════
     Invoices summary header — totals strip above the datagrid.
     Honors the user's current ?filters[...] (channel + created_at) so
     the count reflects what's visible after filtering.
════════════════════════════════════════════════════════════════ --}}
<div id="fujiba-invoices-summary" class="mb-2" v-pre>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1.5 text-[12px] leading-tight">
        <span class="flex items-center gap-1.5">
            <span class="text-gray-500">@lang('invoices-admin::app.summary.total'):</span>
            <span class="font-semibold text-gray-800 dark:text-white" data-fia="count">—</span>
            <span class="text-gray-500" data-fia="total_amount">—</span>
        </span>
        <span class="h-3 w-px bg-gray-200 dark:bg-gray-700"></span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
            <span class="text-emerald-700 dark:text-emerald-300">@lang('invoices-admin::app.summary.paid'):</span>
            <span class="font-semibold text-emerald-700 dark:text-emerald-300" data-fia="paid_count">—</span>
            <span class="text-emerald-600/80" data-fia="paid_amount">—</span>
        </span>
        <span class="h-3 w-px bg-gray-200 dark:bg-gray-700"></span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
            <span class="text-amber-700 dark:text-amber-300">@lang('invoices-admin::app.summary.pending'):</span>
            <span class="font-semibold text-amber-700 dark:text-amber-300" data-fia="pending_count">—</span>
            <span class="text-amber-600/80" data-fia="pending_amount">—</span>
        </span>
        <span class="h-3 w-px bg-gray-200 dark:bg-gray-700"></span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-2 w-2 rounded-full bg-rose-500"></span>
            <span class="text-rose-700 dark:text-rose-300">@lang('invoices-admin::app.summary.overdue'):</span>
            <span class="font-semibold text-rose-700 dark:text-rose-300" data-fia="overdue_count">—</span>
            <span class="text-rose-600/80" data-fia="overdue_amount">—</span>
        </span>
    </div>
</div>

@pushOnce('scripts')
<script>
(function () {
    const SUMMARY_URL = @json(route('fujiba.invoices.summary'));
    const CURRENCY    = @json(core()->getBaseCurrencyCode() ?: 'JPY');

    function fmtMoney(n) {
        try {
            return new Intl.NumberFormat(undefined, { style: 'currency', currency: CURRENCY, maximumFractionDigits: 0 }).format(Number(n) || 0);
        } catch (_) {
            return String(n);
        }
    }

    function readFiltersFromUrl() {
        const u = new URL(window.location.href);
        const out = {};
        const ch = u.searchParams.get('filters[channel_id][]') || u.searchParams.get('filters[channel_id]');
        if (ch) out.channel_id = ch;
        const from = u.searchParams.get('filters[created_at][from]');
        const to   = u.searchParams.get('filters[created_at][to]');
        if (from) out.from = from;
        if (to)   out.to   = to;
        return out;
    }

    function set(key, value) {
        const el = document.querySelector('[data-fia="' + key + '"]');
        if (el) el.textContent = value;
    }

    function load() {
        const params = new URLSearchParams(readFiltersFromUrl());
        fetch(SUMMARY_URL + (params.toString() ? ('?' + params.toString()) : ''), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(j => {
            const d = j && j.data ? j.data : {};
            set('count',          (d.count || 0).toLocaleString());
            set('total_amount',   fmtMoney(d.total_amount));
            set('paid_count',     (d.paid_count || 0).toLocaleString());
            set('paid_amount',    fmtMoney(d.paid_amount));
            set('pending_count',  (d.pending_count || 0).toLocaleString());
            set('pending_amount', fmtMoney(d.pending_amount));
            set('overdue_count',  (d.overdue_count || 0).toLocaleString());
            set('overdue_amount', fmtMoney(d.overdue_amount));
        })
        .catch(() => { /* silent — widget is non-blocking */ });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }

    // Refresh whenever the URL filters change (Bagisto datagrid pushes state).
    let lastSearch = window.location.search;
    setInterval(() => {
        if (window.location.search !== lastSearch) {
            lastSearch = window.location.search;
            load();
        }
    }, 1500);
})();
</script>
@endPushOnce
