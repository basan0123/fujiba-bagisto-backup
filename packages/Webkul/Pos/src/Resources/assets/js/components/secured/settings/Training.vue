<template>
    <div class="min-h-screen bg-[#F2F2F7] pb-6">
        <!-- Live banner preview at the top so manager sees what cashiers will see -->
        <div
            v-if="form.trainingMode"
            class="m-4 rounded-xl bg-[#FF9500] px-4 py-3 text-center text-[14px] font-semibold uppercase tracking-wide text-white shadow"
        >
            {{ $t('pos.settings.training.banner') }}
        </div>

        <p class="pos-section-header">{{ $t('pos.settings.training.title') }}</p>

        <div class="mx-4 mb-2 overflow-hidden rounded-xl bg-white">
            <!-- Intro paragraph -->
            <div class="border-b border-[#E5E5EA] px-4 py-3 text-[13px] leading-snug text-[#6C6C70]">
                {{ $t('pos.settings.training.intro') }}
            </div>

            <!-- Training Mode toggle -->
            <div class="flex min-h-[44px] items-center border-b border-[#E5E5EA] px-4 py-3">
                <span class="flex-1 text-[17px] text-[#1C1C1E]">
                    {{ $t('pos.settings.training.toggle_label') }}
                </span>

                <button
                    type="button"
                    class="relative h-[31px] w-[51px] flex-shrink-0 rounded-full transition-colors duration-200 focus:outline-none"
                    :class="form.trainingMode ? 'bg-[#00B8D9]' : 'bg-[#E5E5EA]'"
                    @click="form.trainingMode = !form.trainingMode"
                >
                    <span
                        class="absolute left-0.5 top-0.5 h-7 w-7 rounded-full bg-white shadow-md transition-transform duration-200"
                        :class="form.trainingMode ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                </button>
            </div>

            <!-- Print receipt sub-toggle (only meaningful when training is on) -->
            <div
                class="flex min-h-[44px] items-center px-4 py-3"
                :class="form.trainingMode ? '' : 'opacity-50'"
            >
                <div class="flex-1 pr-3">
                    <p class="text-[17px] text-[#1C1C1E]">
                        {{ $t('pos.settings.training.print_label') }}
                    </p>
                    <p class="mt-0.5 text-[12px] leading-snug text-[#8E8E93]">
                        {{ $t('pos.settings.training.print_helper') }}
                    </p>
                </div>

                <button
                    type="button"
                    class="relative h-[31px] w-[51px] flex-shrink-0 rounded-full transition-colors duration-200 focus:outline-none"
                    :class="form.trainingPrintReceipt ? 'bg-[#00B8D9]' : 'bg-[#E5E5EA]'"
                    :disabled="!form.trainingMode"
                    @click="form.trainingPrintReceipt = !form.trainingPrintReceipt"
                >
                    <span
                        class="absolute left-0.5 top-0.5 h-7 w-7 rounded-full bg-white shadow-md transition-transform duration-200"
                        :class="form.trainingPrintReceipt ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                </button>
            </div>
        </div>

        <div class="mx-4 mt-4">
            <button
                type="button"
                class="w-full rounded-xl bg-[#00B8D9] py-3 text-[15px] font-semibold text-white active:opacity-70 disabled:opacity-50"
                :disabled="isSaving || !isDirty"
                @click="save"
            >
                <span v-if="isSaving" class="icon-spinner mr-1.5 animate-spin text-sm"></span>
                {{ $t('pos.settings.training.save_btn') }}
            </button>
        </div>
    </div>
</template>

<script setup>
    import { reactive, ref, computed, inject, onMounted } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useMutation } from '@vue/apollo-composable';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { SET_AGENT_TRAINING } from '@src/graphql/session';

    const { t }    = useI18n();
    const emitter  = inject('emitter');
    const isOnline = inject('isOnline');
    const DB       = useIndexedDB();

    /**
     * form: edited values (two-way bound to toggles).
     * snapshot: last-saved values (used for isDirty + revert on error).
     */
    const form = reactive({
        trainingMode:         false,
        trainingPrintReceipt: false,
    });

    const snapshot = reactive({
        trainingMode:         false,
        trainingPrintReceipt: false,
    });

    const isSaving = ref(false);

    const isDirty = computed(() =>
        form.trainingMode         !== snapshot.trainingMode ||
        form.trainingPrintReceipt !== snapshot.trainingPrintReceipt
    );

    onMounted(async () => {
        const agent = await DB.getAgent();

        form.trainingMode             = !! agent?.trainingMode;
        form.trainingPrintReceipt     = !! agent?.trainingPrintReceipt;
        snapshot.trainingMode         = form.trainingMode;
        snapshot.trainingPrintReceipt = form.trainingPrintReceipt;
    });

    const { mutate: setTraining } = useMutation(SET_AGENT_TRAINING);

    const save = async () => {
        if (! isOnline?.value) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.common.flash_messages.offline_error'),
            });
            return;
        }

        isSaving.value = true;

        try {
            const response = await setTraining({
                input: {
                    trainingMode:         form.trainingMode,
                    trainingPrintReceipt: form.trainingPrintReceipt,
                },
            });

            const result = response?.data?.setAgentTraining;

            if (result?.success) {
                snapshot.trainingMode         = form.trainingMode;
                snapshot.trainingPrintReceipt = form.trainingPrintReceipt;

                // Patch the agent record in IDB so the banner / Payment.vue read
                // current state without a round-trip.
                const agent = await DB.getAgent();

                if (agent?.id) {
                    await DB.updateItem('agent', {
                        ...agent,
                        trainingMode:         form.trainingMode,
                        trainingPrintReceipt: form.trainingPrintReceipt,
                    });
                }

                emitter.emit('agent_training_changed', {
                    trainingMode:         form.trainingMode,
                    trainingPrintReceipt: form.trainingPrintReceipt,
                });

                emitter.emit('add_flash', {
                    type: 'success',
                    message: t('pos.settings.training.save_success'),
                });
            } else {
                emitter.emit('add_flash', {
                    type: 'error',
                    message: result?.message || t('pos.settings.training.save_error'),
                });
            }
        } catch (err) {
            emitter.emit('add_flash', {
                type: 'error',
                message: err?.message || t('pos.settings.training.save_error'),
            });
        } finally {
            isSaving.value = false;
        }
    };
</script>
