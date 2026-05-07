<template>
    <div>
        <!-- Desktop Shimmer -->
        <div class="hidden md:block overflow-x-auto">
            <!-- Header Skeleton -->
            <div class="grid grid-cols-4 gap-4 text-gray-700 dark:text-gray-300 font-semibold border-b border-gray-300 dark:border-gray-600 pb-3 mb-3">
                <div class="skeleton h-6 w-20"></div>
                <div class="skeleton h-6 w-16"></div>
                <div class="skeleton h-6 w-24"></div>
                <div class="skeleton h-6 w-28"></div>
            </div>

            <!-- Data Rows Skeleton -->
            <div
                v-for="index in 5"
                :key="index"
                :class="[
                    'grid grid-cols-4 gap-4 py-3 px-2 rounded-md transition-colors',
                    index % 2 === 0 ? 'bg-gray-100 dark:bg-gray-800/50' : ''
                ]"
            >
                <div class="skeleton h-5 w-12"></div>
                <div class="skeleton h-5 w-32"></div>
                <div class="skeleton h-5 w-16"></div>
                <div class="skeleton h-5 w-20"></div>
            </div>
        </div>

        <!-- Mobile Shimmer -->
        <div class="md:hidden space-y-3">
            <div
                v-for="index in 5"
                :key="index"
                class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
            >
                <!-- Date Header Skeleton -->
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
                    <div class="skeleton h-5 w-28"></div>
                    <div class="skeleton h-4 w-8"></div>
                </div>

                <!-- Payment Details Grid Skeleton -->
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <div class="skeleton h-4 w-24 mb-1"></div>
                        <div class="skeleton h-5 w-16"></div>
                    </div>

                    <div>
                        <div class="skeleton h-4 w-28 mb-1"></div>
                        <div class="skeleton h-5 w-20"></div>
                    </div>
                </div>

                <!-- Total Sale Skeleton -->
                <div class="mb-3">
                    <div class="skeleton h-4 w-20 mb-1"></div>
                    <div class="skeleton h-6 w-36"></div>
                </div>
            </div>
        </div>
    </div>
</template>
