<template>
    <!-- Checkbox -->
    <div
        v-if="type === 'checkbox'"
        class="flex items-center gap-2"
    >
        <v-field
            v-model="internalValue"
            type="checkbox"
            :value="true"
            :class="['peer hidden', className]"
            v-bind="$attrs"
        />

        <!-- Visual checkbox icon -->
        <label
            :for="$attrs.id || $attrs.name"
            class="icon-un-checked peer-checked:icon-checked cursor-pointer rounded-md text-2xl dark:text-white transition-all hover:text-gray-500 focus:text-gray-500 focus:outline-none"
        ></label>

        <!-- Label slot -->
        <label
            :for="$attrs.id || $attrs.name"
            class="text-base font-normal leading-5 text-gray-900 dark:text-white"
        >
            <slot name="label">
                {{ $attrs.label }}
            </slot>
        </label>
    </div>

    <!-- Radio -->
    <div
        v-else-if="type === 'radio'"
        class="flex items-center gap-2"
    >
        <v-field
            v-model="internalValue"
            type="radio"
            :class="['peer hidden', className]"
            v-bind="$attrs"
        />

        <!-- Visual radio icon -->
        <label
            :for="$attrs.id || $attrs.name"
            class="icon-radio-unselect peer-checked:icon-radio-select cursor-pointer rounded-md text-2xl dark:text-white transition-all hover:text-gray-500 focus:text-gray-500 focus:outline-none"
        ></label>

        <!-- Label slot -->
        <label
            :for="$attrs.id || $attrs.name"
            class="text-base font-normal leading-5 text-gray-900 dark:text-white"
        >
            <slot name="label">
                {{ $attrs.label }}
            </slot>
        </label>
    </div>

    <!-- Textarea -->
    <v-field
        v-else-if="type === 'textarea'"
        v-model="internalValue"
        as="textarea"
        :class="['w-full rounded-md border border-neutral-400 bg-white dark:bg-slate-900 text-gray-800 dark:text-white px-2.5 py-3 transition-all hover:border-gray-500 focus:border-gray-500 focus:outline-none', className]"
        v-bind="$attrs"
    />

    <!-- Select -->
    <v-field
        v-else-if="type === 'select'"
        v-model="internalValue"
        as="select"
        :class="['w-full rounded-md border border-neutral-400 bg-white dark:bg-slate-900 text-gray-800 dark:text-white px-2.5 py-3.5 transition-all hover:border-gray-500 focus:border-gray-500 focus:outline-none', className]"
        v-bind="$attrs"
    >
        <slot />
    </v-field>

    <!-- Multi-Select -->
    <v-field
        v-else-if="type === 'multiselect'"
        v-model="internalValue"
        as="select"
        multiple
        :class="['w-full rounded-md border border-neutral-400 bg-white dark:bg-slate-900 text-gray-800 dark:text-white px-2.5 py-3 transition-all hover:border-gray-500 focus:border-gray-500 focus:outline-none', className]"
        v-bind="$attrs"
    >
        <slot />
    </v-field>

    <!-- File Input -->
    <v-field
        v-else-if="type === 'file'"
        v-model="internalValue"
        type="file"
        :class="['w-full rounded-md border border-neutral-400 bg-white dark:bg-slate-900 text-gray-800 dark:text-white px-2.5 py-1.5 file:mr-4 file:rounded file:border-0 file:bg-gray-100 file:py-2 file:px-4 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:file:bg-slate-800 dark:file:text-white dark:hover:file:bg-slate-700 transition-all hover:border-gray-500 focus:border-gray-500 focus:outline-none', className]"
        v-bind="$attrs"
    />

    <!-- Date -->
    <Date
        v-else-if="type === 'date'"
        v-bind="$attrs"
    >
        <v-field
            v-model="internalValue"
            :type="type"
            :class="['w-full rounded-md border border-neutral-400 bg-white dark:bg-slate-900 text-gray-800 dark:text-white px-2.5 py-3 transition-all hover:border-gray-500 focus:border-gray-500 focus:outline-none', className]"
            v-bind="$attrs"
        />
    </Date>

    <Time
        v-else-if="type === 'time'"
        v-bind="$attrs"
    >
        <v-field
            v-model="internalValue"
            :type="type"
            :class="['w-full rounded-md border border-neutral-400 bg-white dark:bg-slate-900 text-gray-800 dark:text-white px-2.5 py-3 transition-all hover:border-gray-500 focus:border-gray-500 focus:outline-none', className]"
            v-bind="$attrs"
        />
    </Time>

    <DateTime
        v-else-if="type === 'datetime'"
        v-bind="$attrs"
    >
        <v-field
            v-model="internalValue"
            :type="type"
            :class="['w-full rounded-md border border-neutral-400 bg-white dark:bg-slate-900 text-gray-800 dark:text-white px-2.5 py-3 transition-all hover:border-gray-500 focus:border-gray-500 focus:outline-none', className]"
            v-bind="$attrs"
        />
    </DateTime>

    <!-- Default Input -->
    <v-field
        v-else
        v-model="internalValue"
        :type="type"
        :class="['w-full rounded-md border border-neutral-400 bg-white dark:bg-slate-900 text-gray-800 dark:text-white px-2.5 py-3 transition-all hover:border-gray-500 focus:border-gray-500 focus:outline-none', className]"
        v-bind="$attrs"
    />
</template>

<script setup>
    import { computed, useAttrs } from 'vue';
    import Date from '@components/secured/common/Date.vue';
    import Time from '@components/secured/common/Time.vue';
    import DateTime from '@components/secured/common/DateTime.vue';

    const $attrs = useAttrs();

    const props = defineProps({
        type: {
            type: String,
            default: 'text',
            validator: (value) => [
                'text',
                'password',
                'email',
                'number',
                'date',
                'time',
                'datetime',
                'checkbox',
                'radio',
                'textarea',
                'select',
                'multiselect',
                'file',
            ].includes(value),
        },
        modelValue: {
            type: [String, Number, Boolean, Array, Object],
            default: undefined,
        },
        checked: {
            type: Boolean,
            default: false
        },
        className: {
            type: String,
            default: ''
        }
    });

    /**
     * Enable attribute inheritance but exclude handled props
     */
    defineOptions({
        inheritAttrs: false
    });

    const emit = defineEmits(['update:modelValue']);

    /**
     * Computed property for two-way binding
     */
    const internalValue = computed({
        get() {
            if (props.type === 'checkbox') {
                return props.modelValue !== undefined ? props.modelValue : props.checked;
            }

            if (props.type === 'multiselect') {
                if (! props.modelValue) {
                    return [];
                }

                return Array.isArray(props.modelValue) ? props.modelValue : [props.modelValue];
            }
            
            return props.modelValue;
        },
        set(newValue) {
            emit('update:modelValue', newValue);
        }
    });
</script>