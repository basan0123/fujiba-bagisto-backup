<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="modelValue"
                class="fixed inset-0 flex flex-col justify-end"
                style="z-index: 10020;"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/40"
                    @click="close"
                />

                <!-- Sheet -->
                <Transition
                    enter-active-class="transition-transform ease-out duration-300"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition-transform ease-in duration-200"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                >
                    <div
                        v-if="modelValue"
                        class="relative bg-white rounded-t-2xl overflow-hidden"
                        style="padding-bottom: max(env(safe-area-inset-bottom, 0px), 8px);"
                    >
                        <!-- Pull handle -->
                        <div class="flex justify-center pt-3 pb-1">
                            <div class="h-1 w-10 rounded-full bg-[#D1D1D6]"></div>
                        </div>

                        <!-- Title -->
                        <div
                            v-if="title"
                            class="text-center py-2 px-4 text-[13px] text-[#8E8E93] font-medium border-b border-[#E5E5EA]"
                        >
                            {{ title }}
                        </div>

                        <!-- Slot: custom content -->
                        <slot />

                        <!-- Action items -->
                        <template v-if="items?.length">
                            <button
                                v-for="(item, i) in items"
                                :key="i"
                                type="button"
                                class="block w-full py-4 px-4 text-[17px] font-medium border-b border-[#E5E5EA] text-center transition-colors active:bg-[#F2F2F7]"
                                :class="item.danger ? 'text-[#FF3B30]' : 'text-[#00B8D9]'"
                                @click="handleItem(item)"
                            >
                                {{ item.label }}
                            </button>
                        </template>

                        <!-- Cancel -->
                        <button
                            type="button"
                            class="block w-full py-4 text-[17px] font-medium text-[#8E8E93] text-center mt-2 active:bg-[#F2F2F7] transition-colors"
                            @click="close"
                        >
                            {{ cancelLabel ?? $t('pos.common.cancel') }}
                        </button>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
    const props = defineProps({
        modelValue: {
            type: Boolean,
            required: true,
        },
        title: {
            type: String,
            default: null,
        },
        items: {
            type: Array,
            default: () => [],
            // Each item: { label: String, action?: Function, danger?: Boolean }
        },
        cancelLabel: {
            type: String,
            default: null,
        },
    });

    const emit = defineEmits(['update:modelValue']);

    function close() {
        emit('update:modelValue', false);
    }

    function handleItem(item) {
        item.action?.();
        close();
    }
</script>
