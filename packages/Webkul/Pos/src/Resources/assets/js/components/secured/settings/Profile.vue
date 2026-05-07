<template>
    <v-form v-slot="{ handleSubmit }">
        <form
            class="box-shadow grid gap-6 rounded-lg bg-white dark:bg-gray-900 p-4"
            @submit="handleSubmit($event, submitForm)"
        >
            <div class="grid items-start gap-8 md:grid-cols-2 md:gap-2.5">
                <ControlGroup>
                    <Label for="first_name">
                        {{ $t('pos.settings.profile.first_name') }}
                    </Label>

                    <Field
                        :type="'text'"
                        :name="'first_name'"
                        :id="'first_name'"
                        :rules="'required'"
                        :label="$t('pos.settings.profile.first_name')"
                        :placeholder="$t('pos.settings.profile.first_name')"
                        v-model="agent.firstName"
                    />

                    <Error :name="'first_name'" />
                </ControlGroup>

                <ControlGroup>
                    <Label for="last_name">
                        {{ $t('pos.settings.profile.last_name') }}
                    </Label>

                    <Field
                        :type="'text'"
                        :name="'last_name'"
                        :id="'last_name'"
                        :rules="'required'"
                        :label="$t('pos.settings.profile.last_name')"
                        :placeholder="$t('pos.settings.profile.last_name')"
                        v-model="agent.lastName"
                    />

                    <Error :name="'last_name'" />
                </ControlGroup>
            </div>

            <div class="grid items-start gap-8 md:grid-cols-2 md:gap-2.5">
                <ControlGroup>
                    <Label for="email">
                        {{ $t('pos.settings.profile.email') }}
                    </Label>

                    <Field
                        :type="'email'"
                        :name="'email'"
                        :id="'email'"
                        :rules="'required|email'"
                        :placeholder="$t('pos.settings.profile.email')"
                        v-model="agent.email"
                    />

                    <Error :name="'email'" />
                </ControlGroup>

                <ControlGroup>
                    <Label for="old_password">
                        {{ $t('pos.settings.profile.old_password') }}
                    </Label>

                    <div class="relative">
                        <Field
                            :type="showOldPassword ? 'text' : 'password'"
                            :name="'old_password'"
                            :id="'old_password'"
                            :label="$t('pos.settings.profile.old_password')"
                            :placeholder="$t('pos.settings.profile.old_password')"
                        />

                        <span
                            :class="showOldPassword ? 'icon-eye-off' : 'icon-eye'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 transform cursor-pointer text-2xl leading-6 opacity-60 dark:text-white"
                            @click="showOldPassword = !showOldPassword"
                        >
                        </span>
                    </div>

                    <Error :name="'old_password'" />
                </ControlGroup>
            </div>

            <div class="grid items-start gap-8 md:grid-cols-2 md:gap-2.5">
                <ControlGroup>
                    <Label for="new_password">
                        {{ $t('pos.settings.profile.new_password') }}
                    </Label>

                    <div class="relative">
                        <Field
                            :type="showNewPassword ? 'text' : 'password'"
                            :name="'new_password'"
                            :id="'new_password'"
                            :label="$t('pos.settings.profile.new_password')"
                            :placeholder="$t('pos.settings.profile.new_password')"
                        />

                        <span
                            :class="showNewPassword ? 'icon-eye-off' : 'icon-eye'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 transform cursor-pointer text-2xl leading-6 opacity-60 dark:text-white"
                            @click="showNewPassword = !showNewPassword"
                        >
                        </span>
                    </div>

                    <Error :name="'new_password'" />
                </ControlGroup>

                <ControlGroup>
                    <Label for="new_password_confirmation">
                        {{ $t('pos.settings.profile.confirm_password') }}
                    </Label>

                    <div class="relative">
                        <Field
                            :type="showConfirmPassword ? 'text' : 'password'"
                            :name="'new_password_confirmation'"
                            :id="'new_password_confirmation'"
                            :label="$t('pos.settings.profile.confirm_password')"
                            :placeholder="$t('pos.settings.profile.confirm_password')"
                        />

                        <span
                            :class="showConfirmPassword ? 'icon-eye-off' : 'icon-eye'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 transform cursor-pointer text-2xl leading-6 opacity-60 dark:text-white"
                            @click="showConfirmPassword = !showConfirmPassword"
                        >
                        </span>
                    </div>

                    <Error :name="'new_password_confirmation'" />
                </ControlGroup>
            </div>

            <Button
                type="submit"
                class="primary-button w-full px-7 py-4 md:w-[280px]"
                :label="$t('pos.settings.profile.update_btn_title')"
                :icon="'icon-check'"
                :isLoading="isSubmittingForm"
            />
        </form>
    </v-form>
</template>

<script setup>
    import { ref, watchEffect, inject } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useMutation } from '@vue/apollo-composable';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { PROFILE_UPDATE } from '@src/graphql/settings';

    /**
     * General use variables
     */
    const { t } = useI18n();
    const DB = useIndexedDB();
    const emitter = inject('emitter');
    const isOnline = inject('isOnline');

    /**
     * Get Profile
     */
    const agent = ref({});

    watchEffect(async () => {
        agent.value = await DB.getAgent();
    });

    /**
     * Update Profile
     */
    const { mutate:updateProfile, loading: isSubmittingForm } = useMutation(PROFILE_UPDATE);

    const submitForm = async (params, { setErrors, resetForm }) => {        
        if (! isOnline.value) {
            emitter.emit('add_flash', {
                type: 'warning',
                message: t('pos.common.flash_messages.offline_error'),
            });

            return;
        }

        const input = {
            firstName: params.first_name,
            lastName: params.last_name,
            email: params.email,
            oldPassword: params.old_password || '',
            newPassword: params.new_password || '',
            newPasswordConfirmation: params.new_password_confirmation || '',
        };

        updateProfile({ input }).then(async (response) => {
            const { updateProfile } = response.data;

            if (updateProfile?.success === true) {
                resetForm();
                
                const updatedAgent = updateProfile.agent;                

                await DB.updateItem('agent', updatedAgent).then(() => {
                    agent.value = updatedAgent;
                });

                emitter.emit('add_flash', {
                    type: 'success',
                    message: updateProfile?.message,
                });
            } else if (updateProfile?.success === false) {
                if (updateProfile?.errors) {                    
                    setErrors(JSON.parse(updateProfile.errors));
                } else {
                    emitter.emit('add_flash', {
                        type: 'error',
                        message: updateProfile?.message,
                    });
                }
            }
        });
    };

    /**
     * Show Password
     */
    const showOldPassword = ref(false);
    const showNewPassword = ref(false);
    const showConfirmPassword = ref(false);
</script>
