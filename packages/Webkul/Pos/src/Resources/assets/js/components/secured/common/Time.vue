<template>
    <div
        ref="rootElement"
        class="relative flex items-center"
    >
        <slot />

        <i class="icon-time absolute text-xl text-gray-900 ltr:right-2 rtl:left-2 dark:text-gray-100"></i>
    </div>
</template>

<script setup>
    import { ref, onMounted } from 'vue';

    const timePicker = ref(null);
    const rootElement = ref(null);

    const emit = defineEmits(['onChange']);
    
    onMounted(() => {
        let options = {
            dateFormat: 'H:i',
            altFormat: 'H:i',
            noCalendar: true,
            altInput: true,
            enableTime: true,
            time_24hr: true,

            onChange: (selectedTimes, timeStr, instance) => {
                emit("onChange", timeStr);
            }
        };

        const inputElement = rootElement.value.getElementsByTagName("input")[0];

        timePicker.value = new flatpickr(inputElement, options);
    });

    const clear = () => {
        if (timePicker.value) {
            timePicker.value.clear();
        }
    };
</script>