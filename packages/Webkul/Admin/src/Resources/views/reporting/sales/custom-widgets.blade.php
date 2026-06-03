{{--
    Fujiba custom sales report widgets. Each is a self-contained Vue component
    that listens to the shared `reporting-filter-updated` event and fetches its
    own stats?type=... slice. Mirrors the stock Bagisto widget pattern.
--}}

@php
    $statsRoute = route('admin.reporting.sales.stats');
@endphp

{{-- ════════════════ Mounts ════════════════ --}}
<div class="flex flex-1 flex-col gap-4">
    <v-rep-monthly-sales></v-rep-monthly-sales>

    <div class="flex flex-col gap-4 md:flex-row [&>*]:flex-1">
        <v-rep-new-returning></v-rep-new-returning>
        <v-rep-sales-by-channel></v-rep-sales-by-channel>
    </div>

    <div class="flex flex-col gap-4 md:flex-row [&>*]:flex-1">
        <v-rep-top-products></v-rep-top-products>
        <v-rep-customer-location></v-rep-customer-location>
    </div>

    <div class="flex flex-col gap-4 md:flex-row [&>*]:flex-1">
        <v-rep-sales-by-category></v-rep-sales-by-category>
        <v-rep-sales-by-brand></v-rep-sales-by-brand>
    </div>

    <v-rep-sales-heatmap></v-rep-sales-heatmap>
</div>

