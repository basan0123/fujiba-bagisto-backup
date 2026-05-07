<template>
    <div>
        <!-- Desktop Skeleton -->
        <div class="box-shadow flex gap-6 rounded-lg bg-white dark:bg-gray-900 p-4 max-sm:hidden">
            <div class="skeleton min-h-40 min-w-40"></div>

            <div class="grid w-full">
                <div class="grid content-start gap-1">
                    <div class="flex items-end gap-1">
                        <div class="skeleton h-10 w-60"></div>
                        <div class="skeleton h-6 w-12"></div>
                    </div>

                    <div class="grid gap-0.5">
                        <div class="skeleton h-5 w-48"></div>
                        <div class="skeleton h-5 w-28"></div>
                    </div>
                </div>

                <div class="flex items-end justify-between">
                    <div class="flex gap-6 text-gray-900 dark:text-gray-200">
                        <div class="skeleton h-6 w-16"></div>
                        <div class="skeleton h-6 w-20"></div>
                    </div>

                    <div class="skeleton h-12 w-[88px]"></div>
                </div>
            </div>
        </div>

        <!-- Mobile Skeleton -->
        <div class="box-shadow flex flex-col gap-5 rounded-lg bg-white dark:bg-gray-900 p-4 md:hidden">
            <div class="flex gap-2.5">
                <div class="skeleton min-h-20 min-w-20"></div>

                <div class="grid content-start gap-1">
                    <div class="flex items-end gap-1">
                        <div class="skeleton h-7 w-40"></div>
                        <div class="skeleton h-6 w-12"></div>
                    </div>

                    <div class="grid gap-0.5">
                        <div class="skeleton h-5 w-48"></div>
                        <div class="skeleton h-5 w-28"></div>
                    </div>
                </div>
            </div>

            <div class="flex items-end justify-between">
                <div class="skeleton h-6 w-16"></div>
                <div class="skeleton h-6 w-20"></div>
                <div class="skeleton h-6 w-[92px]"></div>
            </div>
        </div>
    </div>
</template>
