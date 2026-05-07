<template>
    <div class="mt-4 flex items-end justify-between gap-2.5 border-b border-zinc-200 pb-2">
        <div class="grid w-full gap-1">
            <!-- Dropdown Options Container -->
            <Label :class="{ 'required': option.isRequired }">
                {{ option.label }}
            </label>

            <template v-if="option.type == 'select'">
                <Field
                    :as="'select'"
                    :name="`bundle_options[${option.id}][]`"
                    :rules="{'required': option.isRequired}"
                    v-model="selectedProduct"
                    :label="option.label"
                >
                    <option
                        value="0"
                        v-if="! option.isRequired"
                    >
                        {{ $t('pos.home.products.view.type.bundle.none') }}
                    </option>

                    <option
                        v-for="(product, index) in option.products"
                        :key="index"
                        :value="product.id"
                    >
                        {{ `${product.name} + ${product.price.final.formattedPrice}` }}
                    </option>
                </Field>
            </template>
            
            <template v-if="option.type == 'radio'">
                <div class="grid gap-2 max-sm:gap-1">
                    <!-- None radio option if option is not required -->
                    <div
                        class="flex select-none gap-x-4"
                        v-if="! option.isRequired"
                    >
                        <Field
                            :type="'radio'"
                            :name="`bundle_options[${option.id}][]`"
                            :id="`bundle_options[${option.id}][${index}]`"
                            :value="0"
                            v-model="selectedProduct"
                            :rules="{'required': option.isRequired}"
                            :label="option.label"
                        />
                    </div>

                    <!-- Options -->
                    <div
                        class="flex select-none items-center gap-x-4 max-sm:gap-x-1.5"
                        v-for="(product, index) in option.products"
                        :key="index"
                    >
                        <Field
                            :type="'radio'"
                            :name="`bundle_options[${option.id}][]`"
                            :id="`bundle_options[${option.id}][${index}]`"
                            :value="product.id"
                            v-model="selectedProduct"
                            :rules="{'required': option.isRequired}"
                            :label="option.label"
                        >
                            <template v-slot:label>
                                {{ product.name }}

                                <span>
                                    {{ `+ ${product.price.final.formattedPrice}` }}
                                </span>
                            </template>
                        </Field>
                    </div>
                </div>
            </template>

            <template v-if="option.type == 'multiselect'">
                <Field
                    :type="'multiselect'"
                    :name="`bundle_options[${option.id}][]`"
                    :rules="{'required': option.isRequired}"
                    v-model="selectedProduct"
                    :label="option.label"
                >
                    <option
                        value="0"
                        v-if="! option.isRequired"
                    >
                        {{ $t('pos.home.products.view.type.bundle.none') }}
                    </option>

                    <option
                        v-for="(product, index) in option.products"
                        :key="index"
                        :value="product.id"
                        :selected="selectedProduct?.includes(product.id)"
                    >
                        {{ `${product.name} + ${product.price.final.formattedPrice}` }}
                    </option>
                </Field>
            </template>

            <template v-if="option.type == 'checkbox'">
                <div class="grid gap-2">
                    <!-- Options -->
                    <div
                        class="flex select-none items-center gap-x-4 max-sm:gap-x-1.5"
                        v-for="(product, index) in option.products"
                        :key="index"
                    >
                        <Field
                            :type="'checkbox'"
                            :name="`bundle_options[${option.id}][]`"
                            :id="`bundle_options[${option.id}][${index}]`"
                            :value="product.id"
                            v-model="selectedProduct"
                            :rules="{'required': option.isRequired}"
                            :label="option.label"
                        >
                            <template v-slot:label>
                                {{ product.name }}

                                <span>
                                    {{ `+ ${product.price.final.formattedPrice}` }}
                                </span>
                            </template>
                        </Field>
                    </div>
                </div>
            </template>

            <Error :name="`bundle_options[${option.id}][]`" />
        </div>

        <template v-if="['select', 'radio'].includes(option.type)">
            <quantity-changer
                :name="`bundle_option_qty[${option.id}]`"
                :value="productQty"
                @change="qtyUpdated($event)"
            />
        </template>
    </div>
</template>

<script setup>
    import { ref, computed, onMounted, watch } from 'vue';
    import QuantityChanger from '@components/secured/common/QuantityChanger.vue';

    /**
     * Define the props.
     */
    const props = defineProps(['option']);

    /**
     * Define the data properties.
     */
    const selectedProduct = ref(
        props.option.type === 'checkbox' || props.option.type === 'multiselect'
        ? []
        : null
    );

    /**
     * On mounted.
     */
    onMounted(() => {
        props.option.products.forEach((product) => {
            if (product.isDefault) {
                if (
                    props.option.type === 'checkbox'
                    || props.option.type === 'multiselect'
                ) {
                    selectedProduct.value.push(product.id);
                } else {
                    selectedProduct.value = product.id;
                }
            }
        });
    });
    
    /**
     * Product qty.
     */
    const productQty = computed(() => {
        let qty = 0;

        props.option.products.forEach((product) => {
            if (selectedProduct.value === product.id) {
                qty = product.qty;
            }
        });

        return qty;
    });

    /**
     * Watch the selected product.
     */
    const emit = defineEmits(['onProductSelected']);

    watch(selectedProduct, (value) => {
        emit('onProductSelected', value);
    });

    /**
     * Product qty updated.
     */
    const qtyUpdated = (qty) => {
        const product = props.option.products.find((data) => data.id === selectedProduct.value);

        if (product) {
            product.qty = qty;
        }
    };
</script>