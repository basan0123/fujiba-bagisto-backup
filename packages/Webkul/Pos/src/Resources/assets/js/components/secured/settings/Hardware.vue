<template>
    <v-form
        class="box-shadow grid gap-6 rounded-lg bg-white dark:bg-gray-900 p-4"
        @submit="submitForm($event)"
    >
        <!-- Paper Size -->
        <div>
            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ $t('pos.settings.hardware.paper_size') }}
            </p>

            <div class="flex gap-4">
                <button
                    type="button"
                    class="flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors"
                    :class="form.paper_size === '80mm'
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-400'"
                    @click="form.paper_size = '80mm'"
                >
                    <div
                        class="rounded border border-gray-400 bg-white dark:bg-gray-800"
                        style="width: 40px; height: 56px;"
                    ></div>

                    <span class="text-sm font-medium">80mm</span>
                    <span class="text-xs text-gray-500">~48 chars/line</span>
                </button>

                <button
                    type="button"
                    class="flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors"
                    :class="form.paper_size === '58mm'
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-400'"
                    @click="form.paper_size = '58mm'"
                >
                    <div
                        class="rounded border border-gray-400 bg-white dark:bg-gray-800"
                        style="width: 29px; height: 56px;"
                    ></div>

                    <span class="text-sm font-medium">58mm</span>
                    <span class="text-xs text-gray-500">~32 chars/line</span>
                </button>
            </div>
        </div>

        <!-- Default Printer -->
        <ControlGroup>
            <Label for="default_printer">
                {{ $t('pos.settings.hardware.default_printer') }}
            </Label>

            <div class="flex gap-2">
                <Field
                    :type="'select'"
                    :name="'default_printer'"
                    :id="'default_printer'"
                    v-model="form.default_printer"
                    class="flex-1"
                >
                    <option value="">
                        {{ $t('pos.settings.hardware.printer_none') }}
                    </option>

                    <option
                        v-for="printer in printers"
                        :key="printer"
                        :value="printer"
                    >
                        {{ printer }}
                    </option>
                </Field>

                <button
                    type="button"
                    class="secondary-button px-3"
                    :disabled="loadingPrinters"
                    @click="refreshPrinters"
                >
                    <span
                        class="text-xl"
                        :class="loadingPrinters ? 'icon-spinner animate-spin' : 'icon-reload'"
                    ></span>
                </button>
            </div>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t('pos.settings.hardware.printer_hint') }}
            </p>
        </ControlGroup>

        <!-- Default Scanner -->
        <ControlGroup>
            <Label for="default_scanner">
                {{ $t('pos.settings.hardware.default_scanner') }}
            </Label>

            <Field
                :type="'select'"
                :name="'default_scanner'"
                :id="'default_scanner'"
                v-model="form.default_scanner"
            >
                <option value="keyboard-wedge">
                    {{ $t('pos.settings.hardware.scanner_keyboard_wedge') }}
                </option>

                <option value="manual">
                    {{ $t('pos.settings.hardware.scanner_manual') }}
                </option>
            </Field>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t('pos.settings.hardware.scanner_hint') }}
            </p>
        </ControlGroup>

        <!-- Scanner Test -->
        <div
            v-if="form.default_scanner === 'keyboard-wedge'"
            class="rounded-lg border border-dashed border-gray-300 p-4 dark:border-gray-600"
        >
            <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t('pos.settings.hardware.scanner_test_title') }}
            </p>

            <div class="flex items-center gap-2">
                <span
                    class="inline-block h-2.5 w-2.5 rounded-full transition-colors"
                    :class="scannerActive ? 'bg-green-500' : 'bg-gray-300'"
                ></span>

                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ scannerActive
                        ? $t('pos.settings.hardware.scanner_detecting')
                        : $t('pos.settings.hardware.scanner_waiting') }}
                </span>

                <span
                    v-if="lastScanned"
                    class="ml-auto font-mono text-sm text-blue-600 dark:text-blue-400"
                >
                    {{ lastScanned }}
                </span>
            </div>
        </div>

        <button
            type="submit"
            class="primary-button w-full px-7 py-4 md:w-[280px]"
            :disabled="saving"
        >
            <span class="icon-check text-2xl"></span>

            {{ $t('pos.settings.hardware.save_btn_title') }}
        </button>
    </v-form>
</template>

<script setup>
    import { ref, inject, onMounted } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useHardwareDevices } from '@src/composable/useHardwareDevices';
    import { useBarcodeScanner } from '@src/composable/useBarcodeScanner';
    import { useIndexedDB } from '@src/composable/indexed-db';

    const { t }    = useI18n();
    const emitter  = inject('emitter');
    const DB       = useIndexedDB();

    const { printers, loadingPrinters, loadPrinters, loadSettings, saveSettings } = useHardwareDevices();

    const saving     = ref(false);
    const lastScanned = ref('');

    const form = ref({
        paper_size:      '80mm',
        default_printer: '',
        default_scanner: 'keyboard-wedge',
    });

    const { scannerActive } = useBarcodeScanner((barcode) => {
        lastScanned.value = barcode;
    });

    onMounted(async () => {
        // Load current settings from API
        const settings = await loadSettings();

        if (settings) {
            form.value.paper_size      = settings.paper_size      ?? '80mm';
            form.value.default_printer = settings.default_printer ?? '';
            form.value.default_scanner = settings.default_scanner ?? 'keyboard-wedge';
        }

        await loadPrinters();
    });

    async function refreshPrinters() {
        await loadPrinters();
    }

    async function submitForm() {
        saving.value = true;

        try {
            const result = await saveSettings({
                paper_size:      form.value.paper_size,
                default_printer: form.value.default_printer || null,
                default_scanner: form.value.default_scanner || 'keyboard-wedge',
            });

            if (result) {
                // Persist paper_size to IndexedDB so Print.vue can read it offline
                await DB.updateItem('agent_hardware_settings', {
                    id:              1,
                    paper_size:      result.paper_size,
                    default_printer: result.default_printer,
                    default_scanner: result.default_scanner,
                });

                emitter.emit('add_flash', {
                    type:    'success',
                    message: t('pos.settings.save_success'),
                });
            } else {
                emitter.emit('add_flash', {
                    type:    'error',
                    message: t('pos.settings.hardware.save_error'),
                });
            }
        } finally {
            saving.value = false;
        }
    }
</script>
