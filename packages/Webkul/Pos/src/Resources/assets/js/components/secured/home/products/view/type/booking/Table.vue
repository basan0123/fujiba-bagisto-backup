<template>
    <div class="grid grid-cols-1 gap-6">
        <div class="flex gap-3 text-gray-800 dark:text-gray-100">
            <span class="icon-calendar text-2xl text-gray-500 dark:text-gray-400"></span>

            <div class="grid grid-cols-1 gap-1.5 text-sm font-medium">
                <p class="text-neutral-400 dark:text-neutral-500">
                    {{ $t('pos.home.products.view.type.booking.table.slot_duration') }} :
                </p>

                <div>
                    {{ $t('pos.home.products.view.type.booking.table.slot_duration_in_minutes', {
                        minutes: bookingProduct.tableSlot.duration
                    }) }}
                </div>
            </div>
        </div>

        <div class="flex gap-3 text-gray-800 dark:text-gray-100">
            <span class="icon-calendar text-2xl text-gray-500 dark:text-gray-400"></span>

            <div class="grid grid-cols-1 gap-4">
                <div class="grid grid-cols-1 gap-1.5 text-sm font-medium">
                    <p class="text-neutral-400 dark:text-neutral-500">
                        {{ $t('pos.home.products.view.type.booking.table.today_availability') }}
                    </p>

                    <span
                        class="text-base"
                        v-html="todaySlotsHtml"
                    >
                    </span>
                </div>

                <div class="grid w-max select-none gap-3">
                    <!-- Details Toggler -->
                    <p
                        class="flex cursor-pointer items-center gap-x-[15px] text-sm font-medium text-blue-600 dark:text-blue-400"
                        @click="showDaysAvailability = ! showDaysAvailability"
                    >
                        {{ $t('pos.home.products.view.type.booking.table.slots_for_all_days') }}

                        <span
                            class="text-xl font-bold"
                            :class="[showDaysAvailability ? 'icon-arrow-up' : 'icon-arrow-down']"
                        >
                        </span>
                    </p>

                    <!-- Option Details -->
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

                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <template v-if="day.slots && day.slots?.length">
                                <div
                                    v-for="(slot, index) in day.slots"
                                    :key="index"
                                >
                                    {{ `${slot.from} - ${slot.to}` }}
                                </div>
                            </template>

                            <template v-else>
                                {{ $t('pos.home.products.view.type.booking.table.closed') }}
                            </template>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <slots
            :bookingProduct="bookingProduct"
            :title="$t('pos.home.products.view.type.booking.table.book_a_table')"
        />

        <ControlGroup>
            <Label for="booking[note]">
                {{ $t('pos.home.products.view.type.booking.table.special_notes') }}
            </Label>

            <Field
                :type="'textarea'"
                :name="'booking[note]'"
                :id="'booking[note]'"
                :rules="'required'"
                :label="$t('pos.home.products.view.type.booking.table.special_notes')"
                :placeholder="$t('pos.home.products.view.type.booking.table.special_notes')"
            />

            <Error :name="'booking[note]'" />
        </ControlGroup>
    </div>
</template>

<script setup>
    import { ref, watchEffect } from 'vue';
    import Slots from '@components/secured/home/products/view/type/booking/Slots.vue';
    import TableBooking from '@src/helpers/booking/Table';

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
        const tableBooking = new TableBooking();

        todaySlotsHtml.value = tableBooking.getTodaySlotsHtml(
            props.bookingProduct,
            props.bookingProduct.tableSlot
        );

        days.value = tableBooking.getWeekSlotDurations(
            props.bookingProduct,
            props.bookingProduct.tableSlot
        );        
    });
</script>