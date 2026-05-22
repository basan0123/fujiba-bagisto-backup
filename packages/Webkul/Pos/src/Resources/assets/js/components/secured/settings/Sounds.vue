<template>
    <div class="min-h-screen bg-[#F2F2F7] pb-6">
        <p class="pos-section-header">{{ $t('pos.settings.sounds.section_basic') }}</p>

        <div class="mx-4 mb-2 overflow-hidden rounded-xl bg-white">
            <div class="flex min-h-[44px] items-center border-b border-[#E5E5EA] px-4 py-3">
                <div class="flex-1">
                    <p class="text-[17px] text-[#1C1C1E]">{{ $t('pos.settings.groups.items.payment_sound') }}</p>
                    <p class="mt-0.5 text-[13px] text-[#8E8E93]">{{ $t('pos.settings.sounds.payment_hint') }}</p>
                </div>

                <button
                    type="button"
                    class="relative h-[31px] w-[51px] flex-shrink-0 rounded-full transition-colors duration-200 focus:outline-none disabled:opacity-50"
                    :class="form.payment_sound ? 'bg-[#00B8D9]' : 'bg-[#E5E5EA]'"
                    :disabled="saving"
                    @click="toggle('payment_sound')"
                >
                    <span
                        class="absolute left-0.5 top-0.5 h-7 w-7 rounded-full bg-white shadow-md transition-transform duration-200"
                        :class="form.payment_sound ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                </button>
            </div>

            <div class="flex min-h-[44px] items-center px-4 py-3">
                <div class="flex-1">
                    <p class="text-[17px] text-[#1C1C1E]">{{ $t('pos.settings.groups.items.operation_sound') }}</p>
                    <p class="mt-0.5 text-[13px] text-[#8E8E93]">{{ $t('pos.settings.sounds.operation_hint') }}</p>
                </div>

                <button
                    type="button"
                    class="relative h-[31px] w-[51px] flex-shrink-0 rounded-full transition-colors duration-200 focus:outline-none disabled:opacity-50"
                    :class="form.operation_sound ? 'bg-[#00B8D9]' : 'bg-[#E5E5EA]'"
                    :disabled="saving"
                    @click="toggle('operation_sound')"
                >
                    <span
                        class="absolute left-0.5 top-0.5 h-7 w-7 rounded-full bg-white shadow-md transition-transform duration-200"
                        :class="form.operation_sound ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                </button>
            </div>
        </div>

        <p class="px-4 pt-3 text-[12px] text-[#8E8E93]">
            {{ $t('pos.settings.sounds.future_note') }}
        </p>
    </div>
</template>

<script setup>
    import { reactive, ref, inject, onMounted } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useHardwareDevices } from '@src/composable/useHardwareDevices';

    const { t }     = useI18n();
    const emitter   = inject('emitter');
    const { loadSettings, saveSettings } = useHardwareDevices();

    const form = reactive({
        payment_sound:   false,
        operation_sound: false,
    });

    const saving = ref(false);

    async function toggle(key) {
        if (saving.value) return;

        const previous = form[key];
        form[key] = ! previous;
        saving.value = true;

        try {
            const settings = await loadSettings();
            const extra    = { ...(settings?.extra ?? {}) };

            extra.payment_sound   = form.payment_sound;
            extra.operation_sound = form.operation_sound;

            const result = await saveSettings({ extra });

            if (! result) {
                throw new Error('save failed');
            }

            emitter.emit('add_flash', {
                type:    'success',
                message: t('pos.settings.save_success'),
            });
        } catch {
            form[key] = previous;
            emitter.emit('add_flash', {
                type:    'error',
                message: t('pos.settings.save_error_generic'),
            });
        } finally {
            saving.value = false;
        }
    }

    onMounted(async () => {
        try {
            const settings = await loadSettings();
            const extra    = settings?.extra ?? {};
            form.payment_sound   = !! extra.payment_sound;
            form.operation_sound = !! extra.operation_sound;
        } catch {
            // Defaults already applied.
        }
    });
</script>
