<!-- Todays Details Vue Component -->
<v-dashboard-todays-details>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.todays-details />
</v-dashboard-todays-details>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-todays-details-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.todays-details />
        </template>

        <!-- Total Sales Section -->
        <template v-else>
            <div class="box-shadow rounded">
                <div class="flex flex-wrap gap-4 border-b bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <!-- Today's Sales -->
                    <div class="flex min-w-[200px] flex-1 gap-2.5">
                        <div class="h-[60px] max-h-[60px] w-full max-w-[60px] dark:mix-blend-exclusion dark:invert">
                            <img
                                src="{{ bagisto_asset('images/total-sales.svg')}}"
                                title="@lang('admin::app.dashboard.index.today-sales')"
                            >
                        </div>

                        <!-- Sales Stats -->
                        <div class="grid place-content-start gap-1">
                            <p class="text-base font-semibold leading-none text-gray-800 dark:text-white">
                                @{{ report.statistics.total_sales.formatted_total }}
                            </p>

                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                @lang('admin::app.dashboard.index.today-sales')
                            </p>

                            <!-- Percentage Of Sales -->
                            <div class="flex items-center gap-0.5">
                                <span
                                    class="text-base text-emerald-500"
                                    :class="[report.statistics.total_sales.progress < 0 ? 'icon-down-stat text-red-500 dark:!text-red-500' : 'icon-up-stat text-emerald-500 dark:!text-emerald-500']"
                                ></span>

                                <p
                                    class="text-xs font-semibold text-emerald-500"
                                    :class="[report.statistics.total_sales.progress < 0 ?  'text-red-500' : 'text-emerald-500']"
                                >
                                    @{{ Math.abs(report.statistics.total_sales.progress.toFixed(2)) }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Orders -->
                    <div class="flex min-w-[200px] flex-1 gap-2.5">
                        <div class="h-[60px] max-h-[60px] w-full max-w-[60px] dark:mix-blend-exclusion dark:invert">
                            <img
                                src="{{ bagisto_asset('images/total-orders.svg')}}"
                                title="@lang('admin::app.dashboard.index.today-orders')"
                            >
                        </div>

                        <!-- Orders Stats -->
                        <div class="grid place-content-start gap-1">
                            <p class="text-base font-semibold leading-none text-gray-800 dark:text-white">
                                @{{ report.statistics.total_orders.current }}
                            </p>

                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                @lang('admin::app.dashboard.index.today-orders')
                            </p>

                            <!-- Orders Percentage -->
                            <div class="flex items-center gap-0.5">
                                <span
                                    class="text-base text-emerald-500"
                                    :class="[report.statistics.total_orders.progress < 0 ? 'icon-down-stat text-red-500 dark:!text-red-500' : 'icon-up-stat text-emerald-500 dark:!text-emerald-500']"
                                ></span>

                                <p
                                    class="text-xs font-semibold text-emerald-500"
                                    :class="[report.statistics.total_orders.progress < 0 ?  'text-red-500' : 'text-emerald-500']"
                                >
                                    @{{ Math.abs(report.statistics.total_orders.progress.toFixed(2)) }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Customers -->
                    <div class="flex min-w-[200px] flex-1 gap-2.5">
                        <div class="h-[60px] max-h-[60px] w-full max-w-[60px] dark:mix-blend-exclusion dark:invert">
                            <img
                                src="{{ bagisto_asset('images/customers.svg')}}"
                                title="@lang('admin::app.dashboard.index.today-customers')"
                            >
                        </div>

                        <!-- Customers Stats -->
                        <div class="grid place-content-start gap-1">
                            <p class="text-base font-semibold leading-none text-gray-800 dark:text-white">
                                @{{ report.statistics.total_customers.current }}
                            </p>

                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                @lang('admin::app.dashboard.index.today-customers')
                            </p>

                            <!-- Customers Percentage -->
                            <div class="flex items-center gap-0.5">
                                <span
                                    class="text-base text-emerald-500"
                                    :class="[report.statistics.total_customers.progress < 0 ? 'icon-down-stat text-red-500 dark:!text-red-500' : 'icon-up-stat text-emerald-500 dark:!text-emerald-500']"
                                ></span>

                                <p
                                    class="text-xs font-semibold text-emerald-500"
                                    :class="[report.statistics.total_customers.progress < 0 ?  'text-red-500' : 'text-emerald-500']"
                                >
                                    @{{ Math.abs(report.statistics.total_customers.progress.toFixed(2)) }}%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today Orders Details -->
                <!-- Wrapped in template v-if so the v-for is never evaluated
                     when report.statistics is missing/null/empty. Belt-and-
                     braces with the JS axios sanitize + PHP filter. -->
                <template v-if="safeOrders.length">
                <div
                    v-for="order in safeOrders"
                    :key="order.id"
                    class="border-b bg-white p-4 transition-all hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-950"
                >
                    <div class="flex flex-wrap gap-4">
                        <!-- Total Sales -->
                        <div class="flex min-w-[180px] flex-1 gap-2.5">
                            <div class="flex flex-col gap-1.5">
                                <!-- Order Id -->
                                <p class="text-base font-semibold leading-none text-gray-800 dark:text-white">
                                    @{{ "@lang('admin::app.dashboard.index.order-id', ['id' => ':replace'])".replace(':replace', order.increment_id) }}
                                </p>
    
                                <p class="text-gray-600 dark:text-gray-300">
                                    @{{ order.created_at}}
                                </p>
    
                                <!-- Order Status -->
                                <p :class="'label-' + order.status">
                                    @{{ order.status_label }}
                                </p>
                            </div>
                        </div>

                        <div class="flex min-w-[180px] flex-1 gap-2.5">
                            <div class="flex flex-col gap-1.5">
                                <p class="text-base font-semibold leading-none text-gray-800 dark:text-white">
                                    @{{ order.formatted_base_grand_total }}
                                </p>
        
                                <!-- Payment Mode -->
                                <p class="text-gray-600 dark:text-gray-300">
                                    @{{ order.payment_method }}
                                </p>
        
                                <!-- Channel Name -->
                                <p class="text-gray-600 dark:text-gray-300">
                                    @{{ order.channel_name }}
                                </p>
                            </div>
                        </div>

                        <div class="flex min-w-[200px] flex-1 gap-2.5">
                            <div class="flex flex-col gap-1.5">
                            <!-- Customer Details -->
                                <p class="text-base text-gray-800 dark:text-white">
                                    @{{ order.customer_name }}
                                </p>
        
                                <p class="max-w-[180px] break-words text-gray-600 dark:text-gray-300">
                                    @{{ order.customer_email }}
                                </p>
        
                                <!-- Order Address -->
                                <p class="text-gray-600 dark:text-gray-300">
                                    @{{ order.billing_address }}
                                </p>
                            </div>
                        </div>
 
                        <div class="flex min-w-[180px] flex-1 items-center justify-between gap-2.5">
                            <div class="flex flex-col gap-1.5">
                                <!-- Ordered Product Images -->
                                <div
                                    class="flex flex-wrap gap-1.5"
                                    v-html="order.items"
                                >
                                </div>
                            </div>

                             <!-- View More Icon -->
                             <a :href="'{{ route('admin.sales.orders.view', ':replace') }}'.replace(':replace', order.id)">
                                <span class="icon-sort-right rtl:icon-sort-left cursor-pointer p-1.5 text-2xl hover:rounded-md hover:bg-gray-200 dark:hover:bg-gray-800 ltr:ml-1 rtl:mr-1"></span>
                            </a>
                        </div>
                    </div>
                </div>
                </template>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-todays-details', {
            template: '#v-dashboard-todays-details-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            computed: {
                /**
                 * Bulletproof order list: never throws regardless of upstream
                 * data shape. Returns [] on any error path.
                 */
                safeOrders() {
                    try {
                        const stats = this.report && this.report.statistics;
                        if (! stats || typeof stats !== 'object') return [];
                        const arr = Array.isArray(stats.orders) ? stats.orders : [];
                        return arr.filter(o => o && typeof o === 'object' && o.status);
                    } catch (_) {
                        return [];
                    }
                },
            },

            mounted() {
                this.getStats(window.fjSavedFilter ? window.fjSavedFilter() : {});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    var filters = Object.assign({}, filters);

                    filters.type = 'today';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            // Defensive sanitize: drop null entries and orders
                            // missing critical fields BEFORE handing data to Vue
                            // template. Belt-and-braces with the PHP-side filter
                            // in Webkul\Admin\Helpers\Dashboard::getTodayStats().
                            const data = response.data || {};
                            try {
                                if (data.statistics && Array.isArray(data.statistics.orders)) {
                                    data.statistics.orders = data.statistics.orders
                                        .filter(o => o && typeof o === 'object' && o.status);
                                } else if (data.statistics) {
                                    data.statistics.orders = [];
                                }
                            } catch (e) {
                                console.warn('todays-details sanitize failed', e);
                                if (data.statistics) data.statistics.orders = [];
                            }
                            this.report = data;

                            this.isLoading = false;
                        })
                        .catch(error => {});
                }
            }
        });
    </script>
@endPushOnce