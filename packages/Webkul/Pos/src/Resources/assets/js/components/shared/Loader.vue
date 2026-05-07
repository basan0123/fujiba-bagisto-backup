<template>
    <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isLoading"
            class="fixed inset-0 z-50 flex items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-black/80"
        >
            <div
                class="h-10 w-10 animate-spin rounded-full border-4 border-slate-300 border-t-slate-600 dark:border-slate-700 dark:border-t-white"
            ></div>
        </div>
    </Transition>
</template>

<script setup>
    import useGlobalLoader from '@src/composable/global-loader';

    /**
     * General variables
     */
    const { isLoading } = useGlobalLoader();
</script>