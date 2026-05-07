<template>
    <label
        class="text-base leading-5 text-gray-900 dark:text-white"
        :class="className"
    >
        <slot />
    </label>
</template>

<script setup>
    defineProps({
        className: {
            type: String,
            default: '',
        },
    });
</script>
