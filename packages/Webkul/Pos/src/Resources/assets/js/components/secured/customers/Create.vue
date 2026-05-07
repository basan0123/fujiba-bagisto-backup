<template>
    <div class="flex gap-4">
        <div class="my-4 flex w-full flex-col gap-4">
            <div class="padding-right grid gap-4">
                <div class="flex items-center justify-between gap-2.5">
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $t('pos.customers.create.title') }}
                    </p>

                    <div
                        class="flex w-max cursor-pointer items-center text-slate-600 dark:text-slate-400"
                        @click="$router.go(-1)"
                    >
                        <span class="icon-chevron-left text-2xl"></span>

                        <span class="text-base font-semibold leading-5">
                            {{ $t('pos.customers.create.back_btn_title') }}
                        </span>
                    </div>
                </div>

                <v-form v-slot="{ handleSubmit }">
                    <form
                        class="box-shadow grid gap-6 rounded-lg bg-white max-sm:py-4 p-4 dark:bg-gray-900"
                        @submit="handleSubmit($event, submitForm)"
                    >
                        <div class="grid items-start gap-2.5 max-sm:gap-6 md:grid-cols-2">
                            <ControlGroup>
                                <Label for="first_name">
                                    {{ $t('pos.customers.create.first_name') }}
                                </Label>

                                <Field
                                    :type="'text'"
                                    :name="'first_name'"
                                    :id="'first_name'"
                                    :rules="{ required: true, regex: /^[a-zA-Z\s]+$/ }"
                                    :placeholder="$t('pos.customers.create.first_name')"
                                    :label="$t('pos.customers.create.first_name')"
                                    v-model="form.firstName"
                                />

                                <Error :name="'first_name'" />
                            </ControlGroup>

                            <ControlGroup>
                                <Label for="last_name">
                                    {{ $t('pos.customers.create.last_name') }}
                                </Label>

                                <Field
                                    :type="'text'"
                                    :name="'last_name'"
                                    :id="'last_name'"
                                    :rules="{ required: true, regex: /^[a-zA-Z\s]+$/ }"
                                    :placeholder="$t('pos.customers.create.last_name')"
                                    :label="$t('pos.customers.create.last_name')"
                                    v-model="form.lastName"
                                />

                                <Error :name="'last_name'" />
                            </ControlGroup>
                        </div>

                        <div class="grid items-start gap-2.5 max-sm:gap-6 md:grid-cols-2">
                            <ControlGroup>
                                <Label for="phone">
                                    {{ $t('pos.customers.create.phone_number') }}
                                </Label>

                                <Field
                                    :type="'text'"
                                    :name="'phone'"
                                    :id="'phone'"
                                    :rules="'required|phone'"
                                    :placeholder="$t('pos.customers.create.phone_number')"
                                    :label="$t('pos.customers.create.phone_number')"
                                    v-model="form.phone"
                                />

                                <Error :name="'phone'" />
                            </ControlGroup>

                            <ControlGroup>
                                <Label for="email">
                                    {{ $t('pos.customers.create.email') }}
                                </Label>

                                <Field
                                    :type="'email'"
                                    :name="'email'"
                                    :id="'email'"
                                    :rules="'required|email'"
                                    :placeholder="$t('pos.customers.create.email')"
                                    v-model="form.email"
                                />

                                <Error :name="'email'" />
                            </ControlGroup>
                        </div>

                        <ControlGroup>
                            <Label for="address">
                                {{ $t('pos.customers.create.address') }}
                            </Label>

                            <Field
                                :type="'text'"
                                :name="'address[]'"
                                :id="'address'"
                                :rules="'required|address'"
                                :placeholder="$t('pos.customers.create.address')"
                                :label="$t('pos.customers.create.address')"
                                v-model="form.address"
                            />

                            <Error :name="'address[]'" />
                        </ControlGroup>

                        <div class="grid items-start gap-2.5 max-sm:gap-6 md:grid-cols-2">
                            <ControlGroup>
                                <Label for="country">
                                    {{ $t('pos.customers.create.country') }}
                                </Label>

                                <Field
                                    :type="'select'"
                                    :name="'country'"
                                    :id="'country'"
                                    :rules="'required'"
                                    v-model="form.country"
                                >
                                    <option value="">
                                        {{ $t('pos.customers.create.select_country') }}
                                    </option>

                                    <option
                                        v-for="(country, index) in countries"
                                        :key="index"
                                        :value="country.code"
                                    >
                                        {{ country.name }}
                                    </option>
                                </Field>

                                <Error :name="'country'" />
                            </ControlGroup>

                            <ControlGroup>
                                <Label for="state">
                                    {{ $t('pos.customers.create.state') }}
                                </Label>

                                <template v-if="currentCountryStates?.length">
                                    <Field
                                        :type="'select'"
                                        :name="'state'"
                                        :id="'state'"
                                        :rules="'required'"
                                        v-model="form.state"
                                    >
                                        <option value="">
                                            {{ $t('pos.customers.create.select_state') }}
                                        </option>

                                        <option
                                            v-for="(state, index) in currentCountryStates"
                                            :key="index"
                                            :value="state.code"
                                        >
                                            {{ state.defaultName }}
                                        </option>
                                    </Field>
                                </template>

                                <template v-else>
                                    <Field
                                        :type="'text'"
                                        :name="'state'"
                                        :id="'state'"
                                        :rules="'required'"
                                        :placeholder="$t('pos.customers.create.state')"
                                        v-model="form.state"
                                    />
                                </template>

                                <Error :name="'state'" />
                            </ControlGroup>
                        </div>

                        <div class="grid items-start gap-2.5 max-sm:gap-6 md:grid-cols-2">
                            <ControlGroup>
                                <Label for="city">
                                    {{ $t('pos.customers.create.city') }}
                                </Label>

                                <Field
                                    :type="'text'"
                                    :name="'city'"
                                    :id="'city'"
                                    :rules="'required'"
                                    :placeholder="$t('pos.customers.create.city')"
                                    v-model="form.city"
                                />

                                <Error :name="'city'" />
                            </ControlGroup>

                            <ControlGroup>
                                <Label for="postcode">
                                    {{ $t('pos.customers.create.pin_code') }}
                                </Label>

                                <Field
                                    :type="'text'"
                                    :name="'postcode'"
                                    :id="'postcode'"
                                    :rules="'required'"
                                    :placeholder="$t('pos.customers.create.pin_code')"
                                    :label="$t('pos.customers.create.pin_code')"
                                    v-model="form.postcode"
                                />

                                <Error :name="'postcode'" />
                            </ControlGroup>
                        </div>

                        <Button
                            type="submit"
                            class="primary-button w-full px-7 py-4 md:w-[280px]"
                            :label="$t('pos.customers.create.save_btn_title')"
                            :icon="'icon-plus'"
                            :isLoading="isSubmittingForm"
                        />
                    </form>
                </v-form>
            </div>
        </div>

        <Cart v-if="! isMobileOrTab" />
    </div>
