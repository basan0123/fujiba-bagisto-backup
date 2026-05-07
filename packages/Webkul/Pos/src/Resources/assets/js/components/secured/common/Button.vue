<template>
    <button
        :type="type"
        :class="buttonClasses"
        :aria-busy="isLoading"
        :disabled="isLoading"
        :aria-label="label"
    >
        <span class="flex items-center gap-2">
            <span
                v-if="isLoading"
                class="inline-block h-5 w-5 animate-spin rounded-full border-4 border-solid border-r-transparent border-current motion-reduce:animate-[spin_1.5s_linear_infinite]"
            >
            </span>

            <span
                v-else-if="icon"
                :class="icon"
                class="text-2xl"
            >
            </span>

            <span>{{ label }}</span>
        </span>
    </button>
</template>

<script setup>
    import { computed } from 'vue';

    const props = defineProps({
        isLoading: {
            type: Boolean,
            default: false,
        },
        type: {
            type: String,
            default: 'button',
        },
        label: {
            type: String,
            default: '',
        },
        class: {
            type: String,
            default: '',
        },
        icon: {
            type: String,
            default: '',
        },
    });

    const buttonClasses = computed(() =>
        [
            'inline-flex items-center justify-center px-4 py-2 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed',
            props.class,
        ].join(' ')
    );
</script>