<template>
    <v-error-message
        :name="name"
        class="text-sm text-red-500"
    />
</template>

<script setup>
    defineProps({
        name: String,
    });
</script>
