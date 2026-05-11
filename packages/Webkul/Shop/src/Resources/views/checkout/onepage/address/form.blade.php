@php
    /**
     * Messenger-onboarded customers (chatpion_subscribe_id present) lack a
     * real email/postcode/phone — they're synthetic accounts created from a
     * Facebook subscriber. Drive the relax off the customer flag, not the
     * channel code, because on Fujiba the kimono channel ships under code
     * `default` and a name-gate (`['mmfood','kimono']`) misses it. Keep the
     * legacy channel list as a guest-mode fallback for mmfood storefronts.
     */
    $authCustomer = auth('customer')->user();

    $channelRelaxesByCode = in_array(
        optional(core()->getCurrentChannel())->code,
        ['mmfood', 'kimono'],
        true
    );

    $hasSocialLogin = $authCustomer
        ? \DB::table('customer_social_accounts')->where('customer_id', $authCustomer->id)->exists()
        : false;

    $isMessengerOptional = $authCustomer && ! empty($authCustomer->chatpion_subscribe_id);

    // Email optional when social-login or messenger-onboarded.
    $emailOptional = $hasSocialLogin || $isMessengerOptional;

    // Phone/postcode relax: messenger customer (any channel) or guest on mmfood-like channel.
    $channelRelaxes = $isMessengerOptional || $channelRelaxesByCode;

    // Postcode required only when the channel does not relax it.
    $postcodeRequired = core()->isPostCodeRequired() && ! $channelRelaxes;
@endphp

@pushOnce('styles')
<style>
/* Address form improvements */
.addr-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 768px) { .addr-form-grid { grid-template-columns: 1fr; gap: 10px; } }