</template>

<script setup>
    import { useI18n } from 'vue-i18n';
    import { reactive, watchEffect, ref, watch, toRaw, inject } from 'vue';
    import { useMutation } from '@vue/apollo-composable';
    import { CREATE } from '@src/graphql/customers';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { useWindowWidth } from '@src/composable/window';
    import { useRouter } from 'vue-router';

    /**
     * General use variables
     */
    const { t } = useI18n();
    const DB = useIndexedDB();
    const emitter = inject('emitter');
    const isOnline = inject('isOnline');
    const router = useRouter();
    const { isMobileOrTab } = useWindowWidth();

    /**
     * Fetch countries and states
     */
    const countries = ref([]);
    const countryStates = ref([]);
    const currentCountryStates = ref([]);

    watchEffect(async () => {
        const countriesStates = await DB.getItem('countries_states', 1);

        countries.value = countriesStates?.countries;
        countryStates.value = countriesStates?.countryStates;
    });

    const form = reactive({
        firstName: '',
        lastName: '',
        phone: '',
        email: '',
        address: '',
        country: '',
        state: '',
        city: '',
        postcode: '',
    });

    watch(() => form.country, (value) => {
        currentCountryStates.value = countryStates.value.find((countryState) => countryState.countryCode === value)?.states;
    });

    /**
     * Form Submission
     */
    const { mutate, loading: isSubmittingForm } = useMutation(CREATE, () => ({
        variables: {
            input: toRaw(form),
        },
    }));

    const submitForm = async (params, { setErrors, resetForm }) => {
        if (isOnline.value) {
            const data = await mutate();

            const createCustomer = data?.data?.createPosCustomer;

            if (createCustomer?.success === true) {
                emitter.emit('add_flash', {
                    type: 'success',
                    message: createCustomer.message,
                });

                await DB.updateItem('customers', createCustomer.customer);

                router.push({path: '/customers'});
            } else if (createCustomer?.success === false) {
                if (createCustomer?.errors) {
                    setErrors(JSON.parse(createCustomer.errors));
                } else {
                    emitter.emit('add_flash', {
                        type: 'error',
                        message: createCustomer?.message,
                    });
                }
            }
        } else {
            let errors = {};

            for (const table of ['customers', 'offline_customers']) {
                const customers = await DB.getAllItems(table);

                const emailExists = customers.find(customer => {
                    return customer.email === form.email && customer.id !== form.id;
                });

                if (emailExists) {
                    errors.email = [t('pos.customers.create.email_already_exist')];
                }

                const phoneExists = customers.find(customer => {
                    return customer.phone === form.phone && customer.id !== form.id;
                });

                if (phoneExists) {
                    errors.phone = [t('pos.customers.create.phone_already_exist')];
                }

                if (Object.keys(errors).length) {
                    break;
                }
            }

            if (Object.keys(errors).length) {
                setErrors(errors);

                return;
            }

            if (Object.keys(errors).length) {
                setErrors(errors);

                return;
            }

            await DB.addItem('offline_customers', {
                ...toRaw(form),
                isOffline: true
            }).then(() => {
                emitter.emit('add_flash', {
                    type: 'success',
                    message: t('pos.customers.create.create_success'),
                });

                router.push({path: '/customers'});
            });
        }
    }
</script>
