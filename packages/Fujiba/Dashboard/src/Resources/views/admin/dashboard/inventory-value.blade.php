@php
    $kimono     = $inventoryValueSummary['kimono']     ?? ['stock_value' => 0, 'priced_sku_count' => 0];
    $allCompany = $inventoryValueSummary['all']        ?? ['stock_value' => 0, 'priced_sku_count' => 0];
    $missing    = (int) ($inventoryValueSummary['missing']    ?? 0);
    $fetchedAt  = $inventoryValueSummary['fetched_at'] ?? null;
    $asOfLabel  = $fetchedAt ? \Illuminate\Support\Carbon::parse($fetchedAt)->format('H:i') : '';
    $hasData    = $kimono['priced_sku_count'] > 0 || $allCompany['priced_sku_count'] > 0;
@endphp

<div class="flex flex-col gap-2">
    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
        @lang('fujiba::app.dashboard.inventory_value.title')
    </p>

    <v-fujiba-inventory-value
        initial-channel="{{ $inventoryValueChannelId }}"
        :initial-summary="{{ json_encode($inventoryValueSummary, JSON_UNESCAPED_UNICODE) }}"
    ></v-fujiba-inventory-value>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-fujiba-inventory-value-template"
    >
        <div class="box-shadow rounded bg-white p-3 md:p-4 dark:bg-gray-900">
            {{-- Wrong-channel state: admin selected non-kimono channel via filter dropdown --}}
            <template v-if="wrongChannel">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @lang('fujiba::app.dashboard.common.wrong_channel')
                </p>
            </template>

            {{-- Empty state: no priced SKUs in either total --}}
            <template v-else-if="! hasData">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @lang('fujiba::app.dashboard.inventory_value.empty_state')
                </p>
            </template>

            {{-- Dual-total: kimono + all-company side-by-side on desktop, stacked on mobile --}}
            <template v-else>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                    <!-- Kimono channel total -->
                    <div class="min-w-0 flex flex-col gap-1">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                            @lang('fujiba::app.dashboard.inventory_value.kimono_channel_label')
                        </p>
                        <p class="text-lg md:text-2xl font-semibold leading-none text-gray-800 dark:text-white">
                            @{{ formatPrice(summary.kimono.stock_value) }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-300">
                            @{{ summary.kimono.priced_sku_count.toLocaleString() }}
                            @lang('fujiba::app.dashboard.inventory_value.priced_sku_count')
                        </p>
                    </div>

                    <!-- All-company total -->
                    <div class="min-w-0 flex flex-col gap-1 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-800 pt-3 md:pt-0 md:pl-4">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                            @lang('fujiba::app.dashboard.inventory_value.all_company_label')
                            <span class="font-normal text-gray-500 dark:text-gray-400">
                                — @lang('fujiba::app.dashboard.inventory_value.all_company_subtitle')
                            </span>
                        </p>
                        <p class="text-lg md:text-2xl font-semibold leading-none text-gray-800 dark:text-white">
                            @{{ formatPrice(summary.all.stock_value) }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-300">
                            @{{ summary.all.priced_sku_count.toLocaleString() }}
                            @lang('fujiba::app.dashboard.inventory_value.priced_sku_count')
                        </p>
                    </div>
                </div>

                <!-- Missing-cost badge + as-of timestamp -->
                <div class="flex flex-wrap items-center justify-between gap-2 mt-3 md:mt-4 pt-3 border-t border-gray-200 dark:border-gray-800">
                    <a
                        v-if="summary.missing > 0"
                        href="{{ route('admin.catalog.products.index') }}?missing_cost=1"
                        class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40 transition-colors"
                    >
                        <span class="icon-information text-base"></span>
                        @{{ missingBadgeText }}
                        <span class="icon-arrow-right text-base"></span>
                    </a>
                    <span v-else></span>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @{{ asOfLine }}
                    </p>
                </div>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-fujiba-inventory-value', {
            template: '#v-fujiba-inventory-value-template',

            props: {
                initialChannel: { type: [Number, String], required: true },
                initialSummary: { type: Object, required: true },
            },

            data() {
                return {
                    summary: this.initialSummary,
                    selectedChannel: '',
                    asOfLabel: @json($asOfLabel),
                };
            },

            computed: {
                hasData() {
                    return (this.summary?.kimono?.priced_sku_count ?? 0) > 0
                        || (this.summary?.all?.priced_sku_count ?? 0) > 0;
                },

                wrongChannel() {
                    // Empty string = "all channels" filter, treat as kimono-compatible.
                    // Filter emits channel codes ('default', 'mmfood', ...), our pin is the kimono code.
                    if (this.selectedChannel === '' || this.selectedChannel === 'default') {
                        return false;
                    }
                    return true;
                },

                missingBadgeText() {
                    return "@lang('fujiba::app.dashboard.inventory_value.missing_cost_badge', ['count' => ':count'])"
                        .replace(':count', this.summary.missing.toLocaleString());
                },

                asOfLine() {
                    return "@lang('fujiba::app.dashboard.inventory_value.as_of', ['time' => ':time'])"
                        .replace(':time', this.asOfLabel);
                },
            },

            mounted() {
                this.$emitter.on('reporting-filter-updated', (filters) => {
                    this.selectedChannel = filters.channel ?? '';
                });
            },

            methods: {
                formatPrice(amount) {
                    const abs    = Math.abs(Number(amount) || 0);
                    const sign   = amount < 0 ? '-' : '';
                    const locale = "{{ app()->getLocale() }}";

                    if (abs < 10000) {
                        return sign + '¥' + Math.round(abs).toLocaleString();
                    }

                    const oneDecimal = (n) => {
                        const r = Math.round(n * 10) / 10;
                        if (r === Math.trunc(r)) {
                            return Math.trunc(r).toLocaleString();
                        }
                        // toLocaleString with maxDigits keeps the comma + decimal
                        return r.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                    };

                    if (locale === 'ja') {
                        if (abs >= 100000000 || Math.round(abs / 10000 * 10) / 10 >= 10000) {
                            return sign + '¥' + oneDecimal(abs / 100000000) + '億';
                        }
                        return sign + '¥' + oneDecimal(abs / 10000) + '万';
                    }

                    if (abs >= 1000000 || Math.round(abs / 1000 * 10) / 10 >= 1000) {
                        return sign + '¥' + oneDecimal(abs / 1000000) + 'M';
                    }
                    return sign + '¥' + oneDecimal(abs / 1000) + 'K';
                },
            },
        });
    </script>
@endPushOnce
