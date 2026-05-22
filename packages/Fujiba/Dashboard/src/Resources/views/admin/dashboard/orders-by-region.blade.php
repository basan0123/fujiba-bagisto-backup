@php
    $initialJson = json_encode($ordersByRegionInitial, JSON_UNESCAPED_UNICODE);
    $endpointUrl = route('admin.fujiba.dashboard.orders_by_region');

    /*
     * Hex-tile layout for Japan's 47 prefectures.
     * Approximates geographic position; standard tilegram convention.
     * [code, col, row]
     */
    $hexLayout = [
        ['01', 11,  0], // Hokkaido
        ['02', 10,  2], // Aomori
        ['03', 11,  3], // Iwate
        ['05', 10,  3], // Akita
        ['04', 11,  4], // Miyagi
        ['06', 10,  4], // Yamagata
        ['07', 11,  5], // Fukushima
        ['08', 11,  6], // Ibaraki
        ['09', 10,  6], // Tochigi
        ['10',  9,  6], // Gunma
        ['11', 10,  7], // Saitama
        ['12', 11,  7], // Chiba
        ['13', 10,  8], // Tokyo
        ['14', 11,  8], // Kanagawa
        ['15',  9,  5], // Niigata
        ['16',  8,  5], // Toyama
        ['17',  7,  5], // Ishikawa
        ['18',  7,  6], // Fukui
        ['19',  9,  7], // Yamanashi
        ['20',  8,  6], // Nagano
        ['21',  7,  7], // Gifu
        ['22',  8,  8], // Shizuoka
        ['23',  7,  8], // Aichi
        ['24',  6,  8], // Mie
        ['25',  6,  7], // Shiga
        ['26',  5,  7], // Kyoto
        ['27',  5,  8], // Osaka
        ['28',  4,  7], // Hyogo
        ['29',  5,  9], // Nara
        ['30',  4,  8], // Wakayama
        ['31',  3,  7], // Tottori
        ['32',  2,  7], // Shimane
        ['33',  3,  8], // Okayama
        ['34',  2,  8], // Hiroshima
        ['35',  1,  8], // Yamaguchi
        ['37',  3,  9], // Kagawa
        ['36',  4,  9], // Tokushima
        ['38',  2,  9], // Ehime
        ['39',  3, 10], // Kochi
        ['40',  1,  9], // Fukuoka
        ['41',  0,  9], // Saga
        ['42',  0, 10], // Nagasaki
        ['43',  1, 10], // Kumamoto
        ['44',  2, 10], // Oita
        ['45',  1, 11], // Miyazaki
        ['46',  0, 11], // Kagoshima
        ['47',  0, 12], // Okinawa
    ];

    // Hex geometry: pointy-top, side=22
    $hexSide = 22;
    $hexW = sqrt(3) * $hexSide; // ≈38.1
    $hexH = 2 * $hexSide;       // 44
    $hexVStep = 0.75 * $hexH;   // 33

    // Build hex points string
    $hexPoints = '';
    for ($i = 0; $i < 6; $i++) {
        $angle = M_PI / 3 * $i + M_PI / 6;
        $x = round($hexSide * cos($angle), 2);
        $y = round($hexSide * sin($angle), 2);
        $hexPoints .= ($i === 0 ? '' : ' ') . $x . ',' . $y;
    }
@endphp

