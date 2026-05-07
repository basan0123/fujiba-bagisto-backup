<template>
    <div class="grid gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
        <div
            class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-4"
            v-for="(index) in 6"
            :key="index"
        >
            <div class="flex justify-between">
                <div class="grid gap-1.5">
                    <div class="skeleton h-6 w-44"></div>

                    <div class="skeleton h-9 w-16"></div>
                </div>

                <div class="skeleton flex h-10 w-24 items-center self-end rounded-lg"></div>
            </div>

            <div class="grid gap-[22px] px-4 py-6">
                <div class="flex items-end gap-2">
                    <div class="skeleton h-2 w-5"></div>
                    <div class="skeleton h-0 w-full border border-dashed"></div>
                </div>

                <div class="flex items-end gap-2">
                    <div class="skeleton h-2 w-5"></div>
                    <div class="skeleton h-0 w-full border border-dashed"></div>
                </div>

                <div class="flex items-end gap-2">
                    <div class="skeleton h-2 w-5"></div>
                    <div class="skeleton h-0 w-full border border-dashed"></div>
                </div>

                <div class="flex items-end gap-2">
                    <div class="skeleton h-2 w-5"></div>
                    <div class="skeleton h-0 w-full border border-dashed"></div>
                </div>

                <div class="flex items-end gap-2">
                    <div class="skeleton h-2 w-5"></div>
                    <div class="skeleton h-0 w-full border border-dashed"></div>
                </div>

                <div class="flex items-end gap-2">
                    <div class="skeleton h-2 w-5"></div>
                    <div class="skeleton h-0 w-full border border-dashed"></div>
                </div>
            </div>

            <div class="mb-[18px] ml-10 flex gap-4">
                <div class="skeleton h-2 w-7"></div>
                <div class="skeleton h-2 w-7"></div>
                <div class="skeleton h-2 w-7"></div>
                <div class="skeleton h-2 w-7"></div>
                <div class="skeleton h-2 w-7"></div>
                <div class="skeleton h-2 w-7"></div>
                <div class="skeleton h-2 w-7"></div>
            </div>
        </div>
    </div>
</template>