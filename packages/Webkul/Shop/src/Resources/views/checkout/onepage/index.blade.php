@php
    $kimonoCfg = [
        'enabled' => (bool) core()->getConfigData(\App\Services\KimonoChannelRules::CONFIG_PREFIX.'.enabled'),
        'min_qty' => (int) (core()->getConfigData(\App\Services\KimonoChannelRules::CONFIG_PREFIX.'.min_qty') ?: 1),
        'max_qty' => (int) (core()->getConfigData(\App\Services\KimonoChannelRules::CONFIG_PREFIX.'.max_qty') ?: 3),
        'letterpack_qty_threshold' => (int) (core()->getConfigData(\App\Services\KimonoChannelRules::CONFIG_PREFIX.'.letterpack_qty_threshold') ?: 1),
        'time_locked' => \App\Services\KimonoChannelRules::TIME_LOCKED,
    ];
@endphp

<!-- SEO Meta Content -->
@push('meta')
    <meta name="description" content="@lang('shop::app.checkout.onepage.index.checkout')"/>

    <meta name="keywords" content="@lang('shop::app.checkout.onepage.index.checkout')"/>
@endPush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.checkout.onepage.index.checkout')
    </x-slot>

    {!! view_render_event('bagisto.shop.checkout.onepage.header.before') !!}

    <!-- Page Header -->
    <div class="flex-wrap">
        <div class="flex w-full items-center justify-between border border-b border-l-0 border-r-0 border-t-0 px-[60px] py-4 max-lg:px-8 max-sm:px-4">
            <div class="flex items-center gap-x-14 max-[1180px]:gap-x-9 max-sm:gap-x-3">
                <button
                    type="button"
                    onclick="history.back()"
                    class="hidden items-center gap-1 text-sm font-medium text-zinc-600 active:text-navyBlue max-lg:flex"
                    aria-label="@lang('shop::app.checkout.onepage.index.back') @lang('shop::app.checkout.onepage.index.checkout')"
                >
                    <span class="icon-arrow-left text-2xl"></span>
                </button>

                <a
                    href="{{ route('shop.home.index') }}"
                    class="flex min-h-[30px]"
                    aria-label="@lang('shop::checkout.onepage.index.bagisto')"
                >
                    <img
                        src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                        alt="{{ config('app.name') }}"
                        width="131"
                        height="29"
                    >
                </a>
            </div>

            @guest('customer')
                @include('shop::checkout.login')
            @endguest
        </div>
    </div>

    {!! view_render_event('bagisto.shop.checkout.onepage.header.after') !!}

    <!-- Page Content -->
    <div class="container px-[60px] max-lg:px-8 max-sm:px-4">

        {!! view_render_event('bagisto.shop.checkout.onepage.breadcrumbs.before') !!}

        <!-- Breadcrumbs -->
        @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
            <x-shop::breadcrumbs name="checkout" />
        @endif

        {!! view_render_event('bagisto.shop.checkout.onepage.breadcrumbs.after') !!}

        <!-- Checkout Vue Component -->
        <v-checkout>
            <!-- Shimmer Effect -->
            <x-shop::shimmer.checkout.onepage />
        </v-checkout>
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-checkout-template"
        >
            <template v-if="! cart">
                <!-- Shimmer Effect -->
                <x-shop::shimmer.checkout.onepage />
            </template>

            <template v-else>
                <div class="grid grid-cols-[1fr_auto] gap-8 max-lg:grid-cols-[1fr] max-md:gap-5">
                    <!-- Included Checkout Summary Blade File For Mobile view -->
                    <div class="hidden max-md:block">
                        @include('shop::checkout.onepage.summary')
                    </div>

                    <div
                        class="overflow-y-auto max-md:grid max-md:gap-4"
                        id="steps-container"
                    >
                        <!-- Included Addresses Blade File -->
                        <template v-if="['address', 'shipping', 'payment', 'review'].includes(currentStep)">
                            @include('shop::checkout.onepage.address')
                        </template>

                        <!-- Included Shipping Methods Blade File -->
                        <template v-if="cart.have_stockable_items && ['shipping', 'payment', 'review'].includes(currentStep)">
                            @include('shop::checkout.onepage.shipping')
                        </template>

                        <!-- Included Payment Methods Blade File -->
                        <template v-if="['payment', 'review'].includes(currentStep)">
                            @include('shop::checkout.onepage.payment')
                        </template>

                        <!-- COD Notice (between payment and delivery) -->
                        <template v-if="codFee > 0 && currentStep === 'review'">
                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/30 dark:bg-amber-900/20">
                                <div class="flex items-start gap-3">
                                    <span class="text-xl text-amber-600 dark:text-amber-400">⚠️</span>
                                    <div>
                                        <p class="font-semibold text-amber-900 dark:text-amber-200">
                                            @lang('shop::app.checkout.onepage.payment.cod-notice-title')
                                        </p>
                                        <p class="mt-2 text-sm font-medium text-amber-900 dark:text-amber-200">
                                            @lang('shop::app.checkout.onepage.payment.cod-fee-label'): <span class="text-lg font-bold">@{{ codFeeFormatted }}</span>
                                        </p>
                                        <p class="mt-2 text-sm font-semibold text-red-700 dark:text-red-300">
                                            @lang('shop::app.checkout.onepage.payment.cod-address-warning')
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- ── Kimono Channel Notice Card ── -->
                        <template v-if="currentStep === 'review' && kimonoConstraint">
                            <div
                                :style="kimonoConstraint.isBlue
                                    ? 'margin-top:24px;padding:16px 18px;background:#eff6ff;border:1.5px solid #93c5fd;border-radius:14px;'
                                    : 'margin-top:24px;padding:16px 18px;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:14px;'"
                            >
                                <div style="display:flex;align-items:flex-start;gap:10px;">
                                    <span :style="kimonoConstraint.isBlue ? 'color:#1d4ed8;font-size:18px;' : 'color:#b91c1c;font-size:18px;'">⚠</span>
                                    <div>
                                        <p :style="kimonoConstraint.isBlue
                                            ? 'font-size:13px;font-weight:700;color:#1e3a8a;margin:0 0 6px;'
                                            : 'font-size:13px;font-weight:700;color:#7f1d1d;margin:0 0 6px;'">
                                            @{{ kimonoConstraint.shipBadge }}
                                        </p>
                                        <p :style="kimonoConstraint.isBlue
                                            ? 'font-size:12.5px;line-height:1.6;color:#1e3a8a;margin:0;'
                                            : 'font-size:12.5px;line-height:1.6;color:#7f1d1d;margin:0;'">
                                            @{{ kimonoConstraint.noticeText }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- ── Sagawa Delivery Date & Time ── -->
                        <template v-if="currentStep === 'review'">
                            <div style="margin-top:24px;padding:20px 22px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:16px;">
                                <h3 style="font-size:15px;font-weight:700;color:#1e293b;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                                    @lang('shop::app.checkout.onepage.delivery.title')
                                </h3>

                                <!-- Date buttons -->
                                <div style="margin-bottom:14px;">
                                    <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:8px;">
                                        @lang('shop::app.checkout.onepage.delivery.date-label')
                                        <span style="color:#ef4444">*</span>
                                    </label>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                        <button
                                            v-for="d in availableDates"
                                            :key="d.iso"
                                            type="button"
                                            @click="deliveryDate = d.iso"
                                            :style="deliveryDate === d.iso
                                                ? 'background:#4f46e5;color:#fff;border-color:#4f46e5;'
                                                : 'background:#fff;color:#374151;border-color:#e2e8f0;'"
                                            style="padding:8px 14px;border:1.5px solid;border-radius:10px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;text-align:center;min-width:80px;"
                                        >
                                            @{{ d.label }}
                                        </button>
                                    </div>
                                    <p style="font-size:11px;color:#94a3b8;margin-top:6px;">
                                        @lang('shop::app.checkout.onepage.delivery.earliest-hint')
                                        @{{ availableDates.length ? availableDates[0].label : '' }}
                                    </p>
                                </div>

                                <!-- Time slot — locked single option when kimono rules apply -->
                                <div v-if="kimonoConstraint">
                                    <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:8px;">
                                        @lang('shop::app.checkout.onepage.delivery.time-label')
                                        <span style="color:#ef4444">*</span>
                                    </label>
                                    <div>
                                        <button
                                            type="button"
                                            disabled
                                            style="padding:9px 14px;border:1.5px solid #4f46e5;border-radius:10px;font-size:12px;font-weight:700;background:#4f46e5;color:#fff;cursor:not-allowed;text-align:center;min-width:140px;opacity:.95;"
                                        >@{{ kimonoConstraint.timeLocked }}</button>
                                    </div>
                                    <p style="font-size:11px;color:#94a3b8;margin-top:6px;">
                                        @lang('shop::app.checkout.onepage.delivery.time-locked-hint')
                                    </p>
                                </div>

                                <!-- Time slot buttons (default) -->
                                <div v-else>
                                    <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:8px;">
                                        @lang('shop::app.checkout.onepage.delivery.time-label')
                                        <span style="color:#ef4444">*</span>
                                    </label>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                        @foreach(config('sagawa.time_slots', []) as $val => $label)
                                        <button
                                            type="button"
                                            @click="deliveryTime = '{{ $val }}'"
                                            :style="deliveryTime === '{{ $val }}'
                                                ? 'background:#4f46e5;color:#fff;border-color:#4f46e5;'
                                                : 'background:#fff;color:#374151;border-color:#e2e8f0;'"
                                            style="padding:9px 8px;border:1.5px solid;border-radius:10px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;text-align:center;"
                                        >{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>

                                <p v-if="deliveryDate && deliveryTime" style="margin-top:12px;font-size:12px;color:#22c55e;font-weight:600;">
                                    ✓ @{{ deliveryDate }} / @{{ kimonoConstraint ? kimonoConstraint.timeLocked : sagawaSlotLabel(deliveryTime) }}
                                </p>
                            </div>
                        </template>
                    </div>

                    <!-- Included Checkout Summary Blade File For Desktop view -->
                    <div class="sticky top-8 block h-max w-[442px] max-w-full max-lg:w-auto max-lg:max-w-[442px] ltr:pl-8 max-lg:ltr:pl-0 rtl:pr-8 max-lg:rtl:pr-0">
                        <div class="block max-md:hidden">
                            @include('shop::checkout.onepage.summary')
                        </div>

                        <div
                            class="flex justify-end"
                            v-if="canPlaceOrder"
                        >
                            <template v-if="cart.payment_method == 'paypal_smart_button'">
                                {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.before') !!}

                                <!-- Paypal Smart Button Vue Component -->
                                <v-paypal-smart-button></v-paypal-smart-button>

                                {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.after') !!}
                            </template>

                            <template v-else>
                                <x-shop::button
                                    type="button"
                                    class="primary-button w-max rounded-2xl bg-navyBlue px-11 py-3 max-md:mb-4 max-md:w-full max-md:max-w-full max-md:rounded-lg max-sm:py-1.5"
                                    :title="trans('shop::app.checkout.onepage.summary.place-order')"
                                    ::disabled="isPlacingOrder"
                                    ::loading="isPlacingOrder"
                                    @click="placeOrder"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </script>

        <script type="module">
            app.component('v-checkout', {
                template: '#v-checkout-template',

                data() {
                    return {
                        cart: null,

                        displayTax: {
                            prices: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_prices') }}",

                            subtotal: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_subtotal') }}",
                            
                            shipping: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_shipping_amount') }}",
                        },

                        isPlacingOrder: false,

                        currentStep: 'address',

                        shippingMethods: null,

                        paymentMethods: null,

                        canPlaceOrder: false,

                        /* ── COD Fee (from payment step) ── */
                        codFee: 0,
                        codFeeFormatted: '',

                        /* ── Sagawa Delivery ── */
                        deliveryDate: '',
                        deliveryTime: '',
                        availableDates: [],
                        sagawaZones: @json(config('sagawa.zone_days', [])),
                        sagawaSlots: @json(config('sagawa.time_slots', [])),
                        sagawaProc: {{ (int) config('sagawa.processing_days', 1) }},
                        sagawaCutoffH: {{ (int) config('sagawa.cutoff_hour', 22) }},
                        sagawaCutoffM: {{ (int) config('sagawa.cutoff_minute', 30) }},
                        sagawaMaxDays: {{ (int) config('sagawa.max_days_ahead', 30) }},
                        sagawaDateCount: 14,

                        /* ── Kimono Channel Rules (admin-controlled) ── */
                        kimonoCfg: @json($kimonoCfg),
                    }
                },

                computed: {
                    kimonoConstraint() {
                        if (! this.kimonoCfg.enabled || ! this.cart) return null;
                        const country = (this.cart.shipping_address?.country || '').toString().toUpperCase();
                        if (country && country !== 'JP') return null;
                        const qty = parseInt(this.cart.items_qty || 0, 10);
                        if (qty < this.kimonoCfg.min_qty || qty > this.kimonoCfg.max_qty) return null;
                        const isBlue = qty <= this.kimonoCfg.letterpack_qty_threshold;
                        return {
                            qty,
                            isBlue,
                            timeLocked: this.kimonoCfg.time_locked,
                            noticeText: isBlue
                                ? '@lang('shop::app.checkout.onepage.delivery.kimono_letterpack_notice')'
                                : '@lang('shop::app.checkout.onepage.delivery.kimono_letterpack_red_notice')',
                            shipBadge: isBlue
                                ? '@lang('shop::app.checkout.onepage.delivery.kimono_letterpack_badge')'
                                : '@lang('shop::app.checkout.onepage.delivery.kimono_letterpack_red_badge')',
                        };
                    },

                    adjustedGrandTotal() {
                        if (! this.cart) return '';
                        if (this.codFee > 0) {
                            const total = parseFloat(this.cart.grand_total) + this.codFee;
                            return '¥' + Math.round(total).toLocaleString();
                        }
                        return this.cart.formatted_grand_total;
                    },
                },

                mounted() {
                    this.getCart();
                },

                watch: {
                    kimonoConstraint: {
                        handler(c) {
                            if (c) {
                                this.deliveryTime = c.timeLocked;
                            }
                        },
                        immediate: true,
                    },
                },

                methods: {
                    getCart() {
                        this.$axios.get("{{ route('shop.checkout.onepage.summary') }}")
                            .then(response => {
                                this.cart = response.data.data;
                                this.buildAvailableDates();
                                this.scrollToCurrentStep();
                            })
                            .catch(error => {});
                    },

                    stepForward(step) {
                        this.currentStep = step;

                        if (step == 'review') {
                            this.canPlaceOrder = true;

                            return;
                        }

                        this.canPlaceOrder = false;

                        if (this.currentStep == 'shipping') {
                            this.shippingMethods = null;
                        } else if (this.currentStep == 'payment') {
                            this.paymentMethods = null;
                            this.codFee = 0;
                            this.codFeeFormatted = '';
                        }
                    },

                    stepProcessed(data) {
                        if (this.currentStep == 'shipping') {
                            this.shippingMethods = data;
                        } else if (this.currentStep == 'payment') {
                            this.paymentMethods = data;
                        }

                        this.getCart();
                    },

                    scrollToCurrentStep() {
                        let container = document.getElementById('steps-container');

                        if (! container) {
                            return;
                        }

                        container.scrollIntoView({
                            behavior: 'smooth',
                            block: 'end'
                        });
                    },

                    placeOrder() {
                        if (! this.deliveryDate || ! this.deliveryTime) {
                            this.$emitter.emit('add-flash', {
                                type: 'warning',
                                message: '@lang("shop::app.checkout.onepage.delivery.select-prompt")'
                            });
                            return;
                        }

                        this.isPlacingOrder = true;

                        this.$axios.post(
                            '{{ route('shop.checkout.onepage.orders.store') }}',
                            {
                                delivery_date: this.deliveryDate,
                                delivery_time: this.deliveryTime,
                            }
                        )
                            .then(response => {
                                if (response.data.data.redirect) {
                                    window.location.href = response.data.data.redirect_url;
                                } else {
                                    window.location.href = '{{ route('shop.checkout.onepage.success') }}';
                                }

                                this.isPlacingOrder = false;
                            })
                            .catch(error => {
                                this.isPlacingOrder = false

                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            });
                    },

                    onCodFeeUpdated(data) {
                        this.codFee = data.codFee || 0;
                        this.codFeeFormatted = data.codFeeFormatted || '';
                    },

                    /* ── Sagawa helpers ── */
                    buildAvailableDates() {
                        const now = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Tokyo' }));
                        const h = now.getHours(), m = now.getMinutes();
                        const afterCutoff = (h > this.sagawaCutoffH) || (h === this.sagawaCutoffH && m >= this.sagawaCutoffM);

                        let leadDays = this.sagawaProc;
                        if (afterCutoff) leadDays += 1;

                        const pref = this.cart?.shipping_address?.state || '';
                        const zoneDays = this.sagawaZones[pref] ?? 1;
                        leadDays += zoneDays;

                        const dates = [];
                        const weekJa = ['日','月','火','水','木','金','土'];
                        for (let i = 0; i < this.sagawaDateCount; i++) {
                            const d = new Date(now);
                            d.setDate(d.getDate() + leadDays + i);
                            const iso = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
                            const label = (d.getMonth()+1) + '/' + d.getDate() + '(' + weekJa[d.getDay()] + ')';
                            dates.push({ iso, label });
                        }
                        this.availableDates = dates;

                        if (this.deliveryDate && !dates.find(d => d.iso === this.deliveryDate)) {
                            this.deliveryDate = '';
                        }
                    },

                    sagawaSlotLabel(val) {
                        return this.sagawaSlots[val] || val;
                    },
                },
            });
        </script>
    @endPushOnce
</x-shop::layouts>
