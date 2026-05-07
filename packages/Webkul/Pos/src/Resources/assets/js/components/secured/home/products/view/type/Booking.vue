<template>
    <div class="mt-6 grid w-full grid-cols-1 gap-6">
        <div
            class="flex gap-4 text-gray-800 dark:text-gray-100"
            v-if="bookingProduct.location"
        >
            <span class="icon-location text-2xl text-gray-500 dark:text-gray-400"></span>

            <div class="grid grid-cols-1 gap-1.5 text-sm font-medium">
                <p>
                    {{ $t('pos.home.products.view.type.booking.location') }}
                </p>

                <div class="grid grid-cols-1 gap-3">
                    <p class="text-gray-500 dark:text-gray-400">
                        {{ bookingProduct.location }}
                    </p>

                    <a
                        :href="`https://maps.google.com/maps?q=${bookingProduct.location}`"
                        target="_blank"
                        class="w-fit text-blue-600 hover:text-blue-800"
                    >
                        {{ $t('pos.home.products.view.type.booking.view_on_map') }}
                    </a>
                </div>
            </div>
        </div>

        <component
            v-if="bookingProduct?.type"
            :is="components[bookingProduct?.type]"
            :bookingProduct="bookingProduct"
        />
    </div>
</template>

<script setup>
    import { ref, watchEffect } from 'vue';
    import Appointment from '@components/secured/home/products/view/type/booking/Appointment.vue';
    import Default from '@components/secured/home/products/view/type/booking/Default.vue';
    import Event from '@components/secured/home/products/view/type/booking/Event.vue';
    import Rental from '@components/secured/home/products/view/type/booking/Rental.vue';
    import Table from '@components/secured/home/products/view/type/booking/Table.vue';

    /**
     * Define the props.
     */
    const props = defineProps({
        product: {
            type: Object,
            required: true,
        },
    });

    const bookingProduct = ref({});

    watchEffect(() => {
        bookingProduct.value = props.product.booking?.[0];
    });

    /**
     * Define the components.
     */
    const components = {
        appointment: Appointment,
        default: Default,
        event: Event,
        rental: Rental,
        table: Table,
    };
</script>