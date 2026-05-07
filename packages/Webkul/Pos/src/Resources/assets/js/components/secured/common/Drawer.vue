<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 transform ltr:translate-x-full rtl:-translate-x-full"
        enter-to-class="opacity-100 transform translate-x-0"
        leave-active-class="transition ease-in duration-300"
        leave-from-class="opacity-100 transform translate-x-0"
        leave-to-class="opacity-0 transform ltr:translate-x-full rtl:-translate-x-full"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 z-[10001] overflow-y-auto bg-gray-500 bg-opacity-50"
        >
            <!-- Drawer Content -->
            <div
                class="fixed bottom-0 top-0 z-[10002] w-full bg-white shadow-[1px_0px_0px_0px_rgba(0,0,0,0.1)_inset] dark:bg-gray-900 lg:w-[430px] ltr:right-0 rtl:left-0"
            >
                <div class="pointer-events-auto h-full w-full flex flex-col">
                    <!-- Header (fixed at top) -->
                    <div class="flex-shrink-0 flex items-center gap-2.5 shadow-[0px_-1px_0px_0px_rgba(0,0,0,0.1)_inset] dark:shadow-[0px_-1px_0px_0px_rgba(255,255,255,0.1)_inset]" style="min-height:5rem;">
                        <div class="flex-grow">
                            <slot name="header"></slot>
                        </div>

                        <div
                            class="mr-2 flex h-12 min-w-12 cursor-pointer items-center justify-center rounded-md bg-gray-100 dark:bg-gray-800"
                            @click="close"
                        >
                            <span class="icon-cross text-2xl text-gray-900 dark:text-white"></span>
                        </div>
                    </div>

                    <!-- Content (scrollable middle) -->
                    <div class="flex-1 overflow-y-auto min-h-0">
                        <slot name="content"></slot>
                    </div>

                    <!-- Footer (always visible, pinned at bottom) -->
                    <div class="flex-shrink-0 w-full bg-zinc-50 dark:bg-gray-800 p-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom, 1rem));">
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
    import { ref, watch } from 'vue';

    /**
     * To check if the drawer is open or not.
     */
    const isOpen = ref(false);

    /**
     * To open the drawer.
     */
    const open = () => {
        isOpen.value = true;
    };

    /**
     * To close the drawer.
     */
    const close = () => {
        isOpen.value = false;
    };

    /**
     * To toggle the drawer.
     */
    const toggle = () => {
        isOpen.value = !isOpen.value;
    };

    /**
     * To prevent the body from scrolling when the drawer is open.
     */
    watch(isOpen, (value) => {
        if (value) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
        }
    });

    /**
     * Expose the methods to the parent component.
     */
    defineExpose({
        open,
        close,
        toggle,
    });
</script>
