<template>
    <div class="flex-1 flex flex-col select-none">
        <!-- Tab bar — horizontally scrollable on mobile, no wrap -->
        <div class="flex-shrink-0 flex overflow-x-auto bg-white dark:bg-gray-900 border-b border-[#E5E5EA] dark:border-gray-700 px-2"
             style="scrollbar-width:none; -webkit-overflow-scrolling:touch;">
            <button
                v-for="(tab, index) in tabs"
                :key="index"
                type="button"
                class="flex-shrink-0 px-3 py-3 text-[13px] font-medium whitespace-nowrap border-b-2 transition-colors"
                :class="currentTab === tab.key
                    ? 'border-[#00B8D9] text-[#00B8D9]'
                    : 'border-transparent text-[#8E8E93]'"
                @click="goToTab(tab.key)"
            >
                {{ $t(tab.titleKey) }}
            </button>
        </div>

        <!-- Active Tab Content — scrollable -->
        <div class="flex-1 overflow-y-auto min-h-0">
            <component
                :is="activeComponent"
                :key="currentTab"
                v-if="activeComponent"
            />
        </div>
    </div>
</template>

<script setup>
    import { computed } from 'vue'
    import { useRouter } from 'vue-router'

    const props = defineProps({
        current: {
            type: String,
            required: true,
        },
        tabs: {
            type: Array,
            required: true,
        },
        routeBase: {
            type: String,
            required: true,
        },
    });

    const router = useRouter();

    const tabMap = computed(() => {
        const map = {};

        props.tabs.forEach(tab => {
            map[tab.key] = tab.component;
        });

        return map;
    })

    const currentTab = computed(() =>
        props.tabs.find(tab => tab.key === props.current)?.key || props.tabs[0].key
    );

    const activeComponent = computed(() => tabMap.value[currentTab.value]);

    const goToTab = (key) => {
        if (key !== currentTab.value) {
            router.push(`${props.routeBase}/${key}`)
        }
    }
</script>
