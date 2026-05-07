<template>
    <div class="grid grid-cols-1 gap-6">
        <div class="flex gap-3 text-gray-800 dark:text-gray-100">
            <span class="icon-calendar text-2xl text-gray-500 dark:text-gray-400"></span>

            <div class="grid grid-cols-1 gap-1.5 text-sm font-medium">
                <p class="text-neutral-400 dark:text-neutral-500">
                    {{ $t('pos.home.products.view.type.booking.appointment.slot_duration') }} :
                </p>

                <div>
                    {{ $t('pos.home.products.view.type.booking.appointment.slot_duration_in_minutes', {
                        minutes: bookingProduct.appointmentSlot.duration
                    }) }}
                </div>
            </div>
        </div>

        <div class="flex gap-3 text-gray-800 dark:text-gray-100">
            <span class="icon-calendar text-2xl text-gray-500 dark:text-gray-400"></span>

            <div class="grid grid-cols-1 gap-4">
                <div class="grid grid-cols-1 gap-1.5 text-sm font-medium">
                    <p class="text-neutral-400 dark:text-neutral-500">
                        {{ $t('pos.home.products.view.type.booking.appointment.today_availability') }}
                    </p>

                    <span
                        class="text-base"
                        v-html="todaySlotsHtml"
                    >
                    </span>
                </div>

                <div class="grid w-max select-none gap-3">
                    <p
                        class="flex cursor-pointer items-center gap-x-[15px] text-sm font-medium text-blue-600 dark:text-blue-400"
                        @click="showDaysAvailability = !showDaysAvailability"
                    >
                        {{ $t('pos.home.products.view.type.booking.appointment.see_details') }}

                        <span
                            class="text-xl font-bold"
                            :class="[showDaysAvailability ? 'icon-arrow-up' : 'icon-arrow-down']"
                        >
                        </span>
                    </p>

                    <div
                        class="grid grid-cols-2 gap-3"
                        v-show="showDaysAvailability"
                        v-for="(day, index) in days"
                        :key="index"
                    >
                        <p
                            class="text-gray-700 dark:text-gray-300 text-sm font-medium"
                            v-text="day.name"
                        >
                        </p>

                        <p class="grid gap-y-2.5 text-sm text-gray-600 dark:text-gray-400">
                            <template v-if="day.slots && day.slots?.length">
                                <div
                                    v-for="slot in day.slots"
                                    :key="slot.id"
                                >
                                    {{ `${slot.from} - ${slot.to}` }}
                                </div>
                            </template>

                            <template v-else>
                                {{ $t('pos.home.products.view.type.booking.appointment.closed') }}
                            </template>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <slots :bookingProduct="bookingProduct" />
    </div>
</template>

<script setup>
    import { ref, watchEffect } from 'vue';
    import Slots from '@components/secured/home/products/view/type/booking/Slots.vue';
    import AppointmentBooking from '@src/helpers/booking/Appointment';

    /**
     * Define the props.
     */
    const props = defineProps({
        bookingProduct: {
            type: Object,
            required: true,
        },
    });

    const showDaysAvailability = ref(false);
    const todaySlotsHtml = ref('');
    const days = ref([]);

    /**
     * Watch for changes in the bookingProduct prop.
     */
    watchEffect(async () => {
        const appointmentBooking = new AppointmentBooking();

        todaySlotsHtml.value = appointmentBooking.getTodaySlotsHtml(
            props.bookingProduct,
            props.bookingProduct.appointmentSlot
        );

        days.value = appointmentBooking.getWeekSlotDurations(
            props.bookingProduct,
            props.bookingProduct.appointmentSlot
        );
    });
</script>