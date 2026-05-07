<template>
    <div class="flex gap-4 max-sm:flex-col">
        <div class="w-full rounded-lg bg-white dark:bg-gray-900 p-4">
            <div class="relative mb-4 flex items-center text-gray-900 dark:text-gray-200">
                <input
                    type="text"
                    class="h-12 w-full px-8 text-base font-normal leading-5 bg-transparent border-b text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500"
                    :placeholder="$t('pos.orders.offline.search_order_id')"
                    v-model="searchTerm"
                />

                <i class="icon-search left absolute top-3.5 flex items-center text-xl leading-5 dark:text-gray-100"></i>
            </div>

            <template v-if="loading">
                <OrderListSkeleton />
            </template>

            <template v-else>
                <div v-if="outletOrders.length">
                    <!-- Desktop Table View (hidden below lg) -->
                        <div class="hidden lg:block overflow-x-auto">
                        <!-- Header Labels -->
                        <div class="grid grid-cols-4 gap-4 text-gray-700 dark:text-gray-300 font-semibold border-b border-gray-300 dark:border-gray-600 pb-3 mb-3 select-none">
                            <div>{{ $t('pos.orders.history.order_id') }}</div>

                            <div>{{ $t('pos.orders.history.date') }}</div>

                            <div>{{ $t('pos.orders.history.total_sales') }}</div>

                            <div>{{ $t('pos.orders.history.order_status.title') }}</div>
                        </div>

                        <!-- Orders Data -->
                        <div
                            v-for="(outletOrder, index) in outletOrders"
                            :key="index"
                            @click="currentOrder = outletOrder;"
                            :class="[
                                'grid grid-cols-4 gap-4 py-3 px-2 rounded-md transition-colors hover:bg-gray-50 dark:hover:bg-gray-700',
                                index % 2 === 0 ? 'bg-gray-100 dark:bg-gray-800/50' : ''
                            ]"
                        >
                            <div class="text-gray-900 dark:text-gray-100 text-sm">
                                # {{ outletOrder?.order?.id }}
                            </div>

                            <div class="text-gray-900 dark:text-gray-100 text-sm font-medium">
                                {{ outletOrder?.order?.createdAt }}
                            </div>

                            <div class="text-gray-900 dark:text-gray-100 text-sm font-medium">
                                {{ formatPrice(outletOrder?.order?.grandTotal) }}
                            </div>

                            <div class="text-gray-900 dark:text-gray-100 text-sm font-semibold">
                                <template v-if="outletOrder?.order?.status == 'completed'">
                                    <span class="label-success dark:label-success-dark">
                                        {{ $t('pos.orders.history.order_status.completed') }}
                                    </span>
                                </template>

                                <template v-else-if="outletOrder?.order?.status == 'closed'">
                                    <span class="label-closed dark:label-closed-dark">
                                        {{ $t('pos.orders.history.order_status.returned') }}
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Card View (visible below lg) -->
                    <div class="lg:hidden space-y-3">
                        <div
                            v-for="(outletOrder, index) in outletOrders"
                            :key="index"
                            @click="showOrderInfo(outletOrder)"
                            class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
                        >
                            <!-- Date Header -->
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                    {{ outletOrder?.order?.createdAt }}
                                </h3>
                                
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    #{{ index + 1 }}
                                </span>
                            </div>

                            <!-- Payment Details Grid -->
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('pos.orders.history.order_id') }}
                                    </div>

                                    <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                        # {{ outletOrder?.order?.id }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('pos.orders.history.total_sales') }}
                                    </div>

                                    <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                        {{ formatPrice(outletOrder?.order?.grandTotal) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Total Sale (prominent) -->
                            <div class="mb-3">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('pos.orders.history.order_status.title') }}
                                </div>

                                <div class="font-bold text-gray-900 dark:text-gray-100 text-base">
                                    <template v-if="outletOrder?.order?.status == 'completed'">
                                        <span class="label-success dark:label-success-dark">
                                            {{ $t('pos.orders.history.order_status.completed') }}
                                        </span>
                                    </template>

                                    <template v-else-if="outletOrder?.order?.status == 'closed'">
                                        <span class="label-closed dark:label-closed-dark">
                                            {{ $t('pos.orders.history.order_status.returned') }}
                                        </span>
                                    </template>
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
                        {{ $t('pos.orders.history.no_records') }}
                    </div>

                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('pos.orders.history.no_records_desc') }}
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

        <!-- For Desktop view -->
        <Info
            :outletOrder="currentOrder"
            :showReturnButton="isOrderCanBeReturned && isOnline"
            @returnOrder="handleOrderReturn"
        />

        <!-- For Mobile view -->
        <Teleport to="body">
            <Drawer ref="orderInfoDrawer">
                <template v-slot:header>
                    <div class="flex flex-col px-2 py-4">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $t('pos.orders.info.order_id') }} #{{ currentOrder.order?.id }}
                        </p>

                        <p class="text-sm leading-4 dark:text-gray-300">
                            {{ currentOrder.order?.customer?.name }}
                        </p>
                    </div>
                </template>

                <template v-slot:content>
                    <div class="flex min-h-full flex-col justify-between">
                        <div class="flex flex-col gap-1 overflow-hidden p-4">
                            <div
                                class="flex min-h-14 flex-col gap-4 rounded-lg p-2"
                                :class="[index % 2 == 0 ? 'bg-gray-100 dark:bg-gray-800' : '']"
                                v-for="(item, index) in currentOrder?.order?.items"
                                :key="index"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex gap-2.5">
                                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-white">
                                            {{ index + 1 }}.
                                        </p>

                                        <div class="flex flex-col">
                                            <p class="text-base font-semibold leading-5 text-gray-900 dark:text-white">
                                                {{ item.name }}
                                            </p>

                                            <p class="text-sm font-normal leading-4 text-neutral-400 dark:text-neutral-400">
                                                {{ item.sku }}
                                            </p>
                                        </div>
                                    </div>

                                    <p class="text-base font-semibold leading-5 text-gray-900 dark:text-white">
                                        {{ formatPrice(item?.price) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-4 bg-gray-100 dark:bg-gray-800 p-4">
                            <div class="flex flex-col gap-1">
                                <div class="flex justify-between">
                                    <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-300">
                                        {{ $t('pos.orders.info.subtotal') }}
                                    </p>

                                    <p class="text-base font-semibold leading-5 text-gray-900 dark:text-white">
                                        {{ formatPrice(currentOrder.order?.subTotal) }}
                                    </p>
                                </div>

                                <div class="flex justify-between">
                                    <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-300">
                                        {{ $t('pos.orders.info.tax') }}
                                    </p>

                                    <p class="text-base font-semibold leading-5 text-gray-900 dark:text-white">
                                        {{ formatPrice(currentOrder.order?.taxAmount ?? 0) }}
                                    </p>
                                </div>

                                <div class="flex justify-between">
                                    <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-300">
                                        {{ $t('pos.orders.info.discount') }}
                                    </p>

                                    <p class="text-base font-semibold leading-5 text-gray-900 dark:text-white">
                                        {{ formatPrice(currentOrder.order?.discountAmount ?? 0) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex justify-between">
                                <p class="text-xl font-medium leading-6 text-gray-900 dark:text-white">
                                    {{ $t('pos.common.cart.payable_amount') }}
                                </p>

                                <p class="text-xl font-medium leading-6 text-gray-900 dark:text-white">
                                    {{ formatPrice(currentOrder.order?.grandTotal) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-slot:footer>
                    <div class="flex flex-col gap-2">
                        <div class="flex gap-2.5">
                            <div class="flex-1">
                                <Print :outletOrder="currentOrder" />
                            </div>

                            <div class="flex-1">
                                <Print :outletOrder="currentOrder" receiptType="ryoshuho" />
                            </div>
                        </div>

                        <button
                            v-if="isOnline"
                            type="button"
                            class="danger-button w-full"
                            @click="handleOrderReturn(currentOrder)"
                        >
                            <span class="icon-sync text-2xl"></span>

                            {{ $t('pos.orders.history.return_btn') }}
                        </button>
                    </div>
                </template>
            </Drawer>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, inject, watch, computed, onBeforeMount } from 'vue';
    import Info from '@components/secured/orders/Info.vue';
    import Print from '@components/secured/common/Print.vue';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import OrderListSkeleton from '@skeletons/orders/List.vue';
    import { useMutation } from '@vue/apollo-composable';
    import { RETURN_ORDER } from '@src/graphql/orders';
    import { useOutlet } from '@src/composable/outlet';

    /**
     * General variables
     */
    const DB = useIndexedDB();
    const emitter = inject('emitter');
    const isOnline = inject('isOnline');
    const { formatPrice } = useOutlet();

    /**
     * Fetch orders
     */
    const outletOrders = ref([]);
    const pageInfo = ref({
        currentPage: 1,
        lastPage: 1,
        total: 0,
        count: 0
    });
    const perPage = 5;
    const loading = ref(true);

    onBeforeMount(async () => {
        fetchPaginatedOrders();
    });

    /**
     * Paginator
     */
    const fetchPaginatedOrders = async (page = 1) => {
        loading.value = true;

        const result = await DB.paginateItems('orders', page, perPage);
        
        outletOrders.value = result.data;
        pageInfo.value = result.paginatorInfo;

        loading.value = false;
    };

    const goToPage = (page) => {
        fetchPaginatedOrders(page);
    };

    /**
     * Show order info
     */
    const currentOrder = ref({});
    const orderInfoDrawer = ref(null);
    const showOrderInfo = (order) => {
        currentOrder.value = order;

        orderInfoDrawer.value.open();
    };

    /**
     * Search term
     */
    const searchTerm = ref('');

    watch(searchTerm, (newValue) => {
        searchOrder(newValue);
    });

    /**
     * Search order
     */
    const searchOrder = async (searchTerm = '', page = 1, limit = 5) => {
        loading.value = true;

        const db = await DB.getDb();
        const tx = db.transaction('orders', 'readonly');
        const store = tx.objectStore('orders');

        const term = searchTerm.toLowerCase();
        const filteredData = [];

        let cursor = await store.openCursor(null, 'prev');

        while (cursor) {
            const value = cursor.value;

            if (
                ! term
                || (
                    value.order?.customerFirstName?.toLowerCase().includes(term)
                    || value.order?.customerLastName?.toLowerCase().includes(term)
                    || value.order?.customerEmail?.toLowerCase().includes(term)
                    || value.order?.id?.toString().includes(term)
                    || value.order?.status?.toLowerCase().includes(term)
                )
            ) {
                filteredData.push(value);
            }

            cursor = await cursor.continue();
        }

        const total = filteredData.length;
        const lastPage = Math.ceil(total / limit);
        const offset = (page - 1) * limit;

        const paginatedData = filteredData.slice(offset, offset + limit);

        outletOrders.value = paginatedData;
        pageInfo.value = {
            currentPage: page,
            lastPage: lastPage,
            total: total,
            count: paginatedData.length
        };

        loading.value = false;
    };

    /**
     * Handle order return
     */
    const isOrderCanBeReturned = computed(() => {
        return currentOrder.value?.order?.items?.some(item => {
            return item.qtyInvoiced - item.qtyRefunded > 0;
        });
    });

    const { mutate: returnOrder } = useMutation(RETURN_ORDER);

    const handleOrderReturn = async (outletOrder) => {
        const input = {
            orderId: outletOrder.order.id,
            items: outletOrder.order.items.map(item => {
                return {
                    itemId: parseInt(item.id),
                    qty: item.qtyInvoiced - item.qtyRefunded,
                };
            }),
        };

        returnOrder({ input }).then(async (response) => {
            const { returnOrder } = response.data;

            emitter.emit('add_flash', {
                type: 'success',
                message: returnOrder.message,
            });

            const { outletOrder } = returnOrder;

            await DB.updateItem('orders', outletOrder);

            currentOrder.value = outletOrder;

            if (searchTerm.value) {
                searchOrder(searchTerm.value);
            } else {
                fetchPaginatedOrders(pageInfo.value.currentPage);
            }
        }).catch((error) => {
            emitter.emit('add_flash', {
                type: 'error',
                message: error.message,
            });
        });
    };
</script>