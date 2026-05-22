@php
    $sp           = $shippingPerformanceInitial ?? [];
    $summary      = $sp['summary']      ?? ['cost' => [], 'free' => [], 'date_column' => 'created_at'];
    $channelName  = $sp['channel_name'] ?? '';
    $period       = $sp['period']       ?? ['start' => '', 'end' => ''];
    $fetchedAt    = $sp['fetched_at']   ?? null;
@endphp

<div class="flex flex-col gap-2 mt-4">
    <div class="flex items-center justify-between">
        <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
            @lang('fujiba::app.dashboard.shipping_performance.title')
        </p>
        <a
            href="{{ route('admin.fujiba.dashboard.shipping_costs.index') }}"
            class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
        >
            @lang('fujiba::app.dashboard.shipping_performance.manage_costs')
            <span class="icon-arrow-right text-sm align-middle"></span>
        </a>
    </div>

    <v-fujiba-shipping-performance
        :initial-payload="{{ json_encode($sp, JSON_UNESCAPED_UNICODE) }}"
    ></v-fujiba-shipping-performance>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-fujiba-shipping-performance-template"
    >
        <div class="box-shadow rounded bg-white p-3 md:p-4 dark:bg-gray-900">
            <div v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">
                @lang('fujiba::app.dashboard.shipping_performance.loading')
            </div>

            <template v-else>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                    <!-- Collected vs Cost -->
                    <div class="min-w-0 flex flex-col gap-1">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                            @lang('fujiba::app.dashboard.shipping_performance.collected')
                        </p>
                        <p class="text-lg md:text-2xl font-semibold leading-none text-gray-800 dark:text-white">
                            @{{ formatYen(cost.collected) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            @{{ orderCountLine }}
                        </p>
                    </div>

                    <div class="min-w-0 flex flex-col gap-1 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-800 pt-3 md:pt-0 md:pl-4">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                            @lang('fujiba::app.dashboard.shipping_performance.carrier_cost')
                        </p>
                        <p class="text-lg md:text-2xl font-semibold leading-none text-gray-800 dark:text-white">
                            @{{ formatYen(cost.actual) }}
                        </p>
                        <p class="text-xs" :class="coverageClass">
                            @{{ coverageLine }}
                        </p>
                    </div>

                    <div class="min-w-0 flex flex-col gap-1 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-800 pt-3 md:pt-0 md:pl-4">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                            @lang('fujiba::app.dashboard.shipping_performance.margin')
                        </p>
                        <p
                            class="text-lg md:text-2xl font-semibold leading-none"
                            :class="cost.margin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                        >
                            @{{ marginSign }}@{{ formatYen(Math.abs(cost.margin)) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            @lang('fujiba::app.dashboard.shipping_performance.margin_hint')
                        </p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2">
                        @lang('fujiba::app.dashboard.shipping_performance.free_shipping_block')
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                        <div class="min-w-0 flex flex-col gap-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @lang('fujiba::app.dashboard.shipping_performance.free_orders')
                            </p>
                            <p class="text-lg md:text-2xl font-semibold leading-none text-gray-800 dark:text-white">
                                @{{ free.orders.toLocaleString() }}
                            </p>
                        </div>

                        <div class="min-w-0 flex flex-col gap-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @lang('fujiba::app.dashboard.shipping_performance.subsidy')
                            </p>
                            <p class="text-lg md:text-2xl font-semibold leading-none text-amber-600 dark:text-amber-400">
                                @{{ formatYen(free.subsidy) }}
                            </p>
                        </div>

                        <div class="min-w-0 flex flex-col gap-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @lang('fujiba::app.dashboard.shipping_performance.avg_subsidy')
                            </p>
                            <p class="text-lg md:text-2xl font-semibold leading-none text-gray-800 dark:text-white">
                                @{{ formatYen(free.avg_subsidy) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="free.by_rule && free.by_rule.length" class="mt-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                            @lang('fujiba::app.dashboard.shipping_performance.by_rule')
                        </p>
                        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            <li v-for="r in free.by_rule" :key="r.label" class="flex items-center justify-between gap-2">
                                <span class="truncate">@{{ r.label }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">
                                    @{{ r.orders.toLocaleString() }} · @{{ formatYen(r.subsidy) }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <p v-else class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                        @lang('fujiba::app.dashboard.shipping_performance.no_free_orders')
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @{{ periodLine }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @{{ asOfLine }}
                    </p>
                </div>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-fujiba-shipping-performance', {
            template: '#v-fujiba-shipping-performance-template',

            props: {
                initialPayload: { type: Object, required: true },
            },

            data() {
                return {
                    loading:     false,
                    cost:        this.initialPayload?.summary?.cost ?? {},
                    free:        this.initialPayload?.summary?.free ?? { by_rule: [] },
                    channelName: this.initialPayload?.channel_name ?? '',
                    period:      this.initialPayload?.period ?? { start: '', end: '' },
                    fetchedAt:   this.initialPayload?.fetched_at ?? null,
                    mode:        this.initialPayload?.mode ?? 'orders',
                };
            },

            computed: {
                marginSign() {
                    return (this.cost.margin ?? 0) >= 0 ? '+' : '−';
                },

                orderCountLine() {
                    const t = "@lang('fujiba::app.dashboard.shipping_performance.over_n_orders')";
                    return t.replace(':count', (this.cost.order_count ?? 0).toLocaleString());
                },

                coverageLine() {
                    const pending = this.cost.pending_count ?? 0;
                    if (pending === 0) {
                        return "@lang('fujiba::app.dashboard.shipping_performance.fully_reconciled')";
                    }
                    const t = "@lang('fujiba::app.dashboard.shipping_performance.pending_n', ['count' => ':count'])";
                    return t.replace(':count', pending.toLocaleString());
                },

                coverageClass() {
                    const pending = this.cost.pending_count ?? 0;
                    return pending === 0
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-amber-600 dark:text-amber-400';
                },

                periodLine() {
                    return (this.period.start || '') + ' — ' + (this.period.end || '');
                },

                asOfLine() {
                    if (! this.fetchedAt) return '';
                    const d = new Date(this.fetchedAt);
                    const hh = String(d.getHours()).padStart(2, '0');
                    const mm = String(d.getMinutes()).padStart(2, '0');
                    const t = "@lang('fujiba::app.dashboard.inventory_value.as_of', ['time' => ':time'])";
                    return t.replace(':time', `${hh}:${mm}`);
                },
            },

            mounted() {
                this.$emitter.on('reporting-filter-updated', (filters) => {
                    this.refetch(filters);
                });
            },

            methods: {
                formatYen(amount) {
                    const abs    = Math.abs(Number(amount) || 0);
                    const sign   = amount < 0 ? '-' : '';
                    const locale = "{{ app()->getLocale() }}";

                    if (abs < 10000) {
                        return sign + '¥' + Math.round(abs).toLocaleString();
                    }
                    const oneDecimal = (n) => {
                        const r = Math.round(n * 10) / 10;
                        return r === Math.trunc(r)
                            ? Math.trunc(r).toLocaleString()
                            : r.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                    };
                    if (locale === 'ja') {
                        if (abs >= 100000000) return sign + '¥' + oneDecimal(abs / 100000000) + '億';
                        return sign + '¥' + oneDecimal(abs / 10000) + '万';
                    }
                    if (abs >= 1000000) return sign + '¥' + oneDecimal(abs / 1000000) + 'M';
                    return sign + '¥' + oneDecimal(abs / 1000) + 'K';
                },

                async refetch(filters) {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams();
                        if (filters?.channel) params.set('channel', filters.channel);
                        if (filters?.start)   params.set('start',   filters.start);
                        if (filters?.end)     params.set('end',     filters.end);
                        if (filters?.mode)    params.set('mode',    filters.mode);

                        const url = "{{ route('admin.fujiba.dashboard.shipping_performance') }}" + '?' + params.toString();
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (! res.ok) throw new Error('http ' + res.status);
                        const body = await res.json();
                        this.cost        = body?.summary?.cost ?? {};
                        this.free        = body?.summary?.free ?? { by_rule: [] };
                        this.channelName = body?.channel_name ?? this.channelName;
                        this.period      = body?.period ?? this.period;
                        this.fetchedAt   = body?.fetched_at ?? this.fetchedAt;
                        this.mode        = body?.mode ?? this.mode;
                    } catch (e) {
                        console.warn('shipping-performance refetch failed', e);
                    } finally {
                        this.loading = false;
                    }
                },
            },
        });
    </script>
@endPushOnce
