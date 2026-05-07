<x-admin::layouts>
    <x-slot:title>{{ __('orders-admin::app.estimate.page_title') }}</x-slot:title>

    @push('styles')
        <style>
            .est-card { background:#fff; border-radius:8px; padding:14px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
            .dark .est-card { background:#0f172a; }
            .est-h { font-size:14px; font-weight:700; margin-bottom:10px; color:#374151; }
            .dark .est-h { color:#e5e7eb; }
            .est-input, .est-select { padding:6px 8px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; background:#fff; }
            .dark .est-input, .dark .est-select { background:#1f2937; border-color:#374151; color:#e5e7eb; }
            .est-table { width:100%; border-collapse:collapse; }
            .est-table th { background:#f9fafb; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; padding:8px 10px; text-align:left; border-bottom:1px solid #e5e7eb; }
            .dark .est-table th { background:rgba(255,255,255,.03); border-bottom-color:#1f2937; color:#9ca3af; }
            .est-table td { padding:8px 10px; border-bottom:1px solid #f3f4f6; vertical-align:top; font-size:13px; }
            .dark .est-table td { border-bottom-color:#1f2937; }
            .est-table tr:hover td { background:rgba(59,130,246,.04); }
            .est-pill { font-size:10px; font-weight:600; padding:2px 6px; border-radius:999px; }
            .est-pill-warn { background:#fef3c7; color:#92400e; }
            .est-pill-bad { background:#fee2e2; color:#b91c1c; }
            .est-pill-ok { background:#d1fae5; color:#065f46; }
            .est-pill-info { background:#dbeafe; color:#1e40af; }
            .est-img-sm { width:32px; height:32px; object-fit:cover; border-radius:4px; background:#f3f4f6; display:inline-block; vertical-align:middle; margin-right:4px; }
            .est-num { text-align:right; font-variant-numeric: tabular-nums; }
            .est-btn { padding:5px 10px; border:1px solid #d1d5db; border-radius:6px; background:#fff; font-size:12px; cursor:pointer; }
            .dark .est-btn { background:#1f2937; border-color:#374151; color:#e5e7eb; }
            .est-btn-primary { background:#3b82f6; color:#fff; border-color:#3b82f6; }
            .est-empty { color:#6b7280; font-size:13px; padding:30px; text-align:center; }
            .est-stat { background:#fff; border-radius:8px; padding:12px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
            .dark .est-stat { background:#0f172a; }
            .est-stat .v { font-size:20px; font-weight:700; }
            .est-stat .k { font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; }
            .est-row-expand { background:#f9fafb; }
            .dark .est-row-expand { background:rgba(255,255,255,.02); }
            .est-page { padding:5px 10px; border:1px solid #d1d5db; border-radius:6px; background:#fff; font-size:12px; cursor:pointer; }
            .est-page.is-active { background:#3b82f6; color:#fff; border-color:#3b82f6; }
            .est-page[disabled] { opacity:.4; cursor:not-allowed; }
            .dark .est-page { background:#1f2937; border-color:#374151; color:#e5e7eb; }
        </style>
    @endpush

    @php
        $estLocale = app()->getLocale();
        $estI18n = [
            'guest'                  => __('orders-admin::app.estimate.guest'),
            'cost_missing'           => __('orders-admin::app.estimate.cost_missing'),
            'cannot_calc'            => __('orders-admin::app.estimate.cannot_calc'),
            'pill_active'            => __('orders-admin::app.estimate.pill_active'),
            'pill_abandoned'         => __('orders-admin::app.estimate.pill_abandoned'),
            'pill_repeat_order'      => __('orders-admin::app.estimate.pill_repeat_order'),
            'pill_coupon'            => __('orders-admin::app.estimate.pill_coupon'),
            'last_order'             => __('orders-admin::app.estimate.last_order'),
            'updated'                => __('orders-admin::app.estimate.updated'),
            'created'                => __('orders-admin::app.estimate.created'),
            'expand_open'            => __('orders-admin::app.estimate.expand_open'),
            'expand_close'           => __('orders-admin::app.estimate.expand_close'),
            'cost_unset'             => __('orders-admin::app.estimate.cost_unset'),
            'missing_cost_label'     => __('orders-admin::app.estimate.missing_cost_label'),
            'missing_cost_title_prefix' => __('orders-admin::app.estimate.missing_cost_title_prefix'),
            'last_order_title_prefix'   => __('orders-admin::app.estimate.last_order_title_prefix'),
            'count_unit'             => __('orders-admin::app.estimate.count_unit'),
            'loading'                => __('orders-admin::app.estimate.loading'),
            'empty_state'            => __('orders-admin::app.estimate.empty_state'),
            'cart_details'           => __('orders-admin::app.estimate.cart_details'),
            'col_item'               => __('orders-admin::app.estimate.col_item'),
            'col_qty'                => __('orders-admin::app.estimate.col_qty'),
            'col_price'              => __('orders-admin::app.estimate.col_price'),
            'col_cost'               => __('orders-admin::app.estimate.col_cost'),
            'col_line_subtotal'      => __('orders-admin::app.estimate.col_line_subtotal'),
            'col_profit_per'         => __('orders-admin::app.estimate.col_profit_per'),
        ];
    @endphp

    <div id="estimate-app">
        <v-fujiba-estimate
            :channels="{{ json_encode($channels) }}"
            :i18n="{{ json_encode($estI18n) }}"
            locale="{{ $estLocale }}"
        ></v-fujiba-estimate>
    </div>

    @pushOnce('scripts')
    <script type="text/x-template" id="tpl-fujiba-estimate">
        <div>
            <div class="flex justify-between items-center mb-3 flex-wrap gap-2">
                <div>
                    <h1 class="text-xl font-bold">{{ __('orders-admin::app.estimate.page_title') }}</h1>
                    <p class="text-xs text-gray-500">{{ __('orders-admin::app.estimate.subtitle') }}</p>
                </div>
                <a href="{{ url(config('app.admin_url')) }}/sales/orders?view=advanced" class="est-btn">{{ __('orders-admin::app.estimate.back_to_orders') }}</a>
            </div>

            {{-- Filters --}}
            <div class="est-card mb-3">
                <div class="grid gap-2" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('orders-admin::app.estimate.filter_channel') }}</label>
                        <select class="est-select w-full" v-model="filters.channel_id" @change="loadPage(1)">
                            <option :value="null">{{ __('orders-admin::app.estimate.all_stores') }}</option>
                            <option v-for="c in channels" :key="c.id" :value="c.id">@{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('orders-admin::app.estimate.filter_status') }}</label>
                        <select class="est-select w-full" v-model="filters.status" @change="loadPage(1)">
                            <option value="all">{{ __('orders-admin::app.estimate.status_all') }}</option>
                            <option value="active">{{ __('orders-admin::app.estimate.status_active') }}</option>
                            <option value="inactive">{{ __('orders-admin::app.estimate.status_inactive') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('orders-admin::app.estimate.filter_period') }}</label>
                        <select class="est-select w-full" v-model.number="filters.days" @change="loadPage(1)">
                            <option :value="7">{{ __('orders-admin::app.estimate.period_7') }}</option>
                            <option :value="30">{{ __('orders-admin::app.estimate.period_30') }}</option>
                            <option :value="90">{{ __('orders-admin::app.estimate.period_90') }}</option>
                            <option :value="0">{{ __('orders-admin::app.estimate.period_all') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('orders-admin::app.estimate.min_items') }}</label>
                        <input type="number" min="1" class="est-input w-full" v-model.number="filters.min_items" @change="loadPage(1)" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('orders-admin::app.estimate.search_customer') }}</label>
                        <input class="est-input w-full" placeholder="{{ __('orders-admin::app.estimate.search_placeholder') }}" v-model="filters.search" @keyup.enter="loadPage(1)" />
                    </div>
                </div>
            </div>

            {{-- Summary stats --}}
            <div class="grid gap-2 mb-3" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
                <div class="est-stat"><div class="k">{{ __('orders-admin::app.estimate.stat_cart_count') }}</div><div class="v">@{{ summary.cart_count }}</div></div>
                <div class="est-stat"><div class="k">{{ __('orders-admin::app.estimate.stat_total_items') }}</div><div class="v">@{{ summary.total_items }}</div></div>
                <div class="est-stat"><div class="k">{{ __('orders-admin::app.estimate.stat_subtotal_sum') }}</div><div class="v">¥@{{ formatYen(summary.sub_total_sum) }}</div></div>
                <div class="est-stat"><div class="k">{{ __('orders-admin::app.estimate.stat_profit_sum') }}</div>
                    <div class="v" :class="summary.profit_sum >= 0 ? 'text-green-600' : 'text-red-600'">¥@{{ formatYen(summary.profit_sum) }}</div>
                </div>
                <div class="est-stat"><div class="k">{{ __('orders-admin::app.estimate.stat_carts_missing_cost') }}</div>
                    <div class="v" :class="summary.carts_missing_cost > 0 ? 'text-amber-600' : ''">@{{ summary.carts_missing_cost }}</div>
                </div>
                <div class="est-stat"><div class="k">{{ __('orders-admin::app.estimate.stat_unique_missing_products') }}</div>
                    <div class="v" :class="summary.unique_missing_products > 0 ? 'text-amber-600' : ''">@{{ summary.unique_missing_products }}</div>
                </div>
            </div>

            {{-- Table --}}
            <div class="est-card" style="padding:0;overflow-x:auto">
                <table class="est-table">
                    <thead>
                        <tr>
                            <th style="width:30px"></th>
                            <th>{{ __('orders-admin::app.estimate.col_customer') }}</th>
                            <th>{{ __('orders-admin::app.estimate.col_store') }}</th>
                            <th class="est-num">{{ __('orders-admin::app.estimate.col_items') }}</th>
                            <th class="est-num">{{ __('orders-admin::app.estimate.col_subtotal') }}</th>
                            <th class="est-num">{{ __('orders-admin::app.estimate.col_shipping') }}</th>
                            <th class="est-num">{{ __('orders-admin::app.estimate.col_cost_total') }}</th>
                            <th class="est-num">{{ __('orders-admin::app.estimate.col_profit') }}</th>
                            <th>{{ __('orders-admin::app.estimate.col_status') }}</th>
                            <th>{{ __('orders-admin::app.estimate.col_last_activity') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="10" class="est-empty">@{{ i18n.loading }}</td></tr>
                        <tr v-else-if="!records.length"><td colspan="10" class="est-empty">@{{ i18n.empty_state }}</td></tr>
                        <template v-else v-for="rec in records" :key="rec.cart_id">
                            <tr>
                                <td>
                                    <button class="est-btn" @click="toggle(rec.cart_id)" :title="expanded[rec.cart_id] ? i18n.expand_close : i18n.expand_open">
                                        @{{ expanded[rec.cart_id] ? '−' : '+' }}
                                    </button>
                                </td>
                                <td>
                                    <div class="font-semibold">@{{ rec.customer.name || rec.customer.email || i18n.guest }}</div>
                                    <div class="text-xs text-gray-500">@{{ rec.customer.email || '—' }}</div>
                                    <div class="text-xs text-gray-400">cart #@{{ rec.cart_id }}</div>
                                </td>
                                <td>@{{ rec.channel_name }}</td>
                                <td class="est-num">@{{ rec.items_count }}</td>
                                <td class="est-num">¥@{{ formatYen(rec.sub_total) }}</td>
                                <td class="est-num">¥@{{ formatYen(rec.shipping_amount) }}</td>
                                <td class="est-num">
                                    <span v-if="rec.cost_total !== null">¥@{{ formatYen(rec.cost_total) }}</span>
                                    <span v-else class="est-pill est-pill-warn">@{{ i18n.cost_missing }}</span>
                                </td>
                                <td class="est-num">
                                    <span v-if="rec.profit !== null"
                                          class="font-bold"
                                          :class="rec.profit >= 0 ? 'text-green-600' : 'text-red-600'">
                                        ¥@{{ formatYen(rec.profit) }}
                                    </span>
                                    <span v-else class="est-pill est-pill-bad" :title="missingTitle(rec)">@{{ i18n.cannot_calc }}</span>
                                </td>
                                <td>
                                    <span v-if="rec.is_active" class="est-pill est-pill-info">@{{ i18n.pill_active }}</span>
                                    <span v-else class="est-pill est-pill-warn">@{{ i18n.pill_abandoned }}</span>
                                    <span v-if="rec.has_prior_order" class="est-pill est-pill-ok ml-1" :title="i18n.last_order_title_prefix + ' ' + formatDate(rec.last_order_at)">@{{ i18n.pill_repeat_order }}</span>
                                    <span v-if="rec.coupon_code" class="est-pill est-pill-ok ml-1">@{{ i18n.pill_coupon }}</span>
                                </td>
                                <td class="text-xs">
                                    <div>@{{ i18n.updated }}: @{{ formatDate(rec.updated_at) }}</div>
                                    <div class="text-gray-400">@{{ i18n.created }}: @{{ formatDate(rec.created_at) }}</div>
                                    <div v-if="rec.has_prior_order" class="text-gray-400">@{{ i18n.last_order }}: @{{ formatDate(rec.last_order_at) }}</div>
                                </td>
                            </tr>
                            <tr v-if="expanded[rec.cart_id]" class="est-row-expand">
                                <td></td>
                                <td colspan="9">
                                    <div class="text-xs font-semibold mb-2">@{{ i18n.cart_details }}</div>
                                    <table class="est-table" style="font-size:12px">
                                        <thead><tr>
                                            <th>@{{ i18n.col_item }}</th><th class="est-num">@{{ i18n.col_qty }}</th>
                                            <th class="est-num">@{{ i18n.col_price }}</th><th class="est-num">@{{ i18n.col_cost }}</th>
                                            <th class="est-num">@{{ i18n.col_line_subtotal }}</th><th class="est-num">@{{ i18n.col_profit_per }}</th>
                                        </tr></thead>
                                        <tbody>
                                            <tr v-for="it in rec.items" :key="it.product_id + '_' + it.sku">
                                                <td>
                                                    <img v-if="it.image" :src="it.image" class="est-img-sm" />
                                                    <span class="font-semibold">@{{ it.name }}</span>
                                                    <div class="text-xs text-gray-500">@{{ it.sku }}</div>
                                                </td>
                                                <td class="est-num">@{{ it.qty }}</td>
                                                <td class="est-num">¥@{{ formatYen(it.price) }}</td>
                                                <td class="est-num">
                                                    <span v-if="it.cost !== null">¥@{{ formatYen(it.cost) }}</span>
                                                    <span v-else class="est-pill est-pill-bad">@{{ i18n.cost_unset }}</span>
                                                </td>
                                                <td class="est-num">¥@{{ formatYen(it.total) }}</td>
                                                <td class="est-num">
                                                    <span v-if="it.cost !== null"
                                                          :class="(it.price - it.cost) >= 0 ? 'text-green-600' : 'text-red-600'">
                                                        ¥@{{ formatYen(it.price - it.cost) }}
                                                    </span>
                                                    <span v-else class="text-amber-600">—</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div v-if="rec.missing_cost_products.length" class="mt-2 text-xs text-amber-700">
                                        @{{ i18n.missing_cost_label }} <span v-for="(m,i) in rec.missing_cost_products" :key="m.product_id">
                                            <span v-if="i>0">, </span>
                                            <a :href="productEditUrl(m.product_id)" target="_blank" class="underline">@{{ m.name || m.sku }}</a>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div v-if="meta.last_page > 1" class="flex justify-center items-center gap-1 mt-3">
                <button class="est-page" :disabled="meta.current_page <= 1" @click="loadPage(meta.current_page - 1)">‹</button>
                <button v-for="p in pageList" :key="p" class="est-page"
                        :class="{'is-active': p === meta.current_page}"
                        @click="loadPage(p)">@{{ p }}</button>
                <button class="est-page" :disabled="meta.current_page >= meta.last_page" @click="loadPage(meta.current_page + 1)">›</button>
                <span class="text-xs text-gray-500 ml-2">@{{ meta.total }} @{{ i18n.count_unit }}</span>
            </div>
        </div>
    </script>

    <script type="module">
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrf && window.axios && !window.axios.__estCsrfWired) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
                window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
                window.axios.__estCsrfWired = true;
            }
        })();

        app.component('v-fujiba-estimate', {
            template: '#tpl-fujiba-estimate',
            props: {
                channels: { type: Array, default: () => [] },
                i18n:     { type: Object, default: () => ({}) },
                locale:   { type: String, default: 'ja' },
            },
            data() {
                return {
                    filters: { channel_id: null, status: 'all', days: 30, min_items: 1, search: '' },
                    records: [],
                    summary: { cart_count: 0, total_items: 0, sub_total_sum: 0, profit_sum: 0, carts_missing_cost: 0, unique_missing_products: 0 },
                    meta: { current_page: 1, per_page: 10, total: 0, last_page: 1 },
                    isLoading: false,
                    expanded: {},
                };
            },
            computed: {
                pageList() {
                    /* Compact paginator: window of ±3 around current page. */
                    const cur = this.meta.current_page, last = this.meta.last_page;
                    const start = Math.max(1, cur - 3);
                    const end   = Math.min(last, cur + 3);
                    const out = [];
                    for (let i = start; i <= end; i++) out.push(i);
                    return out;
                },
                dateLocale() {
                    return this.locale === 'ja' ? 'ja-JP' : (this.locale === 'en' ? 'en-US' : this.locale);
                },
            },
            mounted() { this.loadPage(1); },
            methods: {
                loadPage(p) {
                    this.isLoading = true;
                    const url = '{{ url(config("app.admin_url")) }}/api/estimate/abandoned';
                    this.$axios.get(url, { params: { ...this.filters, page: p, per_page: this.meta.per_page } })
                        .then(r => {
                            this.records = r.data.data || [];
                            this.meta    = r.data.meta || this.meta;
                            this.summary = r.data.summary || this.summary;
                            this.expanded = {};
                        })
                        .catch(e => {
                            console.error(e);
                            this.records = []; this.summary = { cart_count: 0, total_items: 0, sub_total_sum: 0, profit_sum: 0, carts_missing_cost: 0, unique_missing_products: 0 };
                        })
                        .finally(() => { this.isLoading = false; });
                },
                toggle(id) { this.expanded = { ...this.expanded, [id]: !this.expanded[id] }; },
                formatYen(n) { return new Intl.NumberFormat(this.dateLocale).format(Math.round(Number(n)||0)); },
                formatDate(s) {
                    if (!s) return '—';
                    const d = new Date(s.replace(' ', 'T'));
                    if (isNaN(d)) return s;
                    return d.toLocaleString(this.dateLocale, { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit' });
                },
                missingTitle(rec) {
                    return this.i18n.missing_cost_title_prefix + ' ' + (rec.missing_cost_products||[]).map(m => m.name || m.sku || ('#'+m.product_id)).join(' / ');
                },
                productEditUrl(pid) { return '{{ url(config("app.admin_url")) }}/catalog/products/edit/' + pid; },
            },
        });
    </script>
    @endPushOnce
</x-admin::layouts>
