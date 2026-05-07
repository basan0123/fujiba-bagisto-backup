<template>
    <!-- Active Filters Display -->
    <div
        v-if="activeFilters.length > 0"
        class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3"
    >
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                {{ $t('pos.cashier.sale_history.active_filters') }}
            </span>

            <button
                @click="clearAllFilters"
                class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 font-medium"
            >
                {{ $t('pos.cashier.sale_history.clear_all') }}
            </button>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <div
                v-for="filter in activeFilters"
                :key="`${filter.key}-${filter.value}`"
                class="inline-flex items-center bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 rounded-full px-3 py-1 text-sm"
            >
                <span class="font-medium text-gray-700 dark:text-gray-300 mr-1">
                    {{ filter.label }}
                </span>

                <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">
                    {{ filter.operator }}
                </span>

                <span class="text-blue-600 dark:text-blue-400 mr-2">
                    {{ filter.displayValue }}
                </span>

                <button
                    @click="removeFilter(filter.key, filter.value)"
                    class="flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                >
                    <span class="icon-cross text-lg"></span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
    defineProps({
        activeFilters: {
            type: Array,
            required: true,
            default: () => []
        }
    });

    const emit = defineEmits(['clear-all', 'remove-filter']);

    /**
     * Clear all active filters.
     */
    const clearAllFilters = () => {
        emit('clear-all');
    };

    /**
     * Remove a specific filter by key and value.
     */
    const removeFilter = (key, value) => {
        emit('remove-filter', key, value);
    };
</script>
