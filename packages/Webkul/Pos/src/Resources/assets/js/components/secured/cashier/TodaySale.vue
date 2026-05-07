<template>
    <div class="mb-4 grid gap-4">
        <div class="mt-4 grid grid-cols-3 gap-4 max-sm:grid-cols-2">
            <div class="box-shadow grid gap-1.5 rounded-lg bg-white dark:bg-gray-900 p-4 max-sm:col-span-2">
                <p class="text-base leading-5 text-gray-900 dark:text-white">
                    {{ $t('pos.cashier.today_sale.opening_drawer_amount') }}
                </p>

                <template v-if="loading">
                    <div class="skeleton h-9 w-full"></div>
                </template>

                <template v-else>
                    <p class="text-3xl font-medium text-slate-600 dark:text-slate-300">
                        {{ formatPrice(todaySale?.openingAmount ?? 0) }}
                    </p>
                </template>
            </div>

            <div class="box-shadow col-span-1 grid gap-1.5 rounded-lg bg-white dark:bg-gray-900 p-4">
                <p class="text-base leading-5 text-gray-900 dark:text-white">
                    {{ $t('pos.cashier.today_sale.cash_payment_sale') }}
                </p>

                <template v-if="loading">
                    <div class="skeleton h-9 w-full"></div>
                </template>

                <template v-else>
                    <p class="text-3xl font-medium text-green-600 dark:text-green-400">
                        {{ formatPrice(todaySale?.cashPaymentSale ?? 0) }}
                    </p>
                </template>
            </div>

            <div class="box-shadow col-span-1 grid gap-1.5 rounded-lg bg-white dark:bg-gray-900 p-4">
                <p class="text-base leading-5 text-gray-900 dark:text-white">
                    {{ $t('pos.cashier.today_sale.other_payment_sale') }}
                </p>

                <template v-if="loading">
                    <div class="skeleton h-9 w-full"></div>
                </template>

                <template v-else>
                    <p class="text-3xl font-medium text-slate-600 dark:text-slate-300">
                        {{ formatPrice(todaySale?.otherPaymentSale ?? 0) }}
                    </p>
                </template>
            </div>
        </div>

        <div class="grid gap-3">
            <div class="flex items-center justify-between">
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ $t('pos.cashier.today_sale.sale_history') }}
                </p>

                <button
                    type="button"
                    class="transparent-button relative"
                    :disabled="loading"
                    @click="$refs.todaySaleDrawer.toggle()"
                >
                    <span class="icon-filter text-2xl text-slate-600 dark:text-slate-300"></span>

                    {{ $t('pos.cashier.today_sale.filters') }}

                    <!-- Active filters badge -->
                    <span 
                        v-if="activeFilters.length > 0"
                        class="bg-blue-500 text-white rounded-full h-2 w-2 absolute right-2 top-1.5 text-sm font-bold"
                    >
                    </span>
                </button>
            </div>

            <!-- Active Filters Display -->
            <ActiveFilters
                v-if="activeFilters.length > 0"
                :active-filters="activeFilters"
                @clear-all="clearAllFilters"
                @remove-filter="removeFilter"
            />

            <div class="rounded-lg bg-white dark:bg-gray-900 p-4">
                <template v-if="loading">
                    <TodaySaleSkeleton />
                </template>

                <template v-else>
                    <div v-if="orders.length">
                        <!-- Desktop Table View (hidden on mobile) -->
                        <div class="hidden md:block overflow-x-auto">
                            <!-- Header Labels -->
                            <div class="grid grid-cols-4 gap-4 text-gray-700 dark:text-gray-300 font-semibold border-b border-gray-300 dark:border-gray-600 pb-3 mb-3 select-none">
                                <div>{{ $t('pos.cashier.today_sale.order_id') }}</div>

                                <div>{{ $t('pos.cashier.today_sale.time') }}</div>

                                <div>{{ $t('pos.cashier.today_sale.order_total') }}</div>

                                <div>{{ $t('pos.cashier.today_sale.payment_mode') }}</div>
                            </div>

                            <!-- Orders Data -->
                            <div
                                v-for="(order, index) in orders"
                                :key="index"
                                :class="[
                                    'grid grid-cols-4 gap-4 py-3 px-2 rounded-md transition-colors hover:bg-gray-50 dark:hover:bg-gray-700',
                                    index % 2 === 0 ? 'bg-gray-100 dark:bg-gray-800/50' : ''
                                ]"
                            >
                                <div class="text-gray-900 dark:text-gray-100 text-sm">
                                    # {{ order.orderId }}
                                </div>

                                <div class="text-gray-900 dark:text-gray-100 text-sm font-medium">
                                    {{ order.createdAt }}
                                </div>

                                <div class="text-gray-900 dark:text-gray-100 text-sm font-medium">
                                    {{ formatPrice(order.orderTotal) }}
                                </div>

                                <div class="text-gray-900 dark:text-gray-100 text-sm font-semibold">
                                    {{ order.paymentMode }}
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Card View (visible only on mobile) -->
                        <div class="md:hidden space-y-3">
                            <div
                                v-for="(order, index) in orders"
                                :key="index"
                                class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
                            >
                                <!-- Date Header -->
                                <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                        {{ order.createdAt }}
                                    </h3>
                                    
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        #{{ index + 1 }}
                                    </span>
                                </div>

                                <!-- Payment Details Grid -->
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                            {{ $t('pos.cashier.today_sale.order_id') }}
                                        </div>

                                        <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                            # {{ order.orderId }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                            {{ $t('pos.cashier.today_sale.order_total') }}
                                        </div>

                                        <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                            {{ formatPrice(order.orderTotal) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Sale (prominent) -->
                                <div class="mb-3">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('pos.cashier.today_sale.payment_mode') }}
                                    </div>

                                    <div class="font-bold text-gray-900 dark:text-gray-100 text-base">
                                        {{ order.paymentMode }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No records message -->
                    <div
                        v-else
                        class="py-8 text-center text-gray-700 dark:text-gray-300"
                    >
                        <div class="text-gray-400 dark:text-gray-500 text-4xl mb-3">📋</div>

                        <div class="font-medium mb-1">
                            {{ $t('pos.cashier.today_sale.no_records') }}
                        </div>

                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('pos.cashier.today_sale.no_records_desc') }}
                        </div>
                    </div>
                </template>

                <!-- Pagination Controls -->
                <Pagination
                    :loading="loading"
                    :pageInfo="pageInfo"
                    @page-change="goToPage"
                />
            </div>
        </div>

        <Teleport to="body">
            <Drawer ref="todaySaleDrawer">
                <template v-slot:header>
                    <div class="flex items-center justify-between px-4">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $t('pos.cashier.today_sale.drawer.title') }}
                        </p>

                        <button
                            v-if="hasAnyFilters"
                            @click="clearAllFilters"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 font-medium"
                        >
                            {{ $t('pos.cashier.sale_history.clear_all') }}
                        </button>
                    </div>
                </template>

                <template v-slot:content>
                    <div class="p-4 space-y-6">
                        <div
                            v-for="filter in allFilters"
                            :key="filter.key"
                            class="space-y-3"
                        >
                            <Label>{{ filter.label }}</Label>

                            <!-- Input Section -->
                            <div class="flex gap-2">
                                <Field
                                    :type="'select'"
                                    :name="filter.operatorModel"
                                    class="min-w-16 max-w-16"
                                    v-model="tempFilters[filter.operatorModel]"
                                >
                                    <option value="=">=</option>
                                    <option value=">">&gt;</option>
                                    <option value="<">&lt;</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="<=">&lt;=</option>
                                </Field>

                                <Field
                                    v-if="
                                        filter.fieldType === 'text'
                                        || filter.fieldType === 'time'
                                    "
                                    :type="filter.fieldType"
                                    :name="filter.fieldName"
                                    :id="filter.fieldId"
                                    v-model="tempFilters[filter.valueModel]"
                                    class="flex-1"
                                />

                                <Field
                                    v-else
                                    :type="filter.fieldType"
                                    :name="filter.fieldName"
                                    :id="filter.fieldId"
                                    v-model="tempFilters[filter.valueModel]"
                                    class="flex-1"
                                >
                                    <option
                                        v-for="option in filter.options"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </Field>

                                <button
                                    @click="addDynamicFilter(filter.key, tempFilters[filter.operatorModel], tempFilters[filter.valueModel])"
                                    :disabled="!tempFilters[filter.valueModel]"
                                    class="px-3 py-1 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ $t('pos.cashier.sale_history.drawer.add') }}
                                </button>
                            </div>

                            <!-- Active Filters -->
                            <div
                                v-if="allTempFilters[filter.key]?.length > 0"
                                class="flex flex-wrap gap-2"
                            >
                                <span
                                    v-for="(f, index) in allTempFilters[filter.key]"
                                    :key="`${filter.key}-${f.operator}-${f.value}-${index}`"
                                    class="inline-flex items-center gap-1 bg-blue-100 dark:bg-blue-800/30 text-blue-800 dark:text-blue-100 border border-blue-300 dark:border-blue-600 rounded-md px-2 py-1 text-xs font-medium max-w-full"
                                >
                                    <span class="font-mono font-bold text-blue-700 dark:text-blue-300">
                                        {{ f.operator }}
                                    </span>

                                    <span class="truncate">
                                        <template v-if="filter.fieldId === 'order_total'">
                                            {{ formatPrice(f.value) }}
                                        </template>

                                        <template v-else-if="filter.fieldId === 'payment_mode'">
                                            {{ $t(`pos.cashier.today_sale.drawer.${f.value}`) }}
                                        </template>

                                        <template v-else>
                                            {{ f.value }}
                                        </template>
                                    </span>

                                    <span
                                        @click="removeTempFilter(filter.key, f)"
                                        class="icon-cross text-sm"
                                    >
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-slot:footer>
                    <button
                        type="button"
                        class="secondary-button w-full"
                        @click="applyFilters(); $refs.todaySaleDrawer.close()"
                    >
                        <span class="icon-filter text-2xl leading-6 text-white"></span>

                        {{ $t('pos.cashier.today_sale.drawer.filter_btn_title') }}
                    </button>
                </template>
            </Drawer>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, computed, watch, onMounted, inject } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useQuery } from '@vue/apollo-composable';
    import { GET_TODAY_SALE } from '@src/graphql/drawer';
    import TodaySaleSkeleton from '@skeletons/cashier/TodaySale.vue';
    import { useOutlet } from '@src/composable/outlet';
    import ActiveFilters from '@src/components/secured/cashier/ActiveFilters.vue';

    const { t } = useI18n();
    const { formatPrice } = useOutlet();
    const isOnline = inject('isOnline');

    const page = ref(1);
    const first = ref(5);

    const filtersReady = ref(false);

    const allFilters = computed(() => [
        {
            key: 'orderId',
            label: t('pos.cashier.today_sale.drawer.order_id'),
            valueModel: 'orderIdValue',
            operatorModel: 'orderIdOperator',
            fieldName: 'order_id',
            fieldId: 'order_id',
            fieldType: 'text',
        },
        {
            key: 'time',
            label: t('pos.cashier.today_sale.drawer.time'),
            valueModel: 'timeValue',
            operatorModel: 'timeOperator',
            fieldName: 'time',
            fieldId: 'time',
            fieldType: 'time',
        },
        {
            key: 'orderTotal',
            label: t('pos.cashier.today_sale.drawer.order_total'),
            valueModel: 'orderTotalValue',
            operatorModel: 'orderTotalOperator',
            fieldName: 'order_total',
            fieldId: 'order_total',
            fieldType: 'text',
        },
        {
            key: 'paymentMode',
            label: t('pos.cashier.today_sale.drawer.payment_mode'),
            valueModel: 'paymentModeValue',
            operatorModel: 'paymentModeOperator',
            fieldName: 'payment_mode',
            fieldId: 'payment_mode',
            fieldType: 'select',
            options: [
                { value: 'pos_cash', label: t('pos.cashier.today_sale.drawer.pos_cash') },
                { value: 'pos_card', label: t('pos.cashier.today_sale.drawer.pos_card') },
                { value: 'pos_split', label: t('pos.cashier.today_sale.drawer.pos_split') }
            ],
        }
    ]);

    const defaultFilters = () => ({
        orderId: [],
        time: [],
        orderTotal: [],
        paymentMode: []
    });

    const defaultTempFilters = () => ({
        orderIdValue: '',
        orderIdOperator: '=',
        timeValue: '',
        timeOperator: '=',
        orderTotalValue: '',
        orderTotalOperator: '=',
        paymentModeValue: '',
        paymentModeOperator: '='
    });

    const appliedFilters = ref(defaultFilters());
    const tempFilters = ref(defaultTempFilters());

    const STORAGE_KEY = 'todaySaleFilters';

    const allTempFilters = ref({
        orderId: [],
        time: [],
        orderTotal: [],
        paymentMode: []
    });

    const loadFiltersFromStorage = () => {
        try {
            const savedFilters = localStorage.getItem(STORAGE_KEY);

            if (savedFilters) {
                const parsed = JSON.parse(savedFilters);

                appliedFilters.value = { ...defaultFilters(), ...parsed };                

                allTempFilters.value = {
                    orderId: [...(appliedFilters.value.orderId || [])],
                    time: [...(appliedFilters.value.time || [])],
                    orderTotal: [...(appliedFilters.value.orderTotal || [])],
                    paymentMode: [...(appliedFilters.value.paymentMode || [])]
                };
            }
        } catch (error) {
            appliedFilters.value = defaultFilters();
        } finally {
            filtersReady.value = true;
        }
    };

    const saveFiltersToStorage = (filters) => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(filters));
        } catch (error) {
            console.error('Error saving filters to storage:', error);
        }
    };

    watch(appliedFilters, (newFilters) => {
        saveFiltersToStorage(newFilters);
    }, { deep: true });

    const buildApiFilters = () => {
        const apiFilters = {};

        ['orderId', 'time', 'orderTotal', 'paymentMode'].forEach(field => {
            if (appliedFilters.value[field]?.length > 0) {
                apiFilters[field] = appliedFilters.value[field].map(filter => ({
                    value: filter.value,
                    operator: filter.operator
                }));
            }
        });

        return apiFilters;
    };

    const variables = computed(() => ({
        page: page.value,
        first: first.value,
        filters: buildApiFilters(),
    }));

    const { result, loading, refetch } = useQuery(GET_TODAY_SALE, variables, {
        enabled: filtersReady,
        skip: computed(() => !isOnline.value)
    });

    const todaySale = computed(() => result.value?.getTodaySales?.todaySale ?? []);
    const orders = computed(() => result.value?.getTodaySales?.data ?? []);
    const pageInfo = computed(() => result.value?.getTodaySales?.paginatorInfo ?? {});

    const activeFilters = computed(() => {
        const filters = [];

        ['orderId', 'time', 'orderTotal', 'paymentMode'].forEach(field => {
            if (appliedFilters.value[field]?.length > 0) {
                appliedFilters.value[field].forEach(filter => {
                    filters.push({
                        key: field,
                        value: filter,
                        label: allFilters.value.find(f => f.key === field).label,
                        displayValue: formatPrice(filter.value),
                        operator: filter.operator,
                    });
                });
            }
        });

        return filters;
    });

    const hasAnyFilters = computed(() => {
        return !! (
            appliedFilters.value.orderId?.length > 0
            || appliedFilters.value.time?.length > 0
            || appliedFilters.value.orderTotal?.length > 0
            || appliedFilters.value.paymentMode?.length > 0
        );
    });

    const addDynamicFilter = (key, operator, value) => {        
        if (! value) {
            return;
        }

        if (! allTempFilters.value[key]) {
            allTempFilters.value[key] = [];
        }

        const exists = allTempFilters.value[key].some(f =>
            f.operator === operator && f.value === value
        );

        if (! exists) {
            allTempFilters.value[key].push({ operator, value });
        }

        tempFilters.value[`${key}Value`] = '';
    };

    const removeTempFilter = (key, filterToRemove) => {
        allTempFilters.value[key] = allTempFilters.value[key].filter(f =>
            ! (f.operator === filterToRemove.operator && f.value === filterToRemove.value)
        );
    };

    const removeFilter = (key, filterToRemove) => {
        appliedFilters.value[key] = appliedFilters.value[key].filter(f =>
            ! (f.operator === filterToRemove.operator && f.value === filterToRemove.value)
        );
    };

    const applyFilters = () => {
        appliedFilters.value.orderId = [...allTempFilters.value.orderId];
        appliedFilters.value.time = [...allTempFilters.value.time];
        appliedFilters.value.orderTotal = [...allTempFilters.value.orderTotal];
        appliedFilters.value.paymentMode = [...allTempFilters.value.paymentMode];
        page.value = 1;
        
        refetch();
    };

    const clearAllFilters = () => {
        appliedFilters.value = defaultFilters();

        tempFilters.value = defaultTempFilters();

        allTempFilters.value = {
            orderId: [],
            time: [],
            orderTotal: [],
            paymentMode: []
        };

        localStorage.removeItem(STORAGE_KEY);

        page.value = 1;
    };

    const goToPage = (newPage) => {
        page.value = newPage;
    };

    onMounted(() => {
        loadFiltersFromStorage();
    });
</script>