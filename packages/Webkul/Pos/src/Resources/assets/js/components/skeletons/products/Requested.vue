<template>
    <div class="grid w-full content-start gap-2">
        <div
            class="grid gap-4 rounded-lg p-2"
            v-for="(index) in 4"
            :class="[index % 2 != 0 ? 'bg-zinc-50 dark:bg-gray-800/50' : '']"
            :key="index"
        >
            <div class="flex justify-between gap-y-2.5 max-sm:flex-col md:items-center">
                <div class="skeleton h-5 w-60"></div>

                <div class="flex justify-between gap-6">
                    <div class="skeleton h-5 w-[66px]"></div>

                    <div class="skeleton h-5 w-[60px]"></div>

                    <div class="skeleton h-5 w-[146px]"></div>
                </div>
            </div>

            <div class="skeleton h-5 w-full"></div>
        </div>
    </div>
</template>