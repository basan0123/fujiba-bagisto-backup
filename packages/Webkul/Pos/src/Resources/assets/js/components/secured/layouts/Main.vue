<template>
    <div>
        <Header />

        <!--
            TRAINING MODE banner — shown across the entire POS shell whenever
            the current agent has training_mode=1. Sticky under the Header so
            cashiers can never forget they're in sandbox.
        -->
        <div
            v-if="trainingActive"
            class="fixed inset-x-0 top-11 z-[10004] lg:top-[68px] flex items-center justify-center bg-[#FF9500] py-1.5 text-center text-[12px] font-semibold uppercase tracking-wide text-white shadow"
        >
            <i class="icon-warning mr-1.5"></i>
            {{ $t('pos.settings.training.banner') }}
        </div>

        <FlashMessage />

        <ConfirmModal />

        <div class="flex min-h-[calc(100vh-68px)] gap-4 bg-white pt-11 lg:pt-[68px]">
            <template v-if="$route.path.split('/')[1] != 'payment'">
                <Sidebar />
            </template>

            <div
                :class="$route.path.split('/')[1] == 'payment' ? 'p-4' : 'pl-4 pr-4 lg:ltr:pl-[112px] lg:rtl:pr-[112px]'"
                class="max-w-full flex-1 transition-all duration-300 bg-[#F2F2F7] dark:bg-gray-950"
            >
                <router-view />
            </div>
        </div>

        <Teleport to="body">
            <Modal ref="drawerFormModal">
                <template v-slot:header="{ toggle }">
                    <div class="flex justify-between gap-2.5">
                        {{ $t('pos.layout.main.cash_drawer.title') }}

                        <div
                            class="flex h-6 w-6"
                            @click="toggle"
                        >
                            <span class="icon-cross cursor-pointer text-2xl"></span>
                        </div>
                    </div>
                </template>

                <template v-slot:content>
                    <v-form v-slot="{ handleSubmit }">
                        <form
                            class="grid gap-4"
                            @submit="handleSubmit($event, submitDrawerForm)"
                        >
                            <ControlGroup>
                                <Label for="opening_amount">
                                    {{ $t('pos.layout.main.cash_drawer.opening_drawer_amount') }}
                                </Label>

                                <Field
                                    :type="'text'"
                                    :name="'opening_amount'"
                                    :id="'opening_amount'"
                                    :rules="'required'"
                                    :placeholder="'100'"
                                    :label="$t('pos.layout.main.cash_drawer.opening_drawer_amount')"
                                />

                                <Error :name="'opening_amount'" />
                            </ControlGroup>

                            <ControlGroup>
                                <Label for="remarks">
                                    {{ $t('pos.layout.main.cash_drawer.remarks') }}
                                </Label>

                                <Field
                                    :type="'textarea'"
                                    :name="'remarks'"
                                    :id="'remarks'"
                                    :rules="'required'"
                                    :placeholder="$t('pos.layout.main.cash_drawer.remarks_placeholder')"
                                />

                                <Error :name="'remarks'" />
                            </ControlGroup>

                            <Button
                                type="submit"
                                class="secondary-button w-full"
                                :label="$t('pos.layout.main.cash_drawer.open_drawer')"
                                :icon="'icon-payment'"
                                :isLoading="isSubmittingForm"
                            />
                        </form>
                    </v-form>
                </template>
            </Modal>
        </Teleport>

        <Loader />

        <OfflineLoader />

        <Shortcuts />
    </div>
</template>

<script setup>
    import { ref, onMounted, onBeforeUnmount, inject, computed, watch } from 'vue'
    import { useRouter } from 'vue-router'
    import { useQuery, useMutation } from '@vue/apollo-composable';
    import { useCookies } from '@src/composable/cookies';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { GET_DRAWER, OPEN_DRAWER } from '@src/graphql/drawer';
    import Header from '@components/secured/layouts/Header.vue';
    import Sidebar from '@components/secured/layouts/Sidebar.vue';
    import Shortcuts from '@components/secured/common/Shortcuts.vue';
    import FlashMessage from '@components/shared/FlashMessage.vue';
    import ConfirmModal from '@components/secured/common/ConfirmModal.vue';
    import Loader from '@components/shared/Loader.vue';
    import OfflineLoader from '@components/secured/common/OfflineLoader.vue';

    /**
     * General variables
     */
    const emitter = inject('emitter');
    const router = useRouter();
    const cookies = useCookies();
    const DB      = useIndexedDB();

    /**
     * Whether this agent is in Training Mode — drives the global orange banner.
     * Read from IDB on mount and refreshed whenever the Training settings page
     * emits agent_training_changed.
     */
    const trainingActive = ref(false);

    const refreshTraining = async () => {
        const agent = await DB.getAgent();
        trainingActive.value = !! agent?.trainingMode;
    };

    onMounted(() => {
        refreshTraining();
        emitter.on('agent_training_changed', refreshTraining);
    });

    onBeforeUnmount(() => {
        emitter.off('agent_training_changed', refreshTraining);
    });

    /**
     * Fetch Drawer data
     */
    const { result, error } = useQuery(GET_DRAWER, null, {
        enabled: ! Boolean(cookies.get('drawer_fetched')),
    });

    const drawerData = computed(() => result.value?.getDrawerDetails ?? []);

    watch(error, (value) => {
        if (value.message === 'Unauthenticated.') {
            localStorage.removeItem('accessToken');

            router.push({ path: '/' });
        } else if (value.message === 'status_off') {
            localStorage.removeItem('accessToken');

            router.push({ path: '/404' });
        }
    });

    watch(drawerData, (data) => {
        if (data.openingAmount == null) {
            drawerFormModal.value.toggle();
        }
    });

    /**
     * Drawer Form
     */
    const drawerFormModal = ref(null);

    const { mutate: openDrawer, loading: isSubmittingForm } = useMutation(OPEN_DRAWER);

    const submitDrawerForm = (params, { setErrors, resetForm }) => {
        openDrawer({
            input: {
                openingAmount: parseFloat(params.opening_amount),
                remark: params.remark
            }
        }).then(response => {
            const openDrawerResult = response.data.openDrawer;

            if (openDrawerResult?.success) {
                cookies.set('drawer_fetched', true, 'today');

                drawerFormModal.value.toggle();

                resetForm();

                emitter.emit('add_flash', {
                    type: 'success',
                    message: openDrawerResult?.message
                });
            } else {
                if (openDrawerResult?.errors) {
                    setErrors(JSON.parse(openDrawerResult.errors));
                }
            }
        });
    };

    onMounted(() => {
        const message = localStorage.getItem('loginMessage');

        if (message) {
            emitter.emit('add_flash', {
                type: 'success',
                message: message
            });

            localStorage.removeItem('loginMessage');
        }
    });
</script>
