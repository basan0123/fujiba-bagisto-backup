        <style>
            /* ─── Global mobile-overflow guard for dashboard body only ───
               Anything in the dashboard left-or-right column wider than the
               viewport gets clipped. Sidebar and header are NOT touched. */
            @media (max-width: 768px) {
                html, body { overflow-x: hidden !important; }
                /* Dashboard route's left+right columns and their direct content */
                .group\/container > div:not(:first-child),
                .group\/container > div:not(:first-child) > * {
                    max-width: 100vw !important;
                    overflow-x: hidden !important;
                    box-sizing: border-box;
                }
                /* Any image/SVG inside Fujiba widgets shouldn't push past container */
                [id^="fujiba-"] img,
                [id^="fujiba-"] svg,
                .fujiba-region-svg,
                .fujiba-pie-svg,
                .fujiba-ca-shell img,
                .fujiba-ca-shell svg {
                    max-width: 100% !important;
                    height: auto !important;
                }
                /* Dashboard filter row — wrap chips */
                v-dashboard-filters > div,
                v-dashboard-filters .flex.gap-1\.5 {
                    flex-wrap: wrap !important;
                    gap: 6px !important;
                }
                /* Today's order detail row gets overflow clip */
                v-dashboard-todays-details .flex.flex-wrap.gap-4 {
                    overflow-x: hidden !important;
                }
            }
            /* ─── Card base ─── */
            .fujiba-kpi-card {
                opacity: 0;
                animation: fujiba-fade-up 480ms cubic-bezier(0.215, 0.61, 0.355, 1) forwards;
                position: relative;
                overflow: hidden;
                border-radius: 14px !important;
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                border: 1px solid rgba(15,23,42,0.06) !important;
                box-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 4px 14px -4px rgba(15,23,42,0.05);
                transform-style: preserve-3d;
                transition: transform 220ms cubic-bezier(0.215,0.61,0.355,1),
                            box-shadow 220ms ease,
                            border-color 220ms ease;
            }
            .dark .fujiba-kpi-card {
                background: linear-gradient(135deg, rgb(17,24,39) 0%, rgb(15,23,42) 100%);
                border-color: rgba(255,255,255,0.06) !important;
            }
            .fujiba-kpi-card::before {
                content: '';
                position: absolute;
                inset: 0;
                background: var(--fujiba-kpi-bg, transparent);
                opacity: 0.85;
                z-index: 0;
                transition: opacity 220ms ease;
            }
            .fujiba-kpi-card::after {
                content: '';
                position: absolute;
                top: -40%;
                right: -20%;
                width: 140px; height: 140px;
                background: var(--fujiba-kpi-glow, transparent);
                filter: blur(28px);
                opacity: 0.55;
                z-index: 0;
                transition: transform 320ms ease, opacity 320ms ease;
                pointer-events: none;
            }
            .fujiba-kpi-card > * { position: relative; z-index: 1; }
            .fujiba-kpi-card:hover {
                transform: translateY(-3px) rotateX(2deg) rotateY(-1.5deg);
                box-shadow: 0 12px 28px -10px rgba(99,102,241,0.28),
                            0 6px 16px -8px rgba(15,23,42,0.18);
            }
            .fujiba-kpi-card:hover::after {
                transform: scale(1.25) translate(-12px, 8px);
                opacity: 0.75;
            }

            /* Section palettes */
            .fujiba-kpi-card.tone-financial {
                --fujiba-kpi-bg: linear-gradient(135deg, rgba(89,141,230,0.10) 0%, rgba(168,85,247,0.05) 100%);
                --fujiba-kpi-glow: radial-gradient(circle, #598de6 0%, transparent 70%);
            }
            .fujiba-kpi-card.tone-ops {
                --fujiba-kpi-bg: linear-gradient(135deg, rgba(245,158,11,0.10) 0%, rgba(244,63,94,0.05) 100%);
                --fujiba-kpi-glow: radial-gradient(circle, #f59e0b 0%, transparent 70%);
            }
            .fujiba-kpi-card.tone-inventory {
                --fujiba-kpi-bg: linear-gradient(135deg, rgba(139,92,246,0.10) 0%, rgba(236,72,153,0.05) 100%);
                --fujiba-kpi-glow: radial-gradient(circle, #8b5cf6 0%, transparent 70%);
            }

            /* Big number — beefier on the headline cards */
            .fujiba-kpi-headline-num {
                font-size: clamp(26px, 6vw, 40px) !important;
                font-weight: 900 !important;
                letter-spacing: -0.03em;
                background: linear-gradient(135deg, #1e293b, #475569);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .dark .fujiba-kpi-headline-num {
                background: linear-gradient(135deg, #f8fafc, #cbd5e1);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .fujiba-kpi-headline-num.tone-positive {
                background: linear-gradient(135deg, #10b981, #059669);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .fujiba-kpi-headline-num.tone-negative {
                background: linear-gradient(135deg, #ef4444, #b91c1c);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .fujiba-kpi-headline-num.tone-revenue {
                background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .fujiba-kpi-headline-num.tone-aov {
                background: linear-gradient(135deg, #06b6d4, #6366f1);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .fujiba-kpi-headline-num.tone-stock {
                background: linear-gradient(135deg, #8b5cf6, #ec4899);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            /* Section header pill */
            .fujiba-section-pill {
                display: inline-flex; align-items: center; gap: 6px;
                padding: 4px 10px 4px 6px; border-radius: 999px;
                font-size: 11px; font-weight: 700;
                text-transform: uppercase; letter-spacing: 0.08em;
                margin-bottom: 10px !important;
                border-left: 0 !important;
                padding-left: 10px !important;
            }
            .fujiba-section-pill::before {
                content: '';
                width: 7px; height: 7px; border-radius: 50%;
            }
            .fujiba-section-pill.tone-financial {
                background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(139,92,246,0.12));
                color: #4f46e5;
            }
            .fujiba-section-pill.tone-financial::before { background: #6366f1; box-shadow: 0 0 8px #6366f1; }
            .fujiba-section-pill.tone-ops {
                background: linear-gradient(135deg, rgba(245,158,11,0.14), rgba(244,63,94,0.10));
                color: #b45309;
            }
            .fujiba-section-pill.tone-ops::before { background: #f59e0b; box-shadow: 0 0 8px #f59e0b; }
            .fujiba-section-pill.tone-inventory {
                background: linear-gradient(135deg, rgba(139,92,246,0.14), rgba(236,72,153,0.10));
                color: #7c3aed;
            }
            .fujiba-section-pill.tone-inventory::before { background: #8b5cf6; box-shadow: 0 0 8px #8b5cf6; }

            /* ─── White KPI cards with colorful gradient numbers ───
               Card itself is plain white (light bg). The KPI number is
               painted with a per-tone gradient via background-clip:text.
               Labels stay neutral muted gray. */
            .fujiba-bold-card {
                position: relative !important;
                overflow: hidden !important;
                border-radius: 14px !important;
                padding: 14px 14px 16px !important;
                color: #1e293b !important;
                min-height: 100px !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                align-items: center !important;
                text-align: center !important;
                gap: 10px !important;
                background: #ffffff !important;
                border: 2px solid var(--num-border, rgba(99,102,241,0.22)) !important;
                box-shadow:
                    0 2px 4px rgba(15,23,42,0.04),
                    0 8px 24px -10px var(--num-glow, rgba(99,102,241,0.18)) !important;
                transition: transform 220ms cubic-bezier(0.4,0,0.2,1),
                            box-shadow 220ms ease,
                            border-color 220ms ease !important;
            }
            .dark .fujiba-bold-card {
                background: rgb(17,24,39) !important;
                border-color: var(--num-border, rgba(99,102,241,0.35)) !important;
                color: #f1f5f9 !important;
            }
            .fujiba-bold-card::before, .fujiba-bold-card::after { content: none !important; }
            .fujiba-bold-card:hover {
                transform: translateY(-3px);
                border-color: var(--num-border-hover, rgba(99,102,241,0.5)) !important;
                box-shadow:
                    0 2px 4px rgba(15,23,42,0.04),
                    0 16px 36px -10px var(--num-glow, rgba(99,102,241,0.40)) !important;
            }
            .fujiba-bold-card > * { position: relative; z-index: 1; }
            .fujiba-bold-label {
                font-size: 11px !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.08em !important;
                color: #64748b !important;
                margin: 0 !important;
            }
            @media (max-width: 480px) {
                .fujiba-bold-label { font-size: 10px !important; letter-spacing: 0.06em !important; }
                .fujiba-bold-card { min-height: 84px !important; padding: 12px 10px 14px !important; gap: 6px !important; }
            }
            .dark .fujiba-bold-label { color: #94a3b8 !important; }

            /* Number with gradient text (per-tone gradient via CSS var) */
            .fujiba-bold-num {
                font-size: clamp(22px, 3.6vw, 32px) !important;
                font-weight: 900 !important;
                line-height: 1.05 !important;
                letter-spacing: -0.02em !important;
                font-variant-numeric: tabular-nums !important;
                margin: 0 !important;
                word-break: keep-all !important;
                background-image: var(--num-gradient, linear-gradient(135deg,#6366f1,#8b5cf6,#ec4899)) !important;
                -webkit-background-clip: text !important;
                background-clip: text !important;
                -webkit-text-fill-color: transparent !important;
                color: transparent !important;
                filter: drop-shadow(0 1px 2px var(--num-glow, rgba(99,102,241,0.25)));
            }
            @media (max-width: 768px) {
                .fujiba-bold-num { font-size: clamp(18px, 5vw, 26px) !important; }
            }
            @media (max-width: 480px) {
                .fujiba-bold-num { font-size: clamp(16px, 5.6vw, 22px) !important; }
            }

            /* Per-card number gradient palette + matching border tint + glow */
            .fujiba-tone-revenue   { --num-gradient: linear-gradient(135deg,#6366f1 0%,#8b5cf6 50%,#ec4899 100%); --num-glow: rgba(139,92,246,0.30); --num-border: rgba(139,92,246,0.30); --num-border-hover: rgba(139,92,246,0.55); }
            .fujiba-tone-profit    { --num-gradient: linear-gradient(135deg,#10b981 0%,#059669 100%);             --num-glow: rgba(16,185,129,0.30); --num-border: rgba(16,185,129,0.30); --num-border-hover: rgba(16,185,129,0.55); }
            .fujiba-tone-margin    { --num-gradient: linear-gradient(135deg,#f59e0b 0%,#f97316 100%);             --num-glow: rgba(249,115,22,0.30); --num-border: rgba(249,115,22,0.30); --num-border-hover: rgba(249,115,22,0.55); }
            .fujiba-tone-aov       { --num-gradient: linear-gradient(135deg,#06b6d4 0%,#3b82f6 50%,#8b5cf6 100%); --num-glow: rgba(59,130,246,0.30); --num-border: rgba(59,130,246,0.30); --num-border-hover: rgba(59,130,246,0.55); }
            .fujiba-tone-orders    { --num-gradient: linear-gradient(135deg,#f59e0b 0%,#fb923c 100%);             --num-glow: rgba(249,115,22,0.30); --num-border: rgba(249,115,22,0.30); --num-border-hover: rgba(249,115,22,0.55); }
            .fujiba-tone-open      { --num-gradient: linear-gradient(135deg,#fb923c 0%,#dc2626 100%);             --num-glow: rgba(220,38,38,0.30); --num-border: rgba(220,38,38,0.30); --num-border-hover: rgba(220,38,38,0.55); }
            .fujiba-tone-customers { --num-gradient: linear-gradient(135deg,#06b6d4 0%,#10b981 100%);             --num-glow: rgba(6,182,212,0.30); --num-border: rgba(6,182,212,0.30); --num-border-hover: rgba(6,182,212,0.55); }
            .fujiba-tone-stock     { --num-gradient: linear-gradient(135deg,#8b5cf6 0%,#ec4899 100%);             --num-glow: rgba(139,92,246,0.30); --num-border: rgba(236,72,153,0.30); --num-border-hover: rgba(236,72,153,0.55); }
            .fujiba-tone-cost      { --num-gradient: linear-gradient(135deg,#f59e0b 0%,#ec4899 50%,#8b5cf6 100%); --num-glow: rgba(236,72,153,0.30); --num-border: rgba(236,72,153,0.30); --num-border-hover: rgba(236,72,153,0.55); }
            .fujiba-tone-delivery  { --num-gradient: linear-gradient(135deg,#8b5cf6 0%,#06b6d4 100%);             --num-glow: rgba(59,130,246,0.30); --num-border: rgba(59,130,246,0.30); --num-border-hover: rgba(59,130,246,0.55); }
            .fujiba-tone-expense   { --num-gradient: linear-gradient(135deg,#ef4444 0%,#991b1b 100%);             --num-glow: rgba(239,68,68,0.30); --num-border: rgba(239,68,68,0.30); --num-border-hover: rgba(239,68,68,0.55); }
            .fujiba-tone-loss      { --num-gradient: linear-gradient(135deg,#ef4444 0%,#991b1b 100%);             --num-glow: rgba(239,68,68,0.30); --num-border: rgba(239,68,68,0.30); --num-border-hover: rgba(239,68,68,0.55); }
            .fujiba-tone-neutral   { --num-gradient: linear-gradient(135deg,#64748b 0%,#334155 100%);             --num-glow: rgba(100,116,139,0.18); --num-border: rgba(100,116,139,0.25); --num-border-hover: rgba(100,116,139,0.45); }

            /* Pulse animation reused by warning-lights (was previously defined in meter block) */
            @keyframes fujiba-meter-pulse {
                0%,100% { opacity: 1; transform: scale(1); }
                50%     { opacity: 0.55; transform: scale(0.85); }
            }

            /* ─── Warning-lights strip (between sections) ─── */
            .fujiba-warning-strip {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                padding: 10px 12px;
                margin: 0 0 14px 0;
                background: linear-gradient(135deg, #0f172a, #1e293b);
                border-radius: 10px;
                border: 1px solid rgba(148,163,184,0.15);
                position: relative;
                overflow: hidden;
            }
            .fujiba-warning-strip::before {
                content: 'CHECK';
                position: absolute;
                top: 4px; left: 8px;
                font-size: 7px; font-weight: 800;
                color: rgba(148,163,184,0.4);
                letter-spacing: 0.2em;
            }
            .fujiba-warning-light {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                border-radius: 999px;
                font-size: 11px;
                font-weight: 700;
                background: rgba(15,23,42,0.6);
                border: 1px solid rgba(148,163,184,0.15);
                color: rgba(148,163,184,0.6);
                transition: all 200ms ease;
            }
            .fujiba-warning-light::before {
                content: '';
                width: 8px; height: 8px;
                border-radius: 50%;
                background: rgba(148,163,184,0.3);
            }
            .fujiba-warning-light.is-on {
                background: var(--w-bg, rgba(239,68,68,0.18));
                border-color: var(--w-color, #ef4444);
                color: var(--w-color, #ef4444);
                box-shadow: 0 0 16px var(--w-glow, rgba(239,68,68,0.4));
            }
            .fujiba-warning-light.is-on::before {
                background: var(--w-color, #ef4444);
                box-shadow: 0 0 8px var(--w-color, #ef4444);
                animation: fujiba-meter-pulse 1.2s ease-in-out infinite;
            }
            .fujiba-warning-light.tone-warn  { --w-color:#f59e0b; --w-bg:rgba(245,158,11,0.18); --w-glow:rgba(245,158,11,0.4); }
            .fujiba-warning-light.tone-info  { --w-color:#06b6d4; --w-bg:rgba(6,182,212,0.18); --w-glow:rgba(6,182,212,0.4); }
            .fujiba-warning-light.tone-good  { --w-color:#10b981; --w-bg:rgba(16,185,129,0.18); --w-glow:rgba(16,185,129,0.4); }

            /* hover lift even bigger on financial section */
            .fujiba-kpi-card.tone-financial:hover {
                box-shadow: 0 14px 32px -10px rgba(99,102,241,0.42),
                            0 6px 16px -8px rgba(15,23,42,0.18);
            }

            @keyframes fujiba-fade-up {
                from { opacity: 0; transform: translateY(10px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            /* Mobile-first: stack rings tighter */
            @media (max-width: 640px) {
                .fujiba-gauge-wrap { width: 48px; height: 48px; }
                .fujiba-kpi-headline-num { font-size: 20px !important; }
            }
        </style>

@php
    $initialJson = json_encode($businessOverviewInitial, JSON_UNESCAPED_UNICODE);
    $endpointUrl = route('admin.fujiba.dashboard.business_overview');
@endphp

<div class="flex flex-col gap-2" id="fujiba-bo-unified-card">
    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
        @lang('fujiba::app.dashboard.business_overview.title')
    </p>

    <v-fujiba-business-overview
        endpoint="{{ $endpointUrl }}"
        :initial-data="{{ $initialJson }}"
    ></v-fujiba-business-overview>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-fujiba-business-overview-template"
    >
        <div class="rounded-xl bg-white dark:bg-gray-900 p-3 md:p-4 box-shadow">
            <!-- Header row -->
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    @{{ data.channel_name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 tabular-nums">
                    @{{ data.period.start }} → @{{ data.period.end }}
                </p>
            </div>

            <!-- Loading shimmer for 3 sections + 12 cards -->
            <template v-if="isLoading">
                <div class="shimmer h-3 w-24 rounded mb-2" v-for="n in 1" :key="'h1'+n"></div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                    <div class="shimmer h-[120px] rounded-xl" v-for="n in 4" :key="'a'+n"></div>
                </div>
                <div class="shimmer h-3 w-24 rounded mb-2"></div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                    <div class="shimmer h-[120px] rounded-xl" v-for="n in 4" :key="'b'+n"></div>
                </div>
                <div class="shimmer h-3 w-24 rounded mb-2"></div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="shimmer h-[120px] rounded-xl" v-for="n in 4" :key="'c'+n"></div>
                </div>
            </template>

            <!-- Empty state: nothing has data -->
            <template v-else-if="! hasAnyData">
                <p class="text-sm text-gray-600 dark:text-gray-300 py-4">
                    @lang('fujiba::app.dashboard.business_overview.empty_state')
                </p>
            </template>

            <template v-else>
                <!-- ── Section 1: Performance ── -->
                <p class="fujiba-section-pill tone-financial">
                    @lang('fujiba::app.dashboard.business_overview.section_financial')
                </p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                    <!-- Revenue -->
                    <div class="fujiba-bold-card fujiba-tone-revenue">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_revenue')</p>
                        <p class="fujiba-bold-num">@{{ display.revenue }}</p>
                    </div>

                    <!-- Profit -->
                    <div class="fujiba-bold-card"
                         :class="profitIsPositive ? 'fujiba-tone-profit' : 'fujiba-tone-loss'"
                         :title="profitCaveat">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_profit')</p>
                        <p class="fujiba-bold-num">@{{ display.profit }}</p>
                    </div>

                    <!-- Margin -->
                    <div class="fujiba-bold-card"
                         :class="profitIsPositive ? 'fujiba-tone-margin' : 'fujiba-tone-loss'">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_margin')</p>
                        <p class="fujiba-bold-num">@{{ marginDisplay }}</p>
                    </div>

                    <!-- AOV -->
                    <div class="fujiba-bold-card fujiba-tone-aov">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_aov')</p>
                        <p class="fujiba-bold-num">@{{ display.aov }}</p>
                    </div>
                </div>

                <!-- ── Warning lights strip ── -->
                <div class="fujiba-warning-strip" aria-label="System status">
                    <span class="fujiba-warning-light tone-warn"
                          :class="{ 'is-on': (data.overview?.open_orders ?? 0) > 0 }"
                          :title="openOrdersTooltip">
                        @lang('fujiba::app.dashboard.business_overview.card_open_orders')
                        @{{ display.open_orders }}
                    </span>
                    <span class="fujiba-warning-light"
                          :class="{ 'is-on': unpaidIsAlert }">
                        @lang('fujiba::app.dashboard.business_overview.card_unpaid_invoices')
                        @{{ display.unpaid_invoices }}
                    </span>
                    <span class="fujiba-warning-light tone-info"
                          :class="{ 'is-on': (data.overview?.total_orders ?? 0) > 0 }">
                        @lang('fujiba::app.dashboard.business_overview.card_total_orders')
                        @{{ display.total_orders }}
                    </span>
                    <span class="fujiba-warning-light tone-good"
                          :class="{ 'is-on': profitIsPositive && (data.overview?.profit ?? 0) > 0 }">
                        @{{ profitIsPositive ? '✓ PROFIT' : '✕ LOSS' }}
                    </span>
                </div>

                <!-- ── Section 2: Operations ── -->
                <p class="fujiba-section-pill tone-ops">
                    @lang('fujiba::app.dashboard.business_overview.section_operational')
                </p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                    <!-- Total Orders -->
                    <a :href="totalOrdersHref"
                       class="fujiba-bold-card fujiba-tone-orders no-underline">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_total_orders')</p>
                        <p class="fujiba-bold-num">@{{ display.total_orders }}</p>
                    </a>

                    <!-- Open Orders -->
                    <a :href="openOrdersHref"
                       class="fujiba-bold-card fujiba-tone-open no-underline"
                       :title="openOrdersTooltip">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_open_orders')</p>
                        <p class="fujiba-bold-num">@{{ display.open_orders }}</p>
                    </a>

                    <!-- Total Customers -->
                    <a :href="totalCustomersHref"
                       class="fujiba-bold-card fujiba-tone-customers no-underline">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_total_customers')</p>
                        <p class="fujiba-bold-num">@{{ display.total_customers }}</p>
                    </a>

                    <!-- Unpaid Invoices (number turns red when > 0, neutral gray otherwise) -->
                    <a :href="unpaidInvoicesHref"
                       class="fujiba-bold-card no-underline"
                       :class="unpaidIsAlert ? 'fujiba-tone-loss' : 'fujiba-tone-neutral'">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_unpaid_invoices')</p>
                        <p class="fujiba-bold-num">@{{ display.unpaid_invoices }}</p>
                    </a>
                </div>

                <!-- ── Section 3: Inventory & cost ── -->
                <p class="fujiba-section-pill tone-inventory">
                    @lang('fujiba::app.dashboard.business_overview.section_inventory_cost')
                </p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <!-- Total Stock (clickable) -->
                    <!-- Total Stock -->
                    <a :href="totalStockHref"
                       class="fujiba-bold-card fujiba-tone-stock no-underline">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_total_stock')</p>
                        <p class="fujiba-bold-num">@{{ display.total_stock }}</p>
                    </a>

                    <!-- Inventory Cost -->
                    <div class="fujiba-bold-card fujiba-tone-cost">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_inventory_cost')</p>
                        <p class="fujiba-bold-num">@{{ display.inventory_cost }}</p>
                    </div>

                    <!-- Delivery -->
                    <div class="fujiba-bold-card fujiba-tone-delivery">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_delivery')</p>
                        <p class="fujiba-bold-num">@{{ display.delivery_fees }}</p>
                    </div>

                    <!-- Fixed Expenses -->
                    <div class="fujiba-bold-card fujiba-tone-expense">
                        <p class="fujiba-bold-label">@lang('fujiba::app.dashboard.business_overview.card_fixed_expenses')</p>
                        <p class="fujiba-bold-num">@{{ display.fixed_expenses_this_month }}</p>
                    </div>
                </div>
            </template>
        </div>


    </script>

    <script type="module">
        // ─── Hide the legacy 全体の概要 widget ───
        // Idempotent + silent-fail-safe per F1.6 spec.
        // Runs once after DOM ready; if the legacy widget isn't found
        // (other package hid it, Bagisto upgrade restructured it, etc.)
        // we console.warn and move on.
        (function hideLegacyOverallDetails() {
            if (window.__fujibaLegacyHidden) return;
            window.__fujibaLegacyHidden = true;

            const apply = () => {
                try {
                    const ourCard = document.getElementById('fujiba-bo-unified-card');
                    if (! ourCard) {
                        console.warn('Fujiba: unified BO card not found, skipping legacy hide.');
                        return;
                    }

                    // Walk forward through siblings of our wrapper. The legacy
                    // widget renders as a sibling div containing the
                    // <v-dashboard-overall-details> tag (or .v-dashboard-overall-details
                    // class once Vue hydrates).
                    let sibling = ourCard.nextElementSibling;
                    let hidden = 0;
                    while (sibling) {
                        // The legacy widget is wrapped in a `<div class="flex flex-col gap-2">`
                        // whose child includes either the unhydrated tag name or
                        // hydrated class.
                        const hasLegacy = sibling.querySelector
                            && (sibling.querySelector('v-dashboard-overall-details')
                                || sibling.querySelector('.v-dashboard-overall-details')
                                || sibling.querySelector('[id^="v-dashboard-overall-details"]'));

                        // Heuristic fallback: also hide a sibling whose heading is
                        // the existing widget's "Overall Details" lang key (en or ja).
                        // Bagisto's heading sits inside the wrapper as a <p>.
                        const headingP = sibling.querySelector && sibling.querySelector('p.text-base.font-semibold');
                        const headingText = headingP ? headingP.textContent.trim() : '';
                        const isOverallHeading = headingText === 'Overall Details' || headingText === '全体の概要';

                        if (hasLegacy || isOverallHeading) {
                            sibling.style.display = 'none';
                            hidden++;
                            break; // there is exactly one legacy widget; stop after first hit
                        }

                        sibling = sibling.nextElementSibling;
                    }

                    if (hidden === 0) {
                        console.warn('Fujiba: legacy overall-details widget not found, skipping hide.');
                    }
                } catch (e) {
                    console.warn('Fujiba: legacy hide failed silently:', e);
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', apply);
            } else {
                apply();
            }
        })();

        // ─── Vue component ───
        const boTplEl = document.getElementById('v-fujiba-business-overview-template');
        const boTplStr = boTplEl ? boTplEl.innerHTML : '';
        console.log('[Fujiba BusinessOverview] template length:', boTplStr.length);
        app.component('v-fujiba-business-overview', {
            template: boTplStr,

            props: {
                endpoint:    { type: String, required: true },
                initialData: { type: Object, required: true },
            },

            data() {
                return {
                    data:      this.initialData,
                    isLoading: false,
                    tween: {
                        revenue:                   this.initialData?.overview?.revenue ?? 0,
                        profit:                    this.initialData?.overview?.profit ?? 0,
                        margin_percent:            this.computeMarginFrom(this.initialData) ?? 0,
                        inventory_cost:            this.initialData?.overview?.inventory_cost ?? 0,
                        fixed_expenses_this_month: this.initialData?.overview?.fixed_expenses_this_month ?? 0,
                        delivery_fees:             this.initialData?.overview?.delivery_fees ?? 0,
                        open_orders:               this.initialData?.overview?.open_orders ?? 0,
                        total_orders:              this.initialData?.overview?.total_orders ?? 0,
                        total_customers:           this.initialData?.overview?.total_customers ?? 0,
                        aov:                       this.initialData?.overview?.aov ?? 0,
                        unpaid_invoices:           this.initialData?.overview?.unpaid_invoices ?? 0,
                        total_stock:               this.initialData?.overview?.total_stock ?? 0,
                    },
                    tweenStartFrom: { revenue:0, profit:0, margin_percent:0, inventory_cost:0, fixed_expenses_this_month:0, delivery_fees:0, open_orders:0, total_orders:0, total_customers:0, aov:0, unpaid_invoices:0, total_stock:0 },
                    tweenHandle: null,
                    profitCaveat: "@lang('fujiba::app.dashboard.business_overview.card_profit_caveat')",
                    openOrdersTooltip: "@lang('fujiba::app.dashboard.business_overview.card_open_orders_tooltip')",
                };
            },

            computed: {
                hasAnyData() {
                    const o = this.data?.overview;
                    if (! o) return false;
                    return (o.revenue ?? 0) > 0
                        || (o.cogs ?? 0) > 0
                        || (o.delivery_fees ?? 0) > 0
                        || (o.expenses_total ?? 0) > 0
                        || (o.inventory_cost ?? 0) > 0
                        || (o.fixed_expenses_this_month ?? 0) > 0
                        || (o.open_orders ?? 0) > 0
                        || (o.total_orders ?? 0) > 0
                        || (o.total_customers ?? 0) > 0
                        || (o.unpaid_invoices ?? 0) > 0
                        || (o.total_stock ?? 0) > 0;
                },
                profitIsPositive() { return (this.data?.overview?.profit ?? 0) >= 0; },
                profitColorClass() { return this.profitIsPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'; },
                profitDotStyle()   { return 'background-color:' + (this.profitIsPositive ? '#10b981' : '#ef4444'); },
                profitGradientClass() {
                    if (this.realMarginPercent === null) return '';
                    return this.profitIsPositive ? 'tone-positive' : 'tone-negative';
                },
                marginColorClass() { return this.realMarginPercent === null ? 'text-gray-900 dark:text-white' : (this.profitIsPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'); },
                realMarginPercent() {
                    const r = this.data?.overview?.revenue ?? 0;
                    if (r <= 0) return null;
                    return Math.round((this.data.overview.profit / r) * 1000) / 10;
                },
                marginDisplay() {
                    if (this.realMarginPercent === null) return '—';
                    return (Math.round(this.tween.margin_percent * 10) / 10) + '%';
                },
                unpaidIsAlert() { return (this.data?.overview?.unpaid_invoices ?? 0) > 0; },
                unpaidDotStyle()   { return 'background-color:' + (this.unpaidIsAlert ? '#ef4444' : '#9ca3af'); },
                unpaidColorClass() { return this.unpaidIsAlert ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'; },
                display() {
                    return {
                        revenue:                   this.formatPrice(this.tween.revenue),
                        profit:                    this.formatPrice(this.tween.profit),
                        inventory_cost:            this.formatPrice(this.tween.inventory_cost),
                        fixed_expenses_this_month: this.formatPrice(this.tween.fixed_expenses_this_month),
                        delivery_fees:             this.formatPrice(this.tween.delivery_fees),
                        open_orders:               Math.round(this.tween.open_orders).toLocaleString(),
                        total_orders:              Math.round(this.tween.total_orders).toLocaleString(),
                        total_customers:           Math.round(this.tween.total_customers).toLocaleString(),
                        aov:                       this.formatPrice(this.tween.aov),
                        unpaid_invoices:           this.formatPrice(this.tween.unpaid_invoices),
                        total_stock:               Math.round(this.tween.total_stock).toLocaleString(),
                    };
                },
                openOrdersHref()      { return "{{ route('admin.sales.orders.index') }}?filters[status][]=pending&filters[status][]=processing"; },
                totalOrdersHref()     { return "{{ route('admin.sales.orders.index') }}"; },
                totalCustomersHref()  { return "{{ route('admin.customers.customers.index') }}"; },
                unpaidInvoicesHref()  { return "{{ route('admin.sales.invoices.index') }}?filters[state][]=pending"; },
                totalStockHref()      { return "{{ route('admin.catalog.products.index') }}"; },
            },

            mounted() {
                this.startTween(800, /*fromZero*/ true);
                this.$emitter.on('reporting-filter-updated', this.onFilterChange);

                // Restore previously-saved dashboard filters (per admin user, server-side
                // session). Idempotent — runs once per page load via window guard so
                // multiple Fujiba widgets on the same page don't fight over the URL.
                if (! window.__fujibaFiltersRestored) {
                    window.__fujibaFiltersRestored = true;
                    this.restoreFilters();
                }

            },

            beforeUnmount() {
                if (this.tweenHandle) cancelAnimationFrame(this.tweenHandle);
            },

            methods: {
                async restoreFilters() {
                    try {
                        const resp = await this.$axios.get("{{ route('admin.fujiba.dashboard.filter_state.show') }}");
                        const saved = resp.data?.filters;
                        if (! saved) return;

                        const hasNonEmpty = ['channel', 'mode', 'start', 'end'].some(k => saved[k] && saved[k] !== '');
                        if (! hasNonEmpty) return;

                        const url = new URL(window.location.href);
                        const cur = {
                            channel: url.searchParams.get('channel') ?? '',
                            mode:    url.searchParams.get('mode') ?? '',
                            start:   url.searchParams.get('start') ?? '',
                            end:     url.searchParams.get('end') ?? '',
                        };
                        const urlIsClean = !cur.channel && !cur.mode && !cur.start && !cur.end;

                        // Only auto-apply if URL has no explicit filters — never override
                        // an explicit user choice.
                        if (urlIsClean) {
                            this.$emitter.emit('reporting-filter-updated', saved);
                        }
                    } catch (_) { /* silent */ }
                },
                computeMarginFrom(d) {
                    const r = d?.overview?.revenue ?? 0;
                    if (r <= 0) return 0;
                    return Math.round((d.overview.profit / r) * 1000) / 10;
                },
                async onFilterChange(filters) {
                    this.isLoading = true;
                    try {
                        const params = {
                            channel: filters.channel ?? '',
                            start:   filters.start ?? '',
                            end:     filters.end ?? '',
                            mode:    filters.mode ?? 'orders',
                        };
                        const resp = await this.$axios.get(this.endpoint, { params });
                        this.tweenStartFrom = { ...this.tween };
                        this.data = resp.data;
                        this.$nextTick(() => this.startTween(600, /*fromZero*/ false));

                        // Persist dashboard-scoped filter selection (silent, fire-and-forget).
                        // See DashboardFilterStateController — only channel/mode/start/end
                        // are stored. List-page datagrids are intentionally untouched.
                        try {
                            this.$axios.post(
                                "{{ route('admin.fujiba.dashboard.filter_state.store') }}",
                                params
                            );
                        } catch (_) { /* silent */ }
                    } catch (e) {
                        console.error('Fujiba business overview fetch failed', e);
                    } finally {
                        this.isLoading = false;
                    }
                },
                startTween(durationMs, fromZero) {
                    if (this.tweenHandle) cancelAnimationFrame(this.tweenHandle);
                    const o = this.data?.overview ?? {};
                    const targets = {
                        revenue:                   Number(o.revenue ?? 0),
                        profit:                    Number(o.profit ?? 0),
                        margin_percent:            this.realMarginPercent ?? 0,
                        inventory_cost:            Number(o.inventory_cost ?? 0),
                        fixed_expenses_this_month: Number(o.fixed_expenses_this_month ?? 0),
                        delivery_fees:             Number(o.delivery_fees ?? 0),
                        open_orders:               Number(o.open_orders ?? 0),
                        total_orders:              Number(o.total_orders ?? 0),
                        total_customers:           Number(o.total_customers ?? 0),
                        aov:                       Number(o.aov ?? 0),
                        unpaid_invoices:           Number(o.unpaid_invoices ?? 0),
                        total_stock:               Number(o.total_stock ?? 0),
                    };
                    const starts = fromZero
                        ? { revenue:0, profit:0, margin_percent:0, inventory_cost:0, fixed_expenses_this_month:0, delivery_fees:0, open_orders:0, total_orders:0, total_customers:0, aov:0, unpaid_invoices:0, total_stock:0 }
                        : { ...this.tweenStartFrom };
                    const t0 = performance.now();
                    const easeOutCubic = (x) => 1 - Math.pow(1 - x, 3);
                    const step = (now) => {
                        const elapsed = now - t0;
                        const t = Math.min(1, elapsed / durationMs);
                        const e = easeOutCubic(t);
                        for (const k of Object.keys(targets)) {
                            const s = starts[k] ?? 0;
                            this.tween[k] = s + (targets[k] - s) * e;
                        }
                        if (t < 1) {
                            this.tweenHandle = requestAnimationFrame(step);
                        } else {
                            for (const k of Object.keys(targets)) this.tween[k] = targets[k];
                            this.tweenHandle = null;
                        }
                    };
                    this.tweenHandle = requestAnimationFrame(step);
                },
                formatPrice(amount) {
                    const abs    = Math.abs(Number(amount) || 0);
                    const sign   = amount < 0 ? '-' : '';
                    const locale = "{{ app()->getLocale() }}";
                    if (abs < 10000) return sign + '¥' + Math.round(abs).toLocaleString();
                    const oneDecimal = (n) => {
                        const r = Math.round(n * 10) / 10;
                        if (r === Math.trunc(r)) return Math.trunc(r).toLocaleString();
                        return r.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                    };
                    if (locale === 'ja') {
                        if (abs >= 100000000 || Math.round(abs / 10000 * 10) / 10 >= 10000) return sign + '¥' + oneDecimal(abs / 100000000) + '億';
                        return sign + '¥' + oneDecimal(abs / 10000) + '万';
                    }
                    if (abs >= 1000000 || Math.round(abs / 1000 * 10) / 10 >= 1000) return sign + '¥' + oneDecimal(abs / 1000000) + 'M';
                    return sign + '¥' + oneDecimal(abs / 1000) + 'K';
                },
            },
        });
    </script>
@endPushOnce
