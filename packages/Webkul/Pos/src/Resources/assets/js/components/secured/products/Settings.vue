<template>
    <v-form
        class="box-shadow grid gap-6 rounded-lg bg-white dark:bg-gray-900 p-4"
        @submit="submitForm"
    >
        <ControlGroup>
            <Label for="minimum_qty">
                {{ $t('pos.products.settings.minimum_qty') }}
            </Label>

            <Field
                :type="'text'"
                :name="'minimum_qty'"
                :id="'minimum_qty'"
                :rules="'required|numeric|min_value:10|max_value:1000'"
                :placeholder="$t('pos.products.settings.minimum_qty')"
                :label="$t('pos.products.settings.minimum_qty')"
                v-model="lowStockQty"
            />

            <Error :name="'minimum_qty'" />
        </ControlGroup>

        <Button
            type="submit"
            class="primary-button w-full px-7 py-4 md:w-[280px]"
            :label="$t('pos.products.settings.save_btn_title')"
            :icon="'icon-check'"
            :isLoading="isSubmittingForm"
        />
    </v-form>
</template>

<script setup>
    import { ref, watchEffect, inject } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useMutation } from '@vue/apollo-composable';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { PROFILE_UPDATE } from '@src/graphql/settings';

    /**
     * General Variables
     */
    const DB = useIndexedDB();
    const { t } = useI18n();
    const emitter = inject('emitter');
    const isOnline = inject('isOnline');

    /**
     * Form Variables
     */
    const lowStockQty = ref(null);

    /**
     * Set the form values
     */
    const agent = ref(null);

    watchEffect(() => {
        DB.getAgent().then(user => {
            agent.value = user;

            if (agent.value) {
                lowStockQty.value = agent.value.outlet?.lowStockQty || null;
            }
        });
    });

    /**
     * Update Min Qty
     */
    const { mutate: updateProfile, loading: isSubmittingForm } = useMutation(PROFILE_UPDATE);

    const submitForm = async () => {
        if (! isOnline.value) {
            emitter.emit('add_flash', {
                type: 'warning',
                message: t('pos.common.flash_messages.offline_error'),
            });

            return;
        }

        const input = {
            firstName: agent.value.firstName,
            lastName: agent.value.lastName,
            email: agent.value.email,
            lowStockQty: parseInt(lowStockQty.value),
        };

        updateProfile({ input }).then(response => {
            const updateProfile = response.data?.updateProfile;

            if (updateProfile?.success === true) {
                DB.updateItem('agent', updateProfile?.agent);

                emitter.emit('add_flash', {
                    type: 'success',
                    message: t('pos.products.settings.save_success'),
                });
            } else if (updateProfile?.success === false) {
                emitter.emit('add_flash', {
                    type: 'error',
                    message: t('pos.common.flash_messages.error_message'),
                });
            }
        });
    }
</script>