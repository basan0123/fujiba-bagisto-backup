<template>
    <CustomizableOption :product="product" />
</template>

<script setup>
    import CustomizableOption from '@components/secured/home/products/view/CustomizableOption.vue';

    /**
     * Define the props.
     */
    const props = defineProps({
        product: {
            type: Object,
            required: true,
        },
    });
</script>
