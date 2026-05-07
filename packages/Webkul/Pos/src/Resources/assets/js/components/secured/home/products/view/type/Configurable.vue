<template>
    <div class="max-w-full max-sm:w-full">
        <v-field
            type="hidden"
            name="selected_configurable_option"
            id="selected_configurable_option"
            v-model="selectedOptionVariant"
        />

        <div
            class="variant-price flex gap-2.5 pb-2.5"
            style="display: none;"
        >
            <span class="text-base dark:text-white">
                {{ $t('pos.home.products.view.type.configurable.price') }}:
            </span>

            <span
                class="text-base font-semibold dark:text-white"
                v-html="selectedVariantPrice"
            >
            </span>
        </div>

        <div
            class="mt-2.5"
            v-for='(attribute, index) in childAttributes'
            :key="index"
        >
            <!-- Dropdown Options Container -->
            <template v-if="
                ! attribute.swatch_type
                || attribute.swatch_type == ''
                || attribute.swatch_type == 'dropdown'"
            >
                <!-- Dropdown Label -->
                <h2 class="mb-1 text-base leading-5 text-gray-900 dark:text-white">
                    {{ attribute.label }}
                </h2>
                
                <!-- Dropdown Options -->
                <Field
                    :type="'select'"
                    :name="`super_attribute[${attribute.id}]`"
                    :id="`attribute_${attribute.id}`"
                    v-model="attribute.selectedValue"
                    :rules="'required'"
                    :label="attribute.label"
                    :aria-label="attribute.label"
                    :disabled="attribute.disabled"
                    @change="configure(attribute, $event.target.value)"
                >
                    <option
                        v-for='(option, index) in attribute.options'
                        :key="index"
                        :value="option.id"
                    >
                        {{ option.label }}
                    </option>
                </Field>
            </template>

            <!-- Swatch Options Container -->
            <template v-else>
                <!-- Option Label -->
                <h2 class="mb-4 text-base leading-5 text-gray-900 dark:text-white">
                    {{ attribute.label }}
                </h2>

                <!-- Swatch Options -->
                <div class="flex items-center gap-3">
                    <div
                        v-for="(option, index) in attribute.options"
                        :key="index"
                    >
                        <template v-if="option.id">
                            <!-- Color Swatch Options -->
                            <label
                                class="relative -m-0.5 flex cursor-pointer items-center justify-center rounded-full p-0.5 focus:outline-none"
                                :class="{'ring-2 ring-gray-900' : option.id == attribute.selectedValue}"
                                :style="{ '--tw-ring-color': option.swatch_value }"
                                :title="option.label"
                                v-if="attribute.swatch_type == 'color'"
                            >
                                <v-field
                                    type="radio"
                                    :name="`super_attribute[${attribute.id}]`"
                                    class="peer hidden"
                                    :value="option.id"
                                    v-slot="{ field }"
                                    rules="required"
                                    :label="attribute.label"
                                    :aria-label="attribute.label"
                                >
                                    <input
                                        type="radio"
                                        :name="`super_attribute[${attribute.id}]`"
                                        :value="option.id"
                                        v-bind="field"
                                        :id="`attribute_${attribute.id}`"
                                        :aria-labelledby="'color-choice-' + index + '-label'"
                                        class="peer sr-only"
                                        @click="configure(attribute, $event.target.value)"
                                    />
                                </v-field>

                                <span
                                    class="h-8 w-8 rounded-full border border-opacity-10 max-sm:h-[25px] max-sm:w-[25px]"
                                    tabindex="0"
                                    :style="{ 'background-color': option.swatch_value, 'border-color': option.swatch_value}"
                                ></span>
                            </label>

                            <!-- Image Swatch Options -->
                            <label 
                                class="text-neutral-400-900 hover:bg-neutral-400-50 group relative flex h-[60px] w-[60px] cursor-pointer items-center justify-center overflow-hidden rounded-md border bg-white font-medium uppercase sm:py-6"
                                :class="{'border-navyBlue' : option.id == attribute.selectedValue }"
                                :title="option.label"
                                v-if="attribute.swatch_type == 'image'"
                            >
                                <v-field
                                    type="radio"
                                    :name="`super_attribute[${attribute.id}]`"
                                    v-model="attribute.selectedValue"
                                    :value="option.id"
                                    v-slot="{ field }"
                                    rules="required"
                                    :label="attribute.label"
                                    :aria-label="attribute.label"
                                >
                                    <input
                                        type="radio"
                                        :name="`super_attribute[${attribute.id}]`"
                                        :value="option.id"
                                        v-bind="field"
                                        :id="`attribute_${attribute.id}`"
                                        :aria-labelledby="'color-choice-' + index + '-label'"
                                        class="peer sr-only"
                                        @click="configure(attribute, $event.target.value)"
                                    />
                                </v-field>

                                <img
                                    :src="option.swatch_value"
                                    :title="option.label"
                                />
                            </label>

                            <!-- Text Swatch Options -->
                            <label 
                                class="border-neutral-400-300 text-neutral-400-900 hover:bg-neutral-400-50 group relative flex h-fit min-w-fit cursor-pointer items-center justify-center rounded-full border bg-white px-5 py-3 font-medium uppercase max-sm:h-fit max-sm:w-fit max-sm:px-3.5 max-sm:py-2"
                                :class="{'border-transparent !bg-navyBlue text-white' : option.id == attribute.selectedValue }"
                                :title="option.label"
                                v-if="attribute.swatch_type == 'text'"
                            >
                                <v-field
                                    type="radio"
                                    :name="`super_attribute[${attribute.id}]`"
                                    :value="option.id"
                                    v-model="attribute.selectedValue"
                                    v-slot="{ field }"
                                    rules="required"
                                    :label="attribute.label"
                                    :aria-label="attribute.label"
                                >
                                    <input
                                        type="radio"
                                        :name="`super_attribute[${attribute.id}]`"
                                        :value="option.id"
                                        v-bind="field"
                                        :id="`attribute_${attribute.id}`"
                                        class="peer sr-only"
                                        :aria-labelledby="'color-choice-' + index + '-label'"
                                        @click="configure(attribute, $event.target.value)"
                                    />
                                </v-field>

                                <span class="text-lg max-sm:text-sm">
                                    {{ option.label }}
                                </span>

                                <span
                                    class="pointer-events-none absolute -inset-px rounded-full"
                                    role="presentation"
                                >
                                </span>
                            </label>
                        </template>
                    </div>

                    <span
                        class="text-neutral-400-600 text-sm max-sm:text-xs"
                        v-if="! attribute.options.length"
                    >
                        {{ $t('pos.home.products.view.type.configurable.select_above_option') }}
                    </span>
                </div>
            </template>

            <v-error-message
                :name="`super_attribute[${attribute.id}]`"
                class="text-sm text-red-500"
            />
        </div>
    </div>
