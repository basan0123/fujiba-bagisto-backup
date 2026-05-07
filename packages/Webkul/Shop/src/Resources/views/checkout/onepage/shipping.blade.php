{!! view_render_event('bagisto.shop.checkout.onepage.shipping_methods.before') !!}

<v-shipping-methods
    :methods="shippingMethods"
    @processing="stepForward"
    @processed="stepProcessed"
>
    <x-shop::shimmer.checkout.onepage.shipping-method />
</v-shipping-methods>

{!! view_render_event('bagisto.shop.checkout.onepage.shipping_methods.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-shipping-methods-template"
    >
        <div class="mb-7 max-md:mb-0">
            <template v-if="! methods">
                <x-shop::shimmer.checkout.onepage.shipping-method />
            </template>

            <template v-else>
                <x-shop::accordion class="overflow-hidden !border-b-0 max-md:rounded-lg max-md:!border-none max-md:!bg-gray-100">
                    <x-slot:header class="px-0 py-4 max-md:p-3 max-md:text-sm max-md:font-medium max-sm:p-2">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-medium max-md:text-base">
                                @lang('shop::app.checkout.onepage.shipping.shipping-method')
                            </h2>
                        </div>
                    </x-slot:header>

                    <x-slot:content class="mt-8 !p-0 max-md:mt-0 max-md:rounded-t-none max-md:border max-md:border-t-0 max-md:!p-4">
                        <div class="rounded-xl border border-zinc-200 p-5 bg-blue-50 dark:bg-blue-900/20">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-lg font-semibold text-gray-800 dark:text-white">
                                        @{{ selectedLabel }}
                                    </p>
                                    <p v-if="selectedDescription" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        @{{ selectedDescription }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold" :class="selectedPrice == 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-800 dark:text-white'">
                                        @{{ selectedPriceFormatted }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-slot:content>
                </x-shop::accordion>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-shipping-methods', {
            template: '#v-shipping-methods-template',

            props: {
                methods: {
                    type: [Object, Array],
                    required: false,
                    default: null,
                },
            },

            emits: ['processing', 'processed'],

            data() {
                return {
                    autoSelectedMethod: null,
                    selectedLabel: '',
                    selectedDescription: '',
                    selectedPrice: 0,
                    selectedPriceFormatted: '',
                };
            },

            watch: {
                methods(val) {
                    if (val && !this.autoSelectedMethod) {
                        this.$nextTick(() => this.autoSelect());
                    }
                },
            },

            mounted() {
                this.$nextTick(() => this.autoSelect());
            },

            methods: {
                autoSelect() {
                    if (!this.methods) return;

                    const allRates = [];
                    const arr = Array.isArray(this.methods) ? this.methods : Object.values(this.methods);
                    arr.forEach(m => {
                        if (m && Array.isArray(m.rates)) {
                            m.rates.forEach(r => allRates.push(r));
                        }
                    });

                    if (allRates.length === 0) return;

                    const rate = allRates[0];
                    this.autoSelectedMethod    = rate.method;
                    this.selectedLabel         = rate.method_title || rate.carrier_title || '@lang("shop::app.checkout.onepage.shipping.shipping-method")';
                    this.selectedDescription   = this.parseDescription(rate.method_description);
                    this.selectedPrice         = parseFloat(rate.base_price) || 0;
                    this.selectedPriceFormatted = rate.base_formatted_price || ('¥' + this.selectedPrice.toLocaleString());

                    this.store(rate.method);
                },

                parseDescription(desc) {
                    if (!desc) return '';
                    try {
                        const data = JSON.parse(desc);
                        return data.display || '';
                    } catch (e) {
                        return desc;
                    }
                },

                store(method) {
                    this.$emit('processing', 'payment');

                    this.$axios.post("{{ route('shop.checkout.onepage.shipping_methods.store') }}", {
                            shipping_method: method,
                        })
                        .then(response => {
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                this.$emit('processed', response.data.payment_methods ?? response.data);
                            }
                        })
                        .catch(error => {
                            this.$emit('processing', 'shipping');
                            if (error.response?.data?.redirect_url) {
                                window.location.href = error.response.data.redirect_url;
                            }
                        });
                },
            },
        });
    </script>
@endPushOnce
