<template>
    <div class="grid grid-cols-1 gap-6">
        <div
            v-if="bookingProduct.defaultSlot.duration"
            class="flex gap-3 text-gray-800 dark:text-gray-100"
        >
            <span class="icon-calendar text-2xl text-gray-500 dark:text-gray-400"></span>

            <div class="grid grid-cols-1 gap-1.5 text-sm font-medium">
                <p class="text-gray-500 dark:text-gray-400">
                    {{ $t('pos.home.products.view.type.booking.default.slot_duration') }} :
                </p>

                <div>
                    {{ $t('pos.home.products.view.type.booking.default.slot_duration_in_minutes', {
                        minutes: bookingProduct.defaultSlot.duration
                    }) }}
                </div>
            </div>
        </div>

        <slots :bookingProduct="bookingProduct" />
    </div>
</template>

<script setup>
    import Slots from '@components/secured/home/products/view/type/booking/Slots.vue';

    /**
     * Define the props.
     */
    defineProps({
        bookingProduct: {
            type: Object,
            required: true,
        },
    });
</script>