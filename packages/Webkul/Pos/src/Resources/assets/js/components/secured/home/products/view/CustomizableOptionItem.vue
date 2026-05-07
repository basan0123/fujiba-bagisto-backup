<template>
    <div class="mt-4 border-b border-gray-200 dark:border-gray-700 pb-4">
        <div class="grid content-center gap-y-2">
            <div class="flex items-center gap-x-2">
                <Label :class="[Boolean(option.isRequired) ? 'required' : '']">
                    {{ option.label }}

                    <span v-if="! ['checkbox', 'radio', 'select', 'multiselect'].includes(option.type)">
                        {{ '+ ' + formatPrice(option.price) }}
                    </span>
                </Label>
            </div>

            <!-- Text Field -->
            <template v-if="option.type == 'text'">
                <Field
                    :type="'text'"
                    :name="'customizable_options[' + option.id + '][]'"
                    v-model="selectedItems"
                    :rules="{ 'required': Boolean(option.isRequired), 'max': option.maxCharacters }"
                    :label="option.label"
                />
            </template>

            <!-- Textarea Field -->
            <template v-else-if="option.type == 'textarea'">
                <Field
                    :type="'textarea'"
                    :name="'customizable_options[' + option.id + '][]'"
                    v-model="selectedItems"
                    :rules="{ 'required': Boolean(option.isRequired), 'max': option.maxCharacters }"
                    :label="option.label"
                />
            </template>

            <!-- Checkbox Options -->
            <template v-else-if="option.type == 'checkbox'">
                <div class="grid gap-2">
                    <div
                        class="flex select-none items-center gap-x-4 max-sm:gap-x-1.5"
                        v-for="(item, index) in optionItems"
                        :key="index"
                    >
                        <Field
                            :type="'checkbox'"
                            :name="'customizable_options[' + option.id + '][]'"
                            :value="item.id"
                            :id="'customizable_options[' + option.id + '][' + index + ']'"
                            v-model="selectedItems"
                            :rules="{'required': Boolean(option.isRequired)}"
                            :label="option.label"
                        >
                            <template v-slot:label>
                                {{ item.label }}

                                <span>
                                    {{ '+ ' + formatPrice(item.price) }}
                                </span>
                            </template>
                        </Field>
                    </div>
                </div>
            </template>

            <!-- Radio Options -->
            <template v-else-if="option.type == 'radio'">
                <div class="grid gap-2 max-sm:gap-1">
                    <div
                        class="flex select-none gap-x-4"
                        v-if="! Boolean(option.isRequired)"
                    >
                        <Field
                            :type="'radio'"
                            :name="'customizable_options[' + option.id + '][]'"
                            :value="0"
                            :id="'customizable_options[' + option.id + '][' + index + ']'"
                            v-model="selectedItems"
                            :rules="{'required': Boolean(option.isRequired)}"
                            :label="option.label"
                            :checked="true"
                        >
                            <template v-slot:label>
                                {{ $t('pos.home.products.view.type.simple.customizable_options.none') }}
                            </template>
                        </Field>
                    </div>

                    <!-- Options -->
                    <div
                        class="flex select-none items-center gap-x-4 max-sm:gap-x-1.5"
                        v-for="(item, index) in optionItems"
                        :key="index"
                    >
                        <Field
                            :type="'radio'"
                            :name="'customizable_options[' + option.id + '][]'"
                            :value="item.id"
                            :id="'customizable_options[' + option.id + '][' + index + ']'"
                            v-model="selectedItems"
                            :rules="{'required': Boolean(option.isRequired)}"
                            :label="option.label"
                            :checked="true"
                        >
                            <template v-slot:label>
                                {{ item.label }}

                                <span>
                                    {{ '+ ' + formatPrice(item.price) }}
                                </span>
                            </template>
                        </Field>
                    </div>
                </div>
            </template>

            <!-- Select Options -->
            <template v-else-if="option.type == 'select'">
                <Field
                    :type="'select'"
                    :name="'customizable_options[' + option.id + '][]'"
                    v-model="selectedItems"
                    :rules="{'required': Boolean(option.isRequired)}"
                    :label="option.label"
                >
                    <option
                        value="0"
                        v-if="! Boolean(option.isRequired)"
                    >
                        {{ $t('pos.home.products.view.type.simple.customizable_options.none') }}
                    </option>

                    <option
                        v-for="item in optionItems"
                        :key="item.id"
                        :value="item.id"
                    >
                        {{ item.label + ' + ' + formatPrice(item.price) }}
                    </option>
                </Field>
            </template>

            <!-- Multiselect Options -->
            <template v-else-if="option.type == 'multiselect'">
                <Field
                    :type="'multiselect'"
                    :name="'customizable_options[' + option.id + '][]'"
                    v-model="selectedItems"
                    :rules="{'required': Boolean(option.isRequired)}"
                    :label="option.label"
                >
                    <option
                        v-for="item in optionItems"
                        :key="item.id"
                        :value="item.id"
                        :selected="selectedItems?.includes(item.id)"
                    >
                        {{ item.label + ' + ' + formatPrice(item.price) }}
                    </option>
                </Field>
            </template>

            <!-- Date Field -->
            <template v-else-if="option.type == 'date'">
                <Field
                    :type="'date'"
                    :name="'customizable_options[' + option.id + '][]'"
                    v-model="selectedItems"
                    :rules="{'required': Boolean(option.isRequired)}"
                    :label="option.label"
                />
            </template>

            <!-- Datetime Field -->
            <template v-else-if="option.type == 'datetime'">
                <Field
                    :type="'datetime'"
                    :name="'customizable_options[' + option.id + '][]'"
                    v-model="selectedItems"
                    :rules="{'required': Boolean(option.isRequired)}"
                    :label="option.label"
                />
            </template>

            <!-- Time Field -->
            <template v-else-if="option.type == 'time'">
                <Field
                    type="time"
                    :name="'customizable_options[' + option.id + '][]'"
                    v-model="selectedItems"
                    :rules="{'required': Boolean(option.isRequired)}"
                    :label="option.label"
                />
            </template>

            <!-- File -->
            <template v-else-if="option.type == 'file'">
                <Field
                    type="file"
                    :name="'customizable_options[' + option.id + '][]'"
                    :rules="fileRules(option)"
                    :label="option.label"
                    @change="handleFileChange"
                >
                </Field>
            </template>

            <Error :name="'customizable_options[' + option.id + '][]'" />
        </div>
    </div>
