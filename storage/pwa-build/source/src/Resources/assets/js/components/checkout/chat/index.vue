<template>
    <div class="chat-checkout">
        <!-- Sticky header -->
        <div class="chat-header">
            <button class="chat-back" @click="goBack" aria-label="Back">←</button>
            <div class="chat-title-wrap">
                <div class="chat-title">{{ $t('Checkout') }}</div>
                <div class="chat-substeps">
                    {{ $t('Address') }} → {{ $t('Payment') }} → {{ $t('Confirm') }}
                </div>
            </div>
            <div class="chat-total" v-if="cart">{{ cart.formatted_grand_total }}</div>
        </div>

        <!-- Chat thread -->
        <div class="chat-wrap" ref="thread">
            <div v-for="(b, i) in bubbles" :key="i" class="bubble" :class="b.from === 'bot' ? 'bot' : 'user'">
                <div v-if="b.from === 'bot'" class="bot-avatar">●</div>
                <div class="bubble-msg" :class="b.from + '-msg'">
                    <span v-if="!b.html">{{ b.text }}</span>
                    <span v-else v-html="b.text"></span>
                </div>
            </div>
            <div v-if="typing" class="bubble bot">
                <div class="bot-avatar">●</div>
                <div class="bubble-msg bot-msg typing">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>
        </div>

        <!-- Dynamic input area, swapped per step -->
        <div class="chat-input">
            <!-- Step 1: delivery type -->
            <div v-if="step === 'deli'" class="deli-opts">
                <div class="deli-opt" @click="chooseDeli('delivery')">
                    <div class="deli-ic">🚚</div>
                    <div class="deli-name">{{ $t('Delivery') }}</div>
                </div>
                <div v-if="pickupEnabled" class="deli-opt" @click="chooseDeli('pickup')">
                    <div class="deli-ic">🏪</div>
                    <div class="deli-name">{{ $t('Store Pickup') }}</div>
                </div>
            </div>

            <!-- Step 2: address -->
            <div v-if="step === 'address'" class="addr-step">
                <!-- Saved address: prompt user to choose between using it or entering new -->
                <div v-if="savedAddress && !showAddrForm" class="saved-pick">
                    <div class="saved-card">
                        <div class="saved-head">
                            <span>📍 {{ $t('Saved Address') }}</span>
                        </div>
                        <div class="saved-text">
                            {{ savedAddress.first_name }} {{ savedAddress.last_name }}<br>
                            <span v-if="savedAddress.postcode">〒{{ savedAddress.postcode }}<br></span>
                            <span v-if="savedAddress.state">{{ savedAddress.state }} </span>{{ savedAddress.city }}<br>
                            {{ Array.isArray(savedAddress.address) ? savedAddress.address.join(' ') : (savedAddress.address1 || savedAddress.address) }}
                        </div>
                    </div>
                    <div class="saved-actions">
                        <button class="chat-cta-ghost" @click="enterNewAddress">{{ $t('Enter new address') }}</button>
                        <button class="chat-cta" @click="useSaved">{{ $t('Use this address') }} →</button>
                    </div>
                </div>

                <div v-if="!savedAddress || showAddrForm" class="addr-form">
                    <!-- JP order: postcode → prefecture → city → street → building → name → phone → email -->
                    <input class="addr-in" v-model="form.postcode" inputmode="numeric"
                           :placeholder="'〒 ' + $t('Postal Code') + (addrCfg.postcode_required ? ' *' : ' (' + $t('optional') + ')')"
                           @input="onPostcodeInput" @blur="lookupPostcode">
                    <div v-if="postcodeStatus === 'looking'" class="addr-hint">{{ $t('Looking up...') }}</div>
                    <div v-if="postcodeStatus === 'notfound'" class="addr-hint err">{{ $t('Postcode not found') }}</div>

                    <input class="addr-in" v-model="form.state" :placeholder="$t('Prefecture') + (addrCfg.state_required ? ' *' : '')" :readonly="postcodeStatus === 'found' && !!form.state">
                    <input class="addr-in" v-model="form.city"  :placeholder="$t('City') + ' *'">
                    <input
                        v-for="(line, idx) in streetLineRange"
                        :key="'street-' + idx"
                        class="addr-in"
                        v-model="form.address[idx]"
                        :placeholder="streetPlaceholder(idx)">

                    <div class="addr-row">
                        <input class="addr-in" v-model="form.first_name" :placeholder="$t('First Name') + ' *'">
                        <input class="addr-in" v-model="form.last_name"  :placeholder="$t('Family Name') + ' *'">
                    </div>
                    <input v-if="addrCfg.company_required" class="addr-in" v-model="form.company_name" :placeholder="$t('Company Name') + ' *'">
                    <input class="addr-in" v-model="form.phone" type="tel" :placeholder="$t('Phone') + (addrCfg.phone_required ? ' *' : ' (' + $t('optional') + ')')">
                    <input class="addr-in" v-model="form.email" type="email" :placeholder="$t('Email') + ' *'">

                    <div class="addr-country">🇯🇵 Japan</div>

                    <!-- Different billing toggle: hidden when admin forces billing required -->
                    <div v-if="!addrCfg.billing_required" class="addr-billing-toggle">
                        <label class="addr-bill-row">
                            <input type="checkbox" v-model="differentBilling">
                            <span>{{ $t('Different billing address?') }}</span>
                        </label>
                    </div>

                    <div v-if="showBillingForm" class="addr-billing-form">
                        <div class="addr-section-title">{{ $t('Billing Address') }}</div>
                        <div class="addr-row">
                            <input class="addr-in" v-model="billingForm.first_name" :placeholder="$t('First Name') + ' *'">
                            <input class="addr-in" v-model="billingForm.last_name"  :placeholder="$t('Last Name') + ' *'">
                        </div>
                        <input class="addr-in" v-model="billingForm.postcode" inputmode="numeric"
                               :placeholder="'〒 ' + $t('Postal Code') + (addrCfg.postcode_required ? ' *' : '')">
                        <input class="addr-in" v-model="billingForm.state" :placeholder="$t('Prefecture') + (addrCfg.state_required ? ' *' : '')">
                        <input class="addr-in" v-model="billingForm.city"  :placeholder="$t('City') + ' *'">
                        <input
                            v-for="(line, idx) in streetLineRange"
                            :key="'bill-street-' + idx"
                            class="addr-in"
                            v-model="billingForm.address[idx]"
                            :placeholder="streetPlaceholder(idx)">
                    </div>

                    <button class="chat-cta" :disabled="busy || !addressValid" @click="submitAddress">
                        {{ $t('Confirm Address') }} →
                    </button>
                </div>
            </div>

            <!-- Step 3: shipping method -->
            <div v-if="step === 'shipping'" class="opts">
                <div v-for="rate in shippingRates" :key="rate.method"
                     class="opt-card" :class="{ sel: selectedShipping === rate.method }"
                     @click="selectedShipping = rate.method">
                    <div class="opt-radio"><span v-if="selectedShipping === rate.method">●</span></div>
                    <div class="opt-info">
                        <div class="opt-name">{{ rate.carrier_title }}</div>
                        <div class="opt-desc">{{ rate.method_description || rate.method_title }}</div>
                    </div>
                    <div class="opt-price">{{ rate.formatted_price }}</div>
                </div>
                <button class="chat-cta" :disabled="busy || !selectedShipping" @click="submitShipping">
                    {{ $t('Confirm') }} →
                </button>
            </div>

            <!-- Step 3.5: delivery slot (date + time) -->
            <div v-if="step === 'slot'" class="slot-step">
                <div v-if="slotLoading" class="slot-loading">{{ $t('Loading delivery slots...') }}</div>
                <div v-else-if="!slotDates.length" class="slot-empty">
                    {{ $t('No delivery slots available for this address') }}
                </div>
                <template v-else>
                    <div class="slot-label">{{ $t('Preferred Date') }}</div>
                    <div class="slot-row">
                        <button v-for="d in slotDates" :key="d"
                                class="slot-chip" :class="{ sel: selectedDate === d }"
                                @click="selectedDate = d">
                            {{ formatDate(d) }}
                        </button>
                    </div>

                    <div class="slot-label">{{ $t('Preferred Time') }}</div>
                    <div class="slot-row">
                        <button class="slot-chip" :class="{ sel: !selectedTime }"
                                @click="selectedTime = ''">
                            {{ $t('Any time') }}
                        </button>
                        <button v-for="(label, val) in slotTimes" :key="val"
                                class="slot-chip" :class="{ sel: selectedTime === val }"
                                @click="selectedTime = val">
                            {{ label }}
                        </button>
                    </div>
                </template>

                <button class="chat-cta" :disabled="busy || (slotDates.length && !selectedDate)" @click="submitSlot">
                    {{ $t('Confirm') }} →
                </button>
            </div>

            <!-- Step 4: payment -->
            <div v-if="step === 'payment'" class="opts">
                <div v-for="m in paymentMethods" :key="m.method"
                     class="opt-card" :class="{ sel: selectedPayment === m.method }"
                     @click="selectedPayment = m.method">
                    <div class="opt-radio"><span v-if="selectedPayment === m.method">●</span></div>
                    <div class="opt-info">
                        <div class="opt-name">{{ paymentLabel(m.method) }}</div>
                        <div class="opt-desc">{{ m.description || '' }}</div>
                    </div>
                    <div class="opt-ic">{{ paymentIcon(m.method) }}</div>
                </div>
                <button class="chat-cta" :disabled="busy || !selectedPayment" @click="submitPayment">
                    {{ $t('Confirm') }} →
                </button>
            </div>

            <!-- Step 5: review -->
            <div v-if="step === 'review'" class="review-step">
                <div class="review-card">
                    <div class="review-row" v-if="cart">
                        <span>{{ $t('Subtotal') }}</span>
                        <span>{{ cart.formatted_sub_total }}</span>
                    </div>
                    <div class="review-row" v-if="cart && cart.formatted_shipping_amount && deliMode === 'delivery'">
                        <span>{{ $t('Shipping') }}</span>
                        <span>{{ cart.formatted_shipping_amount }}</span>
                    </div>
                    <div class="review-row" v-if="cart && cart.cod_fee > 0">
                        <span>{{ $t('Cash on Delivery Fee') }}</span>
                        <span>{{ cart.cod_fee_formatted || cart.cod_fee }}</span>
                    </div>
                    <div class="review-row" v-if="selectedDate || selectedTime">
                        <span>{{ $t('Delivery') }}</span>
                        <span>{{ selectedDate ? formatDate(selectedDate) : '' }}<span v-if="selectedTime"> · {{ slotTimes[selectedTime] || selectedTime }}</span></span>
                    </div>
                    <div class="review-row tot" v-if="cart">
                        <span>{{ $t('Order Total') }}</span>
                        <span>{{ cart.formatted_grand_total }}</span>
                    </div>
                </div>
                <div class="review-actions">
                    <button class="chat-cta-ghost" @click="restart">{{ $t('Edit') }}</button>
                    <button class="chat-cta" :disabled="busy" @click="placeOrder">
                        ✓ {{ $t('Place Order') }}
                    </button>
                </div>
            </div>

            <!-- Done -->
            <div v-if="step === 'done'" class="done-step">
                <div class="done-spinner"></div>
                <div class="done-msg">{{ $t('Placing your order...') }}</div>
            </div>

            <div v-if="error" class="chat-err">{{ error }}</div>
        </div>
    </div>
