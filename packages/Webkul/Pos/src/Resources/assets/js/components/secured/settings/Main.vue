<template>
    <!-- No tab selected → show Air Regi grouped navigation list -->
    <div
        v-if="! tab"
        class="min-h-screen bg-[#F2F2F7] pb-6"
    >
        <template
            v-for="group in settingsGroups"
            :key="group.titleKey"
        >
            <p class="pos-section-header">{{ $t(group.titleKey) }}</p>

            <div class="mx-4 mb-2 overflow-hidden rounded-xl bg-white">
                <template
                    v-for="(item, idx) in group.items"
                    :key="item.key"
                >
                    <!-- Toggle row -->
                    <div
                        v-if="item.type === 'toggle'"
                        class="flex min-h-[44px] items-center px-4 py-3"
                        :class="idx < group.items.length - 1 ? 'border-b border-[#E5E5EA]' : ''"
                    >
                        <span class="flex-1 text-[17px] text-[#1C1C1E]">{{ $t(item.labelKey) }}</span>

                        <!-- iOS-style toggle -->
                        <button
                            type="button"
                            class="relative h-[31px] w-[51px] flex-shrink-0 rounded-full transition-colors duration-200 focus:outline-none disabled:opacity-50"
                            :class="toggles[item.key] ? 'bg-[#00B8D9]' : 'bg-[#E5E5EA]'"
                            :disabled="togglesSaving"
                            @click="onToggle(item.key)"
                        >
                            <span
                                class="absolute left-0.5 top-0.5 h-7 w-7 rounded-full bg-white shadow-md transition-transform duration-200"
                                :class="toggles[item.key] ? 'translate-x-5' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>

                    <!-- Value-only row -->
                    <div
                        v-else-if="item.type === 'value'"
                        class="flex min-h-[44px] items-center px-4 py-3"
                        :class="idx < group.items.length - 1 ? 'border-b border-[#E5E5EA]' : ''"
                    >
                        <span class="flex-1 text-[17px] text-[#1C1C1E]">{{ $t(item.labelKey) }}</span>
                        <span class="text-[15px] text-[#8E8E93]">{{ item.value }}</span>
                    </div>

                    <!-- Navigation row -->
                    <button
                        v-else
                        type="button"
                        class="flex w-full min-h-[44px] items-center px-4 py-3 text-left active:bg-[#F2F2F7] transition-colors"
                        :class="idx < group.items.length - 1 ? 'border-b border-[#E5E5EA]' : ''"
                        @click="navigate(item)"
                    >
                        <span class="flex-1 text-[17px] text-[#1C1C1E]">{{ $t(item.labelKey) }}</span>

                        <span
                            v-if="item.comingSoon"
                            class="mr-2 rounded-full bg-[#FFE9B0] px-2 py-0.5 text-[11px] font-medium text-[#8A6100]"
                        >{{ $t('pos.settings.coming_soon') }}</span>

                        <span
                            v-else-if="item.value"
                            class="mr-2 text-[15px]"
                            :class="item.key === 'training' && trainingActive ? 'text-[#FF9500] font-semibold' : 'text-[#8E8E93]'"
                        >{{ item.valueIsKey ? $t(item.value) : item.value }}</span>

                        <i
                            v-if="item.external"
                            class="icon-external-link text-xl text-[#C7C7CC]"
                        ></i>
                        <i
                            v-else
                            class="icon-chevron-right text-xl text-[#C7C7CC]"
                        ></i>
                    </button>
                </template>
            </div>
        </template>
    </div>

    <!-- Tab selected → render the sub-component -->
    <component
        v-else
        :is="activeComponent"
    />
</template>

