@php
    $initialJson = json_encode($salesByStateInitial, JSON_UNESCAPED_UNICODE);
    $endpointUrl = route('admin.fujiba.dashboard.sales_by_state');
@endphp

<style>
    .fujiba-sbs-card {
        background: linear-gradient(135deg,#0f172a 0%,#1e293b 100%) !important;
        color: #fff !important;
        border-radius: 16px !important;
        padding: 18px !important;
        box-shadow: 0 6px 20px -8px rgba(99,102,241,0.45),
                    0 2px 4px rgba(15,23,42,0.10) !important;
    }
    .fujiba-sbs-title {
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.12em !important;
        color: rgba(255,255,255,0.92) !important;
        margin: 0 0 4px 0 !important;
    }
    .fujiba-sbs-subtitle {
        font-size: 11px;
        color: rgba(255,255,255,0.55);
        margin: 0 0 14px 0;
    }
    .fujiba-sbs-totals {
        display: flex; gap: 18px; margin-bottom: 14px;
        font-variant-numeric: tabular-nums;
    }
    .fujiba-sbs-total-block { display: flex; flex-direction: column; }
    .fujiba-sbs-total-label {
        font-size: 10px;
        color: rgba(255,255,255,0.55);
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .fujiba-sbs-total-value {
        font-size: 18px; font-weight: 800; color: #fff;
    }
    .fujiba-sbs-list {
        display: flex; flex-direction: column; gap: 6px;
        max-height: 420px; overflow-y: auto;
        padding-right: 4px;
    }
    .fujiba-sbs-list::-webkit-scrollbar { width: 6px; }
    .fujiba-sbs-list::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.15); border-radius: 3px;
    }
    .fujiba-sbs-row {
        display: grid;
        grid-template-columns: 22px minmax(0, 1.2fr) minmax(120px, 2fr) auto;
        gap: 10px;
        align-items: center;
        padding: 7px 8px;
        border-radius: 6px;
        transition: background 150ms ease;
    }
    .fujiba-sbs-row:hover { background: rgba(255,255,255,0.06); }
    .fujiba-sbs-rank {
        font-size: 11px; font-weight: 700;
        color: rgba(255,255,255,0.5);
        text-align: center;
        font-variant-numeric: tabular-nums;
    }
    .fujiba-sbs-name {
        font-size: 13px; font-weight: 600;
        color: #fff;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .fujiba-sbs-name-orders {
        font-size: 10px; font-weight: 500;
        color: rgba(255,255,255,0.5);
        margin-left: 6px;
    }
    .fujiba-sbs-bar-wrap {
        position: relative;
        height: 10px;
        background: rgba(255,255,255,0.08);
        border-radius: 5px;
        overflow: hidden;
    }
    .fujiba-sbs-bar {
        height: 100%;
        background: linear-gradient(90deg,#3b82f6 0%,#8b5cf6 60%,#ec4899 100%);
        transition: width 600ms cubic-bezier(0.4,0,0.2,1);
        border-radius: 5px;
    }
    .fujiba-sbs-amount {
        font-size: 13px; font-weight: 800;
        color: #fff; text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .fujiba-sbs-empty {
        text-align: center;
        padding: 28px 12px;
        color: rgba(255,255,255,0.6);
    }
    .fujiba-sbs-unmapped {
        margin-top: 10px;
        padding: 8px 10px;
        background: rgba(255,255,255,0.04);
        border-radius: 6px;
        font-size: 11px;
        color: rgba(255,255,255,0.55);
        display: flex; justify-content: space-between;
        font-variant-numeric: tabular-nums;
    }
    @media (max-width: 640px) {
        .fujiba-sbs-card { padding: 14px !important; max-width: 100% !important; overflow: hidden !important; }
        .fujiba-sbs-row { grid-template-columns: 18px minmax(0, 1.2fr) minmax(60px, 1.5fr) auto !important; gap: 6px !important; padding: 6px 4px !important; }
        .fujiba-sbs-name { font-size: 11px !important; }
        .fujiba-sbs-amount { font-size: 11px !important; }
        .fujiba-sbs-summary { gap: 8px !important; }
    }
</style>

<div class="flex flex-col gap-2" id="fujiba-sales-by-state-card">
    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
        @lang('fujiba::app.dashboard.sales_by_state.title')
    </p>

    <v-fujiba-sales-by-state
        endpoint="{{ $endpointUrl }}"
        :initial-data="{{ $initialJson }}"
    ></v-fujiba-sales-by-state>
</div>

<script type="text/x-template" id="v-fujiba-sales-by-state-template">
    <div class="fujiba-sbs-card">
        <p class="fujiba-sbs-title">
            @{{ data.channel_name }} · @lang('fujiba::app.dashboard.sales_by_state.subtitle')
        </p>
        <p class="fujiba-sbs-subtitle">
            @lang('fujiba::app.dashboard.sales_by_state.caption')
        </p>

        <template v-if="rows.length === 0">
            <div class="fujiba-sbs-empty">
                @lang('fujiba::app.dashboard.sales_by_state.empty_state')
            </div>
        </template>

        <template v-else>
            <div class="fujiba-sbs-totals">
                <div class="fujiba-sbs-total-block">
                    <span class="fujiba-sbs-total-label">
                        @lang('fujiba::app.dashboard.sales_by_state.total_revenue')
                    </span>
                    <span class="fujiba-sbs-total-value">
                        @{{ formatPrice(totalRevenue) }}
                    </span>
                </div>
                <div class="fujiba-sbs-total-block">
                    <span class="fujiba-sbs-total-label">
                        @lang('fujiba::app.dashboard.sales_by_state.total_orders')
                    </span>
                    <span class="fujiba-sbs-total-value">
                        @{{ totalOrders.toLocaleString() }}
                    </span>
                </div>
                <div class="fujiba-sbs-total-block">
                    <span class="fujiba-sbs-total-label">
                        @lang('fujiba::app.dashboard.sales_by_state.states_count')
                    </span>
                    <span class="fujiba-sbs-total-value">
                        @{{ rows.length }}
                    </span>
                </div>
            </div>

            <div class="fujiba-sbs-list">
                <div v-for="(row, idx) in rows" :key="row.code" class="fujiba-sbs-row">
                    <span class="fujiba-sbs-rank">@{{ idx + 1 }}</span>
                    <span class="fujiba-sbs-name">
                        @{{ stateLabel(row) }}
                        <span class="fujiba-sbs-name-orders">
                            @{{ row.orders }} @lang('fujiba::app.dashboard.sales_by_state.orders_short')
                        </span>
                    </span>
                    <span class="fujiba-sbs-bar-wrap">
                        <span class="fujiba-sbs-bar"
                              :style="{ width: barWidth(row.revenue) + '%' }"></span>
                    </span>
                    <span class="fujiba-sbs-amount">@{{ formatPrice(row.revenue) }}</span>
                </div>
            </div>

            <div v-if="unmappedRevenue > 0" class="fujiba-sbs-unmapped">
                <span>@lang('fujiba::app.dashboard.sales_by_state.unmapped')</span>
                <span>
                    @{{ formatPrice(unmappedRevenue) }}
                    · @{{ unmappedOrders }} @lang('fujiba::app.dashboard.sales_by_state.orders_short')
                </span>
            </div>
        </template>
    </div>
</script>

<script type="module">
    (function () {
        console.log('[Fujiba SalesByState] script loaded, app=', typeof window.app);
        if (window.__fujibaSalesByStateRegistered) return;
        window.__fujibaSalesByStateRegistered = true;

        let sbsTries = 0;
        function registerSalesByState() {
            sbsTries++;
            if (typeof window.app === 'undefined' || !window.app || !window.app.component) {
                if (sbsTries > 600) {
                    console.error('[Fujiba SalesByState] gave up waiting for app');
                    return;
                }
                return requestAnimationFrame(registerSalesByState);
            }
            console.log('[Fujiba SalesByState] registering after', sbsTries, 'tries');
            const sbsTplEl = document.getElementById('v-fujiba-sales-by-state-template');
            const sbsTplStr = sbsTplEl ? sbsTplEl.innerHTML : '';
            console.log('[Fujiba SalesByState] template length:', sbsTplStr.length);
            window.app.component('v-fujiba-sales-by-state', {
            template: sbsTplStr,

            props: {
                endpoint:    { type: String, required: true },
                initialData: { type: Object, required: true },
            },

            data() {
                return { data: this.initialData };
            },

            computed: {
                rows()             { return this.data?.sales_by_state?.rows ?? []; },
                maxRevenue()       { return this.data?.sales_by_state?.max_revenue ?? 0; },
                totalRevenue()     { return this.data?.sales_by_state?.total_revenue ?? 0; },
                totalOrders()      { return this.data?.sales_by_state?.total_orders ?? 0; },
                unmappedRevenue()  { return this.data?.sales_by_state?.unmapped_revenue ?? 0; },
                unmappedOrders()   { return this.data?.sales_by_state?.unmapped_orders ?? 0; },
                locale()           { return "{{ app()->getLocale() }}"; },
            },

            mounted() {
                this.$emitter.on('reporting-filter-updated', this.onFilterChange);
            },

            methods: {
                stateLabel(row) {
                    return this.locale === 'ja' ? row.name_ja : row.name_en;
                },

                barWidth(revenue) {
                    if (this.maxRevenue <= 0) return 0;
                    return Math.min(100, (revenue / this.maxRevenue) * 100);
                },

                async onFilterChange(filters) {
                    try {
                        const params = {
                            channel: filters.channel ?? '',
                            start:   filters.start ?? '',
                            end:     filters.end ?? '',
                            mode:    filters.mode ?? 'orders',
                        };
                        const resp = await this.$axios.get(this.endpoint, { params });
                        this.data = resp.data;
                    } catch (e) {
                        console.error('Fujiba sales-by-state fetch failed', e);
                    }
                },

                formatPrice(amount) {
                    const abs    = Math.abs(Number(amount) || 0);
                    const sign   = amount < 0 ? '-' : '';
                    if (abs < 10000) return sign + '¥' + Math.round(abs).toLocaleString();
                    const oneDec = (n) => {
                        const r = Math.round(n * 10) / 10;
                        if (r === Math.trunc(r)) return Math.trunc(r).toLocaleString();
                        return r.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                    };
                    if (this.locale === 'ja') {
                        if (abs >= 100000000) return sign + '¥' + oneDec(abs / 100000000) + '億';
                        return sign + '¥' + oneDec(abs / 10000) + '万';
                    }
                    if (abs >= 1000000) return sign + '¥' + oneDec(abs / 1000000) + 'M';
                    return sign + '¥' + oneDec(abs / 1000) + 'K';
                },
            },
            });
        }

        registerSalesByState();
    })();
</script>
