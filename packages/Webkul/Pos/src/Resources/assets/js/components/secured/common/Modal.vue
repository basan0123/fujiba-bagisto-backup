<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 transform scale-90"
        enter-to-class="opacity-100 transform scale-100"
        leave-active-class="transition ease-in duration-300"
        leave-from-class="opacity-100 transform scale-100"
        leave-to-class="opacity-0 transform scale-90"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 z-[10003] overflow-y-auto bg-gray-500 bg-opacity-50"
        >
            <div class="flex min-h-full items-center justify-center p-2 sm:p-4">
                <div
                    class="relative z-[999] w-full max-w-[576px] max-h-[calc(100vh-1rem)] sm:max-h-[calc(100vh-2rem)] overflow-y-auto rounded-2xl bg-white dark:bg-gray-900 max-sm:w-[96%]"
                >
                    <div class="grid gap-4 p-4 sm:p-6">
                        <p class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white">
                            <slot
                                name="header"
                                :toggle="toggle"
                            />
                        </p>

                        <slot
                            name="content"
                            :toggle="toggle"
                        />
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
    import { ref, watch } from 'vue';

    const isOpen = ref(false);

    const open = () => {
        isOpen.value = true;
    };

    const close = () => {
        isOpen.value = false;
    };

    const toggle = () => {
        isOpen.value = !isOpen.value;
    };

    watch(isOpen, (value) => {
        if (value) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
        }
    });

    defineExpose({
        isOpen,
        open,
        close,
        toggle,
    });
</script>
