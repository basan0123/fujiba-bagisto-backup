<template>
    <div>
        <bundle-option
            v-for="(option, index) in options"
            :option="option"
            :key="index"
            @onProductSelected="productSelected(option, $event)"
        />

        <div class="my-2.5 flex items-center justify-between">
            <p class="text-sm text-gray-800 dark:text-gray-200">
                {{ $t('pos.home.products.view.type.bundle.total_amount') }}
            </p>

            <p class="text-lg font-medium max-sm:text-sm text-gray-900 dark:text-white">
                {{ outlet.formatPrice(totalPrice) }}
            </p>
        </div>

        <ul class="grid gap-2.5 text-base max-sm:text-sm">
            <li
                v-for="(option, index) in options"
                :key="index"
            >
                <span class="mb-1.5 inline-block max-sm:mb-0 text-gray-800 dark:text-gray-100">
                    {{ option.label }}
                </span>

                <template v-for="product in option.products">
                    <div
                        class="text-zinc-500 dark:text-zinc-300"
                        :key="product.id"
                        v-if="product.isDefault"
                    >
                        {{ `${product.qty} x ${product.name}` }}
                    </div>
                </template>
            </li>
        </ul>
    </div>
</template>

<script setup>
    import { ref, computed } from 'vue';
    import { useOutlet } from '@src/composable/outlet';
    import BundleOption from '@components/secured/home/products/view/type/BundleOption.vue';
    
    /**
     * Define the props.
     */
    const props = defineProps({
        product: {
            type: Object,
            required: true,
        },
    });

    const outlet = useOutlet();

    /**
     * Define the data properties.
     */
    const options = ref(props.product.options);

    /**
     * Formatted total price.
     */
    const totalPrice = computed(() => {
        let total = 0;        

        for (const key in options.value) {            
            for (const key1 in options.value[key].products) {
                if (! options.value[key].products[key1].isDefault) {
                    continue;
                }                

                total += options.value[key].products[key1].qty * options.value[key].products[key1].price.final.price;
            }
        }

        return total;
    });

    /**
     * Product selected event handler.
     */
    const productSelected = (option, value) => {
        const selectedProductIds = Array.isArray(value) ? value : [value];

        for (var key in option.products) {            
            option.products[key].isDefault = selectedProductIds.indexOf(option.products[key].id) > -1 ? true : false;
        }
    }
</script>
