<template>
    <div class="grid grid-cols-1 gap-2.5">
        <template v-if="rentingType == 'daily_hourly'">
            <label class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                {{ $t('pos.home.products.view.type.booking.rental.select_rent_option') }}
            </label>

            <div class="mb-3 grid grid-cols-2 gap-2.5">
                <!-- Daily Radio Button -->
                <Field
                    :type="'radio'"
                    :id="'booking[daily]'"
                    :name="'booking[renting_type]'"
                    :value="'daily'"
                    :label="$t('pos.home.products.view.type.booking.rental.daily_basis')"
                    v-model="subRentingType"
                />

                <!-- Hourly Radio Button -->
                <Field
                    :type="'radio'"
                    :id="'booking[hourly]'"
                    :name="'booking[renting_type]'"
                    :value="'hourly'"
                    :label="$t('pos.home.products.view.type.booking.rental.hourly_basis')"
                    v-model="subRentingType"
                />
            </div>
        </template>

        <div
            class="flex flex-col gap-2.5"
            v-if="rentingType != 'daily'
            && subRentingType == 'hourly'"
        >
            <div class="grid gap-1.5">
                <label class="required text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                    {{ $t('pos.home.products.view.type.booking.rental.select_slot') }}
                </label>

                <div class="flex gap-2.5">
                    <!-- Select Slot Date -->
                    <ControlGroup className="flex-1">
                        <Label for="booking[date]">
                            {{ $t('pos.home.products.view.type.booking.rental.select_date') }}
                        </Label>

                        <Field
                            :type="'date'"
                            :name="'booking[date]'"
                            :id="'booking[date]'"
                            :rules="'required'"
                            :label="$t('pos.home.products.view.type.booking.rental.select_date')"
                            :placeholder="$t('pos.home.products.view.type.booking.rental.select_date')"
                            :minDate="today"
                            @change="dateSelected($event)"
                        />

                        <Error :name="'booking[date]'" />
                    </ControlGroup>

                    <ControlGroup className="flex-1">
                        <Label for="booking[slot]">
                            {{ $t('pos.home.products.view.type.booking.rental.select_slot') }}
                        </Label>

                        <Field
                            :type="'text'"
                            :name="'booking[slot]'"
                            :id="'booking[slot]'"
                            :rules="'required'"
                            :label="$t('pos.home.products.view.type.booking.rental.slot')"
                            :placeholder="$t('pos.home.products.view.type.booking.rental.slot')"
                            v-model="selectedSlot"
                        >
                            <option value="">
                                {{ $t('pos.home.products.view.type.booking.rental.select_slot') }}
                            </option>
                            
                            <option
                                v-if="! slots?.length"
                                disabled
                            >
                                {{ $t('pos.home.products.view.type.booking.rental.no_slots_available') }}
                            </option>

                            <option
                                v-for="(slot, index) in slots"
                                :key="index"
                                :value="index"
                                v-text="slot.time"
                            >
                            </option>
                        </Field>

                        <Error :name="'booking[slot]'" />
                    </ControlGroup>
                </div>
            </div>

            <div
                class="grid gap-1.5"
                v-if="parseInt(slots[selectedSlot] && slots[selectedSlot]?.slots?.length)"
            >
                <label class="text-base leading-5 text-gray-900 dark:text-white">
                    {{ $t('pos.home.products.view.type.booking.rental.select_rent_time') }}
                </label>

                <div class="flex gap-2.5">
                    <!-- Select Time Slot From -->
                    <ControlGroup className="flex-1">
                        <Field
                            :type="'select'"
                            :name="'booking[slot][from]'"
                            :rules="'required'"
                            :label="$t('pos.home.products.view.type.booking.rental.select_date')"
                            :placeholder="$t('pos.home.products.view.type.booking.rental.select_date')"
                        >
                            <option value="">
                                {{ $t('pos.home.products.view.type.booking.rental.select_time_slot') }}
                            </option>

                            <option
                                v-for="(slot, index) in slots[selectedSlot]?.slots"
                                :key="index"
                                :value="slot.from_timestamp"
                                v-text="slot.from"
                            >
                            </option>
                        </Field>

                        <Error :name="'booking[slot][from]'" />
                    </ControlGroup>

                    <!-- Select Time Slot To -->
                    <ControlGroup className="flex-1">
                        <Field
                            :type="'select'"
                            :name="'booking[slot][to]'"
                            :rules="'required'"
                            :label="$t('pos.home.products.view.type.booking.rental.slot')"
                            :placeholder="$t('pos.home.products.view.type.booking.rental.slot')"
                        >
                            <option value="">
                                {{ $t('pos.home.products.view.type.booking.rental.select_time_slot') }}
                            </option>

                            <option
                                v-for="(slot, index) in slots[selectedSlot]?.slots"
                                :key="index"
                                :value="slot?.to_timestamp"
                                v-text="slot.to"
                            >
                            </option>
                        </Field>

                        <Error :name="'booking[slot][to]'" />
                    </ControlGroup>
                </div>
            </div>
        </div>

        <div v-else>
            <label class="text-base leading-5 text-gray-900 dark:text-white">
                {{ $t('pos.home.products.view.type.booking.rental.select_date') }}
            </label>

            <div class="flex gap-2.5">
                <!-- Select Date From -->
                <ControlGroup className="flex-1">
                    <Field
                        :type="'date'"
                        :name="'booking[date_from]'"
                        :rules="'required'"
                        :label="$t('pos.home.products.view.type.booking.rental.from')"
                        :placeholder="$t('pos.home.products.view.type.booking.rental.from')"
                        :minDate="today"
                        @change="dateSelected($event)"
                    />

                    <Error :name="'booking[date_from]'" />
                </ControlGroup>

                <!-- Select Date To -->
                <ControlGroup className="flex-1">
                    <Field
                        :type="'date'"
                        :name="'booking[date_to]'"
                        :rules="'required'"
                        :label="$t('pos.home.products.view.type.booking.rental.to')"
                        :placeholder="$t('pos.home.products.view.type.booking.rental.to')"
                        :minDate="today"
                        @change="dateSelected($event)"
                    />

                    <Error :name="'booking[date_to]'" />
                </ControlGroup>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { ref } from 'vue';
    import Booking from '@src/helpers/Booking';

    /**
     * Define the props.
     */
    const props = defineProps({
        bookingProduct: {
            type: Object,
            required: true,
        },
    });

    const rentingType = ref(props.bookingProduct.rentalSlot.rentingType);
    const subRentingType = ref('hourly');
    const slots = ref([]);
    const selectedSlot = ref(null);
    const today = (new Date()).toISOString().split('T')[0];

    /**
     * Get available slots for the selected date.
     */
     const dateSelected = (event) => {
        const date = event.target.value;

        const booking = new Booking();

        slots.value = booking.slotsCalculation(
            props.bookingProduct,
            date,
            props.bookingProduct.rentalSlot,
        );
    };
</script>