<template>
    <div class="grid grid-cols-1 gap-6 text-gray-800 dark:text-gray-100">
        <!-- Event Header -->
        <div class="flex gap-3">
            <span class="icon-calendar text-2xl text-gray-500 dark:text-gray-400"></span>

            <div class="grid grid-cols-1 gap-1.5 text-sm font-medium">
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $t('pos.home.products.view.type.booking.event.title') }}
                </p>

                <p class="text-base">
                    {{ eventDate }}
                </p>
            </div>
        </div>

        <!-- Ticket Booking Section -->
        <div class="grid grid-cols-1 gap-4">
            <div class="text-xl font-medium max-sm:text-base">
                {{ $t('pos.home.products.view.type.booking.event.book_your_ticket') }}
            </div>

            <div
                class="flex justify-between border-b border-slate-500 dark:border-slate-700 last:border-b-0"
                :class="tickets?.length - index == 1 ? '' : 'pb-4'"
                v-for="(ticket, index) in tickets"
                :key="index"
            >
                <div class="grid gap-1.5">
                    <!-- Name -->
                    <p
                        class="font-medium max-sm:text-sm"
                        v-text="ticket.name"
                    >
                    </p>

                    <!-- Original & Final Price -->
                    <div
                        v-if="ticket.originalFormattedPrice"
                        class="text-gray-600 dark:text-gray-400 max-sm:text-sm"
                    >
                        <p
                            class="mr-1.5 line-through"
                            v-text="ticket.originalFormattedPrice"
                        >
                        </p>

                        <p class="text-lg max-sm:text-sm">
                            {{ $t('pos.home.products.view.type.booking.event.per_ticket_price', {
                                price: ticket.formattedPrice,
                            }) }}
                        </p>
                    </div>

                    <!-- Just Final Price -->
                    <p v-else>
                        {{ $t('pos.home.products.view.type.booking.event.per_ticket_price', {
                            price: ticket.formattedPrice,
                        }) }}
                    </p>

                    <!-- Description -->
                    <div v-text="ticket.description"></div>
                </div>

                <!-- Quantity Selector -->
                <div class="place-items-end">
                    <quantity-changer
                        :name="'booking[qty][' + ticket.id + ']'"
                        :value="tickets.length > 1 ? 1 : 0"
                        rules="required|numeric|min_value:0"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { ref, watchEffect } from 'vue';
    import QuantityChanger from '@components/secured/common/QuantityChanger.vue';
    import EventBooking from '@src/helpers/booking/Event';

    /**
     * Define the props.
     */
    const props = defineProps({
        bookingProduct: {
            type: Object,
            required: true,
        },
    });

    const tickets = ref([]);
    const eventDate = ref('');

    /**
     * Watch for changes in the bookingProduct prop.
     */
    watchEffect(async () => {
        const eventBooking = new EventBooking(props.bookingProduct);

        eventDate.value = eventBooking.getEventDate();
        tickets.value = await eventBooking.getTickets();
    });
</script>