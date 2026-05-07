<template>
    <v-form ref="productViewForm">
        <form
            class="grid gap-6"
            enctype="multipart/form-data"
            @submit="submitForm($event)"
        >
            <component
                v-if="product?.type"
                :is="components[product?.type]"
                :product="product"
            />

            <div class="flex justify-between gap-2.5">
                <template v-if="displayQuantityChanger">
                    <quantity-changer
                        name="quantity"
                        :value="1"
                    />
                </template>

                <button
                    type="submit"
                    class="transparent-button w-full"
                >
                    <span class="icon-cart text-2xl"></span>

                    {{ $t('pos.home.products.view.add_to_cart') }}
                </button>
            </div>
        </form>
    </v-form>
</template>

<script setup>
    import { ref, computed, inject } from 'vue';
    import QuantityChanger from '@components/secured/common/QuantityChanger.vue';
    import Booking from '@components/secured/home/products/view/type/Booking.vue';
    import Bundle from '@components/secured/home/products/view/type/Bundle.vue';
    import Configurable from '@components/secured/home/products/view/type/Configurable.vue';
    import Downloadable from '@components/secured/home/products/view/type/Downloadable.vue';
    import Grouped from '@components/secured/home/products/view/type/Grouped.vue';
    import Simple from '@components/secured/home/products/view/type/Simple.vue';
    import Virtual from '@components/secured/home/products/view/type/Virtual.vue';

    /**
     * Define the props.
     */
    const props = defineProps({
        product: {
            type: Object,
            required: true,
        },
        productModal: {
            type: Object,
            required: true,
        },
    });

    const emitter = inject('emitter');

    const displayQuantityChanger = computed(() => !(['downloadable', 'grouped'].includes(props.product.type)));

    /**
     * Submit the form.
     */
    const productViewForm = ref(null);
    
    const submitForm = (event) => {
        event.preventDefault();

        productViewForm.value.validate().then(result => {            
            if (result.valid) {
                let params = new FormData(event.target);

                const formData = {};
                const arrayCounters = {};

                params.forEach((value, key) => {
                    const matches = [...key.matchAll(/\[([^\]]*)\]/g)];
                    const baseKey = key.split('[')[0];

                    if (matches.length) {
                        let current = formData;

                        if (! current[baseKey]) {
                            current[baseKey] = {};
                        }
                        
                        current = current[baseKey];

                        matches.forEach((match, index) => {
                            let subKey = match[1];

                            const isLast = index === matches.length - 1;

                            if (subKey === "") {
                                const path = baseKey + matches.slice(0, index).map(m => `[${m[1]}]`).join("");

                                arrayCounters[path] = (arrayCounters[path] || 0);

                                subKey = arrayCounters[path]++;
                            }

                            if (isLast) {
                                if (! current[subKey]) {
                                    current[subKey] = [];
                                }

                                current[subKey].push(value);
                            } else {
                                if (! current[subKey]) {
                                    current[subKey] = {};
                                }

                                current = current[subKey];
                            }
                        });
                    } else {
                        formData[baseKey] = value;
                    }
                });

                addItemToCart(formData);
            }
        });
    };

    /**
     * Add item to cart.
     */
    const addItemToCart = (data) => {
        let productData = {};        

        if (props.product.type === 'configurable') {
            productData = {
                productId: props.product.id,
                quantity: parseInt(data.quantity),
                superAttribute: [
                    {
                        attributeId: parseInt(data.super_attribute[23]),
                        attributeOptionId: parseInt(data.super_attribute[24]),
                    }
                ],
                selectedConfigurableOption: parseInt(data.selected_configurable_option),
            };
        } else if (props.product.type === 'downloadable') {
            productData = {
                productId: props.product.id,
                quantity: 1,
                links:  Object.values(data.links).flat(),
            };
        } else if (props.product.type === 'grouped') {
            let qty = Object.keys(data.qty).map(key => ({
                productId: Number(key),
                quantity: parseInt(data.qty[key][0])
            }));

            productData = {
                productId: props.product.id,
                quantity: 1,
                qty: qty,
            };
        } else if (props.product.type === 'bundle') {
            const bundleOptions = Object.entries(data.bundle_options).map(([optionId, productIds]) => {
                let flatIds = [];

                if (Array.isArray(productIds)) {
                    flatIds = productIds;
                } else if (
                    typeof productIds === 'object'
                    && productIds !== null
                ) {
                    flatIds = Object.values(productIds).flat();
                }

                return {
                    bundleOptionId: parseInt(optionId),
                    bundleOptionProductId: flatIds.map(id => parseInt(id)),
                    qty: data.bundle_option_qty[optionId]
                        ? parseInt(data.bundle_option_qty[optionId][0])
                        : parseInt(data.quantity)
                };
            });

            productData = {
                productId: props.product.id,
                quantity: parseInt(data.quantity),
                bundleOptions,
            };
        } else if (
            props.product.type === 'simple'
            || props.product.type === 'virtual'
        ) {            
            const customizableOptions = Object.keys(data.customizable_options).map(key => {
                const value = data.customizable_options[key];                

                if (
                    Array.isArray(value)
                    && value[0] instanceof File
                ) {                    
                    return {
                        id: Number(key),
                        file: value[0],
                    };
                } else {
                    return {
                        id: Number(key),
                        value: value[0],
                    };
                }
            });

            productData = {
                productId: props.product.id,
                quantity: parseInt(data.quantity),
            };
            
            if (customizableOptions.length) {
                productData.customizableOptions = customizableOptions;
            }
        } else if (props.product.type === 'booking') {            
            const bookingData = {};            

            if (data.booking.date?.[0]) {
                bookingData.date = data.booking.date[0];
            }

            if (data.booking.date_from?.[0]) {
                bookingData.dateFrom = data.booking.date_from[0];
            }

            if (data.booking.date_to?.[0]) {
                bookingData.dateTo = data.booking.date_to[0];
            }

            if (data.booking.slot?.[0]) {
                const [from, to] = data.booking.slot[0].split('-').map(Number);
                bookingData.slot = { from, to };
            }

            if (data.booking.note?.[0]) {
                bookingData.note = data.booking.note[0];
            }

            if (data.booking.qty) {
                bookingData.qty = Object.keys(data.booking.qty).map(ticketId => ({
                    ticketId: Number(ticketId),
                    quantity: parseInt(data.booking.qty[ticketId][0]),
                }));
            }

            productData = {
                productId: props.product.id,
                quantity: parseInt(data.quantity),
                booking: bookingData,
            };            
        }
        
        emitter.emit('add_to_cart', productData);

        props.productModal.toggle();
    };

    /**
     * Map the product type to the component.
     */
    const components = {
        booking: Booking,
        bundle: Bundle,
        configurable: Configurable,
        downloadable: Downloadable,
        grouped: Grouped,
        simple: Simple,
        virtual: Virtual,
    };
</script>
