<template>
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="
                ! isOnline
                && ['cashier', 'products', 'reports'].includes($route.path.split('/')[1])
            "
            class="fixed inset-0 z-50 flex items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-black/80"
        >
            <div class="flex flex-col items-center gap-4 text-center">
                <!-- Spinner with Icon -->
                <div class="relative">
                    <div
                        class="h-16 w-16 animate-spin rounded-full border-4 border-slate-300 border-t-slate-600 dark:border-slate-700 dark:border-t-white"
                    >
                    </div>
                    
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="icon-network text-2xl text-red-500"></span>
                    </div>
                </div>

                <!-- Message -->
                <div class="flex flex-col gap-1">
                    <p class="text-xl font-medium text-slate-800 dark:text-white">
                        {{ $t('pos.common.offline_loader.title') }}
                    </p>

                    <p class="text-base text-slate-700 dark:text-white">
                        {{ $t('pos.common.offline_loader.description') }}
                    </p>
                    
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        {{ $t('pos.common.offline_loader.reconnecting') }}
                    </p>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
    import { inject } from 'vue';

    /**
     * General variables
     */
    const isOnline = inject('isOnline');
</script>