@pushOnce('scripts')
    <script type="module">
        /* Shared helper: largest value of `key` across rows (min 1 to avoid /0). */
        window.repMaxRevenue = (rows, key) =>
            (rows || []).reduce((m, r) => Math.max(m, Number(r[key]) || 0), 0) || 1;
    </script>

    {{-- ════════════════ Monthly sales table ════════════════ --}}
    <script type="text/x-template" id="v-rep-monthly-sales-template">
        <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-600 dark:text-white">
                @lang('admin::app.reporting.sales.index.monthly-sales')
            </p>
            <template v-if="isLoading">
                <div class="shimmer h-40 w-full rounded"></div>
            </template>
            <template v-else-if="report && report.statistics.rows.length">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <th class="py-2 pr-4 font-semibold">@lang('admin::app.reporting.sales.index.month')</th>
                                <th class="py-2 pr-4 text-right font-semibold">@lang('admin::app.reporting.sales.index.orders')</th>
                                <th class="py-2 pr-4 text-right font-semibold">@lang('admin::app.reporting.sales.index.total-sales')</th>
                                <th class="py-2 text-right font-semibold">@lang('admin::app.reporting.sales.index.aov')</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in report.statistics.rows" :key="row.ym" class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pr-4 dark:text-gray-200">@{{ row.label }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums dark:text-gray-200">@{{ row.orders.toLocaleString() }}</td>
                                <td class="py-2 pr-4 text-right font-semibold tabular-nums dark:text-white">@{{ row.formatted_sales }}</td>
                                <td class="py-2 text-right tabular-nums text-gray-500 dark:text-gray-400">@{{ row.formatted_aov }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-300 font-bold dark:border-gray-600">
                                <td class="py-2 pr-4 dark:text-white">@lang('admin::app.reporting.sales.index.total')</td>
                                <td class="py-2 pr-4 text-right tabular-nums dark:text-white">@{{ totalOrders.toLocaleString() }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums dark:text-white">@{{ totalSales }}</td>
                                <td class="py-2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>
            <template v-else>
                <p class="py-8 text-center text-sm text-gray-400">@{{ nodata() }}</p>
            </template>
        </div>
    </script>

    {{-- ════════════════ Top products ════════════════ --}}
    <script type="text/x-template" id="v-rep-top-products-template">
        <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-base font-semibold text-gray-600 dark:text-white">
                    @lang('admin::app.reporting.sales.index.top-products')
                </p>
                <a href="{{ route('admin.reporting.sales.view', ['type' => 'top-products']) }}"
                   class="cursor-pointer text-sm text-blue-600 transition-all hover:underline">
                    @lang('admin::app.reporting.sales.index.view-details')
                </a>
            </div>
            <template v-if="isLoading"><div class="shimmer h-40 w-full rounded"></div></template>
            <template v-else-if="report && report.statistics.products.length">
                <div class="grid gap-3">
                    <div v-for="(p, i) in report.statistics.products" :key="p.sku" class="grid">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate dark:text-white" :title="p.name">
                                <span class="text-gray-400">@{{ i + 1 }}.</span> @{{ p.name }}
                            </p>
                            <p class="shrink-0 text-sm font-semibold text-gray-600 dark:text-gray-300">@{{ p.formatted_revenue }}</p>
                        </div>
                        <div class="mt-1 flex items-center gap-3">
                            <div class="relative h-2 w-full rounded bg-slate-100 dark:bg-gray-800">
                                <div class="absolute left-0 h-2 rounded bg-blue-500"
                                     :style="{ width: (p.revenue / maxRev * 100) + '%' }"></div>
                            </div>
                            <p class="shrink-0 text-xs text-gray-400">@{{ p.qty }} @lang('admin::app.reporting.sales.index.qty')</p>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else><p class="py-8 text-center text-sm text-gray-400">@{{ nodata() }}</p></template>
        </div>
    </script>

    {{-- ════════════════ Customer location ════════════════ --}}
    <script type="text/x-template" id="v-rep-customer-location-template">
        <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-base font-semibold text-gray-600 dark:text-white">
                    @lang('admin::app.reporting.sales.index.customer-location')
                </p>
                <a href="{{ route('admin.reporting.sales.view', ['type' => 'customer-location']) }}"
                   class="cursor-pointer text-sm text-blue-600 transition-all hover:underline">
                    @lang('admin::app.reporting.sales.index.view-details')
                </a>
            </div>
            <template v-if="isLoading"><div class="shimmer h-40 w-full rounded"></div></template>
            <template v-else-if="report && report.statistics.locations.length">
                <div class="grid gap-3">
                    <div v-for="(loc, i) in report.statistics.locations" :key="i" class="grid">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate dark:text-white">
                                @{{ loc.state }}
                                <span class="text-xs text-gray-400" v-if="loc.country !== '—'">(@{{ loc.country }})</span>
                            </p>
                            <p class="shrink-0 text-sm font-semibold text-gray-600 dark:text-gray-300">@{{ loc.formatted_sales }}</p>
                        </div>
                        <div class="mt-1 flex items-center gap-3">
                            <div class="relative h-2 w-full rounded bg-slate-100 dark:bg-gray-800">
                                <div class="absolute left-0 h-2 rounded bg-teal-500"
                                     :style="{ width: (loc.sales / maxSales * 100) + '%' }"></div>
                            </div>
                            <p class="shrink-0 text-xs text-gray-400">@{{ loc.orders }} @lang('admin::app.reporting.sales.index.orders')</p>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else><p class="py-8 text-center text-sm text-gray-400">@{{ nodata() }}</p></template>
        </div>
    </script>

    {{-- ════════════════ New vs returning ════════════════ --}}
    <script type="text/x-template" id="v-rep-new-returning-template">
        <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-600 dark:text-white">
                @lang('admin::app.reporting.sales.index.new-vs-returning')
            </p>
            <template v-if="isLoading"><div class="shimmer h-32 w-full rounded"></div></template>
            <template v-else-if="report">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg bg-blue-50 p-4 text-center dark:bg-blue-900/20">
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">@{{ report.statistics.new_customers.toLocaleString() }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('admin::app.reporting.sales.index.new-customers')</p>
                        <p class="text-xs text-gray-400">@{{ report.statistics.new_orders }} @lang('admin::app.reporting.sales.index.orders')</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-4 text-center dark:bg-emerald-900/20">
                        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">@{{ report.statistics.returning_customers.toLocaleString() }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('admin::app.reporting.sales.index.returning-customers')</p>
                        <p class="text-xs text-gray-400">@{{ report.statistics.returning_orders }} @lang('admin::app.reporting.sales.index.orders')</p>
                    </div>
                </div>
                <div class="mt-3 flex h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="bg-blue-500" :style="{ width: newPct + '%' }"></div>
                    <div class="bg-emerald-500" :style="{ width: (100 - newPct) + '%' }"></div>
                </div>
            </template>
        </div>
    </script>

    {{-- ════════════════ Sales by channel/store ════════════════ --}}
    <script type="text/x-template" id="v-rep-sales-by-channel-template">
        <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-600 dark:text-white">
                @lang('admin::app.reporting.sales.index.sales-by-channel')
            </p>
            <template v-if="isLoading"><div class="shimmer h-32 w-full rounded"></div></template>
            <template v-else-if="report && report.statistics.channels.length">
                <div class="grid gap-4">
                    <div v-for="ch in report.statistics.channels" :key="ch.channel" class="grid">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate dark:text-white">@{{ ch.channel }}</p>
                            <p class="shrink-0 text-sm font-semibold text-gray-600 dark:text-gray-300">@{{ ch.formatted_sales }}</p>
                        </div>
                        <div class="mt-1 flex items-center gap-3">
                            <div class="relative h-2 w-full rounded bg-slate-100 dark:bg-gray-800">
                                <div class="absolute left-0 h-2 rounded bg-indigo-500"
                                     :style="{ width: (ch.sales / maxSales * 100) + '%' }"></div>
                            </div>
                            <p class="shrink-0 text-xs text-gray-400">@{{ ch.orders }} @lang('admin::app.reporting.sales.index.orders')</p>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else><p class="py-8 text-center text-sm text-gray-400">@{{ nodata() }}</p></template>
        </div>
    </script>

    {{-- ════════════════ Sales by category ════════════════ --}}
    <script type="text/x-template" id="v-rep-sales-by-category-template">
        <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-base font-semibold text-gray-600 dark:text-white">@lang('admin::app.reporting.sales.index.sales-by-category')</p>
                <a href="{{ route('admin.reporting.sales.view', ['type' => 'sales-by-category']) }}"
                   class="cursor-pointer text-sm text-blue-600 transition-all hover:underline">@lang('admin::app.reporting.sales.index.view-details')</a>
            </div>
            <template v-if="isLoading"><div class="shimmer h-40 w-full rounded"></div></template>
            <template v-else-if="report && report.statistics.categories.length">
                <div class="grid gap-3">
                    <div v-for="(c, i) in report.statistics.categories" :key="i" class="grid">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate dark:text-white" :title="c.label">@{{ c.label }}</p>
                            <p class="shrink-0 text-sm font-semibold text-gray-600 dark:text-gray-300">@{{ c.formatted_revenue }}</p>
                        </div>
                        <div class="relative mt-1 h-2 w-full rounded bg-slate-100 dark:bg-gray-800">
                            <div class="absolute left-0 h-2 rounded bg-amber-500" :style="{ width: (c.revenue / maxRev * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else><p class="py-8 text-center text-sm text-gray-400">@{{ nodata() }}</p></template>
        </div>
    </script>

    {{-- ════════════════ Sales by brand ════════════════ --}}
    <script type="text/x-template" id="v-rep-sales-by-brand-template">
        <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-base font-semibold text-gray-600 dark:text-white">@lang('admin::app.reporting.sales.index.sales-by-brand')</p>
                <a href="{{ route('admin.reporting.sales.view', ['type' => 'sales-by-brand']) }}"
                   class="cursor-pointer text-sm text-blue-600 transition-all hover:underline">@lang('admin::app.reporting.sales.index.view-details')</a>
            </div>
            <template v-if="isLoading"><div class="shimmer h-40 w-full rounded"></div></template>
            <template v-else-if="report && report.statistics.brands.length">
                <div class="grid gap-3">
                    <div v-for="(b, i) in report.statistics.brands" :key="i" class="grid">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate dark:text-white" :title="b.label">@{{ b.label }}</p>
                            <p class="shrink-0 text-sm font-semibold text-gray-600 dark:text-gray-300">@{{ b.formatted_revenue }}</p>
                        </div>
                        <div class="relative mt-1 h-2 w-full rounded bg-slate-100 dark:bg-gray-800">
                            <div class="absolute left-0 h-2 rounded bg-rose-500" :style="{ width: (b.revenue / maxRev * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else><p class="py-8 text-center text-sm text-gray-400">@{{ nodata() }}</p></template>
        </div>
    </script>

    {{-- ════════════════ Sales heatmap (weekday x hour) ════════════════ --}}
    <script type="text/x-template" id="v-rep-sales-heatmap-template">
        <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-600 dark:text-white">
                @lang('admin::app.reporting.sales.index.sales-heatmap')
            </p>
            <template v-if="isLoading"><div class="shimmer h-40 w-full rounded"></div></template>
            <template v-else-if="report && report.statistics.cells.length">
                <div class="overflow-x-auto">
                    <table class="border-separate" style="border-spacing:2px;">
                        <thead>
                            <tr>
                                <th></th>
                                <th v-for="h in 24" :key="h" class="text-[9px] font-normal text-gray-400" style="width:18px;">
                                    @{{ (h - 1) % 6 === 0 ? (h - 1) : '' }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(dayLabel, d) in dayLabels" :key="d">
                                <td class="pr-1 text-right text-[10px] text-gray-500 dark:text-gray-400">@{{ dayLabel }}</td>
                                <td v-for="h in 24" :key="h"
                                    class="rounded"
                                    style="width:18px;height:18px;"
                                    :style="{ backgroundColor: cellColor(d, h - 1) }"
                                    :title="cellTitle(d, h - 1)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
            <template v-else><p class="py-8 text-center text-sm text-gray-400">@{{ nodata() }}</p></template>
        </div>
    </script>

    {{-- ════════════════ Component registrations ════════════════ --}}
    <script type="module">
        const REP_STATS_URL = "{{ $statsRoute }}";
        const REP_NODATA    = "@lang('admin::app.reporting.sales.index.no-data')";

        /* Each widget fetches one stats `type`, exposing report + isLoading,
           plus any extra computed/methods passed in. */
        const base = (type, templateId, extra = {}) => ({
            template: templateId,
            data() { return { report: null, isLoading: true }; },
            mounted() {
                this.getStats(window.fjSavedFilter ? window.fjSavedFilter() : {});
                this.$emitter.on('reporting-filter-updated', this.getStats);
            },
            methods: {
                nodata() { return REP_NODATA; },
                getStats(filters) {
                    this.isLoading = true;
                    const params = Object.assign({}, filters, { type });
                    this.$axios.get(REP_STATS_URL, { params })
                        .then(res => { this.report = res.data; this.isLoading = false; })
                        .catch(() => { this.isLoading = false; });
                },
                ...(extra.methods || {}),
            },
            computed: extra.computed || {},
        });

        app.component('v-rep-monthly-sales', base('monthly-sales', '#v-rep-monthly-sales-template', {
            computed: {
                totalOrders() { return (this.report?.statistics.rows || []).reduce((s, r) => s + r.orders, 0); },
                totalSales() {
                    const t = (this.report?.statistics.rows || []).reduce((s, r) => s + r.sales, 0);
                    return '¥' + Math.round(t).toLocaleString();
                },
            },
        }));

        app.component('v-rep-top-products', base('top-products', '#v-rep-top-products-template', {
            computed: { maxRev() { return window.repMaxRevenue(this.report?.statistics.products || [], 'revenue'); } },
        }));

        app.component('v-rep-customer-location', base('customer-location', '#v-rep-customer-location-template', {
            computed: { maxSales() { return window.repMaxRevenue(this.report?.statistics.locations || [], 'sales'); } },
        }));

        app.component('v-rep-new-returning', base('new-vs-returning', '#v-rep-new-returning-template', {
            computed: {
                newPct() {
                    const s = this.report?.statistics;
                    if (!s) return 50;
                    const total = s.new_customers + s.returning_customers;
                    return total ? Math.round(s.new_customers / total * 100) : 100;
                },
            },
        }));

        app.component('v-rep-sales-by-channel', base('sales-by-channel', '#v-rep-sales-by-channel-template', {
            computed: { maxSales() { return window.repMaxRevenue(this.report?.statistics.channels || [], 'sales'); } },
        }));

        app.component('v-rep-sales-by-category', base('sales-by-category', '#v-rep-sales-by-category-template', {
            computed: { maxRev() { return window.repMaxRevenue(this.report?.statistics.categories || [], 'revenue'); } },
        }));

        app.component('v-rep-sales-by-brand', base('sales-by-brand', '#v-rep-sales-by-brand-template', {
            computed: { maxRev() { return window.repMaxRevenue(this.report?.statistics.brands || [], 'revenue'); } },
        }));

        app.component('v-rep-sales-heatmap', base('sales-heatmap', '#v-rep-sales-heatmap-template', {
            computed: {
                dayLabels() { return ['日','月','火','水','木','金','土']; },
                grid() {
                    const g = {};
                    let max = 1;
                    (this.report?.statistics.cells || []).forEach(c => {
                        g[c.dow + '-' + c.hour] = c;
                        if (c.sales > max) max = c.sales;
                    });
                    g.__max = max;
                    return g;
                },
            },
            methods: {
                cellColor(d, h) {
                    const c = this.grid[d + '-' + h];
                    if (!c) return 'rgba(148,163,184,0.12)';
                    const ratio = Math.min(1, c.sales / this.grid.__max);
                    return `rgba(37,99,235,${0.12 + ratio * 0.78})`;
                },
                cellTitle(d, h) {
                    const c = this.grid[d + '-' + h];
                    const label = this.dayLabels[d] + ' ' + h + ':00';
                    if (!c) return label + ' — 0';
                    return `${label} — ${c.orders} 件 / ¥${Math.round(c.sales).toLocaleString()}`;
                },
            },
        }));
    </script>
@endPushOnce
