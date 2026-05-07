<template>
    <div>
        <div class="pb-2 text-base leading-5 text-gray-900 dark:text-white">
            {{ title ?? $t('pos.home.products.view.type.booking.slots.book_an_appointment') }}
        </div>

        <div class="grid items-start gap-8 md:grid-cols-2 md:gap-2.5">
            <!-- Select Date -->
            <ControlGroup>
                <Field
                    :type="'date'"
                    :name="'booking[date]'"
                    rules="required"
                    :label="$t('pos.home.products.view.type.booking.slots.date')"
                    :placeholder="'YYYY-MM-DD'"
                    :disable="disabledDates"
                    :minDate="today"
                    @change="getAvailableSlots($event)"
                />

                <Error :name="'booking[date]'" />
            </ControlGroup>

            <!-- Select Slots -->
            <ControlGroup>
                <Field
                    :type="'select'"
                    :name="'booking[slot]'"
                    rules="required"
                    v-model="selectedSlot"
                    :label="$t('pos.home.products.view.type.booking.slots.title')"
                    :placeholder="$t('pos.home.products.view.type.booking.slots.title')"
                >
                    <option value="">
                        {{ $t('pos.home.products.view.type.booking.slots.select_slot') }}
                    </option>
                    
                    <option
                        v-if="! slots?.length"
                        disabled
                    >
                        {{ $t('pos.home.products.view.type.booking.slots.no_slots_available') }}
                    </option>

                    <option
                        v-for="(slot, index) in slots"
                        :key="index"
                        :value="slot.timestamp"
                    >
                        {{ `${slot.from} - ${slot.to}` }}
                    </option>
                </Field>

                <Error :name="'booking[slot]'" />
            </ControlGroup>
        </div>
    </div>
</template>

<script setup>
    import { ref, computed } from 'vue';
    import Booking from '@src/helpers/Booking';
    import DefaultBooking from '@src/helpers/booking/Default';

    /**
     * Define the props.
     */
    const props = defineProps({
        bookingProduct: {
            type: Object,
            required: true,
        },
        title: {
            type: String,
            required: false,
        },
    });

    const slots = ref([]);
    const selectedSlot = ref('');
    const minAllowedDate = ref('');
    const bookingProduct = ref(props.bookingProduct);
    const today = (new Date()).toISOString().split('T')[0];

    /**
     * Number of days before the current date that the user cannot select a slot.
     */
    const preventDays = computed(() => {
        return props.bookingProduct.tableSlot?.preventSchedulingBefore || 0;
    });

    /**
     * Minimum date is the date from which the user can select the slot.
     */
    const calculateMinDate = () => {
        const today = new Date();

        const days = parseInt(preventDays.value, 10) || 0;

        today.setDate(today.getDate() + days);

        return today.toISOString().split('T')[0];
    };

    minAllowedDate.value = calculateMinDate();

    /**
     * Disabled dates are the dates that are not allowed to be selected.
     */
    const disabledDates = computed(() => {
        const dates = [];

        const today = new Date();

        const endDate = new Date(minAllowedDate.value);

        while (today < endDate) {
            dates.push(today.toISOString().split('T')[0]);

            today.setDate(today.getDate() + 1);
        }

        return dates;
    });

    /**
     * Get available slots for the selected date.
     */
    const getAvailableSlots = (event) => {
        const date = event.target.value;

        const slotType = `${bookingProduct.value.type}Slot`;

        const bookingSlot = bookingProduct.value[slotType];

        if (bookingSlot?.slots?.length === 0) {
            return [];
        }

        if (bookingProduct.value.type === 'default') {
            const booking = new DefaultBooking();            

            slots.value = booking.getSlotsByDate(
                bookingProduct.value,
                bookingSlot,
                date,
            );
        } else {
            const booking = new Booking();

            slots.value = booking.slotsCalculation(
                bookingProduct.value,
                date,
                bookingSlot,
            );
        }
    };
</script>