<style>
    .fujiba-region-card {
        background: #fff !important;
        color: #1f2937 !important;
        border-radius: 12px !important;
        padding: 18px !important;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 4px 12px -8px rgba(15,23,42,0.06) !important;
        border: 1px solid rgba(15,23,42,0.06);
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .dark .fujiba-region-card {
        background: rgb(17,24,39) !important;
        color: #f3f4f6 !important;
        border-color: rgba(255,255,255,0.06);
    }
    .fujiba-region-card > .fujiba-region-grid,
    .fujiba-region-card > .fujiba-region-empty {
        flex: 1 1 auto;
    }
    .fujiba-region-title {
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.12em !important;
        color: #1e3a8a !important;
        margin: 0 0 12px 0 !important;
    }
    .dark .fujiba-region-title { color: #93c5fd !important; }
    .fujiba-region-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
        gap: 18px;
        align-items: start;
    }
    @media (max-width: 768px) {
        .fujiba-region-card { padding: 14px !important; max-width: 100% !important; overflow: hidden !important; }
        .fujiba-region-grid { grid-template-columns: 1fr; gap: 14px; }
        .fujiba-region-map { padding: 8px; }
        .fujiba-region-svg { width: 100% !important; max-width: 100% !important; }
        .fujiba-region-hex-label { font-size: 8px !important; }
        .fujiba-region-list { max-height: 320px; overflow-y: auto; }
        .fujiba-region-row { grid-template-columns: 14px 1fr 60px 38px !important; gap: 6px !important; }
        .fujiba-region-row-name { font-size: 12px !important; }
        .fujiba-region-row-num { font-size: 12px !important; }
    }
    .fujiba-region-map {
        position: relative;
        width: 100%;
        background: rgba(15,23,42,0.03);
        border-radius: 12px;
        padding: 12px;
    }
    .dark .fujiba-region-map { background: rgba(255,255,255,0.03); }
    .fujiba-region-svg { width: 100%; height: auto; display: block; }
    .fujiba-region-hex {
        stroke: rgba(15,23,42,0.25);
        stroke-width: 1;
        cursor: pointer;
        transition: transform 150ms ease, filter 150ms ease;
        transform-origin: center;
        transform-box: fill-box;
    }
    .dark .fujiba-region-hex { stroke: rgba(15,23,42,0.6); }
    .fujiba-region-hex:hover {
        filter: brightness(1.1);
    }
    .dark .fujiba-region-hex:hover { filter: brightness(1.3); }
    .fujiba-region-hex.is-selected {
        stroke: #1e3a8a;
        stroke-width: 2;
        filter: brightness(1.05) drop-shadow(0 0 4px rgba(30,58,138,0.5));
    }
    .dark .fujiba-region-hex.is-selected {
        stroke: #fff;
        filter: brightness(1.25) drop-shadow(0 0 4px rgba(255,255,255,0.6));
    }
    .fujiba-region-hex-label {
        font-size: 9px;
        font-weight: 700;
        fill: #fff;
        text-anchor: middle;
        pointer-events: none;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        paint-order: stroke;
        stroke: rgba(15,23,42,0.45);
        stroke-width: 0.6px;
    }
    .dark .fujiba-region-hex-label {
        fill: rgba(255,255,255,0.85);
        stroke: none;
    }

    .fujiba-region-tooltip {
        position: absolute;
        background: rgba(15,23,42,0.96);
        color: #fff;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        pointer-events: none;
        box-shadow: 0 6px 16px rgba(0,0,0,0.4);
        z-index: 10;
        min-width: 140px;
        border: 1px solid rgba(255,255,255,0.12);
    }
    .fujiba-region-tooltip-name { font-weight: 700; margin-bottom: 4px; }
    .fujiba-region-tooltip-row {
        display: flex; justify-content: space-between; gap: 8px;
        font-variant-numeric: tabular-nums;
    }
    .fujiba-region-tooltip-row span:first-child { color: rgba(255,255,255,0.6); }

    /* Color legend strip */
    .fujiba-region-legend {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
        font-size: 10px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .dark .fujiba-region-legend { color: rgba(255,255,255,0.6); }
    .fujiba-region-legend-bar {
        flex: 1; height: 8px; border-radius: 4px;
        background: linear-gradient(90deg,#1e3a8a 0%,#3b82f6 35%,#8b5cf6 65%,#ec4899 100%);
    }

    /* Top-N ranked list */
    .fujiba-region-list {
        display: flex; flex-direction: column; gap: 8px;
    }
    .fujiba-region-list-header {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #4b5563;
        margin-bottom: 4px;
    }
    .dark .fujiba-region-list-header { color: rgba(255,255,255,0.7); }
    .fujiba-region-row {
        display: grid;
        grid-template-columns: 18px 1fr auto 56px;
        gap: 8px;
        align-items: center;
        padding: 6px 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: background 150ms ease;
    }
    .fujiba-region-row:hover { background: rgba(59,130,246,0.06); }
    .dark .fujiba-region-row:hover { background: rgba(255,255,255,0.06); }
    .fujiba-region-row.is-selected { background: rgba(99,102,241,0.12); }
    .dark .fujiba-region-row.is-selected { background: rgba(99,102,241,0.18); }
    .fujiba-region-rank {
        font-size: 11px; font-weight: 700;
        color: #9ca3af;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }
    .dark .fujiba-region-rank { color: rgba(255,255,255,0.5); }
    .fujiba-region-row-name {
        font-size: 13px; font-weight: 600;
        color: #1f2937;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .dark .fujiba-region-row-name { color: #f3f4f6; }
    .fujiba-region-row-bar-wrap {
        width: 80px; height: 6px;
        background: rgba(15,23,42,0.06);
        border-radius: 3px;
        overflow: hidden;
    }
    .dark .fujiba-region-row-bar-wrap { background: rgba(255,255,255,0.08); }
    .fujiba-region-row-bar {
        height: 100%;
        background: linear-gradient(90deg,#3b82f6,#ec4899);
        transition: width 600ms cubic-bezier(0.4,0,0.2,1);
    }
    .fujiba-region-row-num {
        font-size: 13px; font-weight: 800;
        color: #111827; text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .dark .fujiba-region-row-num { color: #f9fafb; }
    .fujiba-region-empty {
        text-align: center;
        padding: 28px 12px;
        color: #6b7280;
    }
    .dark .fujiba-region-empty { color: rgba(255,255,255,0.6); }
</style>

<div class="flex flex-col gap-2" id="fujiba-orders-by-region-card">
    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
        @lang('fujiba::app.dashboard.orders_by_region.title')
    </p>

    <v-fujiba-orders-by-region
        endpoint="{{ $endpointUrl }}"
        :initial-data="{{ $initialJson }}"
    ></v-fujiba-orders-by-region>
</div>

<script type="text/x-template" id="v-fujiba-orders-by-region-template">
    <div class="fujiba-region-card">
        <p class="fujiba-region-title">
            @{{ data.channel_name }} · @lang('fujiba::app.dashboard.orders_by_region.subtitle')
        </p>

        <template v-if="totalOrders === 0">
            <div class="fujiba-region-empty">
                @lang('fujiba::app.dashboard.orders_by_region.empty_state')
            </div>
        </template>

        <template v-else>
            <div class="fujiba-region-grid">
                <!-- Map -->
                <div class="fujiba-region-map" @mouseleave="hovered=null">
                    <svg class="fujiba-region-svg"
                         :viewBox="'0 0 ' + mapW + ' ' + mapH"
                         aria-hidden="true">
                        <g v-for="hex in hexes" :key="hex.code"
                           :transform="'translate(' + hex.cx + ',' + hex.cy + ')'">
                            <polygon class="fujiba-region-hex"
                                     :class="{ 'is-selected': selected === hex.code }"
                                     :points="hexPoints"
                                     :fill="colorFor(hex.code)"
                                     @mouseenter="hovered=hex"
                                     @click="selected = (selected === hex.code ? null : hex.code)"/>
                            <text class="fujiba-region-hex-label" y="3">@{{ hex.shortLabel }}</text>
                        </g>
                    </svg>

                    <div v-if="hovered" class="fujiba-region-tooltip"
                         :style="{ left: tooltipX + 'px', top: tooltipY + 'px' }">
                        <div class="fujiba-region-tooltip-name">@{{ hovered.name_ja }}</div>
                        <div class="fujiba-region-tooltip-row">
                            <span>@lang('fujiba::app.dashboard.orders_by_region.col_orders')</span>
                            <span>@{{ hovered.orders }}</span>
                        </div>
                        <div class="fujiba-region-tooltip-row">
                            <span>@lang('fujiba::app.dashboard.orders_by_region.col_revenue')</span>
                            <span>@{{ formatPrice(hovered.revenue) }}</span>
                        </div>
                    </div>

                    <div class="fujiba-region-legend">
                        <span>0</span>
                        <span class="fujiba-region-legend-bar"></span>
                        <span>@{{ maxOrders }}</span>
                    </div>
                </div>

                <!-- Top list -->
                <div>
                    <div class="fujiba-region-list-header">
                        @lang('fujiba::app.dashboard.orders_by_region.top_prefectures')
                    </div>
                    <div class="fujiba-region-list">
                        <div v-for="(p, idx) in topPrefectures" :key="p.code"
                             class="fujiba-region-row"
                             :class="{ 'is-selected': selected === p.code }"
                             @click="selected = (selected === p.code ? null : p.code)">
                            <span class="fujiba-region-rank">@{{ idx+1 }}</span>
                            <span class="fujiba-region-row-name">@{{ p.name_ja }}</span>
                            <span class="fujiba-region-row-bar-wrap">
                                <span class="fujiba-region-row-bar"
                                      :style="{ width: barWidth(p.orders) + '%' }"></span>
                            </span>
                            <span class="fujiba-region-row-num">@{{ p.orders }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</script>

<script type="module">
    (function () {
        console.log('[Fujiba OrdersByRegion] script loaded, app=', typeof window.app);
        if (window.__fujibaRegionRegistered) return;
        window.__fujibaRegionRegistered = true;

        // Hex layout from PHP — each entry: [code, col, row]
        const HEX_LAYOUT = @json($hexLayout);
        const HEX_SIDE = {{ $hexSide }};
        const HEX_W    = {{ $hexW }};
        const HEX_VSTEP = {{ $hexVStep }};
        const HEX_POINTS = "{{ $hexPoints }}";

        // 7-step ramp: dark slate → pink-violet
        const RAMP = ['#1e293b','#1e3a8a','#1d4ed8','#3b82f6','#6366f1','#8b5cf6','#ec4899'];

        let regionTries = 0;
        function registerOrdersByRegion() {
            regionTries++;
            if (typeof window.app === 'undefined' || !window.app || !window.app.component) {
                if (regionTries > 600) {
                    console.error('[Fujiba OrdersByRegion] gave up waiting for app');
                    return;
                }
                return requestAnimationFrame(registerOrdersByRegion);
            }
            console.log('[Fujiba OrdersByRegion] registering after', regionTries, 'tries');
            const obrTplEl = document.getElementById('v-fujiba-orders-by-region-template');
            const obrTplStr = obrTplEl ? obrTplEl.innerHTML : '';
            console.log('[Fujiba OrdersByRegion] template length:', obrTplStr.length);
            window.app.component('v-fujiba-orders-by-region', {
            template: obrTplStr,

            props: {
                endpoint:    { type: String, required: true },
                initialData: { type: Object, required: true },
            },

            data() {
                return {
                    data: this.initialData,
                    hovered: null,
                    selected: null,
                    tooltipX: 0,
                    tooltipY: 0,
                    hexPoints: HEX_POINTS,
                };
            },

            computed: {
                prefectures() { return this.data?.orders_by_region?.prefectures ?? []; },
                maxOrders()   { return this.data?.orders_by_region?.max_orders ?? 0; },
                totalOrders() { return this.data?.orders_by_region?.total_orders ?? 0; },

                // Map prefecture by code for fast lookup
                byCode() {
                    const m = {};
                    for (const p of this.prefectures) m[p.code] = p;
                    return m;
                },

                hexes() {
                    // Compute hex positions; offset odd rows by half the hex width
                    return HEX_LAYOUT.map(([code, col, row]) => {
                        const offset = (row % 2 === 1) ? HEX_W / 2 : 0;
                        const cx = col * HEX_W + offset + HEX_W / 2 + 6;
                        const cy = row * HEX_VSTEP + HEX_SIDE + 6;
                        const p = this.byCode[code] || { name_ja: '?', name_en: '?', orders: 0, revenue: 0 };
                        return {
                            code,
                            cx, cy,
                            name_ja: p.name_ja,
                            name_en: p.name_en,
                            orders: p.orders,
                            revenue: p.revenue,
                            shortLabel: this.shortLabelFor(p.name_ja),
                        };
                    });
                },

                mapW() {
                    let max = 0;
                    for (const [, col, row] of HEX_LAYOUT) {
                        const offset = (row % 2 === 1) ? HEX_W / 2 : 0;
                        const right = col * HEX_W + offset + HEX_W + 12;
                        if (right > max) max = right;
                    }
                    return Math.ceil(max);
                },
                mapH() {
                    let max = 0;
                    for (const [, , row] of HEX_LAYOUT) {
                        const bottom = row * HEX_VSTEP + 2 * HEX_SIDE + 12;
                        if (bottom > max) max = bottom;
                    }
                    return Math.ceil(max);
                },

                topPrefectures() {
                    return [...this.prefectures]
                        .filter(p => p.orders > 0)
                        .sort((a, b) => b.orders - a.orders)
                        .slice(0, 10);
                },
            },

            mounted() {
                this.$emitter.on('reporting-filter-updated', this.onFilterChange);

                // Track mouse for tooltip positioning
                this.$el.addEventListener('mousemove', (e) => {
                    const rect = this.$el.querySelector('.fujiba-region-map')?.getBoundingClientRect();
                    if (! rect) return;
                    this.tooltipX = e.clientX - rect.left + 12;
                    this.tooltipY = e.clientY - rect.top + 12;
                });
            },

            methods: {
                colorFor(code) {
                    const p = this.byCode[code];
                    if (! p || p.orders === 0) return RAMP[0];
                    if (this.maxOrders <= 0) return RAMP[0];
                    const idx = Math.min(RAMP.length - 1,
                        Math.max(1, Math.ceil((p.orders / this.maxOrders) * (RAMP.length - 1))));
                    return RAMP[idx];
                },

                barWidth(orders) {
                    if (this.maxOrders <= 0) return 0;
                    return Math.min(100, (orders / this.maxOrders) * 100);
                },

                shortLabelFor(nameJa) {
                    // First character of prefecture name (most readable on small hex)
                    return (nameJa || '').slice(0, 1);
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
                        console.error('Fujiba orders-by-region fetch failed', e);
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

        registerOrdersByRegion();
    })();
</script>
