@php
    // Channel-agnostic: messenger-onboarded customers lack real email/postcode/phone.
    // Fujiba's kimono channel is coded `default`, so a channel-name gate misses it.
    $authCustomer = auth('customer')->user();
    $isMessengerOptional = $authCustomer && ! empty($authCustomer->chatpion_subscribe_id);
    $emailOptional    = $isMessengerOptional;
    $postcodeRequired = core()->isPostCodeRequired() && ! $isMessengerOptional;
    $phoneRequired    = ! $isMessengerOptional;
@endphp
<x-shop::layouts.account>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.customers.account.addresses.create.add-address')
    </x-slot>

    <!-- Breadcrumbs -->
    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        @section('breadcrumbs')
            <x-shop::breadcrumbs name="addresses.create" />
        @endSection
    @endif

    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <div class="mx-4 flex-auto max-md:mx-6 max-sm:mx-4">
        <div class="mb-8 flex items-center max-md:mb-5">
            <!-- Back Button -->
            <a
                class="grid md:hidden"
                href="{{ route('shop.customers.account.addresses.index') }}"
            >
                <span class="icon-arrow-left rtl:icon-arrow-right text-2xl"></span>
            </a>

            <h2 class="text-2xl font-medium max-md:text-xl max-sm:text-base ltr:ml-2.5 md:ltr:ml-0 rtl:mr-2.5 md:rtl:mr-0">
                @lang('shop::app.customers.account.addresses.create.add-address')
            </h2>
        </div>

        <v-create-customer-address>
            <!--Address Shimmer-->
            <x-shop::shimmer.form.control-group :count="10" />
        </v-create-customer-address>

    </div>

    @push('scripts')
        <script
            type="text/x-template"
            id="v-create-customer-address-template"
        >
            <div>
                <x-shop::form :action="route('shop.customers.account.addresses.store')">
                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.before') !!}

                    <!--Company Name -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label>
                            @lang('shop::app.customers.account.addresses.create.company-name')
                        </x-shop::form.control-group.label>
            
                        <x-shop::form.control-group.control
                            type="text"
                            name="company_name"
                            :value="old('company_name')"
                            :label="trans('shop::app.customers.account.addresses.create.company-name')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.company-name')"
                        />
            
                        <x-shop::form.control-group.error control-name="company_name" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.company_name.after') !!}

                    <!-- First Name -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.account.addresses.create.first-name')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="first_name"
                            rules="required"
                            :value="old('first_name')"
                            :label="trans('shop::app.customers.account.addresses.create.first-name')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.first-name')"
                        />

                        <x-shop::form.control-group.error control-name="first_name" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.first_name.after') !!}

                    <!-- Last Name  -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.account.addresses.create.last-name')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="last_name"
                            rules="required"
                            :value="old('last_name')"
                            :label="trans('shop::app.customers.account.addresses.create.last-name')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.last-name')"
                        />

                        <x-shop::form.control-group.error control-name="last_name" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.last_name.after') !!}

                    <!-- E-mail -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="{{ $emailOptional ? '' : 'required' }}">
                            @lang('shop::app.customers.account.addresses.create.email')
                            @if ($emailOptional)
                                <span style="font-size:11px;color:#94a3b8;font-weight:400;margin-left:4px">(任意 / Optional)</span>
                            @endif
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="email"
                            name="email"
                            rules="{{ $emailOptional ? 'email' : 'required|email' }}"
                            :value="old('email')"
                            :label="trans('shop::app.customers.account.addresses.create.email')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.email')"
                        />

                        <x-shop::form.control-group.error control-name="email" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.email.after') !!}

                    <!-- Vat Id -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label>
                            @lang('shop::app.customers.account.addresses.create.vat-id')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="vat_id"
                            :value="old('vat_id')"
                            :label="trans('shop::app.customers.account.addresses.create.vat-id')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.vat-id')"
                        />

                        <x-shop::form.control-group.error control-name="vat_id" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.vat_id.after') !!}

                    <!-- Street Address -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.account.addresses.create.street-address')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="address[]"
                            rules="required|address"
                            v-model="street"
                            :label="trans('shop::app.customers.account.addresses.create.street-address')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.street-address')"
                        />

                        <x-shop::form.control-group.error control-name="address[]" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.street_address.after') !!}

                    @if (
                        core()->getConfigData('customer.address.information.street_lines')
                        && core()->getConfigData('customer.address.information.street_lines') > 1
                    )
                        @for ($i = 1; $i < core()->getConfigData('customer.address.information.street_lines'); $i++)
                            <x-shop::form.control-group.control
                                type="text"
                                name="address[{{ $i }}]"
                                :value="old('address[{{ $i }}]')"
                                rules="address"
                                :label="trans('shop::app.customers.account.addresses.create.street-address')"
                                :placeholder="trans('shop::app.customers.account.addresses.create.street-address')"
                            />

                            <x-shop::form.control-group.error
                                class="mb-2"
                                name="address[{{ $i }}]"
                            />
                        @endfor
                    @endif

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.street_address.after') !!}

                    <!-- Country List-->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="{{ core()->isCountryRequired() ? 'required' : '' }}">
                            @lang('shop::app.customers.account.addresses.create.country')
                        </x-shop::form.control-group.label>
            
                        <x-shop::form.control-group.control
                            type="select"
                            name="country"
                            rules="{{ core()->isCountryRequired() ? 'required' : '' }}"
                            v-model="country"
                            :aria-label="trans('shop::app.customers.account.addresses.create.country')"
                            :label="trans('shop::app.customers.account.addresses.create.country')"
                        >
                            <option value="">
                                @lang('shop::app.customers.account.addresses.create.select-country')
                            </option>
            
                            @foreach (core()->countries() as $country)
                                <option value="{{ $country->code }}">{{ $country->name }}</option>
                            @endforeach
                        </x-shop::form.control-group.control>
            
                        <x-shop::form.control-group.error control-name="country" />
                    </x-shop::form.control-group>
        
                    <!-- State Name -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="{{ core()->isStateRequired() ? 'required' : '' }}">
                            @lang('shop::app.customers.account.addresses.create.state')
                        </x-shop::form.control-group.label>
        
                        <template v-if="haveStates()">
                            <x-shop::form.control-group.control
                                type="select"
                                id="state"
                                name="state"
                                rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                                v-model="state"
                                :label="trans('shop::app.customers.account.addresses.create.state')"
                                :placeholder="trans('shop::app.customers.account.addresses.create.state')"
                            >
                                <option 
                                    v-for='(state, index) in countryStates[country]'
                                    :value="state.code"
                                >
                                    @{{ state.default_name }}
                                </option>
                            </x-shop::form.control-group.control>
                        </template>
        
                        <template v-else>
                            <x-shop::form.control-group.control
                                type="text"
                                name="state"
                                v-model="state"
                                rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                                :label="trans('shop::app.customers.account.addresses.create.state')"
                                :placeholder="trans('shop::app.customers.account.addresses.create.state')"
                            />
                        </template>
        
                        <x-shop::form.control-group.error control-name="state" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.state.after') !!}

                    <!-- City -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.account.addresses.create.city')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="city"
                            rules="required"
                            v-model="city"
                            :label="trans('shop::app.customers.account.addresses.create.city')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.city')"
                        />

                        <x-shop::form.control-group.error control-name="city" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.city.after') !!}

                    <!-- Post Code -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="{{ $postcodeRequired ? 'required' : '' }}">
                            @lang('shop::app.customers.account.addresses.create.post-code')
                            @if ($isMessengerOptional)
                                <span style="font-size:11px;color:#94a3b8;font-weight:400;margin-left:4px">(任意 / Optional)</span>
                            @endif
                            <span style="font-size:10px;color:#94a3b8;font-weight:400;margin-left:4px">&rarr; 住所自動入力</span>
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="postcode"
                            rules="{{ $postcodeRequired ? 'required|postcode' : 'postcode' }}"
                            v-model="postcode"
                            @input="onPostcodeInput"
                            :label="trans('shop::app.customers.account.addresses.create.post-code')"
                            placeholder="〒 123-4567"
                        />
                        <p style="font-size:11px;color:#94a3b8;margin-top:3px" v-if="postcodeStatus==='loading'">🔍 検索中…</p>
                        <p style="font-size:11px;color:#22c55e;margin-top:3px" v-if="postcodeStatus==='ok'">✓ 住所を自動入力しました</p>
                        <p style="font-size:11px;color:#ef4444;margin-top:3px" v-if="postcodeStatus==='error'">郵便番号が見つかりません</p>

                        <x-shop::form.control-group.error control-name="postcode" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.postcode.after') !!}

                    <!-- Contact -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="{{ $phoneRequired ? 'required' : '' }}">
                            @lang('shop::app.customers.account.addresses.create.phone')
                            @if ($isMessengerOptional)
                                <span style="font-size:11px;color:#94a3b8;font-weight:400;margin-left:4px">(任意 / Optional)</span>
                            @endif
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="phone"
                            rules="{{ $phoneRequired ? 'required|phone' : 'phone' }}"
                            :value="old('phone')"
                            :label="trans('shop::app.customers.account.addresses.create.phone')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.phone')"
                        />

                        <x-shop::form.control-group.error control-name="phone" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.phone.after') !!}

                    <!-- Set As Default -->
                    <div class="text-md mb-4 flex select-none items-center gap-x-1.5 text-zinc-500">
                        <input
                            type="checkbox"
                            name="default_address"
                            value="1"
                            id="default_address"
                            class="peer hidden cursor-pointer"
                        >

                        <label
                            class="icon-uncheck peer-checked:icon-check-box cursor-pointer text-2xl text-navyBlue peer-checked:text-navyBlue"
                            for="default_address"
                        >
                        </label>

                        <label 
                            class="block cursor-pointer text-base max-md:text-sm"
                            for="default_address"
                        >
                            @lang('shop::app.customers.account.addresses.create.set-as-default')
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="primary-button m-0 block rounded-2xl px-11 py-3 text-center text-base max-md:w-full max-md:max-w-full max-md:rounded-lg max-md:py-2 max-sm:py-1.5"
                    >
                        @lang('shop::app.customers.account.addresses.create.save')
                    </button>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.after') !!}
                </x-shop::form>
                {!! view_render_event('bagisto.shop.customers.account.address.create.after') !!}
            </div>
        </script>
    
        <script type="module">
            app.component('v-create-customer-address', {
                template: '#v-create-customer-address-template',
    
                data() {
                    return {
                        country: "{{ old('country', 'JP') }}",
                        state: "{{ old('state') }}",
                        city: "{{ old('city') }}",
                        postcode: "{{ old('postcode') }}",
                        street: "{{ collect(old('address'))->first() ?? '' }}",
                        postcodeStatus: null,
                        postcodeTimer: null,
                        countryStates: @json(core()->groupedStatesByCountries()),
                    }
                },
    
                methods: {
                    haveStates() {
                        return !!this.countryStates[this.country]?.length;
                    },
                    onPostcodeInput(event) {
                        clearTimeout(this.postcodeTimer);
                        const inputVal = (event && event.target ? event.target.value : this.postcode) || '';
                        const raw = inputVal.replace(/[^0-9]/g, '');
                        if (raw.length === 7 && event && event.target && !inputVal.includes('-')) {
                            const formatted = raw.slice(0, 3) + '-' + raw.slice(3);
                            event.target.value = formatted;
                            this.postcode = formatted;
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
                                this.state  = r.address1;
                                this.city   = r.address2;
                                this.street = r.address3 || '';
                                this.postcodeStatus = 'ok';
                            } else {
                                this.postcodeStatus = 'error';
                            }
                        } catch(e) {
                            this.postcodeStatus = 'error';
                        }
                    },
                }
            });
        </script>
    @endpush

</x-shop::layouts.account>
