<template>
    <div class="flex flex-col h-full gap-4 overflow-hidden select-none">
        <Tab
            :current="tab"
            :tabs="tabs"
            routeBase="/products"
        />

        <template v-if="tab === 'low-stock-products'">
            <!-- Mobile View -->
            <div
                v-if="products.length"
                class="fixed bottom-0 left-0 right-0 flex w-full items-center justify-between gap-10 border-t border-gray-200 bg-white p-2.5 dark:border-gray-700 dark:bg-gray-900 lg:hidden"
            >
                <p class="text-base font-medium text-gray-900 dark:text-gray-100">
                    {{ $t('pos.products.low_stock_products.items_in_request', {
                        count: products.length
                    }) }}
                </p>

                <button
                    class="secondary-button w-1/2 text-gray-900 dark:text-white"
                    @click="$refs.purchaseRequestDrawer.toggle()"
                >
                    <span class="icon-eye text-2xl leading-6"></span>

                    {{ $t('pos.products.low_stock_products.view_btn_title') }}
                </button>
            </div>

            <!-- Desktop View -->
            <div class="fixed hidden h-[calc(100vh-68px)] w-[clamp(420px,_30vw,_600px)] flex-col justify-between bg-white shadow-[1px_0px_0px_0px_rgba(0,0,0,0.1)_inset] dark:bg-gray-900 lg:flex ltr:right-0 rtl:left-0">
                <div class="grid">
                    <div class="px-2.5 py-4 shadow-[0px_-1px_0px_0px_rgba(0,0,0,0.1)_inset] dark:shadow-[0px_-1px_0px_0px_rgba(255,255,255,0.05)_inset]">
                        <p class="flex h-12 items-center text-xl font-medium text-gray-900 dark:text-gray-100">
                            {{ $t('pos.products.low_stock_products.purchase_request') }}
                        </p>
                    </div>

                    <div class="h-[calc(100vh-220px)] overflow-y-auto py-2">
                        <template v-if="products.length">
                            <div
                                class="grid min-h-14 rounded-lg p-2"
                                :class="[index % 2 != 0 ? 'bg-gray-50 dark:bg-gray-800' : '']"
                                v-for="(product, index) in products"
                                :key="index"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div
                                        class="flex cursor-pointer items-center gap-2"
                                        @click="showHide(index)"
                                    >
                                        <span
                                            :class="[showMore[index] ? 'icon-chevron-down' : 'icon-chevron-right']"
                                            class="text-2xl leading-6 text-gray-400 dark:text-gray-300"
                                        >
                                        </span>

                                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                            {{ index + 1 }}
                                        </p>

                                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                            {{ product.name }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                            {{ $t('pos.products.low_stock_products.qty', { qty: product.requestedQuantity }) }}
                                        </p>

                                        <div
                                            class="flex h-4 w-4 cursor-pointer items-center justify-center rounded-full bg-gray-400 dark:bg-gray-500"
                                            @click="products.splice(index, 1)"
                                        >
                                            <span class="icon-cross text-base leading-6 text-white"></span>
                                        </div>
                                    </div>
                                </div>

                                <p
                                    class="px-8 text-sm text-gray-400 dark:text-gray-300"
                                    v-if="showMore[index]"
                                >
                                    {{ product.comment }}
                                </p>
                            </div>
                        </template>

                        <template v-else>
                            <div class="flex h-full items-center justify-center">
                                <p class="text-base text-gray-400 dark:text-gray-300">
                                    {{ $t('pos.products.low_stock_products.no_purchase_request') }}
                                </p>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-3">
                    <Button
                        type="button"
                        :class="[
                            'secondary-button w-full',
                            !products.length ? 'opacity-50 hover:opacity-50' : ''
                        ]"
                        :label="$t('pos.products.low_stock_products.send_request_btn')"
                        :icon="'icon-checkout rtl:rotate-180'"
                        :isLoading="isSubmittingForm"
                        @click="requestProductQuantity"
                    />
                </div>
            </div>
        </template>

        <!-- Purchase Request Drawer (Mobile View Only) -->
        <Teleport to="body">
            <Drawer ref="purchaseRequestDrawer">
                <template v-slot:header>
                    <p class="mx-4 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $t('pos.products.low_stock_products.purchase_request') }}
                    </p>
                </template>

                <template v-slot:content>
                    <template v-if="products.length">
                        <div
                            class="grid min-h-14 rounded-lg p-4"
                            :class="[index % 2 !== 0 ? 'bg-gray-50 dark:bg-gray-800' : '']"
                            v-for="(product, index) in products"
                            :key="index"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div
                                    class="flex cursor-pointer items-center gap-2"
                                    @click="showHide(index)"
                                >
                                    <span
                                        :class="[showMore[index] ? 'icon-chevron-down' : 'icon-chevron-right']"
                                        class="text-2xl leading-6 text-gray-400 dark:text-gray-300"
                                    ></span>

                                    <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                        {{ index + 1 }}
                                    </p>

                                    <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                        {{ product.name }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                        {{ $t('pos.products.low_stock_products.qty', { qty: product.requestedQuantity }) }}
                                    </p>

                                    <div
                                        class="flex h-4 w-4 cursor-pointer items-center justify-center rounded-full bg-gray-400 dark:bg-gray-500"
                                        @click="products.splice(index, 1)"
                                    >
                                        <span class="icon-cross text-base leading-6 text-white"></span>
                                    </div>
                                </div>
                            </div>

                            <p
                                class="px-8 text-sm text-gray-400 dark:text-gray-300"
                                v-if="showMore[index]"
                            >
                                {{ product.comment }}
                            </p>
                        </div>
                    </template>

                    <template v-else>
                        <div class="flex h-full items-center justify-center">
                            <p class="text-base text-gray-400 dark:text-gray-300">
                                {{ $t('pos.products.low_stock_products.no_purchase_request') }}
                            </p>
                        </div>
                    </template>
                </template>

                <template v-slot:footer>
                    <Button
                        type="button"
                        :class="[
                            'secondary-button w-full',
                            ! products.length ? 'opacity-50 hover:opacity-50' : ''
                        ]"
                        :label="$t('pos.products.low_stock_products.send_request_btn')"
                        :icon="'icon-checkout rtl:rotate-180'"
                        :isLoading="isSubmittingForm"
                        @click="requestProductQuantity"
                    />
                </template>
            </Drawer>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, inject, onMounted, computed, onBeforeUnmount } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useMutation } from '@vue/apollo-composable';
    import { REQUEST_PRODUCT_QTY } from '@src/graphql/products';
    import LowStockProducts from '@components/secured/products/LowStockProducts.vue';
    import RequestedProducts from '@components/secured/products/RequestedProducts.vue';
    import Settings from '@components/secured/products/Settings.vue';

    const props = defineProps({
        tab: String
    });

    const tab = computed(() => props.tab || 'low-stock-products');

    const tabs = [
        {
            key: 'low-stock-products',
            component: LowStockProducts,
            titleKey: 'pos.products.low_stock_products.title',
        },
        {
            key: 'requested-products',
            component: RequestedProducts,
            titleKey: 'pos.products.requested_products.title',
        },
        {
            key: 'settings',
            component: Settings,
            titleKey: 'pos.products.settings.title',
        },
    ];

    /**
     * General Variables
     */
    const emitter = inject('emitter');
    const { t } = useI18n();

    const products = ref([]);

    onMounted(() => {
        emitter.on('stock_request', handleStockRequest);        
    });

    /**
     * Handle stock request
     */
    const handleStockRequest = (request) => {        
        let alreadyExists = products.value.find((item) => item.productId === request.productId);

        if (alreadyExists) {
            emitter.emit('add_flash', {
                type: 'warning',
                message: t('pos.products.low_stock_products.request.already_exist'),
            });
        } else {
            products.value.push(request);
        }
    };

    onBeforeUnmount(() => {
        emitter.off('stock_request', handleStockRequest);
    });

    /**
     * send the product quantity request
     */
    const purchaseRequestDrawer = ref(null);

    const { mutate, loading: isSubmittingForm } = useMutation(REQUEST_PRODUCT_QTY, {
        onCompleted: () => {
            products.value = [];
        },
    });

    const requestProductQuantity = () => {
        if (products.value.length) {
            mutate({ input: {
                products: products.value.map(product => ({
                    productId: parseInt(product.productId),
                    requestedQuantity: parseInt(product.requestedQuantity),
                    comment: product.comment,
                })),
            }}).then(response => {
                if (response.data.requestProductQty.success) {
                    emitter.emit('add_flash', {
                        type: 'success',
                        message: response.data.requestProductQty.message,
                    });

                    products.value = [];
                }
            }).catch(error => {
                emitter.emit('add_flash', {
                    type: 'error',
                    message: error.message,
                });
            }).finally(() => {
                purchaseRequestDrawer.value.close();
            })
        }
    };

    /**
     * Show/Hide Comment
     */
    const showMore = ref({});

    const showHide = (key) => {
        showMore.value[key] = !showMore.value[key];
    }
</script>