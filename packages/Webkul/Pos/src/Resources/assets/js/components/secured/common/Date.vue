<template>
    <div
        ref="rootElement"
        class="relative flex items-center"
    >
        <slot />

        <i
            class="icon-calendar absolute text-xl text-gray-900 ltr:right-2 rtl:left-2 dark:text-gray-100"
        ></i>
    </div>
</template>

<script setup>
    import { ref, onMounted } from 'vue';

    const props = defineProps({
        name: String,
        value: String,
        allowInput: {
            type: Boolean,
            default: true,
        },
        disable: Array,
        minDate: String,
        maxDate: String,
    });

    const datePicker = ref(null);
    const rootElement = ref(null);

    const emit = defineEmits(['onChange']);

    onMounted(() => {        
        let options = {
            allowInput: props.allowInput,
            disable: props.disable || [],
            minDate: props.minDate || '',
            maxDate: props.maxDate || '',
            altFormat: "Y-m-d",
            dateFormat: "Y-m-d",
            weekNumbers: true,

            onChange: (selectedDates, dateStr, instance) => {
                emit("onChange", dateStr);
            }
        };

        const inputElement = rootElement.value.getElementsByTagName("input")[0];

        datePicker.value = new flatpickr(inputElement, options);
    });

    const clear = () => {
        if (datePicker.value) {
            datePicker.value.clear();
        }
    };
</script>