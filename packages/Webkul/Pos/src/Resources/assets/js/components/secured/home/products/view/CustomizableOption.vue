<template>
    <div>
        <customizable-option-item
            v-for="(option, index) in options"
            :option="option"
            :index="index"
            :key="index"
            @priceUpdated="priceUpdated"
        />

        <div class="mb-2.5 mt-5 flex items-center justify-between">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ $t('pos.home.products.view.type.simple.customizable_options.total_amount') }}
            </p>

            <p class="text-lg font-medium max-sm:text-sm text-gray-900 dark:text-white">
                {{ formattedTotalPrice() }}
            </p>
        </div>
    </div>
</template>

<script setup>
    import { ref, watchEffect } from 'vue';
    import { useOutlet } from '@src/composable/outlet';
    import CustomizableOptionItem from '@components/secured/home/products/view/CustomizableOptionItem.vue';

    /**
     * General use imports
     */
    const { formatPrice } = useOutlet();

    /**
     * Define the props.
     */
    const props = defineProps({
        product: {
            type: Object,
            required: true,
        },
    });

    const options = ref(props.product.customizableOptions);
    const prices = ref([]);
    
    watchEffect(() => {
        options.value = options.value.map(option => {
            const isPriceType = ! [
                'checkbox',
                'radio',
                'select',
                'multiselect'
            ].includes(option.type);

            return {
                ...option,
                price: isPriceType ? option.customizableOptionPrices[0]?.price : 0,
            };
        });

        prices.value = options.value.map(option => {
            return {
                optionId: option.id,
                price: 0,
            };
        });
    });

    const priceUpdated = ({ option, totalPrice }) => {
        let price = prices.value.find(price => price.optionId === option.id);

        price.price = totalPrice;
    };

    const formattedTotalPrice = () => {
        let totalPrice = props.product.convertedPrice;

        for (let price of prices.value) {
            totalPrice += parseFloat(price.price);
        }

        return formatPrice(totalPrice);
    };
</script>