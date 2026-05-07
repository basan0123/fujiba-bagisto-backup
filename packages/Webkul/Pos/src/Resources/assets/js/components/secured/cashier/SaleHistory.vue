<template>
    <div class="my-4 grid gap-3">
        <div class="flex items-center justify-between">
            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $t('pos.cashier.sale_history.title') }}
            </p>

            <button
                type="button"
                class="transparent-button relative"
                :disabled="loading"
                @click="$refs.totalSaleDrawer.toggle()"
            >
                <span class="icon-filter text-2xl text-slate-600 dark:text-slate-300"></span>
                
                {{ $t('pos.cashier.sale_history.filters') }}
                
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
                <SaleHistorySkeleton />
            </template>

            <template v-else>
                <div v-if="orders.length">
                    <!-- Desktop Table View (hidden on mobile) -->
                    <div class="hidden md:block overflow-x-auto">
                        <!-- Header Labels -->
                        <div class="grid grid-cols-5 gap-4 text-gray-700 dark:text-gray-300 font-semibold border-b border-gray-300 dark:border-gray-600 pb-3 mb-3 select-none">
                            <div>{{ $t('pos.cashier.sale_history.date') }}</div>
                            <div>{{ $t('pos.cashier.sale_history.cash_payments') }}</div>
                            <div>{{ $t('pos.cashier.sale_history.other_payments') }}</div>
                            <div>{{ $t('pos.cashier.sale_history.total_sale') }}</div>
                            <div>{{ $t('pos.cashier.sale_history.drawer_note') }}</div>
                        </div>

                        <!-- Orders Data -->
                        <div
                            v-for="(order, index) in orders"
                            :key="order.id || index"
                            :class="[
                                'grid grid-cols-5 gap-4 py-3 px-2 rounded-md transition-colors hover:bg-gray-50 dark:hover:bg-gray-700',
                                index % 2 === 0 ? 'bg-gray-100 dark:bg-gray-800/50' : ''
                            ]"
                        >
                            <div class="text-gray-900 dark:text-gray-100 text-sm">
                                {{ order.date }}
                            </div>

                            <div class="text-gray-900 dark:text-gray-100 text-sm font-medium">
                                {{ formatPrice(order.cashPayment) }}
                            </div>
                            
                            <div class="text-gray-900 dark:text-gray-100 text-sm font-medium">
                                {{ formatPrice(order.otherPayment) }}
                            </div>

                            <div class="text-gray-900 dark:text-gray-100 text-sm font-semibold">
                                {{ formatPrice(order.totalSale) }}
                            </div>

                            <div
                                class="text-gray-600 dark:text-gray-400 text-sm truncate"
                                :title="order.drawerNote"
                            >
                                {{ order.drawerNote ?? 'N/A' }}
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Card View (visible only on mobile) -->
                    <div class="md:hidden space-y-3">
                        <div
                            v-for="(order, index) in orders"
                            :key="order.id || index"
                            class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
                        >
                            <!-- Date Header -->
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                    {{ order.date }}
                                </h3>

                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    #{{ index + 1 }}
                                </span>
                            </div>

                            <!-- Payment Details Grid -->
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('pos.cashier.sale_history.cash_payments') }}
                                    </div>

                                    <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                        {{ formatPrice(order.cashPayment) }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('pos.cashier.sale_history.other_payments') }}
                                    </div>

                                    <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                        {{ formatPrice(order.otherPayment) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Total Sale (prominent) -->
                            <div class="mb-3">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('pos.cashier.sale_history.total_sale') }}
                                </div>

                                <div class="font-bold text-gray-900 dark:text-gray-100 text-base">
                                    {{ formatPrice(order.totalSale) }}
                                </div>
                            </div>

                            <!-- Drawer Note -->
                            <div
                                v-if="order.drawerNote"
                                class="pt-2 border-t border-gray-200 dark:border-gray-600"
                            >
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('pos.cashier.sale_history.drawer_note') }}
                                </div>

                                <div class="text-sm text-gray-700 dark:text-gray-300 break-words">
                                    {{ order.drawerNote }}
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
                        {{ $t('pos.cashier.sale_history.no_records') }}
                    </div>

                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('pos.cashier.sale_history.no_records_desc') }}
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

        <Teleport to="body">
            <Drawer ref="totalSaleDrawer">
                <template v-slot:header>
                    <div class="flex items-center justify-between px-4">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $t('pos.cashier.sale_history.drawer.title') }}
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
                    <div class="p-4 space-y-4">
                        <!-- Date Range Filter -->
                        <div class="space-y-3">
                            <Label for="date_from">
                                {{ $t('pos.cashier.sale_history.drawer.date_range') }}
                            </Label>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <Field
                                    type="date"
                                    name="date_from"
                                    id="date_from"
                                    :placeholder="$t('pos.cashier.sale_history.drawer.from_date')"
                                    v-model="tempFilters.dateFrom"
                                />

                                <Field
                                    type="date"
                                    name="date_to"
                                    id="date_to"
                                    :placeholder="$t('pos.cashier.sale_history.drawer.to_date')"
                                    v-model="tempFilters.dateTo"
                                />
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div
                                v-for="filter in numericFilters"
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
                                        type="text"
                                        :name="filter.fieldName"
                                        :id="filter.fieldId"
                                        placeholder="0.00"
                                        v-model="tempFilters[filter.valueModel]"
                                        class="flex-1"
                                        step="0.01"
                                        min="0"
                                    />

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
                                    v-if="tempNumericFilters[filter.key]?.length > 0"
                                    class="flex flex-wrap gap-2"
                                >
                                    <span
                                        v-for="(f, index) in tempNumericFilters[filter.key]"
                                        :key="`${filter.key}-${f.operator}-${f.value}-${index}`"
                                        class="inline-flex items-center gap-1 bg-blue-100 dark:bg-blue-800/30 text-blue-800 dark:text-blue-100 border border-blue-300 dark:border-blue-600 rounded-md px-2 py-1 text-xs font-medium max-w-full"
                                    >
                                        <span class="font-mono font-bold text-blue-700 dark:text-blue-300">
                                            {{ f.operator }}
                                        </span>

                                        <span class="truncate">
                                            {{ formatPrice(f.value) }}
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
                    </div>
                </template>

                <template v-slot:footer>
                    <button
                        type="button"
                        class="secondary-button w-full"
                        @click="applyFilters(); $refs.totalSaleDrawer.toggle()"
                    >
                        <span class="icon-filter text-xl leading-6 mr-2"></span>

                        {{ $t('pos.cashier.sale_history.drawer.filter_btn_title') }}
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
    import { GET_SALE_HISTORY } from '@src/graphql/drawer';
    import SaleHistorySkeleton from '@skeletons/cashier/SaleHistory.vue';
    import ActiveFilters from '@src/components/secured/cashier/ActiveFilters.vue';
    import { useOutlet } from '@src/composable/outlet';

    const { t } = useI18n();
    const { formatPrice } = useOutlet();
    const isOnline = inject('isOnline');

    const page = ref(1);
    const first = ref(5);

    const filtersReady = ref(false);

    const numericFilters = computed(() => [
        {
            key: 'cashPayment',
            label: t('pos.cashier.sale_history.drawer.cash_payment'),
            valueModel: 'cashPaymentValue',
            operatorModel: 'cashPaymentOperator',
            fieldName: 'cash_payment',
            fieldId: 'cash_payment'
        },
        {
            key: 'otherPayment',
            label: t('pos.cashier.sale_history.drawer.other_payment'),
            valueModel: 'otherPaymentValue',
            operatorModel: 'otherPaymentOperator',
            fieldName: 'other_payment',
            fieldId: 'other_payment'
        },
        {
            key: 'totalSale',
            label: t('pos.cashier.sale_history.drawer.total_sale'),
            valueModel: 'totalSaleValue',
            operatorModel: 'totalSaleOperator',
            fieldName: 'total_sale',
            fieldId: 'total_sale'
        }
    ]);

    const defaultFilters = () => ({
        dateFrom: null,
        dateTo: null,
        cashPayment: [],
        otherPayment: [],
        totalSale: []
    });

    const defaultTempFilters = () => ({
        dateFrom: '',
        dateTo: '',
        cashPaymentOperator: '=',
        cashPaymentValue: '',
        otherPaymentOperator: '=',
        otherPaymentValue: '',
        totalSaleOperator: '=',
        totalSaleValue: ''
    });

    const appliedFilters = ref(defaultFilters());
    const tempFilters = ref(defaultTempFilters());

    const STORAGE_KEY = 'saleHistoryFilters';

    const tempNumericFilters = ref({
        cashPayment: [],
        otherPayment: [],
        totalSale: []
    });

    const loadFiltersFromStorage = () => {
        try {
            const savedFilters = localStorage.getItem(STORAGE_KEY);

            if (savedFilters) {
                const parsed = JSON.parse(savedFilters);

                appliedFilters.value = { ...defaultFilters(), ...parsed };

                if (appliedFilters.value.dateFrom) {
                    tempFilters.value.dateFrom = appliedFilters.value.dateFrom;
                }

                if (appliedFilters.value.dateTo) {
                    tempFilters.value.dateTo = appliedFilters.value.dateTo;
                }

                tempNumericFilters.value = {
                    cashPayment: [...(appliedFilters.value.cashPayment || [])],
                    otherPayment: [...(appliedFilters.value.otherPayment || [])],
                    totalSale: [...(appliedFilters.value.totalSale || [])]
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

        if (appliedFilters.value.dateFrom) {
            apiFilters.dateFrom = appliedFilters.value.dateFrom;
        }

        if (appliedFilters.value.dateTo) {
            apiFilters.dateTo = appliedFilters.value.dateTo;
        }

        ['cashPayment', 'otherPayment', 'totalSale'].forEach(field => {
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

    const { result, loading, refetch } = useQuery(GET_SALE_HISTORY, variables, {
        enabled: filtersReady,
        skip: computed(() => !isOnline.value)
    });

    const orders = computed(() => result.value?.getSaleHistory?.data ?? []);
    const pageInfo = computed(() => result.value?.getSaleHistory?.paginatorInfo ?? {});

    const activeFilters = computed(() => {
        const filters = [];

        if (appliedFilters.value.dateFrom) {
            filters.push({
                key: 'dateFrom',
                value: appliedFilters.value.dateFrom,
                label: t('pos.cashier.sale_history.drawer.from_date'),
                displayValue: appliedFilters.value.dateFrom,
                operator: ':',
            });
        }

        if (appliedFilters.value.dateTo) {
            filters.push({
                key: 'dateTo',
                value: appliedFilters.value.dateTo,
                label: t('pos.cashier.sale_history.drawer.to_date'),
                displayValue: appliedFilters.value.dateTo,
                operator: ':',
            });
        }

        ['cashPayment', 'otherPayment', 'totalSale'].forEach(field => {
            if (appliedFilters.value[field]?.length > 0) {
                appliedFilters.value[field].forEach(filter => {
                    filters.push({
                        key: field,
                        value: filter,
                        label: numericFilters.value.find(f => f.key === field).label,
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
            appliedFilters.value.dateFrom
            || appliedFilters.value.dateTo
            || appliedFilters.value.cashPayment?.length > 0
            || appliedFilters.value.otherPayment?.length > 0
            || appliedFilters.value.totalSale?.length > 0
        );
    });

    const addDynamicFilter = (key, operator, value) => {
        if (! value) {
            return;
        }

        if (! tempNumericFilters.value[key]) {
            tempNumericFilters.value[key] = [];
        }

        const exists = tempNumericFilters.value[key].some(f =>
            f.operator === operator && f.value === value
        );

        if (! exists) {
            tempNumericFilters.value[key].push({ operator, value });
        }

        tempFilters.value[`${key}Value`] = '';
    };

    const removeTempFilter = (key, filterToRemove) => {
        tempNumericFilters.value[key] = tempNumericFilters.value[key].filter(f =>
            ! (f.operator === filterToRemove.operator && f.value === filterToRemove.value)
        );
    };

    const removeFilter = (key, filterToRemove) => {
        if (key === 'dateFrom') {
            appliedFilters.value.dateFrom = null;
            tempFilters.value.dateFrom = '';
        } else if (key === 'dateTo') {
            appliedFilters.value.dateTo = null;
            tempFilters.value.dateTo = '';
        } else {
            appliedFilters.value[key] = appliedFilters.value[key].filter(f =>
                ! (f.operator === filterToRemove.operator && f.value === filterToRemove.value)
            );
        }
    };

    const applyFilters = () => {
        appliedFilters.value.dateFrom = tempFilters.value.dateFrom || null;
        appliedFilters.value.dateTo = tempFilters.value.dateTo || null;
        appliedFilters.value.cashPayment = [...tempNumericFilters.value.cashPayment];
        appliedFilters.value.otherPayment = [...tempNumericFilters.value.otherPayment];
        appliedFilters.value.totalSale = [...tempNumericFilters.value.totalSale];
        page.value = 1;

        refetch();
    };

    const clearAllFilters = () => {
        appliedFilters.value = defaultFilters();

        tempFilters.value = defaultTempFilters();

        tempNumericFilters.value = {
            cashPayment: [],
            otherPayment: [],
            totalSale: []
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
