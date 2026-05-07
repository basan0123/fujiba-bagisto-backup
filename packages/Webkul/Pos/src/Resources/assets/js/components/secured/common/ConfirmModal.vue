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
            <div class="flex min-h-full items-end justify-center p-4">
                <div class="absolute top-1/2 z-[999] w-full max-w-[576px] -translate-y-1/2 rounded-2xl bg-white dark:bg-gray-900 max-sm:w-[96%]">
                    <div class="grid gap-4 p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="icon-warning cursor-pointer text-2xl text-gray-900 dark:text-gray-100"></span>

                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $t('pos.common.confirm_modal.title') }}
                            </p>
                        </div>

                        <p class="pb-2.5 text-base font-normal text-gray-900 dark:text-gray-200">
                            {{ $t('pos.common.confirm_modal.description') }}
                        </p>

                        <div class="flex justify-end gap-6">
                            <button
                                type="button"
                                class="transparent-button w-36 dark:border-gray-400 dark:text-gray-100 dark:hover:bg-gray-700"
                                @click="disagree()"
                            >
                                {{ $t('pos.common.confirm_modal.disagree_btn_title') }}
                            </button>

                            <button
                                type="submit"
                                class="primary-button w-36 dark:bg-blue-600 dark:hover:bg-blue-700 dark:text-white"
                                @click="agree()"
                            >
                                {{ $t('pos.common.confirm_modal.agree_btn_title') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
    import { ref, onMounted, inject, onBeforeUnmount } from 'vue';

    const isOpen = ref(false);
    const agreeCallback = ref(null);
    const disagreeCallback = ref(null);

    const emitter = inject('emitter');

    const open = ({ agree = () => {}, disagree = () => {} }) => {
        isOpen.value = true;
        agreeCallback.value = agree;
        disagreeCallback.value = disagree;
    };

    const disagree = () => {
        isOpen.value = false;

        disagreeCallback.value();
    };

    const agree = () => {
        isOpen.value = false;

        agreeCallback.value();
    };

    onMounted(() => {
        emitter.on('open_confirm_modal', open);
    });

    onBeforeUnmount(() => {
        emitter.off('open_confirm_modal');
    });
</script>
