<template>
    <div class="grid gap-8">
        <div class="mt-8 grid gap-4 max-sm:mt-4">
            <h1 class="text-xl font-medium text-gray-900 dark:text-gray-100">
                {{ $t('pos.cashier.cash_drawer.drawer_amount_summary') }}
            </h1>

            <template v-if="loading">
                <DrawerSkeleton />
            </template>

            <template v-else>
                <div class="rounded-lg bg-white dark:bg-gray-900">
                    <div class="grid border-b border-[#E1E1E1] dark:border-gray-700 p-4">
                        <div class="flex h-12 justify-between rounded-lg bg-gray-100 dark:bg-gray-800 p-4 max-sm:h-auto max-sm:flex-col max-sm:px-2.5 max-sm:py-4 md:items-center">
                            <p class="text-base leading-5 text-gray-900 dark:text-gray-200">
                                {{ $t('pos.cashier.cash_drawer.opening_drawer_amount') }}
                            </p>

                            <p class="text-base leading-5 text-gray-900 dark:text-gray-200">
                                {{ formatPrice(drawer.openingAmount ?? 0) }}
                            </p>
                        </div>

                        <div class="flex h-12 justify-between rounded-lg p-4 max-sm:h-auto max-sm:flex-col max-sm:px-2.5 max-sm:py-4 md:items-center">
                            <p class="text-base leading-5 text-gray-900 dark:text-gray-200">
                                {{ $t('pos.cashier.cash_drawer.cash_payment_sale') }}
                            </p>

                            <p class="text-base leading-5 text-gray-900 dark:text-gray-200">
                                {{ formatPrice(drawer.cashPaymentSale ?? 0) }}
                            </p>
                        </div>

                        <div class="flex h-12 justify-between rounded-lg bg-gray-100 dark:bg-gray-800 p-4 max-sm:h-auto max-sm:flex-col max-sm:px-2.5 max-sm:py-4 md:items-center">
                            <p class="text-base leading-5 text-gray-900 dark:text-gray-200">
                                {{ $t('pos.cashier.cash_drawer.other_payment_sale') }}
                            </p>

                            <p class="text-base leading-5 text-gray-900 dark:text-gray-200">
                                {{ formatPrice(drawer.otherPaymentSale ?? 0) }}
                            </p>
                        </div>

                        <div class="flex h-12 justify-between rounded-lg p-4 max-sm:h-auto max-sm:flex-col max-sm:px-2.5 max-sm:py-4 md:items-center">
                            <p class="text-base leading-5 text-gray-900 dark:text-gray-200">
                                {{ $t('pos.cashier.cash_drawer.expected_drawer_amount') }}
                            </p>

                            <p class="text-base leading-5 text-gray-900 dark:text-gray-200">
                                {{ formatPrice(drawer.expectedDrawerAmount ?? 0) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex h-12 justify-between rounded-lg p-8 max-sm:h-auto max-sm:flex-col max-sm:px-2.5 max-sm:py-4 md:items-center">
                        <p class="text-xl leading-6 text-gray-900 dark:text-gray-100">
                            {{ $t('pos.cashier.cash_drawer.difference') }}
                        </p>
                        
                        <p class="text-xl font-medium leading-6 text-green-600 dark:text-green-400">
                            {{ formatPrice(drawer.differenceAmount ?? 0) }}
                        </p>
                    </div>
                </div>
            </template>
        </div>

        <v-form v-slot="{ handleSubmit }">
            <form
                class="mb-4 grid content-start gap-4"
                @submit="handleSubmit($event, submitForm)"
            >
                <ControlGroup>
                    <Label for="expected_drawer_amount">
                        {{ $t('pos.cashier.cash_drawer.expected_drawer_amount') }}
                    </Label>

                    <Field
                        :type="'text'"
                        :name="'expected_drawer_amount'"
                        :id="'expected_drawer_amount'"
                        :rules="'required'"
                        :placeholder="'100'"
                        :label="$t('pos.cashier.cash_drawer.expected_drawer_amount')"
                        v-model="expectedDrawerAmount"
                    />

                    <Error :name="'expected_drawer_amount'" />
                </ControlGroup>

                <ControlGroup>
                    <Label for="remarks">
                        {{ $t('pos.cashier.cash_drawer.remarks') }}
                    </Label>

                    <Field
                        :type="'textarea'"
                        :name="'remarks'"
                        :id="'remarks'"
                        :rules="'required'"
                        :placeholder="$t('pos.cashier.cash_drawer.remarks_placeholder')"
                    />

                    <Error :name="'remarks'" />
                </ControlGroup>

                <Button
                    type="submit"
                    class="secondary-button w-full md:w-80"
                    :label="$t('pos.cashier.cash_drawer.close_drawer')"
                    :icon="'icon-check'"
                    :isLoading="isSubmittingForm"
                />
            </form>
        </v-form>
    </div>
</template>

<script setup>
    import { computed, inject } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useOutlet } from '@src/composable/outlet';
    import { useCookies } from '@src/composable/cookies';
    import { useQuery, useMutation } from '@vue/apollo-composable';
    import { GET_DRAWER, CLOSE_DRAWER } from '@src/graphql/drawer';
    import DrawerSkeleton from '@skeletons/cashier/Drawer.vue';

    /**
     * General Variables
     */
    const { t } = useI18n();
    const emitter = inject('emitter');
    const isOnline = inject('isOnline');
    const { formatPrice } = useOutlet();
    const cookies = useCookies();

    /**
     * Fetch Drawer data
     */
    const { result, loading } = useQuery(GET_DRAWER, null, {
        skip: computed(() => !isOnline.value), 
    });

    const drawer = computed(() => result.value?.getDrawerDetails ?? []);

    /**
     * Expected Drawer Amount
     */
    const expectedDrawerAmount = computed(() => (parseFloat(drawer.value.expectedDrawerAmount ?? 0)).toFixed(2));

    /**
     * Close Drawer
     */
    const { mutate: closeDrawer, loading: isSubmittingForm } = useMutation(CLOSE_DRAWER);

    const submitForm = (params, { setErrors, resetForm }) => {
        if (! isOnline.value) {
            emitter.emit('add_flash', {
                type: 'warning',
                message: t('pos.common.flash_messages.offline_error'),
            });

            return;
        }

        closeDrawer({ input: {
            openingAmount: parseFloat(params.expected_drawer_amount),
            remark: params.remark
        }}).then(response => {
            if (response.data.closeDrawer.success) {
                resetForm();

                cookies.remove('drawer_fetched');
                
                emitter.emit('add_flash', {
                    type: 'success',
                    message: response.data.closeDrawer.message
                });
            }
        }).catch(error => {
            emitter.emit('add_flash', {
                type: 'error',
                message: error.message
            });
        });
    };
</script>
