<template>
    <div class="flex justify-between gap-2.5">
        <div class="transparent-button min-w-[114px] justify-between dark:border-slate-400 dark:bg-gray-900 dark:text-slate-300">
            <span 
                class="icon-minus cursor-pointer text-2xl dark:text-slate-300"
                @click="decrease"
            />
            
            <p class="select-none text-center dark:text-slate-300">
                {{ quantity }}
            </p>
            
            <span 
                class="icon-plus cursor-pointer text-2xl dark:text-slate-300"
                @click="increase"
            />
        </div>

        <v-field
            type="hidden"
            :name="name"
            v-model="quantity"
            :rules="rules"
        />
    </div>
</template>

<script setup>
    import { ref, watch } from 'vue';

    /**
     * Define the props.
     */
    const props = defineProps({
        name: {
            type: String,
            required: true,
        },
        value: {
            type: Number,
            default: 1,
        },
        rules: {
            type: String,
            default: '',
        },
    });

    const emit = defineEmits(['change']);

    const quantity = ref(props.value);

    /**
     * Watch the value prop.
     */
    watch(() => props.value, (value) => {
        quantity.value = value;
    });

    /**
     * Increase the quantity of the product.
     */
    const increase = () => {
        emit('change', ++quantity.value);
    };

    /**
     * Decrease the quantity of the product.
     */
    const decrease = () => {
        if (quantity.value > 1) {
            emit('change', --quantity.value);
        }
    };
</script>
