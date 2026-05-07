{!! view_render_event('bagisto.shop.checkout.onepage.payment_methods.before') !!}

<v-payment-methods
    :methods="paymentMethods"
    @processing="stepForward"
    @processed="stepProcessed"
    @cod-fee-updated="onCodFeeUpdated"
>
    <x-shop::shimmer.checkout.onepage.payment-method />
</v-payment-methods>

{!! view_render_event('bagisto.shop.checkout.onepage.payment_methods.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-payment-methods-template"
    >
        <div class="mb-7 max-md:last:!mb-0">
            <template v-if="! methods">
                <x-shop::shimmer.checkout.onepage.payment-method />
            </template>

            <template v-else>
                {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.accordion.before') !!}

                <x-shop::accordion class="overflow-hidden !border-b-0 max-md:rounded-lg max-md:!border-none max-md:!bg-gray-100">
                    <x-slot:header class="px-0 py-4 max-md:p-3 max-md:text-sm max-md:font-medium max-sm:p-2">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-medium max-md:text-base">
                                @lang('shop::app.checkout.onepage.payment.payment-method')
                            </h2>
                        </div>
                    </x-slot>

                    <x-slot:content class="mt-8 !p-0 max-md:mt-0 max-md:rounded-t-none max-md:border max-md:border-t-0 max-md:!p-4">
                        <div class="flex flex-wrap gap-7 max-md:gap-4 max-sm:gap-2.5">
                            <div
                                class="relative cursor-pointer max-md:max-w-full max-md:flex-auto"
                                v-for="(payment, index) in methodList"
                            >
                                <input
                                    type="radio"
                                    name="payment[method]"
                                    :value="payment.payment || payment.method"
                                    :id="payment.method"
                                    class="peer hidden"
                                    @change="store(payment)"
                                >

                                <label
                                    :for="payment.method"
                                    class="icon-radio-unselect peer-checked:icon-radio-select absolute top-5 cursor-pointer text-2xl text-navyBlue ltr:right-5 rtl:left-5"
                                >
                                </label>

                                <label
                                    :for="payment.method"
                                    class="block w-[190px] cursor-pointer rounded-xl border border-zinc-200 p-5 max-md:flex max-md:w-full max-md:gap-5 max-md:rounded-lg max-sm:gap-4 max-sm:px-4 max-sm:py-2.5"
                                >
                                    <img
                                        class="max-h-11 max-w-14"
                                        :src="payment.image"
                                        width="55"
                                        height="55"
                                        :alt="payment.method_title"
                                        :title="payment.method_title"
                                    />
                                    <div>
                                        <p class="mt-1.5 text-sm font-semibold max-md:mt-1 max-sm:mt-0">
                                            @{{ payment.method_title }}
                                        </p>
                                        <p class="mt-2.5 text-xs font-medium text-zinc-500 max-md:mt-1 max-sm:mt-0">
                                            @{{ payment.description }}
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </x-slot>
                </x-shop::accordion>

                {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.accordion.after') !!}
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-payment-methods', {
            template: '#v-payment-methods-template',

            props: {
                methods: {
                    type: [Object, Array],
                    required: true,
                    default: () => null,
                },
            },

            emits: ['processing', 'processed', 'cod-fee-updated'],

            data() {
                return {
                    selectedPaymentMethod: null,
                };
            },

            computed: {
                methodList() {
                    if (!this.methods) return [];
                    if (Array.isArray(this.methods)) return this.methods;
                    if (this.methods.payment_methods) {
                        const pm = this.methods.payment_methods;
                        if (pm.payment_methods) return pm.payment_methods;
                        if (Array.isArray(pm)) return pm;
                    }
                    return Object.values(this.methods);
                },
            },

            methods: {
                store(selectedMethod) {
                    const method = selectedMethod.method || selectedMethod.payment;
                    this.selectedPaymentMethod = method;
                    this.$emit('processing', 'review');

                    this.$axios.post("{{ route('shop.checkout.onepage.payment_methods.store') }}", {
                            payment: selectedMethod,
                        })
                        .then(response => {
                            const data = response.data;

                            this.$emit('cod-fee-updated', {
                                codFee: data.cod_fee || 0,
                                codFeeFormatted: data.cod_fee_formatted || '',
                            });

                            this.$emit('processed', data.cart);
                        })
                        .catch(error => {
                            this.$emit('processing', 'payment');
                            if (error.response?.data?.redirect_url) {
                                window.location.href = error.response.data.redirect_url;
                            }
                        });
                },
            },
        });
    </script>
@endPushOnce