</template>

<script setup>
    import { ref, watch, watchEffect } from 'vue';
    import { useOutlet } from '@src/composable/outlet';

    /**
     * Define the props.
     */
    const props = defineProps({
        option: {
            type: Object,
            required: true,
        },
        index: {
            type: Number,
            required: false,
        },
    });

    const emit = defineEmits(['priceUpdated']);

    /**
     * General use imports
     */
    const { formatPrice } = useOutlet();

    const selectedItems = ref([]);
    const optionItems = ref([]);

    watchEffect(() => {        
        selectedItems.value = ['checkbox', 'multiselect'].includes(props.option.type)
            ? []
            : null;        

        if (! props.option.customizableOptionPrices) {
            return;
        }        

        optionItems.value = props.option.customizableOptionPrices.map(item => {
            return {
                id: item.id,
                label: item.label,
                price: item.price,
            };
        });
    });

    watch(selectedItems, (newValue) => {
        let selectedItemValues = Array.isArray(newValue) ? newValue : [newValue];        

        let totalPrice = 0;

        for (let item of optionItems.value) {
            switch (props.option.type) {
                case 'text':
                case 'textarea':
                case 'date':
                case 'datetime':
                case 'time':
                    if (selectedItemValues[0].length > 0) {
                        totalPrice += parseFloat(item.price);
                    }

                    break;

                case 'checkbox':
                case 'radio':
                case 'select':
                case 'multiselect':
                    if (selectedItemValues.includes(item.id)) {
                        totalPrice += parseFloat(item.price);
                    }

                case 'file':
                    if (selectedItemValues[0] instanceof File) {
                        totalPrice += parseFloat(item.price);
                    }

                    break;
            }
        }

        emit('priceUpdated', { option: props.option, totalPrice });
    });

    const handleFileChange = (event) => {
        const selectedFiles = event.target.files;

        selectedItems.value = selectedFiles[0];
    };

    const fileRules = (option) => {
        const rules = {};

        if (option.isRequired) {
            rules.required = true;
        }

        if (
            option.supportedFileExtensions
            && option.supportedFileExtensions.length
        ) {
            rules.ext = option.supportedFileExtensions
                .split(',')
                .map(ext => ext.trim());
        }

        return rules;
    };
</script>