<script setup>
    import { computed, reactive, ref, inject, onMounted, onBeforeUnmount } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { useHardwareDevices } from '@src/composable/useHardwareDevices';
    import pkg from '../../../../../../../package.json';

    const emitter = inject('emitter');
    import Profile              from '@components/secured/settings/Profile.vue';
    import Sync                 from '@components/secured/settings/Sync.vue';
    import Shortcuts            from '@components/secured/settings/Shortcuts.vue';
    import Regional             from '@components/secured/settings/Regional.vue';
    import Hardware             from '@components/secured/settings/Hardware.vue';
    import ReceiptSettings      from '@components/secured/settings/ReceiptSettings.vue';
    import CustomerDisplaySettings from '@components/secured/settings/CustomerDisplaySettings.vue';
    import Training             from '@components/secured/settings/Training.vue';
    import Sounds               from '@components/secured/settings/Sounds.vue';
    import StubPage             from '@components/secured/settings/StubPage.vue';

    const props = defineProps({
        tab: String
    });

    const router = useRouter();
    const { t }  = useI18n();

    /**
     * Toggle state for items with type:'toggle'
     * Persisted to AgentHardwareSetting.extra (JSON column) via the
     * existing /api/v1/pos/agent/settings endpoint.
     */
    const toggles = reactive({
        auto_change:     false,
        payment_sound:   false,
        operation_sound: false,
    });

    const togglesSaving = ref(false);
    const { loadSettings, saveSettings } = useHardwareDevices();

    /**
     * Map tab key → component
     */
    const componentMap = {
        profile:  Profile,
        sync:     Sync,
        shortcuts: Shortcuts,
        regional: Regional,
        hardware: Hardware,
        receipt:  ReceiptSettings,
        display:  CustomerDisplaySettings,
        training: Training,
        sounds:   Sounds,
        // Stubs share one component; the tab key drives the title.
        basic:    StubPage,
        tax:      StubPage,
        timezones: StubPage,
        service_charge: StubPage,
        discount: StubPage,
        payment:  StubPage,
        cash_drawer: StubPage,
        x_z_report: StubPage,
        restrictions: StubPage,
        faq:      StubPage,
        contact:  StubPage,
        feedback: StubPage,
        peripherals: StubPage,
        terms:    StubPage,
        privacy:  StubPage,
        account_deletion: StubPage,
    };

    const activeComponent = computed(() => componentMap[props.tab] ?? null);

    /**
     * Reactive snapshot of the agent's training_mode for the badge below.
     * Updated on mount + via the global agent_training_changed event.
     */
    const trainingActive = ref(false);

    const refreshTrainingBadge = async () => {
        const { useIndexedDB } = await import('@src/composable/indexed-db');
        const agent = await useIndexedDB().getAgent();
        trainingActive.value = !! agent?.trainingMode;
    };

    /**
     * Grouped settings list — matches the Air Regi / Fujiba menu spec.
     * Items with `comingSoon: true` route to the stub page placeholder.
     */
    const settingsGroups = computed(() => [
        {
            titleKey: 'pos.settings.groups.register',
            items: [
                { key: 'basic',          type: 'nav', labelKey: 'pos.settings.groups.items.basic',          path: '/settings/basic',          comingSoon: true },
                { key: 'tax',            type: 'nav', labelKey: 'pos.settings.groups.items.tax',            path: '/settings/tax',            comingSoon: true },
                { key: 'timezones',      type: 'nav', labelKey: 'pos.settings.groups.items.timezones',      path: '/settings/timezones',      comingSoon: true },
                { key: 'service_charge', type: 'nav', labelKey: 'pos.settings.groups.items.service_charge', path: '/settings/service-charge', comingSoon: true },
                { key: 'discount',       type: 'nav', labelKey: 'pos.settings.groups.items.discount',       path: '/settings/discount',       comingSoon: true },
                { key: 'payment',        type: 'nav', labelKey: 'pos.settings.groups.items.payment',        path: '/settings/payment-methods', comingSoon: true },
                { key: 'hardware',       type: 'nav', labelKey: 'pos.settings.groups.items.hardware',       path: '/settings/hardware' },
                { key: 'display',        type: 'nav', labelKey: 'pos.settings.groups.items.display',        path: '/settings/display' },
                { key: 'scanner',        type: 'nav', labelKey: 'pos.settings.groups.items.scanner',        path: '/settings/hardware' },
                { key: 'auto_change',    type: 'toggle', labelKey: 'pos.settings.groups.items.auto_change' },
            ],
        },
        {
            titleKey: 'pos.settings.groups.receipt',
            items: [
                { key: 'receipt',    type: 'nav', labelKey: 'pos.settings.groups.items.receipt',    path: '/settings/receipt' },
                { key: 'x_z_report', type: 'nav', labelKey: 'pos.settings.groups.items.x_z_report', path: '/settings/x-z-report', comingSoon: true },
            ],
        },
        {
            titleKey: 'pos.settings.groups.security',
            items: [
                { key: 'restrictions', type: 'nav', labelKey: 'pos.settings.groups.items.restrictions', path: '/settings/restrictions', comingSoon: true },
                { key: 'sounds',       type: 'nav', labelKey: 'pos.settings.groups.items.sounds',       path: '/settings/sounds' },
                { key: 'payment_sound',   type: 'toggle', labelKey: 'pos.settings.groups.items.payment_sound' },
                { key: 'operation_sound', type: 'toggle', labelKey: 'pos.settings.groups.items.operation_sound' },
            ],
        },
        {
            titleKey: 'pos.settings.groups.account',
            items: [
                { key: 'profile',  type: 'nav', labelKey: 'pos.settings.groups.items.profile',  path: '/settings/profile' },
                { key: 'sync',     type: 'nav', labelKey: 'pos.settings.groups.items.sync',     path: '/settings/sync' },
                { key: 'regional', type: 'nav', labelKey: 'pos.settings.groups.items.regional', path: '/settings/regional' },
                { key: 'shortcuts', type: 'nav', labelKey: 'pos.settings.groups.items.shortcuts', path: '/settings/shortcuts' },
                {
                    key: 'training',
                    type: 'nav',
                    labelKey: 'pos.settings.groups.items.training',
                    path: '/settings/training',
                    value: trainingActive.value
                        ? 'pos.settings.groups.items.training_value_on'
                        : 'pos.settings.groups.items.training_value_off',
                    valueIsKey: true,
                },
            ],
        },
        {
            titleKey: 'pos.settings.groups.support',
            items: [
                { key: 'faq',         type: 'nav', labelKey: 'pos.settings.groups.items.faq',         path: '/settings/faq',         comingSoon: true },
                { key: 'contact',     type: 'nav', labelKey: 'pos.settings.groups.items.contact',     path: '/settings/contact',     comingSoon: true },
                { key: 'feedback',    type: 'nav', labelKey: 'pos.settings.groups.items.feedback',    path: '/settings/feedback',    comingSoon: true },
                { key: 'peripherals', type: 'nav', labelKey: 'pos.settings.groups.items.peripherals', path: '/settings/peripherals', comingSoon: true },
            ],
        },
        {
            titleKey: 'pos.settings.groups.legal',
            items: [
                { key: 'terms',            type: 'nav', labelKey: 'pos.settings.groups.items.terms',            path: '/settings/terms',            comingSoon: true },
                { key: 'privacy',          type: 'nav', labelKey: 'pos.settings.groups.items.privacy',          path: '/settings/privacy',          comingSoon: true },
                { key: 'version',          type: 'value', labelKey: 'pos.settings.groups.items.version',        value: pkg.version },
                { key: 'account_deletion', type: 'nav', labelKey: 'pos.settings.groups.items.account_deletion', path: '/settings/account-deletion', comingSoon: true },
            ],
        },
    ]);

    function navigate(item) {
        if (item.path) {
            router.push(item.path);
        }
    }

    /**
     * Persist toggle state to AgentHardwareSetting.extra.
     * Optimistic update + rollback on failure.
     */
    async function onToggle(key) {
        if (togglesSaving.value) return;

        const previous = toggles[key];
        toggles[key]   = ! previous;
        togglesSaving.value = true;

        try {
            const result = await saveSettings({
                extra: {
                    auto_change:     toggles.auto_change,
                    payment_sound:   toggles.payment_sound,
                    operation_sound: toggles.operation_sound,
                },
            });

            if (! result) {
                throw new Error('save failed');
            }

            // Mirror the change to IndexedDB so client-side checks
            // (payment confirm, button click) don't need an extra round trip.
            const { useIndexedDB } = await import('@src/composable/indexed-db');
            await useIndexedDB().updateItem('agent_hardware_settings', {
                id:    1,
                extra: result.extra ?? {},
            });
        } catch {
            toggles[key] = previous;
            emitter.emit('add_flash', {
                type:    'error',
                message: t('pos.settings.save_error_generic'),
            });
        } finally {
            togglesSaving.value = false;
        }
    }

    onMounted(async () => {
        refreshTrainingBadge();
        emitter.on('agent_training_changed', refreshTrainingBadge);

        // Hydrate toggle state from the server.
        try {
            const settings = await loadSettings();
            const extra    = settings?.extra ?? {};
            toggles.auto_change     = !! extra.auto_change;
            toggles.payment_sound   = !! extra.payment_sound;
            toggles.operation_sound = !! extra.operation_sound;
        } catch {
            // Non-fatal — defaults already applied.
        }
    });

    onBeforeUnmount(() => {
        emitter.off('agent_training_changed', refreshTrainingBadge);
    });
</script>
