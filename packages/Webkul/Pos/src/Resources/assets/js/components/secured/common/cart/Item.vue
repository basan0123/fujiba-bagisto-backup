<template>
    <div
        class="group relative rounded-xl border mb-3 overflow-hidden"
        :class="{
            'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900': !showMore,
            'border-green-500 dark:border-green-600 bg-gradient-to-r from-green-50 to-white dark:from-green-950/30 dark:to-gray-900 shadow-md': showMore
        }"
    >
        <!-- Main Row -->
        <div class="flex items-center justify-between gap-4 p-4">
            <!-- Left Section -->
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <!-- Expand Button -->
                <button
                    class="flex-shrink-0 w-11 h-11 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800"
                    @click="showMore = !showMore"
                >
                    <span
                        class="text-2xl"
                        :class="[
                            showMore ? 'icon-chevron-down text-green-600 dark:text-green-500' : 'icon-chevron-right text-gray-400 dark:text-gray-500'
                        ]"
                    >
                    </span>
                </button>

                <!-- Item Number Badge -->
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">
                        {{ index + 1 }}
                    </span>
                </div>

                <!-- Thumbnail -->
                <div class="flex-shrink-0 w-10 h-10 overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800">
                    <img
                        v-if="thumbnailUrl"
                        :src="thumbnailUrl"
                        class="h-full w-full object-cover"
                        :alt="displayName"
                    />
                    <img
                        v-else
                        src="@images/product-placeholder.webp"
                        class="h-full w-full object-cover opacity-30"
                        alt=""
                    />
                </div>

                <!-- Item Name + SKU -->
                <div class="flex flex-col min-w-0">
                    <h3
                        class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate cursor-pointer"
                        :title="displayName"
                    >
                        {{ displayName }}
                    </h3>
                    <span
                        v-if="item.sku"
                        class="text-xs text-gray-500 dark:text-gray-400 truncate"
                    >
                        {{ item.sku }}
                    </span>
                </div>
            </div>

            <!-- Right Section -->
            <div class="flex items-center gap-3 flex-shrink-0">
                <!-- Price Display -->
                <div class="text-right">
                    <template v-if="item.customPrice">
                        <div class="text-xs text-gray-400 dark:text-gray-500 line-through">
                            {{ formatPrice(item.product?.price) }}
                        </div>

                        <div class="text-lg font-bold text-green-600 dark:text-green-500">
                            {{ formatPrice(item?.customPrice) }}
                        </div>
                    </template>
                    
                    <template v-else>
                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                            {{ formatPrice(item?.price) }}
                        </span>
                    </template>
                </div>

                <!-- Remove Button -->
                <button
                    class="w-11 h-11 rounded-lg flex items-center justify-center hover:bg-red-100 dark:hover:bg-red-900/30 group/remove"
                    @click="removeCartItem(item)"
                    aria-label="Remove item"
                >
                    <span class="icon-cross text-2xl text-gray-400 dark:text-gray-500 group-hover/remove:text-red-600 dark:group-hover/remove:text-red-500"></span>
                </button>
            </div>
        </div>

        <!-- Expanded Details -->
        <div
            v-if="showMore"
            class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
        >
            <div class="p-4 space-y-4">
                <!-- Attributes -->
                <template v-if="hasAttributes">
                    <div class="space-y-1.5">
                        <div
                            v-for="(option, key) in item.additional.attributes"
                            :key="key"
                            class="flex items-start gap-2"
                        >
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ option.attributeName }}:
                            </span>

                            <span class="text-sm text-gray-800 dark:text-gray-200 break-words">
                                {{ option.optionLabel }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Quantity and Discount Controls -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Quantity Controls -->
                    <div class="space-y-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            {{ $t('pos.common.cart.quantity') }}
                        </span>
                        
                        <div class="flex items-center gap-2 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                            <!-- Decrement Button -->
                            <button
                                class="w-11 h-11 flex items-center justify-center rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed"
                                @click="item.quantity--; updateCartItemQty(item)"
                                :disabled="item.quantity <= 1"
                            >
                                <span class="icon-minus text-2xl text-gray-700 dark:text-gray-300"></span>
                            </button>

                            <!-- Quantity -->
                            <span class="flex-1 text-center text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ item.quantity }}
                            </span>

                            <!-- Increment Button -->
                            <button
                                class="w-11 h-11 flex items-center justify-center rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
                                @click="item.quantity++; updateCartItemQty(item)"
                                aria-label="Increase quantity"
                            >
                                <span class="icon-plus text-2xl text-gray-700 dark:text-gray-300"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Discount Controls -->
                    <div
                        v-if="item.type !== 'bundle'"
                        class="space-y-2"
                    >
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            {{ $t('pos.common.cart.discount') }}
                        </span>
                        
                        <div class="flex items-center gap-2">
                            <!-- Discount Input -->
                            <div class="relative flex-1">
                                <input
                                    type="number"
                                    v-model="discountPercentage"
                                    :placeholder="getAdditionalDiscountPercentage(item).toString()"
                                    class="w-full h-10 pl-10 pr-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-green-500 dark:focus:ring-green-600 focus:border-transparent"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                />

                                <div class="absolute left-2 top-1/2 -translate-y-1/2 flex items-center justify-center w-6 h-6 rounded bg-gray-100 dark:bg-gray-800">
                                    <span class="icon-percentage text-base text-gray-600 dark:text-gray-400"></span>
                                </div>
                            </div>

                            <!-- Apply Button -->
                            <button
                                class="h-11 w-11 flex-shrink-0 rounded-lg bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700 flex items-center justify-center transition-colors"
                                @click="applyAdditionalDiscount(item)"
                                aria-label="Apply discount"
                            >
                                <span class="icon-checkout text-2xl text-white rtl:rotate-180"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Current Discount Display -->
                <div
                    v-if="item.customPrice"
                    class="text-xs text-green-600 dark:text-green-500 font-medium"
                >
                    {{ $t('pos.common.cart.discount_applied', {
                        percentage: getAdditionalDiscountPercentage(item)
                    }) }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { ref, computed, onMounted, watch } from 'vue';
    import CartManager from '@src/helpers/Cart';
    import { useOutlet } from '@src/composable/outlet';
    import { useIndexedDB } from '@src/composable/indexed-db';

    const props = defineProps({
        item: {
            type: Object,
            required: true,
        },
        index: {
            type: Number,
            required: true,
        },
        customer: {
            type: Object,
            required: true,
        },
    });

    /**
     * General use imports
     */
    const { formatPrice } = useOutlet();

    const emit = defineEmits(['get-cart']);

    /**
     * State management
     */
    const showMore = ref(false);
    const discountPercentage = ref(0);

    /**
     * Check if item has attributes
     */
    const hasAttributes = computed(() => {
        return props.item.additional?.attributes
            && Object.keys(props.item.additional.attributes).length > 0;
    });

    /**
     * Display name fallback. Cart items written before the channel fix can
     * carry a NULL/empty name; fall back to product.name then SKU.
     */
    const displayName = computed(() => {
        return props.item.name
            || props.item.product?.name
            || props.item.sku
            || '';
    });

    /**
     * First image URL, if available on the cached product. Falls back to
     * looking up the product in IndexedDB so cart entries written before the
     * cart-product enrichment still get a thumbnail.
     */
    const fallbackImageUrl = ref(null);
    const DB = useIndexedDB();

    const thumbnailUrl = computed(() => {
        return props.item.product?.images?.[0]?.url
            || fallbackImageUrl.value
            || null;
    });

    const resolveFallbackImage = async () => {
        if (props.item.product?.images?.[0]?.url) {
            return;
        }

        const productId = props.item.productId ?? props.item.product_id;

        if (! productId) {
            return;
        }

        try {
            const cached = await DB.getItem('products', productId);
            const url = cached?.images?.[0]?.url;

            if (url) {
                fallbackImageUrl.value = url;
            }
        } catch (e) {
            // best-effort
        }
    };

    onMounted(resolveFallbackImage);
    watch(() => props.item, resolveFallbackImage, { deep: false });

    /**
     * Update cart item
     */    
    const updateCartItemQty = async (item) => {
        const input = {
            cartId: item.cartId,
            customerId: props.customer.id,
            qty: [{
                cartItemId: item.id,
                customPrice: parseFloat(item.customPrice),
                quantity: parseInt(item.quantity),
            }]
        };

        const cartManager = new CartManager();

        await cartManager.updateItems(input).then(() => {
            emit('get-cart');
        });
    };

    /**
     * Apply additional discount
     */
    const applyAdditionalDiscount = (item) => {
        const finalPrice = parseFloat(item.product.basePrice);
        const percentage = parseFloat(discountPercentage.value);

        if (
            isNaN(percentage)
            || percentage <= 0
        ) {
            item.customPrice = 0;
        } else {
            item.customPrice = finalPrice - ((percentage / 100) * finalPrice);
        }

        updateCartItemQty(item);
    };

    /**
     * Get additional discount percentage
     */
    const getAdditionalDiscountPercentage = (item) => {
        const finalPrice = parseFloat(item.product.basePrice);
        const customPrice = parseFloat(item.customPrice);

        if (
            isNaN(customPrice)
            || customPrice <= 0
        ) {
            return 0;
        }

        const discount = ((finalPrice - customPrice) / finalPrice) * 100;

        return parseFloat(discount.toFixed(2));
    };

    /**
     * Remove cart item
     */
    const removeCartItem = async (item) => {
        const cartManager = new CartManager();

        await cartManager.removeItem(item.id).then(() => {
            emit('get-cart');
        });

        return;
    };
</script>