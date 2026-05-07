<template>
    <div
        v-if="loading"
        class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600"
    >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between my-6 space-y-3 sm:space-y-0">
            <!-- Results Text Skeleton -->
            <div class="text-center sm:text-left">
                <div class="skeleton h-4 w-40 mx-auto sm:mx-0"></div>
            </div>

            <!-- Pagination Controls Skeleton -->
            <div class="flex items-center justify-center space-x-1">
                <!-- Previous Button -->
                <div class="skeleton w-9 h-9 rounded-md"></div>
                
                <!-- Page Info -->
                <div class="skeleton h-9 w-24 rounded-md"></div>
                
                <!-- Next Button -->
                <div class="skeleton w-9 h-9 rounded-md"></div>
            </div>
        </div>
    </div>

    <div
        v-else-if="pageInfo.total > 0"
        class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600"
    >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between my-6 space-y-3 sm:space-y-0">
            <!-- Results Text -->
            <div class="text-sm text-center text-gray-600 dark:text-gray-300">
                {{ $t('pos.common.pagination.showing', {
                    count: pageInfo.count,
                    total: pageInfo.total,
                }) }}
            </div>

            <!-- Pagination Controls -->
            <div class="flex items-center justify-center space-x-1">
                <button
                    class="flex items-center justify-center w-9 h-9 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed dark:text-gray-200 dark:bg-gray-900 dark:border-gray-600 dark:hover:bg-gray-700"
                    :disabled="pageInfo.currentPage === 1"
                    @click="$emit('page-change', pageInfo.currentPage - 1)"
                    aria-label="Previous Page"
                >
                    <span class="icon-chevron-left text-2xl"></span>
                </button>

                <span
                    class="h-9 px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-md dark:text-gray-200 dark:bg-gray-800 dark:border-gray-600 whitespace-nowrap"
                >
                    {{ $t('pos.common.pagination.page', {
                        current: pageInfo.currentPage,
                        total: pageInfo.lastPage,
                    }) }}
                </span>

                <button
                    class="flex items-center justify-center w-9 h-9 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed dark:text-gray-200 dark:bg-gray-900 dark:border-gray-600 dark:hover:bg-gray-700"
                    :disabled="pageInfo.currentPage === pageInfo.lastPage"
                    @click="$emit('page-change', pageInfo.currentPage + 1)"
                    aria-label="Next Page"
                >
                    <span class="icon-chevron-right text-2xl"></span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
    defineProps({
        loading: {
            type: Boolean,
            required: true,
        },
        pageInfo: {
            type: Object,
            required: true,
        },
    });
</script>
