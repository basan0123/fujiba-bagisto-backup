{!! view_render_event('bagisto.shop.checkout.onepage.address.guest.before') !!}

<!-- Guest Address Vue Component -->
<v-checkout-address-guest
    :cart="cart"
    @processing="stepForward"
    @processed="stepProcessed"
></v-checkout-address-guest>

{!! view_render_event('bagisto.shop.checkout.onepage.address.guest.after') !!}

@include('shop::checkout.onepage.address.form')

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-address-guest-template"
    >
        <!-- Address Form -->
        <x-shop::form
            v-slot="{ meta, errors, handleSubmit }"
            as="div"
        >
            <form @submit="handleSubmit($event, addAddress)">
                {{-- ─────────── SHIPPING ADDRESS (PRIMARY) ─────────── --}}
                <template v-if="cart.have_stockable_items">
                    <div class="mb-4">
                        {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.shipping.before') !!}

                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-medium max-md:text-lg max-sm:text-base">
                                @lang('shop::app.checkout.onepage.address.shipping-address')
                            </h2>
                        </div>

                        <v-checkout-address-form
                            control-name="shipping"
                            :address="cart.shipping_address || cart.billing_address || undefined"
                        ></v-checkout-address-form>

                        {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.shipping.after') !!}
                    </div>

                    {{-- Same-as-shipping toggle (default checked) --}}
                    <x-shop::form.control-group
                        class="!mb-0 flex items-center gap-2.5"
                    >
                        <x-shop::form.control-group.control
                            type="checkbox"
                            name="billing_same_as_shipping"
                            id="billing_same_as_shipping"
                            for="billing_same_as_shipping"
                            value="1"
                            @change="billingSameAsShipping = ! billingSameAsShipping"
                            ::checked="!! billingSameAsShipping"
                        />

                        <label
                            class="cursor-pointer select-none text-base text-zinc-500 max-md:text-sm max-sm:text-xs ltr:pl-0 rtl:pr-0"
                            for="billing_same_as_shipping"
                        >
                            @lang('shop::app.checkout.onepage.address.same-as-shipping')
                        </label>
                    </x-shop::form.control-group>
                </template>

                {{-- ─────────── BILLING ADDRESS (only when different, OR when no stockables) ─────────── --}}
                <div
                    class="mt-8"
                    v-if="! cart.have_stockable_items || ! billingSameAsShipping"
                >
                    {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.billing.before') !!}

                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-medium max-md:text-lg max-sm:text-base">
                            @lang('shop::app.checkout.onepage.address.billing-address')
                        </h2>
                    </div>

                    <v-checkout-address-form
                        control-name="billing"
                        :address="cart.billing_address || undefined"
                    ></v-checkout-address-form>

                    {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.billing.after') !!}
                </div>

                <!-- Proceed Button -->
                <div class="mt-4 flex justify-end">
                    <x-shop::button
                        class="primary-button rounded-2xl px-11 py-3 max-md:w-full max-md:max-w-full max-md:rounded-lg"
                        :title="trans('shop::app.checkout.onepage.address.proceed')"
                        ::loading="isStoring"
                        ::disabled="isStoring"
                    />
                </div>
            </form>
        </x-shop::form>
    </script>

    <script type="module">
        app.component('v-checkout-address-guest', {
            template: '#v-checkout-address-guest-template',

            props: ['cart'],

            emits: ['processing', 'processed'],

            data() {
                return {
                    // Default: billing == shipping. Customer opts in to a separate billing.
                    billingSameAsShipping: true,

                    isStoring: false,
                }
            },

            created() {
                // If we already have a billing address with use_for_shipping flag, restore the toggle.
                if (this.cart.billing_address && this.cart.billing_address.use_for_shipping === false) {
                    this.billingSameAsShipping = false;
                }
            },

            methods: {
                addAddress(params, { setErrors }) {
                    this.isStoring = true;

                    let payload;

                    if (this.cart.have_stockable_items) {
                        if (this.billingSameAsShipping) {
                            // Backend requires a `billing` block; we forward shipping data as billing
                            // and set use_for_shipping=true so Cart::updateOrCreateShippingAddress
                            // copies billing → shipping. No separate `shipping` key needed.
                            payload = {
                                billing: {
                                    ...(params.shipping || {}),
                                    use_for_shipping: true,
                                },
                            };
                        } else {
                            // Distinct billing & shipping. Backend reads use_for_shipping=false on
                            // billing and consumes the standalone shipping block as-is.
                            payload = {
                                billing: {
                                    ...(params.billing || {}),
                                    use_for_shipping: false,
                                },
                                shipping: params.shipping || {},
                            };
                        }
                    } else {
                        // Digital-only cart: only billing matters.
                        payload = {
                            billing: {
                                ...(params.billing || {}),
                                use_for_shipping: false,
                            },
                        };
                    }

                    this.moveToNextStep();

                    this.$axios.post('{{ route('shop.checkout.onepage.addresses.store') }}', payload)
                        .then((response) => {
                            this.isStoring = false;

                            if (response.data.data.redirect_url) {
                                window.location.href = response.data.data.redirect_url;
                            } else {
                                if (this.cart.have_stockable_items) {
                                    this.$emit('processed', response.data.data.shippingMethods);
                                } else {
                                    this.$emit('processed', response.data.data.payment_methods);
                                }
                            }
                        })
                        .catch(error => {
                            this.isStoring = false;

                            if (error.response.status == 422) {
                                // Backend errors come back keyed by `billing.<field>`. When
                                // billingSameAsShipping=true, those errors actually belong to
                                // the shipping form fields (the only ones the user sees), so
                                // remap so VeeValidate highlights the right inputs.
                                if (this.cart.have_stockable_items && this.billingSameAsShipping) {
                                    const remapped = {};
                                    Object.keys(error.response.data.errors).forEach(key => {
                                        remapped[key.replace(/^billing\./, 'shipping.')] = error.response.data.errors[key];
                                    });
                                    setErrors(remapped);
                                } else {
                                    setErrors(error.response.data.errors);
                                }
                            }
                        });
                },

                moveToNextStep() {
                    if (this.cart.have_stockable_items) {
                        this.$emit('processing', 'shipping');
                    } else {
                        this.$emit('processing', 'payment');
                    }
                }
            }
        });
    </script>
@endPushOnce
