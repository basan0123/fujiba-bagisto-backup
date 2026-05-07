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
                            class="relative h-[31px] w-[51px] flex-shrink-0 rounded-full transition-colors duration-200 focus:outline-none"
                            :class="toggles[item.key] ? 'bg-[#00B8D9]' : 'bg-[#E5E5EA]'"
                            @click="toggles[item.key] = !toggles[item.key]"
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
                            v-if="item.value"
                            class="mr-2 text-[15px]"
                            :class="item.key === 'training' && trainingActive ? 'text-[#FF9500] font-semibold' : 'text-[#8E8E93]'"
                        >{{ item.valueIsKey ? $t(item.value) : item.value }}</span>

                        <i class="icon-chevron-right text-xl text-[#C7C7CC]"></i>
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
    import { useRouter } from 'vue-router';

    const emitter = inject('emitter');
    import Profile              from '@components/secured/settings/Profile.vue';
    import Sync                 from '@components/secured/settings/Sync.vue';
    import Shortcuts            from '@components/secured/settings/Shortcuts.vue';
    import Regional             from '@components/secured/settings/Regional.vue';
    import Hardware             from '@components/secured/settings/Hardware.vue';
    import ReceiptSettings      from '@components/secured/settings/ReceiptSettings.vue';
    import CustomerDisplaySettings from '@components/secured/settings/CustomerDisplaySettings.vue';
    import Training             from '@components/secured/settings/Training.vue';

    const props = defineProps({
        tab: String
    });

    const router = useRouter();

    /**
     * Toggle state for items with type:'toggle'
     */
    const toggles = reactive({
        auto_change:  false,
        payment_sound: false,
        operation_sound: false,
    });

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
     * Grouped settings list definition
     */
    const settingsGroups = computed(() => [
        {
            titleKey: 'pos.settings.groups.register',
            items: [
                { key: 'profile',    type: 'nav', labelKey: 'pos.settings.groups.items.profile',    path: '/settings/profile' },
                { key: 'sync',       type: 'nav', labelKey: 'pos.settings.groups.items.sync',       path: '/settings/sync' },
                { key: 'regional',   type: 'nav', labelKey: 'pos.settings.groups.items.regional',   path: '/settings/regional' },
                { key: 'shortcuts',  type: 'nav', labelKey: 'pos.settings.groups.items.shortcuts',  path: '/settings/shortcuts' },
                { key: 'hardware',   type: 'nav', labelKey: 'pos.settings.groups.items.hardware',   path: '/settings/hardware' },
                { key: 'display',    type: 'nav', labelKey: 'pos.settings.groups.items.display',    path: '/settings/display' },
                { key: 'receipt',    type: 'nav', labelKey: 'pos.settings.groups.items.receipt',    path: '/settings/receipt' },
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
                { key: 'auto_change', type: 'toggle', labelKey: 'pos.settings.groups.items.auto_change' },
                { key: 'payment_sound', type: 'toggle', labelKey: 'pos.settings.groups.items.payment_sound' },
                { key: 'operation_sound', type: 'toggle', labelKey: 'pos.settings.groups.items.operation_sound' },
            ],
        },
        {
            titleKey: 'pos.settings.groups.language_currency',
            items: [
                { key: 'language',  type: 'nav', labelKey: 'pos.settings.groups.items.language',  path: '/settings/regional' },
                { key: 'currency',  type: 'nav', labelKey: 'pos.settings.groups.items.currency',  path: '/settings/regional' },
            ],
        },
        {
            titleKey: 'pos.settings.groups.hardware',
            items: [
                { key: 'printer',  type: 'nav', labelKey: 'pos.settings.groups.items.printer',  path: '/settings/hardware' },
                { key: 'scanner',  type: 'nav', labelKey: 'pos.settings.groups.items.scanner',  path: '/settings/hardware' },
            ],
        },
        {
            titleKey: 'pos.settings.groups.other',
            items: [
                { key: 'version', type: 'value', labelKey: 'pos.settings.groups.items.version', value: '2.3.0' },
            ],
        },
    ]);

    function navigate(item) {
        if (item.path) {
            router.push(item.path);
        }
    }

    onMounted(() => {
        refreshTrainingBadge();
        emitter.on('agent_training_changed', refreshTrainingBadge);
    });

    onBeforeUnmount(() => {
        emitter.off('agent_training_changed', refreshTrainingBadge);
    });
</script>
