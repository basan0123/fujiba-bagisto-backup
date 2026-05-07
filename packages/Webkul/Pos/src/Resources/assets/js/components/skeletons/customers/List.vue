<template>
    <div class="grid h-[428px] w-full content-start gap-1 overflow-y-auto overflow-x-hidden">
        <div
            class="mr-4 flex cursor-pointer justify-between rounded-lg p-2.5 hover:bg-gray-100 dark:hover:bg-zinc-800"
            v-for="index in 10"
            :key="index"
        >
            <div class="flex gap-2.5">
                <div class="skeleton h-12 w-12 rounded"></div>

                <div class="grid gap-1">
                    <div class="skeleton h-5 w-24"></div>
                    <div class="skeleton h-5 w-40"></div>
                </div>
            </div>

            <div class="skeleton h-5 w-20"></div>
        </div>
    </div>
</template>
