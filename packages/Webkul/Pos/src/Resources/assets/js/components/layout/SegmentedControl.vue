<template>
    <div class="pos-segment">
        <button
            v-for="(segment, i) in segments"
            :key="segment.key ?? i"
            type="button"
            class="pos-segment__item"
            :class="modelValue === segment.key
                ? 'pos-segment__item--active'
                : 'pos-segment__item--inactive'"
            @click="$emit('update:modelValue', segment.key)"
        >
            <span
                v-if="segment.icon"
                class="mr-1"
            >{{ segment.icon }}</span>

            {{ segment.label }}
        </button>
    </div>
</template>

<script setup>
    defineProps({
        modelValue: {
            type: String,
            required: true,
        },
        /**
         * Array of segment descriptors:
         *   { key: String, label: String, icon?: String (emoji or text) }
         */
        segments: {
            type: Array,
            required: true,
        },
    });

    defineEmits(['update:modelValue']);
</script>