</template>

<script setup>
    import { ref, onMounted } from 'vue';
    import { useI18n } from 'vue-i18n';

    /**
     * Get the i18n instance.
     */
    const { t } = useI18n();

    /**
     * Define the props.
     */
    const props = defineProps({
        product: {
            type: Object,
            required: true
        }
    });

    /**
     * Define the data properties.
     */
    const config = ref(props.product.variantConfigurations);

    const attributes = ref([]);
    
    const childAttributes = ref([]);

    const possibleOptionVariant = ref(null);

    const selectedOptionVariant = ref('');

    const selectedVariantPrice = ref('');

    /**
     * Fill the attribute options.
     */
    onMounted(() => {
        attributes.value = JSON.parse(JSON.stringify(config.value)).attributes.slice();

        let index = attributes.value.length;

        while (index--) {
            let attribute = attributes.value[index];

            attribute.options = [];

            if (index) {
                attribute.disabled = true;
            } else {
                fillAttributeOptions(attribute);
            }

            attribute = Object.assign(attribute, {
                childAttributes: childAttributes.value.slice(),
                prevAttribute: attributes.value[index - 1],
                nextAttribute: attributes.value[index + 1]
            });

            childAttributes.value.unshift(attribute);            
        }
    });

    /**
     * Fill the attribute options.
     */
    const configure = (attribute, optionId) => {
        possibleOptionVariant.value = getPossibleOptionVariant(attribute, optionId);

        if (optionId) {
            attribute.selectedValue = optionId;

            if (attribute.nextAttribute) {
                attribute.nextAttribute.disabled = false;

                clearAttributeSelection(attribute.nextAttribute);

                fillAttributeOptions(attribute.nextAttribute);

                resetChildAttributes(attribute.nextAttribute);
            } else {
                selectedOptionVariant.value = possibleOptionVariant.value;
            }
        } else {
            clearAttributeSelection(attribute);

            clearAttributeSelection(attribute.nextAttribute);

            resetChildAttributes(attribute);
        }

        reloadPrice();
    };

    /**
     * Get the possible option variant.
     */
    const getPossibleOptionVariant = (attribute, optionId) => {
        let matchedOptions = attribute.options.filter(option => option.id == optionId);

        if (matchedOptions[0]?.allowedProducts) {
            return matchedOptions[0].allowedProducts[0];
        }

        return undefined;
    };

    /**
     * Fill the attribute options.
     */
    const fillAttributeOptions = (attribute) => {        
        let options = config.value.attributes.slice().find(tempAttribute => tempAttribute.id === attribute.id)?.options;

        attribute.options = [{
            'id': '',
            'label': t('pos.home.products.view.type.configurable.select_option'),
            'products': []
        }];

        if (! options) {
            return;
        }

        let prevAttributeSelectedOption = attribute.prevAttribute?.options.find(option => option.id == attribute.prevAttribute.selectedValue);

        let index = 1;

        for (let i = 0; i < options.length; i++) {
            let allowedProducts = [];

            if (prevAttributeSelectedOption) {
                for (let j = 0; j < options[i].products.length; j++) {
                    if (
                        prevAttributeSelectedOption.allowedProducts
                        && prevAttributeSelectedOption.allowedProducts.includes(options[i].products[j])
                    ) {
                        allowedProducts.push(options[i].products[j]);
                    }
                }
            } else {
                allowedProducts = options[i].products.slice(0);
            }

            if (allowedProducts.length > 0) {
                options[i].allowedProducts = allowedProducts;

                attribute.options[index++] = options[i];
            }
        }
    };

    /**
     * Reset the child attributes.
     */
    const resetChildAttributes = (attribute) => {
        if (! attribute.childAttributes) {
            return;
        }

        attribute.childAttributes.forEach((set) => {
            set.selectedValue = null;

            set.disabled = true;
        });
    };

    /**
     * Clear the attribute selection.
     */
    const clearAttributeSelection = (attribute) => {
        if (! attribute) {
            return;
        }

        attribute.selectedValue = null;

        selectedOptionVariant.value = null;
    };

    const reloadPrice = () => {
        let selectedOptionCount = childAttributes.value.filter(attribute => attribute.selectedValue).length;

        if (childAttributes.value.length == selectedOptionCount) {
            document.querySelector('.variant-price').style.display = 'block';

            selectedVariantPrice.value = config.value.variant_prices[possibleOptionVariant.value].final.formatted_price;
        } else {
            document.querySelector('.variant-price').style.display = 'none';

            selectedVariantPrice.value = config.value.regular.formatted_price;
        }
    };
</script>