</template>

<script>
    import { mapState, mapActions } from 'vuex';

    export default {
        name: 'chat-checkout',

        data () {
            return {
                step: 'deli',         // deli → address → shipping → payment → review → done
                bubbles: [],
                typing: false,
                busy: false,
                error: '',

                deliMode: 'delivery',
                pickupEnabled: false,
                paymentMethods: [],
                shippingRates: [],

                selectedShipping: null,
                selectedPayment: null,

                savedAddress: null,
                showAddrForm: false,
                differentBilling: false,
                form: {
                    first_name: '',
                    last_name: '',
                    company_name: '',
                    email: '',
                    phone: '',
                    postcode: '',
                    state: '',
                    city: '',
                    address: ['', '', '', ''],
                    country: 'JP',
                    address_id: 0,
                    use_for_shipping: true,
                    save_as_address: 1,
                },
                billingForm: {
                    first_name: '',
                    last_name: '',
                    company_name: '',
                    email: '',
                    phone: '',
                    postcode: '',
                    state: '',
                    city: '',
                    address: ['', '', '', ''],
                    country: 'JP',
                },

                postcodeStatus: '',
                _postcodeTimer: null,
                fujibaCfg: null,

                // Delivery slot — populated from /sagawa/delivery-slots
                slotDates:    [],
                slotTimes:    {},
                slotLoading:  false,
                selectedDate: '',
                selectedTime: '',
            }
        },

        computed: {
            ...mapState({
                cart: state => state.cart,
                customer: state => state.customer,
            }),

            addressValid () {
                if (this.deliMode === 'pickup') {
                    return !!(this.form.first_name && this.form.email);
                }
                // Base required: name, email, city, street line 1
                var cfg = this.addrCfg;
                var ok = !!(
                    this.form.first_name && this.form.last_name && this.form.email &&
                    this.form.city && this.form.address[0]
                );
                if (!ok) return false;
                if (cfg.state_required    && !this.form.state)        return false;
                if (cfg.postcode_required && !this.form.postcode)     return false;
                if (cfg.phone_required    && !this.form.phone)        return false;
                if (cfg.company_required  && !this.form.company_name) return false;

                if (this.showBillingForm) {
                    if (!(this.billingForm.first_name && this.billingForm.last_name &&
                          this.billingForm.city && this.billingForm.address[0])) {
                        return false;
                    }
                    if (cfg.state_required    && !this.billingForm.state)        return false;
                    if (cfg.postcode_required && !this.billingForm.postcode)     return false;
                    if (cfg.company_required  && !this.billingForm.company_name) return false;
                }
                return true;
            },

            // Address requirements from /api/v1/pwa-frontend/config (admin-driven).
            // Defaults match Bagisto out-of-the-box behaviour when config missing.
            addrCfg () {
                var a = (this.fujibaCfg && this.fujibaCfg.address) || {};
                return {
                    street_lines:      Math.max(1, Math.min(4, parseInt(a.street_lines, 10) || 2)),
                    postcode_required: a.postcode_required !== false,
                    state_required:    a.state_required    !== false,
                    phone_required:    a.phone_required    !== false,
                    company_required: !!a.company_required,
                    billing_required: !!a.billing_required,
                };
            },

            streetLineRange () {
                var n = this.addrCfg.street_lines;
                var arr = [];
                for (var i = 0; i < n; i++) arr.push(i);
                return arr;
            },

            showBillingForm () {
                return this.addrCfg.billing_required || this.differentBilling;
            },
        },

        mounted () {
            var self = this;

            // Trust the Vuex cart when it was updated within the last 5 s
            // (typical when navigating here directly from cart page). Saves
            // one full /api/v1/customer/cart round-trip on chat mount.
            var freshAge = Date.now() - (this.$store?.state?.cartUpdatedAt || 0);
            var cached   = this.$store?.state?.cart;

            var proceed = function (cart) {
                if (! cart || ! cart.items || ! cart.items.length) {
                    self.$toasted && self.$toasted.show(self.$t('Your cart is empty'), { type: 'info' });
                    self.$router.push({ name: 'cart' });
                    return;
                }
                self.cart = cart;
                self.fetchFujibaCfg();
                self.start();
                // Saved address is only needed when we reach the address
                // step, so we lazy-fetch it there. Chat mount no longer
                // pays for that round-trip up front.
            };

            if (cached && freshAge < 5000) {
                proceed(cached);
                return;
            }

            this.$http.get('/api/v1/customer/cart').then(function (resp) {
                var c = resp && resp.data && resp.data.data;
                EventBus.$emit('checkout.cart.changed', c);
                proceed(c);
            }).catch(function (e) {
                if (e && e.response && e.response.status === 401) {
                    // Guest cart works via session; fall through.
                    proceed({ items: [{}] }); // dummy non-empty so we don't bounce
                } else {
                    self.error = self.$t('Could not load cart. Please try again.');
                }
            });
        },

        beforeDestroy () {
            if (this._postcodeTimer) clearTimeout(this._postcodeTimer);
        },

        methods: {
            ...mapActions(['getCart']),

            // ── Per-channel config from Fujiba PwaFrontend ─────────────────
            fetchFujibaCfg () {
                var self = this;
                this.$http.get('/api/v1/pwa-frontend/config').then(function (r) {
                    if (r && r.data && r.data.data) {
                        self.fujibaCfg = r.data.data;
                        self.pickupEnabled = !!(self.fujibaCfg.pickup && self.fujibaCfg.pickup.enabled);
                    }
                }).catch(function () {});
            },

            fetchSavedAddress () {
                var self = this;
                if (!this.customer) return;
                this.$http.get('/api/v1/customer/addresses').then(function (r) {
                    var rows = (r && r.data && r.data.data) || [];
                    if (rows.length) {
                        self.savedAddress = rows[0];
                    }
                }).catch(function () {});
            },

            // ── Bubble helpers ────────────────────────────────────────────
            sleep (ms) { return new Promise(function(r){ setTimeout(r, ms); }); },

            async addBot (text, html) {
                this.typing = true;
                this.scrollEnd();
                await this.sleep(450);
                this.typing = false;
                this.bubbles.push({ from: 'bot', text: text, html: !!html });
                this.scrollEnd();
            },

            addUser (text) {
                this.bubbles.push({ from: 'user', text: text });
                this.scrollEnd();
            },

            scrollEnd () {
                this.$nextTick(() => {
                    var el = this.$refs.thread;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },

            // ── Step controllers ──────────────────────────────────────────
            async start () {
                var welcome = (this.fujibaCfg && this.fujibaCfg.chat && this.fujibaCfg.chat.welcome_message)
                    || this.$t('Hello! How would you like to receive your order?');
                await this.addBot(welcome);
            },

            chooseDeli (mode) {
                this.deliMode = mode;
                this.addUser(mode === 'pickup' ? '🏪 ' + this.$t('Store Pickup') : '🚚 ' + this.$t('Delivery'));
                this.step = 'address';
                if (mode === 'pickup') {
                    this.form.use_for_shipping = false;
                }
                this.addBot(this.$t('Please fill in your details'));

                // Lazy-fetch saved addresses now that we actually need them.
                // Mount no longer pays for this round-trip up front.
                if (! this._addressesFetched) {
                    this._addressesFetched = true;
                    this.fetchSavedAddress();
                }
            },

            // Postcode autofill
            onPostcodeInput () {
                if (this._postcodeTimer) clearTimeout(this._postcodeTimer);
                this.postcodeStatus = '';
                var clean = String(this.form.postcode || '').replace(/[^0-9]/g, '');
                if (clean.length !== 7) return;
                this._postcodeTimer = setTimeout(this.lookupPostcode, 350);
            },
            // Placeholder for street-line input N. Line 0 is required;
            // subsequent lines are optional unless admin marks the address as
            // strict, which we don't currently expose.
            streetPlaceholder (idx) {
                if (idx === 0) {
                    return this.$t('Street Address') + ' *';
                }
                return this.$t('Building / Room (optional)');
            },

            lookupPostcode () {
                var clean = String(this.form.postcode || '').replace(/[^0-9]/g, '');
                if (clean.length !== 7) { this.postcodeStatus = ''; return; }
                this.postcodeStatus = 'looking';
                var self = this;
                this.$http.get('/api/v1/postcode/' + clean).then(function (r) {
                    var d = r && r.data && r.data.data;
                    if (!d || !d.found) { self.postcodeStatus = 'notfound'; return; }
                    self.postcodeStatus = 'found';
                    if (!self.form.state) self.form.state = d.state;
                    if (!self.form.city)  self.form.city  = d.city;
                }).catch(function () { self.postcodeStatus = 'notfound'; });
            },

            enterNewAddress () {
                this.showAddrForm = true;
            },

            useSaved () {
                if (!this.savedAddress) return;
                this.form.address_id = this.savedAddress.id;
                this.form.first_name = this.savedAddress.first_name || '';
                this.form.last_name  = this.savedAddress.last_name  || '';
                this.form.email      = this.savedAddress.email      || (this.customer && this.customer.email) || '';
                this.form.phone      = this.savedAddress.phone      || '';
                this.form.postcode   = this.savedAddress.postcode   || '';
                this.form.state      = this.savedAddress.state      || '';
                this.form.city       = this.savedAddress.city       || '';
                this.form.company_name = this.savedAddress.company_name || '';
                var lines = Array.isArray(this.savedAddress.address)
                    ? this.savedAddress.address.slice()
                    : [(this.savedAddress.address1 || this.savedAddress.address || '')];
                while (lines.length < this.addrCfg.street_lines) lines.push('');
                this.form.address = lines;
                this.submitAddress();
            },

            async submitAddress () {
                if (!this.addressValid) return;
                this.busy = true; this.error = '';

                var summary = '📍 ' + (this.form.first_name || '') + ' · 〒' + (this.form.postcode || '');
                this.addUser(summary);

                // Build a clean address record with the shape Bagisto expects.
                // Address must be a non-empty array of street lines.
                var addressLines = (this.form.address || []).filter(function (s) {
                    return s && String(s).trim().length;
                });
                if (!addressLines.length) addressLines = [' '];

                var base = {
                    first_name: this.form.first_name,
                    last_name:  this.form.last_name,
                    company_name: this.form.company_name || '',
                    email:      this.form.email,
                    phone:      this.form.phone || '',
                    postcode:   this.form.postcode || '',
                    state:      this.form.state,
                    city:       this.form.city,
                    address:    addressLines,
                    country:    this.form.country || 'JP',
                    address_id: this.form.address_id || 0,
                    save_as_address: this.form.save_as_address || 1,
                };

                var payload;
                if (this.showBillingForm) {
                    var billingLines = (this.billingForm.address || []).filter(function (s) {
                        return s && String(s).trim().length;
                    });
                    if (!billingLines.length) billingLines = [' '];
                    var billing = {
                        first_name: this.billingForm.first_name,
                        last_name:  this.billingForm.last_name,
                        company_name: this.billingForm.company_name || '',
                        email:      this.billingForm.email || this.form.email,
                        phone:      this.billingForm.phone || this.form.phone || '',
                        postcode:   this.billingForm.postcode || '',
                        state:      this.billingForm.state,
                        city:       this.billingForm.city,
                        address:    billingLines,
                        country:    this.billingForm.country || 'JP',
                        use_for_shipping: false,
                    };
                    payload = { billing: billing, shipping: base };
                } else {
                    payload = {
                        billing:  Object.assign({}, base, { use_for_shipping: true }),
                        shipping: Object.assign({}, base),
                    };
                }

                var self = this;
                try {
                    var resp = await this.$http.post('/api/pwa/checkout/save-address', payload);
                    var data = resp && resp.data;

                    // address-done bubble (from admin chat config if set)
                    var doneMsg = (this.fujibaCfg && this.fujibaCfg.chat && this.fujibaCfg.chat.address_done_message)
                        || ('✓ ' + this.$t('Address confirmed'));
                    await this.addBot(doneMsg);

                    // Refresh cart so shipping/totals reflect the address
                    this.getCart();

                    console.log('[chat-checkout] save-address response:', data);

                    // Pickup → straight to payment (no shipping, no slot).
                    if (this.deliMode === 'pickup') {
                        this.shippingRates = [];
                        this.selectedShipping = null;
                        this.paymentMethods = (data && (data.methods || (data.methods && data.methods.payment_methods))) || [];
                        await this.goToPayment();
                        return;
                    }

                    // Virtual / downloadable cart → no shipping at all, but
                    // we still want the slot step for physical+virtual mixed
                    // carts. If the server skipped shipping (nextStep=payment)
                    // assume there's no shippable item and skip slot too.
                    if (data && data.nextStep === 'payment') {
                        this.shippingRates = [];
                        this.selectedShipping = null;
                        this.paymentMethods = (data && data.methods && data.methods.payment_methods) || data.methods || [];
                        await this.goToPayment();
                        return;
                    }

                    // Shipping rates returned in data.data.rates (each group has .rates[])
                    var rates = ((data && data.data && data.data.rates) || []).reduce(function (acc, group) {
                        return acc.concat(group.rates || []);
                    }, []);
                    this.shippingRates = rates;
                    console.log('[chat-checkout] shipping rates:', rates);

                    if (rates.length === 1) {
                        // Single rate: auto-select but still SHOW the slot picker
                        this.selectedShipping = rates[0].method;
                        await this.submitShipping();   // submits + transitions to slot
                    } else if (rates.length > 1) {
                        this.step = 'shipping';
                        await this.addBot(this.$t('Choose a shipping method'));
                    } else {
                        // No shipping rates → still ask for delivery slot (date/time)
                        // because Sagawa might still deliver via fallback. If the
                        // slot endpoint also returns nothing, the chat shows
                        // "No delivery slots available" and lets the user
                        // continue to payment.
                        this.step = 'slot';
                        await this.addBot(this.$t('When would you like it delivered?'));
                        await this.fetchSlots();
                        if (!this.slotDates.length) {
                            // Nothing the user can pick — fall through to payment.
                            await this.fetchPayments();
                            await this.goToPayment();
                        }
                    }
                } catch (e) {
                    var msg = '';
                    if (e && e.response) {
                        var d = e.response.data || {};
                        msg = d.message
                            || (d.errors && Object.values(d.errors).flat().join(', '))
                            || ('HTTP ' + e.response.status);
                        // Specific 400 hint: Bagisto's PWA controller aborts 400
                        // when no shipping rate matches the address. Suggest fix.
                        if (e.response.status === 400) {
                            msg = msg + ' — ' + this.$t('Check that all address fields are filled.');
                        }
                    }
                    this.error = msg || this.$t('Something went wrong');
                    console.warn('[chat-checkout] save-address failed:', e && e.response && e.response.data);
                } finally {
                    this.busy = false;
                }
            },

            async submitShipping () {
                if (!this.selectedShipping) return;
                this.busy = true; this.error = '';
                this.addUser('🚚 ' + this.selectedShipping.replace('_', ' '));

                try {
                    var resp = await this.$http.post('/api/v1/customer/checkout/save-shipping', { shipping_method: this.selectedShipping });
                    console.log('[chat-checkout] save-shipping response:', resp && resp.data);
                    this.getCart();
                    this.paymentMethods = ((resp && resp.data && resp.data.methods) || []);
                    await this.addBot('✓ ' + this.$t('Shipping selected'));

                    // After shipping, ask for delivery slot (Sagawa). Pickup
                    // mode skips this whole step.
                    if (this.deliMode === 'pickup') {
                        await this.goToPayment();
                    } else {
                        this.step = 'slot';
                        await this.addBot(this.$t('When would you like it delivered?'));
                        await this.fetchSlots();
                        console.log('[chat-checkout] slot data:', { dates: this.slotDates.length, times: Object.keys(this.slotTimes).length });
                    }
                } catch (e) {
                    console.warn('[chat-checkout] save-shipping failed:', e && e.response && e.response.data);
                    this.error = this.$t('Could not save shipping');
                } finally {
                    this.busy = false;
                }
            },

            // ── Delivery slot (Sagawa) ────────────────────────────────────
            async fetchSlots () {
                this.slotLoading  = true;
                this.slotDates    = [];
                this.slotTimes    = {};
                this.selectedDate = '';
                this.selectedTime = '';
                var pref = (this.form && this.form.state) || '';
                try {
                    var resp = await this.$http.get('/sagawa/delivery-slots' + (pref ? ('?prefecture=' + encodeURIComponent(pref)) : ''));
                    var data = resp && resp.data;
                    this.slotDates = (data && data.dates) || [];
                    this.slotTimes = (data && data.time_slots) || (window.SAGAWA_TIME_SLOTS) || {
                        am:    '午前中 (9:00-12:00)',
                        '1214':'12:00-14:00',
                        '1416':'14:00-16:00',
                        '1618':'16:00-18:00',
                        '1820':'18:00-20:00',
                        '1921':'19:00-21:00',
                    };
                    this.selectedDate = (data && data.earliest) || (this.slotDates[0] || '');
                } catch (e) {
                    // Endpoint missing or errored — let user skip slot entirely.
                    this.slotDates = [];
                } finally {
                    this.slotLoading = false;
                }
            },

            formatDate (iso) {
                try {
                    var d = new Date(iso + 'T00:00:00+09:00');
                    var wd = ['日','月','火','水','木','金','土'][d.getDay()];
                    return (d.getMonth()+1) + '/' + d.getDate() + ' (' + wd + ')';
                } catch (_) { return iso; }
            },

            async submitSlot () {
                this.busy = true; this.error = '';
                if (this.selectedDate || this.selectedTime) {
                    var label = (this.selectedDate ? this.formatDate(this.selectedDate) : '')
                              + (this.selectedTime && this.slotTimes[this.selectedTime] ? ' · ' + this.slotTimes[this.selectedTime] : '');
                    this.addUser('📅 ' + (label || this.$t('Any date / time')));
                } else {
                    this.addUser('📅 ' + this.$t('Any date / time'));
                }

                // Slot is held on the component (selectedDate / selectedTime)
                // and submitted with the order in placeOrder() below — no
                // separate cart-update endpoint exists for it.

                this.goToPayment();
                this.busy = false;
            },

            async goToPayment () {
                this.step = 'payment';
                if (!this.paymentMethods.length) {
                    await this.fetchPayments();
                }
                await this.addBot(this.$t('Please choose a payment method'));
            },

            // Fallback fetch in case save-address didn't include methods.
            // Bagisto has no public GET for payment methods, so we expose our
            // own (channel-allow-list aware) at /api/v1/pwa-frontend/payment-methods.
            async fetchPayments () {
                try {
                    var resp = await this.$http.get('/api/v1/pwa-frontend/payment-methods');
                    var rows = (resp && resp.data && resp.data.data && resp.data.data.methods) || [];
                    this.paymentMethods = rows;
                } catch (_) {}
            },

            paymentLabel (code) {
                var map = {
                    cashondelivery: this.$t('Cash on Delivery'),
                    moneytransfer:  this.$t('Bank Transfer'),
                    paypal_smart_button: 'PayPal',
                    paypay: 'PayPay',
                    komoju: 'Komoju',
                    card:   this.$t('Credit Card'),
                };
                return map[code] || code;
            },
            paymentIcon (code) {
                var map = { cashondelivery: '💵', moneytransfer: '🏦', paypal_smart_button: '🅿', paypay: '📱', komoju: '💳', card: '💳' };
                return map[code] || '💳';
            },

            async submitPayment () {
                if (!this.selectedPayment) return;
                this.busy = true; this.error = '';
                this.addUser(this.paymentIcon(this.selectedPayment) + ' ' + this.paymentLabel(this.selectedPayment));

                try {
                    await this.$http.post('/api/v1/customer/checkout/save-payment', {
                        payment: { method: this.selectedPayment }
                    });
                    this.getCart();
                    var doneMsg = (this.fujibaCfg && this.fujibaCfg.chat && this.fujibaCfg.chat.payment_done_message)
                        || ('✓ ' + this.$t('Payment selected'));
                    await this.addBot(doneMsg);
                    this.step = 'review';
                    var conf = (this.fujibaCfg && this.fujibaCfg.chat && this.fujibaCfg.chat.confirm_message)
                        || this.$t('Please review your order');
                    await this.addBot(conf);
                } catch (e) {
                    this.error = this.$t('Could not save payment');
                } finally {
                    this.busy = false;
                }
            },

            async placeOrder () {
                this.busy = true; this.error = '';
                this.addUser('✓ ' + this.$t('Place Order'));
                this.step = 'done';

                // Pass the chosen delivery slot through with the order. The
                // server-side Sagawa listener can read these from the request
                // payload and stash them on the order. Optional — backend may
                // ignore them safely.
                var orderPayload = {};
                if (this.selectedDate) orderPayload.delivery_date = this.selectedDate;
                if (this.selectedTime) orderPayload.delivery_time = this.selectedTime;

                try {
                    var resp = await this.$http.post('/api/v1/customer/checkout/save-order', orderPayload);
                    if (resp && resp.data && resp.data.order) {
                        this.$router.push({ name: 'order-success', params: { id: resp.data.order.id } });
                    } else {
                        this.$router.push({ name: 'order-success' });
                    }
                } catch (e) {
                    this.busy = false;
                    this.step = 'review';
                    var msg = '';
                    if (e && e.response) {
                        var d = e.response.data || {};
                        msg = d.message
                            || (d.errors && Object.values(d.errors).flat().join(', '))
                            || ('HTTP ' + e.response.status);
                    }
                    this.error = msg || this.$t('Order failed');
                    console.warn('[chat-checkout] save-order failed:', e && e.response && e.response.data);
                }
            },

            restart () {
                this.bubbles = [];
                this.step = 'deli';
                this.selectedShipping = null;
                this.selectedPayment = null;
                this.error = '';
                this.start();
            },

            goBack () {
                if (this.step === 'deli') {
                    this.$router.push({ name: 'cart' });
                } else if (this.step === 'address') {
                    this.step = 'deli';
                } else if (this.step === 'shipping') {
                    this.step = 'address';
                } else if (this.step === 'slot') {
                    this.step = this.shippingRates.length > 1 ? 'shipping' : 'address';
                } else if (this.step === 'payment') {
                    if (this.deliMode === 'pickup' || !this.slotDates.length) {
                        this.step = this.shippingRates.length > 1 ? 'shipping' : 'address';
                    } else {
                        this.step = 'slot';
                    }
                } else if (this.step === 'review') {
                    this.step = 'payment';
                }
            },
        },
    }
</script>

<style lang="scss" scoped>
    .chat-checkout {
        background: #faf8f5;
        padding-bottom: 24px;
        display: block;
    }

    .chat-header {
        position: sticky;
        top: 0;
        background: #fff;
        border-bottom: 1px solid #e2d5c8;
        padding: 10px 12px;
        display: flex; align-items: center; gap: 10px;
        z-index: 50;
    }
    .chat-back {
        width: 34px; height: 34px; border-radius: 8px;
        background: #f2ede8; border: 0; font-size: 18px; cursor: pointer;
    }
    .chat-title-wrap { flex: 1; }
    .chat-title { font-size: 15px; font-weight: 700; }
    .chat-substeps { font-size: 10px; color: #9a7a5a; margin-top: 2px; }
    .chat-total { font-size: 15px; font-weight: 700; color: var(--p, #c8420a); }

    .chat-wrap {
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .bubble { display: flex; flex-direction: column; }
    .bubble.user { align-items: flex-end; }
    .bubble.bot  { align-items: flex-start; }
    .bot-avatar {
        width: 28px; height: 28px; border-radius: 50%;
        background: var(--dark, #1a0800); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; margin-bottom: 4px;
    }
    .bubble-msg {
        max-width: 85%;
        padding: 10px 14px;
        font-size: 13px;
        line-height: 1.5;
        animation: bubblePop 0.22s cubic-bezier(.34,1.56,.64,1) both;
    }
    .bot-msg {
        background: #f2ede8;
        color: #1a0800;
        border-radius: 4px 18px 18px 18px;
        border: 1px solid #e2d5c8;
    }
    .user-msg {
        background: var(--p, #c8420a);
        color: #fff;
        border-radius: 18px 4px 18px 18px;
    }
    @keyframes bubblePop {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .typing { display: flex; gap: 4px; padding: 12px 14px; }
    .typing-dot {
        width: 7px; height: 7px; border-radius: 50%; background: #9a7a5a;
        animation: tdot 1.2s infinite ease-in-out;
    }
    .typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes tdot { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }

    .chat-input {
        padding: 12px;
        background: #fff;
        border-top: 1px solid #e2d5c8;
    }

    .deli-opts { display: flex; gap: 8px; }
    .deli-opt {
        flex: 1; padding: 14px 10px;
        border: 2px solid #e2d5c8; border-radius: 12px;
        text-align: center; cursor: pointer;
        background: #fff; transition: 0.18s;
    }
    .deli-opt:hover { border-color: var(--p, #c8420a); background: rgba(200, 66, 10, 0.04); }
    .deli-ic   { font-size: 24px; margin-bottom: 4px; }
    .deli-name { font-size: 13px; font-weight: 700; }

    .saved-card {
        background: rgba(200, 66, 10, 0.04);
        border: 2px solid var(--p, #c8420a);
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        cursor: pointer;
    }
    .saved-head { display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; color: var(--p, #c8420a); margin-bottom: 6px; }
    .saved-edit { color: #9a7a5a; font-weight: 500; }
    .saved-text { font-size: 13px; line-height: 1.5; color: #5a3a1a; }
    .addr-or {
        display: flex; align-items: center; gap: 8px;
        font-size: 10px; color: #9a7a5a; font-weight: 600;
        text-transform: uppercase; margin-bottom: 8px;
    }
    .addr-or::before, .addr-or::after { content: ''; flex: 1; height: 1px; background: #e2d5c8; }

    .addr-form { display: flex; flex-direction: column; gap: 8px; }
    .addr-row  { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .addr-in {
        height: 44px;
        border: 1.5px solid #e2d5c8;
        border-radius: 10px;
        padding: 0 12px;
        font-size: 14px;
        background: #fff;
        outline: none;
        transition: border-color 0.18s;
    }
    .addr-in:focus { border-color: var(--p, #c8420a); }
    .addr-in[readonly] { background: #faf8f5; color: #5a3a1a; }
    .addr-hint { font-size: 11px; color: #9a7a5a; margin: -4px 0; }
    .addr-hint.err { color: #c8220a; }
    .addr-country {
        font-size: 11px; color: #9a7a5a;
        padding: 6px 0;
    }

    .saved-pick { display: flex; flex-direction: column; gap: 10px; }
    .saved-actions {
        display: flex; gap: 8px;
    }
    .saved-actions .chat-cta { flex: 1.4; }
    .saved-actions .chat-cta-ghost { flex: 1; padding: 12px; font-size: 12px; }

    .addr-billing-toggle { padding: 6px 0; }
    .addr-bill-row {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; color: #5a3a1a; font-weight: 600;
        cursor: pointer;
    }
    .addr-bill-row input[type="checkbox"] {
        width: 18px; height: 18px; accent-color: var(--p, #c8420a);
    }
    .addr-billing-form {
        display: flex; flex-direction: column; gap: 8px;
        padding: 10px 0 4px;
        border-top: 1px dashed #e2d5c8;
    }
    .addr-section-title {
        font-size: 11px; font-weight: 700; color: #5a3a1a;
        text-transform: uppercase; letter-spacing: 0.05em;
        margin-bottom: 4px;
    }

    .opts { display: flex; flex-direction: column; gap: 8px; }
    .opt-card {
        display: flex; align-items: center; gap: 10px;
        padding: 12px;
        background: #fff;
        border: 2px solid #e2d5c8;
        border-radius: 12px;
        cursor: pointer;
        transition: 0.18s;
    }
    .opt-card.sel { border-color: var(--p, #c8420a); background: rgba(200, 66, 10, 0.04); }
    .opt-radio {
        width: 18px; height: 18px; border-radius: 50%;
        border: 2px solid #e2d5c8;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; line-height: 1; color: var(--p, #c8420a);
    }
    .opt-card.sel .opt-radio { border-color: var(--p, #c8420a); }
    .opt-info { flex: 1; }
    .opt-name { font-size: 13px; font-weight: 700; }
    .opt-desc { font-size: 11px; color: #9a7a5a; margin-top: 2px; }
    .opt-price { font-size: 13px; font-weight: 700; color: var(--p, #c8420a); }
    .opt-ic { font-size: 22px; }

    .slot-step { display: flex; flex-direction: column; gap: 8px; }
    .slot-loading, .slot-empty {
        font-size: 12px; color: #9a7a5a; padding: 10px 0;
    }
    .slot-empty { color: #c8220a; }
    .slot-label {
        font-size: 11px; font-weight: 700; color: #5a3a1a;
        text-transform: uppercase; letter-spacing: 0.05em;
        margin-top: 4px;
    }
    .slot-row { display: flex; flex-wrap: wrap; gap: 6px; }
    .slot-chip {
        padding: 10px 16px;
        background: #fff;
        border: 1.5px solid #e2d5c8;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 600;
        color: #5a3a1a;
        cursor: pointer;
        transition: 0.18s;
    }
    .slot-chip.sel {
        background: var(--p, #c8420a) !important;
        border-color: var(--p, #c8420a) !important;
        color: #fff !important;
    }

    .review-card {
        background: #faf8f5;
        border: 1px solid #e2d5c8;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
    }
    .review-row {
        display: flex; justify-content: space-between;
        padding: 6px 0; font-size: 13px; color: #5a3a1a;
        border-bottom: 1px solid #e2d5c8;
    }
    .review-row:last-child { border: 0; }
    .review-row.tot { font-weight: 700; font-size: 15px; color: #1a0800; padding-top: 10px; }
    .review-row.tot span:last-child { color: var(--p, #c8420a); font-size: 17px; }
    .review-actions { display: flex; gap: 8px; }

    .chat-cta {
        width: 100%;
        padding: 14px;
        background: var(--p, #c8420a) !important;
        color: #fff !important;
        border: 0 !important;
        border-radius: var(--br, 12px);
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: filter 0.15s, transform 0.1s;
        opacity: 1;
        -webkit-appearance: none;
        appearance: none;
    }
    .chat-cta:hover    { filter: brightness(1.08); }
    .chat-cta:active   { transform: scale(0.98); }
    .chat-cta:disabled { opacity: 0.5; cursor: not-allowed; filter: none; }

    .chat-cta-ghost {
        flex: 1;
        padding: 14px;
        background: #f2ede8;
        color: #5a3a1a;
        border: 0;
        border-radius: var(--br, 12px);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .review-actions .chat-cta { flex: 2; }

    .done-step {
        text-align: center;
        padding: 28px 16px;
    }
    .done-spinner {
        width: 36px; height: 36px;
        border: 3px solid #e2d5c8;
        border-top-color: var(--p, #c8420a);
        border-radius: 50%;
        margin: 0 auto 12px;
        animation: spinDot 0.8s linear infinite;
    }
    @keyframes spinDot { to { transform: rotate(360deg); } }
    .done-msg { font-size: 13px; color: #5a3a1a; }

    .chat-err {
        margin-top: 8px;
        padding: 8px 12px;
        background: rgba(200, 34, 10, 0.08);
        border: 1px solid #c8220a;
        border-radius: 8px;
        color: #c8220a;
        font-size: 12px;
    }

    @media (min-width: 1024px) {
        .chat-checkout {
            max-width: 480px;
            margin: 0 auto;
            box-shadow: 0 0 24px rgba(0,0,0,0.04);
        }
    }
</style>