.addr-field { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
.addr-field label { font-size: 12px; font-weight: 500; color: #64748b; }
.addr-field label.required::after { content: ' *'; color: #ef4444; }
.addr-field input, .addr-field select {
    width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 14px; color: #1e293b; background: #f8fafc; transition: border-color 0.15s, background 0.15s;
    outline: none; appearance: none;
}
.addr-field input:focus, .addr-field select:focus {
    border-color: #3d94fd; background: #fff; box-shadow: 0 0 0 3px rgba(61,148,253,0.12);
}
.addr-field .addr-error { font-size: 11px; color: #ef4444; min-height: 16px; }

/* Postcode lookup */
.postcode-wrap { position: relative; }
.postcode-hint { font-size: 11px; color: #94a3b8; margin-top: 3px; }
.postcode-filling { animation: pulse 1s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }

/* Section label */
.addr-section-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
    color: #94a3b8; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; margin-bottom: 12px; margin-top: 8px;
}
</style>
@endPushOnce

@pushOnce('scripts')
    <script type="text/x-template" id="v-checkout-address-form-template">
        <div class="mt-2 max-md:mt-3">

            <!-- Hidden ID -->
            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control type="text" ::name="controlName + '.id'" ::value="address.id" />
            </x-shop::form.control-group>

            <!-- ── Name ── -->
            <div class="addr-form-grid">
                <div class="addr-field">
                    <label class="required">@lang('shop::app.checkout.onepage.address.first-name')</label>
                    <x-shop::form.control-group.control
                        type="text"
                        ::name="controlName + '.first_name'"
                        ::value="address.first_name"
                        rules="required"
                        :label="trans('shop::app.checkout.onepage.address.first-name')"
                        :placeholder="trans('shop::app.checkout.onepage.address.first-name')"
                    />
                    <x-shop::form.control-group.error ::name="controlName + '.first_name'" />
                </div>
                <div class="addr-field">
                    <label class="required">@lang('shop::app.checkout.onepage.address.last-name')</label>
                    <x-shop::form.control-group.control
                        type="text"
                        ::name="controlName + '.last_name'"
                        ::value="address.last_name"
                        rules="required"
                        :label="trans('shop::app.checkout.onepage.address.last-name')"
                        :placeholder="trans('shop::app.checkout.onepage.address.last-name')"
                    />
                    <x-shop::form.control-group.error ::name="controlName + '.last_name'" />
                </div>
            </div>

            <!-- ── Email ── -->
            <div class="addr-field">
                <label {!! $emailOptional ? '' : 'class="required"' !!}>@lang('shop::app.checkout.onepage.address.email'){!! $emailOptional ? ' <span style="font-size:11px;color:#94a3b8;font-weight:400">(任意 / Optional)</span>' : '' !!}</label>
                <x-shop::form.control-group.control
                    type="email"
                    ::name="controlName + '.email'"
                    ::value="address.email"
                    rules="{{ $emailOptional ? 'email' : 'required|email' }}"
                    :label="trans('shop::app.checkout.onepage.address.email')"
                    placeholder="email@example.com"
                />
                <x-shop::form.control-group.error ::name="controlName + '.email'" />
            </div>

            <!-- ── Phone (optional) ── -->
            <div class="addr-field">
                <label>@lang('shop::app.checkout.onepage.address.telephone') <span style="font-size:11px;color:#94a3b8;font-weight:400">(任意 / Optional)</span></label>
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.phone'"
                    ::value="address.phone"
                    rules="phone"
                    :label="trans('shop::app.checkout.onepage.address.telephone')"
                    :placeholder="trans('shop::app.checkout.onepage.address.telephone')"
                />
                <x-shop::form.control-group.error ::name="controlName + '.phone'" />
            </div>

            <!-- ── Address ── -->
            <p class="addr-section-title">@lang('shop::app.checkout.onepage.address.title')</p>

            <!-- Postcode → auto-fill -->
            <div class="addr-form-grid" style="align-items: start">
                <div class="addr-field">
                    <label class="{{ $postcodeRequired ? 'required' : '' }}">
                        @lang('shop::app.checkout.onepage.address.postcode')
                        @if ($isMessengerOptional)
                            <span style="font-size:11px;color:#94a3b8;font-weight:400;margin-left:4px">(任意 / Optional)</span>
                        @endif
                        <span style="font-size:10px;color:#94a3b8;font-weight:400;margin-left:4px">→ 住所自動入力</span>
                    </label>
                    <div class="postcode-wrap">
                        <x-shop::form.control-group.control
                            type="text"
                            ::name="controlName + '.postcode'"
                            ::value="address.postcode"
                            rules="{{ $postcodeRequired ? 'required|postcode' : 'postcode' }}"
                            :label="trans('shop::app.checkout.onepage.address.postcode')"
                            placeholder="〒 123-4567"
                            v-model="localPostcode"
                            @input="onPostcodeInput"
                        />
                        <p class="postcode-hint" v-if="postcodeStatus === 'loading'">🔍 検索中…</p>
                        <p class="postcode-hint" style="color:#22c55e" v-if="postcodeStatus === 'ok'">✓ 住所を自動入力しました</p>
                        <p class="postcode-hint" style="color:#ef4444" v-if="postcodeStatus === 'error'">郵便番号が見つかりません</p>
                    </div>
                    <x-shop::form.control-group.error ::name="controlName + '.postcode'" />
                </div>

                <!-- Country (Japan pre-selected) -->
                <div class="addr-field">
                    <label class="{{ core()->isCountryRequired() ? 'required' : '' }}">@lang('shop::app.checkout.onepage.address.country')</label>
                    <x-shop::form.control-group.control
                        type="select"
                        ::name="controlName + '.country'"
                        ::value="address.country"
                        v-model="selectedCountry"
                        rules="{{ core()->isCountryRequired() ? 'required' : '' }}"
                        :label="trans('shop::app.checkout.onepage.address.country')"
                    >
                        <option value="">@lang('shop::app.checkout.onepage.address.select-country')</option>
                        <option v-for="country in countries" :value="country.code">@{{ country.name }}</option>
                    </x-shop::form.control-group.control>
                    <x-shop::form.control-group.error ::name="controlName + '.country'" />
                </div>
            </div>

            <!-- Prefecture / State -->
            <div class="addr-field">
                <label class="{{ core()->isStateRequired() ? 'required' : '' }}">@lang('shop::app.checkout.onepage.address.state')</label>
                <template v-if="states">
                    <template v-if="haveStates">
                        <x-shop::form.control-group.control
                            type="select"
                            ::name="controlName + '.state'"
                            rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                            ::value="address.state"
                            v-model="localState"
                            :label="trans('shop::app.checkout.onepage.address.state')"
                        >
                            <option value="">@lang('shop::app.checkout.onepage.address.select-state')</option>
                            <option v-for="(state, index) in states[selectedCountry]" :value="state.code">@{{ state.default_name }}</option>
                        </x-shop::form.control-group.control>
                    </template>
                    <template v-else>
                        <x-shop::form.control-group.control
                            type="text"
                            ::name="controlName + '.state'"
                            ::value="address.state"
                            rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                            v-model="localState"
                            :label="trans('shop::app.checkout.onepage.address.state')"
                            :placeholder="trans('shop::app.checkout.onepage.address.state')"
                        />
                    </template>
                </template>
                <x-shop::form.control-group.error ::name="controlName + '.state'" />
            </div>

            <!-- City -->
            <div class="addr-field">
                <label class="required">@lang('shop::app.checkout.onepage.address.city')</label>
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.city'"
                    ::value="address.city"
                    rules="required"
                    v-model="localCity"
                    :label="trans('shop::app.checkout.onepage.address.city')"
                    :placeholder="trans('shop::app.checkout.onepage.address.city')"
                />
                <x-shop::form.control-group.error ::name="controlName + '.city'" />
            </div>

            <!-- Street (admin street_lines drives count; first line required) -->
            @php
                $streetLines = (int) (core()->getConfigData('customer.address.information.street_lines') ?? 2);
                if ($streetLines < 1) { $streetLines = 1; }
                if ($streetLines > 4) { $streetLines = 4; }
            @endphp
            <div class="addr-field">
                <label class="required">@lang('shop::app.checkout.onepage.address.street-address')</label>
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.address.[0]'"
                    ::value="address.address[0]"
                    rules="required|address"
                    v-model="localStreet"
                    :label="trans('shop::app.checkout.onepage.address.street-address')"
                    :placeholder="trans('shop::app.checkout.onepage.address.street-address')"
                />
                <x-shop::form.control-group.error class="mb-2" ::name="controlName + '.address.[0]'" />
            </div>

            @for ($i = 1; $i < $streetLines; $i++)
                <div class="addr-field">
                    <x-shop::form.control-group.control
                        type="text"
                        ::name="controlName + '.address.[{{ $i }}]'"
                        ::value="address.address[{{ $i }}] || ''"
                        :label="trans('shop::app.checkout.onepage.address.street-address')"
                        :placeholder="trans('shop::app.checkout.onepage.address.street-address')"
                    />
                </div>
            @endfor

        </div>
    </script>

    <script type="module">
        app.component('v-checkout-address-form', {
            template: '#v-checkout-address-form-template',

            props: {
                controlName: { type: String, required: true },
                address: {
                    type: Object,
                    default: () => ({
                        id: 0, company_name: '', first_name: '', last_name: '',
                        email: '', address: [], country: 'JP', state: '', city: '', postcode: '', phone: '',
                    }),
                },
            },

            data() {
                return {
                    selectedCountry: this.address.country || 'JP',
                    localPostcode: this.address.postcode || '',
                    localState: this.address.state || '',
                    localCity: this.address.city || '',
                    localStreet: (this.address.address && this.address.address[0]) || '',
                    countries: [],
                    states: null,
                    postcodeStatus: null, // null | 'loading' | 'ok' | 'error'
                    postcodeTimer: null,
                }
            },

            computed: {
                haveStates() {
                    return !!this.states[this.selectedCountry]?.length;
                },
            },

            mounted() {
                this.getCountries();
                this.getStates();
            },

            methods: {
                getCountries() {
                    this.$axios.get("{{ route('shop.api.core.countries') }}")
                        .then(response => { this.countries = response.data.data; })
                        .catch(() => {});
                },
                getStates() {
                    this.$axios.get("{{ route('shop.api.core.states') }}")
                        .then(response => { this.states = response.data.data; })
                        .catch(() => {});
                },

                onPostcodeInput(event) {
                    clearTimeout(this.postcodeTimer);
                    // Read directly from DOM event — avoids VeeValidate update-timing issues
                    // so both "1160014" (no hyphen) and "116-0014" (with hyphen) are handled correctly
                    const inputVal = (event && event.target ? event.target.value : this.localPostcode) || '';
                    const raw = inputVal.replace(/[^0-9]/g, '');

                    // Auto-format to NNN-NNNN once 7 digits are present
                    if (raw.length === 7 && event && event.target && !inputVal.includes('-')) {
                        const formatted = raw.slice(0, 3) + '-' + raw.slice(3);
                        event.target.value = formatted;
                        this.localPostcode = formatted;
                    }

                    if (raw.length !== 7) { this.postcodeStatus = null; return; }
                    this.postcodeStatus = 'loading';
                    this.postcodeTimer = setTimeout(() => this.lookupPostcode(raw), 400);
                },

                async lookupPostcode(code) {
                    try {
                        const res = await fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${code}`);
                        const data = await res.json();
                        if (data.status === 200 && data.results && data.results.length > 0) {
                            const r = data.results[0];
                            // Use the Japanese prefecture name directly (address1 = 東京都 / 大阪府 etc.)
                            // Bagisto has no rows in country_states for JP, so the state field
                            // renders as a free-text input — we must write the human-readable name.
                            this.localState  = r.address1;          // 都道府県
                            this.localCity   = r.address2;          // 市区町村
                            this.localStreet = r.address3 || '';    // 大字・丁目
                            this.selectedCountry = 'JP';
                            this.postcodeStatus = 'ok';
                        } else {
                            this.postcodeStatus = 'error';
                        }
                    } catch (e) {
                        this.postcodeStatus = 'error';
                    }
                },
            },
        });
    </script>
@endPushOnce
