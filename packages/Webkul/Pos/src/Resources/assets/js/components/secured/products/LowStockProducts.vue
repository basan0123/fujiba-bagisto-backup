<template>
    <ProductCardSkeleton v-if="loading" />

    <div
        v-else-if="!products.length"
        class="box-shadow flex items-center justify-center rounded-lg bg-white p-5"
    >
        <p class="text-base font-normal leading-5 text-gray-900">
            {{ $t('pos.products.low_stock_products.no_products') }}
        </p>
    </div>

    <div
        v-else
        class="grid gap-4 [grid-template-columns:repeat(auto-fill,_minmax(172px,_1fr))]"
    >
        <div
            class="box-shadow flex cursor-pointer flex-col gap-2 rounded-lg bg-white dark:bg-gray-900 p-3"
            v-for="(product, index) in products"
            :key="index"
            @click="openLowStockRequestModal(product)"
        >
            <template v-if="product.images?.length">
                <img
                    :src="product.images[0].url"
                    class="aspect-square rounded sm:max-w-[150px] md:max-w-[210px]"
                    alt="product image"
                >
            </template>

            <template v-else>
                <img
                    src="@images/product-placeholder.webp"
                    class="aspect-square rounded sm:max-w-[150px] md:max-w-[210px] dark:invert"
                    alt="product image"
                >
            </template>

            <div class="flex h-full flex-col justify-between gap-2">
                <div class="flex flex-col items-center gap-1">
                    <p class="truncate-text-2 text-center text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                        {{ product.name }}
                    </p>

                    <p
                        class="flex gap-1.5 text-base font-normal leading-5 text-gray-900 dark:text-gray-100"
                        v-html="product.priceHtml"
                    >
                    </p>
                </div>

                <div class="flex items-center justify-between">
                    <p class="text-[12px] font-semibold leading-4 text-gray-900 dark:text-gray-200">
                        {{ $t('pos.products.low_stock_products.qty', { qty: product.quantity }) }}
                    </p>

                    <div class="flex h-10 w-10 items-center justify-center rounded-md bg-gray-100 dark:bg-gray-800 p-1">
                        <span class="icon-attribute text-2xl text-slate-600 dark:text-slate-300"></span>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <Modal ref="lowStockRequestModal">
                <template v-slot:header>
                    {{ $t('pos.products.low_stock_products.request.title') }}
                </template>
                
                <template v-slot:content="{ toggle }">
                    <v-form
                        class="grid gap-4"
                        @submit="submitLowStockRequestForm"
                    >
                        <ControlGroup>
                            <Label for="requested_quantity">
                                {{ $t('pos.products.low_stock_products.request.requested_quantity') }}
                            </Label>

                            <Field
                                :type="'text'"
                                :name="'requested_quantity'"
                                :id="'requested_quantity'"
                                :rules="'required|integer|min_value:1|max_value:1000'"
                                :placeholder="'100'"
                                :label="$t('pos.products.low_stock_products.request.requested_quantity')"
                                v-model="productRequest.requestedQuantity"
                            />

                            <Error :name="'requested_quantity'" />
                        </ControlGroup>

                        <ControlGroup>
                            <Label for="comment">
                                {{ $t('pos.products.low_stock_products.request.comment') }}
                            </Label>

                            <Field
                                :type="'textarea'"
                                :name="'comment'"
                                :id="'comment'"
                                :rules="'required'"
                                :placeholder="$t('pos.products.low_stock_products.request.comment_placeholder')"
                                v-model="productRequest.comment"
                            />

                            <Error :name="'comment'" />
                        </ControlGroup>

                        <div class="flex justify-end gap-6">
                            <button
                                type="button"
                                class="transparent-button w-36"
                                @click="toggle"
                            >
                                {{ $t('pos.products.low_stock_products.request.cancel_btn_title') }}
                            </button>

                            <button
                                type="submit"
                                class="primary-button w-36"
                            >
                                {{ $t('pos.products.low_stock_products.request.add_btn_title') }}
                            </button>
                        </div>
                    </v-form>
                </template>
            </Modal>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, inject, computed } from 'vue';
    import { useQuery } from '@vue/apollo-composable';
    import { GET_LOW_STOCK_PRODUCTS } from '@src/graphql/products';
    import ProductCardSkeleton from '@skeletons/products/Card.vue';

    /**
     * General Variables
     */
    const emitter = inject('emitter');
    const isOnline = inject('isOnline');

    const lowStockRequestModal = ref(null);
    const productRequest = ref({
        productId: null,
        name: null,
        requestedQuantity: null,
        comment: null,
    });

    /**
     * Sending the purchase request
     */
    const openLowStockRequestModal = (product) => {
        productRequest.value.productId = product.id;
        productRequest.value.name = product.name;

        lowStockRequestModal.value.toggle();
    };

    /**
     * Submitting the low stock request form
     */
    const submitLowStockRequestForm = async () => {
        lowStockRequestModal.value.toggle();

        emitter.emit('stock_request', productRequest.value);

        productRequest.value = {
            productId: null,
            name: null,
            requestedQuantity: null,
            comment: null,
        };
    };

    /**
     * Fetching the minimum quantity
     */
    const { result, loading } = useQuery(GET_LOW_STOCK_PRODUCTS, null, {
        skip: computed(() => !isOnline.value)
    });

    const products = computed(() => result.value?.getLowStockProducts ?? []);
</script>