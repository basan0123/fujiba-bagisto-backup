<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.reporting.sales.index.title')
    </x-slot>

    <!-- Compact sticky filter bar (title + presets + range + channel in one slim row) -->
    <div
        id="rep-filter-bar"
        class="sticky top-0 z-20 -mx-2 mb-4 border-b border-gray-200 bg-white/95 px-2 py-2 backdrop-blur dark:border-gray-800 dark:bg-gray-950/95 sm:-mx-4 sm:px-4"
    >
        <v-reporting-filters>
            <!-- Shimmer -->
            <div class="flex items-center gap-1.5">
                <div class="shimmer h-[34px] w-[90px] rounded-md"></div>
                <div class="shimmer h-[34px] w-[260px] rounded-md"></div>
                <div class="shimmer h-[34px] w-[120px] rounded-md"></div>
            </div>
        </v-reporting-filters>
    </div>

    <!-- Sales Stats Vue Component -->
    <div class="flex flex-1 flex-col gap-4 max-xl:flex-auto">
        <!-- Sales Section -->
        @include('admin::reporting.sales.total-sales')

        <!-- Purchase Funnel and Abandoned Carts Sections Container -->
        <div class="flex flex-col justify-between gap-4 flex-1 [&>*]:flex-1 md:flex-row">
            <!-- Purchase Funnel Section -->
            @include('admin::reporting.sales.purchase-funnel')

            <!-- Abandoned Carts Section -->
            @include('admin::reporting.sales.abandoned-carts')
        </div>

        <!-- Total Orders and Average Order Value Sections Container -->
        <div class="flex flex-col justify-between gap-4 flex-1 [&>*]:flex-1 md:flex-row">
            <!-- Total Orders Section -->
            @include('admin::reporting.sales.total-orders')

            <!-- Average Order Value Section -->
            @include('admin::reporting.sales.average-order-value')
        </div>

        <!-- Tax Collected and Shipping Collected Sections Container -->
        <div class="flex flex-col justify-between gap-4 flex-1 [&>*]:flex-1 md:flex-row">
            <!-- Tax Collected Section -->
            @include('admin::reporting.sales.tax-collected')

            <!-- Shipping Collected Section -->
            @include('admin::reporting.sales.shipping-collected')
        </div>

        <!-- Refunds and Top Payment Methods Sections Container -->
        <div class="flex flex-col justify-between gap-4 flex-1 [&>*]:flex-1 md:flex-row">
            <!-- Refunds Section -->
            @include('admin::reporting.sales.total-refunds')

            <!-- Top Payment Methods Section -->
            @include('admin::reporting.sales.top-payment-methods')
        </div>

        <!-- Fujiba custom report widgets -->
        @include('admin::reporting.sales.custom-widgets')
    </div>

    @pushOnce('scripts')
        <script type="module" src="{{ bagisto_asset('js/chart.js') }}"></script>

        <script
            type="text/x-template"
            id="v-reporting-filters-template"
        >
            <div class="flex flex-nowrap items-center gap-2 overflow-x-auto" style="scrollbar-width:thin;">
                <!-- Title (compact, hidden on small screens to save room) -->
                <span class="hidden shrink-0 text-base font-bold leading-none text-gray-800 dark:text-white md:inline">
                    @lang('admin::app.reporting.sales.index.title')
                </span>

                <!-- Period preset chips -->
                <div class="flex shrink-0 items-center gap-1">
                    <button
                        v-for="preset in presets"
                        :key="preset.key"
                        type="button"
                        @click="applyPreset(preset.key)"
                        ::class="activePreset === preset.key
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700'"
                        class="whitespace-nowrap rounded-md border px-2.5 py-1 text-xs font-semibold leading-none transition-all"
                    >
                        @{{ preset.label }}
                    </button>
                </div>

                <span class="h-5 w-px shrink-0 bg-gray-200 dark:bg-gray-700"></span>

                <!-- Date range -->
                <x-admin::flat-picker.date class="!w-[120px] shrink-0" ::allow-input="false">
                    <input
                        class="flex h-[32px] w-full rounded-md border px-2 py-1 text-xs text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                        v-model="filters.start"
                        placeholder="@lang('admin::app.reporting.sales.index.start-date')"
                    />
                </x-admin::flat-picker.date>

                <span class="shrink-0 text-gray-400">–</span>

                <x-admin::flat-picker.date class="!w-[120px] shrink-0" ::allow-input="false">
                    <input
                        class="flex h-[32px] w-full rounded-md border px-2 py-1 text-xs text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                        v-model="filters.end"
                        placeholder="@lang('admin::app.reporting.sales.index.end-date')"
                    />
                </x-admin::flat-picker.date>

                <!-- Channel selector -->
                <template v-if="channels.length > 2">
                    <span class="h-5 w-px shrink-0 bg-gray-200 dark:bg-gray-700"></span>
                    <x-admin::dropdown position="bottom-right">
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="inline-flex shrink-0 cursor-pointer appearance-none items-center gap-x-1 rounded-md border bg-white px-2 py-1 text-center text-xs leading-none text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                style="height:32px;"
                            >
                                @{{ channels.find(channel => channel.code == filters.channel).name }}
                                <span class="icon-sort-down text-lg"></span>
                            </button>
                        </x-slot>

                        <x-slot:menu class="!p-0 shadow-[0_5px_20px_rgba(0,0,0,0.15)] dark:border-gray-800">
                            <x-admin::dropdown.menu.item
                                v-for="channel in channels"
                                ::class="{'bg-gray-100 dark:bg-gray-950': channel.code == filters.channel}"
                                @click="filters.channel = channel.code"
                            >
                                @{{ channel.name }}
                            </x-admin::dropdown.menu.item>
                        </x-slot>
                    </x-admin::dropdown>
                </template>

                <!-- Orders / Payments mode -->
                <span class="h-5 w-px shrink-0 bg-gray-200 dark:bg-gray-700"></span>
                <div class="inline-flex shrink-0 rounded-md border bg-white dark:border-gray-800 dark:bg-gray-900" role="group">
                    <button
                        type="button"
                        @click="filters.mode = 'orders'"
                        ::class="filters.mode === 'orders' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300'"
                        class="rounded-l-md px-2.5 py-1 text-xs leading-none transition-all hover:bg-gray-100 dark:hover:bg-gray-800"
                        style="height:32px;"
                    >
                        @lang('admin::app.reporting.sales.index.orders')
                    </button>
                    <button
                        type="button"
                        @click="filters.mode = 'payments'"
                        ::class="filters.mode === 'payments' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300'"
                        class="rounded-r-md px-2.5 py-1 text-xs leading-none transition-all hover:bg-gray-100 dark:hover:bg-gray-800"
                        style="height:32px;"
                    >
                        @lang('admin::app.reporting.sales.index.payment-method')
                    </button>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-reporting-filters', {
                template: '#v-reporting-filters-template',

                data() {
                    const KEY = 'fujiba_global_filters:' + window.location.host;
                    // Per-session login fingerprint — see v-dashboard-filters. A
                    // mismatch means a fresh login → reset the date range to the
                    // server default; channel + mode survive across sessions.
                    const SESS_KEY = KEY + ':session';
                    const SESS_ID  = @json(session()->getId());
                    const ISO = /^\d{4}-\d{2}-\d{2}$/;
                    const defaults = {
                        channel: '',
                        start: "{{ $startDate->format('Y-m-d') }}",
                        end: "{{ $endDate->format('Y-m-d') }}",
                        mode: 'orders',
                    };

                    let saved = {};
                    try {
                        const raw = localStorage.getItem(KEY);
                        if (raw) saved = JSON.parse(raw) || {};
                    } catch (e) {}

                    let freshLogin = false;
                    try {
                        if (localStorage.getItem(SESS_KEY) !== SESS_ID) {
                            freshLogin = true;
                            localStorage.setItem(SESS_KEY, SESS_ID);
                        }
                    } catch (e) {}

                    if (freshLogin) {
                        delete saved.start;
                        delete saved.end;
                    }

                    if (saved.start && (!ISO.test(saved.start) || (saved.end && saved.start > saved.end))) {
                        delete saved.start;
                        delete saved.end;
                    }
                    if (saved.end && !ISO.test(saved.end)) delete saved.end;
                    if (saved.mode && saved.mode !== 'orders' && saved.mode !== 'payments') delete saved.mode;

                    return {
                        storageKey: KEY,

                        channels: [
                            {
                                name: "@lang('admin::app.reporting.sales.index.all-channels')",
                                code: ''
                            },
                            ...@json(core()->getAllChannels()),
                        ],

                        presets: [
                            { key: 'today',        label: "@lang('admin::app.reporting.sales.index.preset-today')" },
                            { key: 'yesterday',    label: "@lang('admin::app.reporting.sales.index.preset-yesterday')" },
                            { key: 'day-before',   label: "@lang('admin::app.reporting.sales.index.preset-day-before')" },
                            { key: 'this-week',    label: "@lang('admin::app.reporting.sales.index.preset-this-week')" },
                            { key: 'this-month',   label: "@lang('admin::app.reporting.sales.index.preset-this-month')" },
                            { key: 'last-month',   label: "@lang('admin::app.reporting.sales.index.preset-last-month')" },
                            { key: 'this-quarter', label: "@lang('admin::app.reporting.sales.index.preset-this-quarter')" },
                            { key: 'last-quarter', label: "@lang('admin::app.reporting.sales.index.preset-last-quarter')" },
                            { key: 'this-year',    label: "@lang('admin::app.reporting.sales.index.preset-this-year')" },
                            { key: 'last-year',    label: "@lang('admin::app.reporting.sales.index.preset-last-year')" },
                        ],

                        filters: { ...defaults, ...saved },
                    }
                },

                computed: {
                    /* Highlights the preset chip whose date range matches the
                       current start/end, so manual edits clear the highlight. */
                    activePreset() {
                        for (const p of this.presets) {
                            const r = this.rangeFor(p.key);
                            if (r.start === this.filters.start && r.end === this.filters.end) {
                                return p.key;
                            }
                        }
                        return null;
                    },
                },

                watch: {
                    filters: {
                        handler() {
                            try {
                                let prev = {};
                                try { prev = JSON.parse(localStorage.getItem(this.storageKey) || '{}') || {}; } catch (e) {}
                                localStorage.setItem(this.storageKey, JSON.stringify({ ...prev, ...this.filters }));
                            } catch (e) {}
                            this.$emitter.emit('reporting-filter-updated', this.filters);
                        },

                        deep: true
                    }
                },

                methods: {
                    fmt(d) {
                        const y = d.getFullYear();
                        const m = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        return `${y}-${m}-${day}`;
                    },

                    /* Compute {start,end} (YYYY-MM-DD) for a preset key. Quarters
                       are calendar quarters (Jan-Mar, Apr-Jun, ...). */
                    rangeFor(key) {
                        const now = new Date();
                        const y = now.getFullYear();
                        const m = now.getMonth();
                        let s, e;

                        switch (key) {
                            case 'today':
                                s = e = now;
                                break;
                            case 'yesterday':
                                s = e = new Date(y, m, now.getDate() - 1);
                                break;
                            case 'day-before':
                                s = e = new Date(y, m, now.getDate() - 2);
                                break;
                            case 'this-week': {
                                // Week starts Monday.
                                const dow = (now.getDay() + 6) % 7;
                                s = new Date(y, m, now.getDate() - dow);
                                e = now;
                                break;
                            }
                            case 'this-month':
                                s = new Date(y, m, 1);
                                e = now;
                                break;
                            case 'last-month':
                                s = new Date(y, m - 1, 1);
                                e = new Date(y, m, 0);
                                break;
                            case 'this-quarter': {
                                const q = Math.floor(m / 3);
                                s = new Date(y, q * 3, 1);
                                e = now;
                                break;
                            }
                            case 'last-quarter': {
                                const q = Math.floor(m / 3) - 1;
                                const qy = q < 0 ? y - 1 : y;
                                const qm = ((q % 4) + 4) % 4 * 3;
                                s = new Date(qy, qm, 1);
                                e = new Date(qy, qm + 3, 0);
                                break;
                            }
                            case 'this-year':
                                s = new Date(y, 0, 1);
                                e = now;
                                break;
                            case 'last-year':
                                s = new Date(y - 1, 0, 1);
                                e = new Date(y - 1, 11, 31);
                                break;
                            default:
                                s = e = now;
                        }

                        return { start: this.fmt(s), end: this.fmt(e) };
                    },

                    applyPreset(key) {
                        const r = this.rangeFor(key);
                        this.filters.start = r.start;
                        this.filters.end = r.end;
                    },
                },

                mounted() {
                    this.$emitter.emit('reporting-filter-updated', this.filters);
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
