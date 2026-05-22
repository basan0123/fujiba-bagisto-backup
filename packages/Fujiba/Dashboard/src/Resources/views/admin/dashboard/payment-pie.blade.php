@php
    $initialJson = json_encode($paymentMethodsInitial, JSON_UNESCAPED_UNICODE);
    $endpointUrl = route('admin.fujiba.dashboard.payment_methods');
@endphp

<style>
    .fujiba-pie-card {
        background: #fff !important;
        color: #1f2937 !important;
        border-radius: 12px !important;
        padding: 18px 18px 20px !important;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 4px 12px -8px rgba(15,23,42,0.06) !important;
        border: 1px solid rgba(15,23,42,0.06);
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .dark .fujiba-pie-card {
        background: rgb(17,24,39) !important;
        color: #f3f4f6 !important;
        border-color: rgba(255,255,255,0.06);
    }
    .fujiba-pie-card > .fujiba-pie-grid,
    .fujiba-pie-card > .fujiba-pie-empty {
        flex: 1 1 auto;
    }
    .fujiba-pie-title {
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.12em !important;
        color: #1e3a8a !important;
        margin: 0 0 12px 0 !important;
    }
    .dark .fujiba-pie-title { color: #93c5fd !important; }
    .fujiba-pie-grid {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 18px;
    }
    .fujiba-pie-svg-wrap {
        position: relative;
        width: clamp(220px, 32vw, 360px);
        aspect-ratio: 1 / 1;
        height: auto;
        margin: 0 auto;
    }
    @media (max-width: 640px) {
        .fujiba-pie-card { padding: 14px !important; max-width: 100% !important; overflow: hidden !important; }
        .fujiba-pie-grid { gap: 12px; }
        .fujiba-pie-svg-wrap { width: min(72vw, 260px); }
        .fujiba-pie-center-num { font-size: 26px; }
        .fujiba-pie-legend-row { font-size: 12px; }
        .fujiba-pie-meta { font-size: 9px; margin-left: 18px; }
    }
    .fujiba-pie-svg {
        width: 100%; height: 100%;
        transform: rotate(-90deg);
    }
    .fujiba-pie-arc {
        fill: none;
        stroke-width: 36;
        transition: stroke-dashoffset 800ms cubic-bezier(0.4,0,0.2,1);
    }
    .fujiba-pie-track {
        fill: none;
        stroke: rgba(15,23,42,0.06);
        stroke-width: 36;
    }
    .dark .fujiba-pie-track { stroke: rgba(255,255,255,0.08); }
    .fujiba-pie-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .fujiba-pie-center-num {
        font-size: clamp(28px, 3vw, 40px);
        font-weight: 900;
        color: #111827;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    .dark .fujiba-pie-center-num { color: #f9fafb; }
    .fujiba-pie-center-lbl {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #6b7280;
        margin-top: 4px;
    }
    .dark .fujiba-pie-center-lbl { color: rgba(255,255,255,0.7); }
    .fujiba-pie-legend {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .fujiba-pie-legend-row {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
    }
    .fujiba-pie-swatch {
        width: 12px; height: 12px;
        border-radius: 3px;
        flex-shrink: 0;
        box-shadow: 0 0 6px currentColor;
    }
    .fujiba-pie-method {
        flex: 1;
        font-weight: 600;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dark .fujiba-pie-method { color: #f3f4f6; }
    .fujiba-pie-pct {
        font-weight: 800;
        color: #111827;
        font-variant-numeric: tabular-nums;
        min-width: 50px;
        text-align: right;
    }
    .dark .fujiba-pie-pct { color: #f9fafb; }
    .fujiba-pie-meta {
        font-size: 10px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-left: 22px;
        margin-top: -4px;
    }
    .dark .fujiba-pie-meta { color: rgba(255,255,255,0.6); }
    .fujiba-pie-empty {
        text-align: center;
        padding: 28px 12px;
        color: #6b7280;
    }
    .dark .fujiba-pie-empty { color: rgba(255,255,255,0.6); }
</style>

<div class="flex flex-col gap-2" id="fujiba-payment-pie-card">
    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
        @lang('fujiba::app.dashboard.payment_methods.title')
    </p>

    <v-fujiba-payment-pie
        endpoint="{{ $endpointUrl }}"
        :initial-data="{{ $initialJson }}"
    ></v-fujiba-payment-pie>
</div>

<script type="text/x-template" id="v-fujiba-payment-pie-template">
    <div class="fujiba-pie-card">
        <p class="fujiba-pie-title">
            @{{ data.channel_name }} · @lang('fujiba::app.dashboard.payment_methods.subtitle')
        </p>

        <template v-if="methods.length === 0">
            <div class="fujiba-pie-empty">
                @lang('fujiba::app.dashboard.payment_methods.empty_state')
            </div>
        </template>

        <template v-else>
            <div class="fujiba-pie-grid">
                <!-- Donut SVG -->
                <div class="fujiba-pie-svg-wrap">
                    <svg class="fujiba-pie-svg" viewBox="0 0 100 100" aria-hidden="true">
                        <circle class="fujiba-pie-track" cx="50" cy="50" r="32"
                                fill="none" stroke="rgba(255,255,255,0.10)" stroke-width="36"/>
                        <circle
                            v-for="(m, idx) in methods"
                            :key="m.method"
                            class="fujiba-pie-arc"
                            cx="50" cy="50" r="32"
                            fill="none"
                            stroke-width="36"
                            :stroke="colorFor(idx)"
                            :stroke-dasharray="arcDashArray(idx)"
                            :stroke-dashoffset="arcDashOffset(idx)"
                        />
                    </svg>
                    <div class="fujiba-pie-center">
                        <span class="fujiba-pie-center-num">@{{ totals.orders_count }}</span>
                        <span class="fujiba-pie-center-lbl">@lang('fujiba::app.dashboard.payment_methods.col_orders')</span>
                    </div>
                </div>

                <!-- Legend -->
                <div class="fujiba-pie-legend">
                    <div v-for="(m, idx) in methods" :key="m.method">
                        <div class="fujiba-pie-legend-row">
                            <span class="fujiba-pie-swatch" :style="{ background: colorFor(idx), color: colorFor(idx) }"></span>
                            <span class="fujiba-pie-method">@{{ m.method_title }}</span>
                            <span class="fujiba-pie-pct">@{{ m.orders_share }}%</span>
                        </div>
                        <div class="fujiba-pie-meta">
                            @{{ m.orders_count }} @lang('fujiba::app.dashboard.payment_methods.col_orders') · @{{ formatPrice(m.revenue) }}
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</script>

<script type="module">
    (function () {
        console.log('[Fujiba PaymentPie] script loaded, app=', typeof window.app);
        if (window.__fujibaPaymentPieRegistered) return;
        window.__fujibaPaymentPieRegistered = true;

        const PALETTE = [
            '#6366f1', // indigo
            '#ec4899', // pink
            '#f59e0b', // amber
            '#10b981', // emerald
            '#06b6d4', // cyan
            '#a855f7', // violet
            '#ef4444', // red
            '#3b82f6', // blue
        ];

        let paymentPieTries = 0;
        function registerPaymentPie() {
            paymentPieTries++;
            if (typeof window.app === 'undefined' || !window.app || !window.app.component) {
                if (paymentPieTries > 600) {
                    console.error('[Fujiba PaymentPie] gave up waiting for app');
                    return;
                }
                return requestAnimationFrame(registerPaymentPie);
            }
            console.log('[Fujiba PaymentPie] registering after', paymentPieTries, 'tries');
            const ppTplEl = document.getElementById('v-fujiba-payment-pie-template');
            const ppTplStr = ppTplEl ? ppTplEl.innerHTML : '';
            console.log('[Fujiba PaymentPie] template length:', ppTplStr.length);
            window.app.component('v-fujiba-payment-pie', {
            template: ppTplStr,

            props: {
                endpoint:    { type: String, required: true },
                initialData: { type: Object, required: true },
            },

            data() {
                return {
                    data: this.initialData,
                };
            },

            computed: {
                methods() { return this.data?.payment_methods?.methods ?? []; },
                totals()  { return this.data?.payment_methods?.totals  ?? { orders_count: 0, revenue: 0 }; },
                circumference() { return 2 * Math.PI * 32; },  // r=32 → ~201
            },

            mounted() {
                this.$emitter.on('reporting-filter-updated', this.onFilterChange);
            },

            methods: {
                colorFor(idx) { return PALETTE[idx % PALETTE.length]; },

                arcDashArray(idx) {
                    const m = this.methods[idx];
                    if (! m) return '0 1000';
                    const share = (m.orders_share || 0) / 100;
                    const arcLen = this.circumference * share;
                    return arcLen + ' ' + (this.circumference - arcLen);
                },

                arcDashOffset(idx) {
                    // Sum of preceding shares — places this arc at the right angle
                    let priorPct = 0;
                    for (let i = 0; i < idx; i++) {
                        priorPct += (this.methods[i]?.orders_share || 0) / 100;
                    }
                    return -1 * this.circumference * priorPct;
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
                        console.error('Fujiba payment pie fetch failed', e);
                    }
                },

                formatPrice(amount) {
                    const abs    = Math.abs(Number(amount) || 0);
                    const sign   = amount < 0 ? '-' : '';
                    const locale = "{{ app()->getLocale() }}";
                    if (abs < 10000) return sign + '¥' + Math.round(abs).toLocaleString();
                    const oneDec = (n) => {
                        const r = Math.round(n * 10) / 10;
                        if (r === Math.trunc(r)) return Math.trunc(r).toLocaleString();
                        return r.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                    };
                    if (locale === 'ja') {
                        if (abs >= 100000000) return sign + '¥' + oneDec(abs / 100000000) + '億';
                        return sign + '¥' + oneDec(abs / 10000) + '万';
                    }
                    if (abs >= 1000000) return sign + '¥' + oneDec(abs / 1000000) + 'M';
                    return sign + '¥' + oneDec(abs / 1000) + 'K';
                },
            },
            });
        }

        registerPaymentPie();
    })();
</script>
