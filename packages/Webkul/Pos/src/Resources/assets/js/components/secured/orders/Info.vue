<template>
    <div class="fixed top-[68px] hidden h-[calc(100vh-68px)] w-[clamp(420px,_30vw,_600px)] flex-col justify-between bg-white dark:bg-gray-900 lg:flex ltr:right-0 rtl:left-0">
        <template v-if="outletOrder?.order">
            <div class="flex flex-col px-3">
                <div class="flex flex-col px-2.5 py-4 border-b bg-white dark:bg-gray-900">
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $t('pos.orders.info.order_id') }} #{{ outletOrder.order?.id }}
                    </p>

                    <p class="text-sm leading-4 text-gray-700 dark:text-gray-300">
                        {{ outletOrder.order?.customer?.name }}
                    </p>
                </div>

                <div class="flex h-[calc(100vh-400px)] flex-col gap-1 overflow-y-auto overflow-x-hidden py-2">
                    <div
                        class="flex min-h-14 flex-col gap-4 rounded-lg p-2"
                        :class="[
                        index % 2 === 0
                            ? 'bg-zinc-50 dark:bg-gray-900'
                            : 'bg-white dark:bg-gray-900',
                        ]"
                        v-for="(item, index) in outletOrder.order?.items"
                        :key="index"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex gap-2.5">
                                <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                    {{ index + 1 }}.
                                </p>

                                <div class="flex flex-col">
                                    <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                        {{ item.name }}
                                    </p>

                                    <p class="text-sm font-normal leading-4 text-neutral-400 dark:text-neutral-400">
                                        {{ item.sku }}
                                    </p>
                                </div>
                            </div>

                            <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                {{ formatPrice(item?.price) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 bg-zinc-50 dark:bg-gray-900 p-3">
                <div class="flex flex-col gap-1">
                    <div class="flex justify-between">
                        <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                            {{ $t('pos.orders.info.subtotal') }}
                        </p>

                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                            {{ formatPrice(outletOrder.order?.subTotal) }}
                        </p>
                    </div>

                    <div class="flex justify-between">
                        <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                            {{ $t('pos.orders.info.tax') }}
                        </p>

                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                            <template v-if="outletOrder.order?.taxAmount">
                                {{ formatPrice(outletOrder.order.taxAmount) }}
                            </template>
                            
                            <template v-else>
                                {{ formatPrice(outletOrder.order?.taxTotal ?? 0) }}
                            </template>
                        </p>
                    </div>

                    <div class="flex justify-between">
                        <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                            {{ $t('pos.orders.info.discount') }}
                        </p>

                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                            {{ formatPrice(outletOrder.order?.discountAmount ?? 0) }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-between">
                    <p class="text-xl font-medium leading-6 text-gray-900 dark:text-gray-100">
                        {{ $t('pos.common.cart.payable_amount') }}
                    </p>

                    <p class="text-xl font-medium leading-6 text-gray-900 dark:text-gray-100">
                        {{ formatPrice(outletOrder.order?.grandTotal) }}
                    </p>
                </div>

                <div class="flex gap-2.5">
                    <button
                        v-if="showSyncButton"
                        type="button"
                        class="primary-button flex-1"
                        @click="$emit('syncOrder', outletOrder)"
                    >
                        <span class="icon-sync text-2xl"></span>

                        {{ $t('pos.orders.offline.sync_order') }}
                    </button>

                    <button
                        v-if="showReturnButton"
                        type="button"
                        class="danger-button flex-1"
                        @click="$emit('returnOrder', outletOrder)"
                    >
                        <span class="icon-sync text-2xl"></span>

                        {{ $t('pos.orders.history.return_btn') }}
                    </button>

                    <div class="flex-1">
                        <Print :outletOrder="outletOrder" />
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="flex h-full items-center justify-center px-8">
                <p class="text-base font-normal text-gray-900 dark:text-gray-100">
                {{ $t('pos.orders.info.no_order_selected') }}
                </p>
            </div>
        </template>
    </div>
</template>

<script setup>
    import Print from '@components/secured/common/Print.vue';
    import { useOutlet } from '@src/composable/outlet';

    /**
     * General variables
     */
    const { formatPrice } = useOutlet();

    defineProps({
        outletOrder: {
            type: Object,
            required: true,
        },
        showSyncButton: {
            type: Boolean,
            required: false,
        },
        showReturnButton: {
            type: Boolean,
            required: false,
        },
    });
</script>
