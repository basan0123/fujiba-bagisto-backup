<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.users.outlets.create.title')
    </x-slot:title>

    <x-admin::form :action="route('admin.pos.outlets.store')">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('pos::app.admin.users.outlets.create.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <!-- Back Button -->
                <a
                    href="{{ route('admin.pos.outlets.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('pos::app.admin.users.outlets.create.back-btn')
                </a>

                <!-- Save Button -->
                @if (bouncer()->hasPermission('pos.users.outlets.create'))
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('pos::app.admin.users.outlets.create.btn-title')
                    </button>
                @endif
            </div>
        </div>

        <!-- Full Panel -->
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.users.outlets.create.general')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="status"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Store Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="name"
                            :value="old('name')"
                            rules="required"
                            :label="trans('pos::app.admin.users.outlets.create.name')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.name')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <!-- Store Email -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="email"
                            :value="old('email')"
                            rules="required|email"
                            :label="trans('pos::app.admin.users.outlets.create.email')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.email')"
                        />

                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>

                    <!-- Store Phone -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.phone')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="phone"
                            :value="old('phone')"
                            rules="required|phone"
                            :label="trans('pos::app.admin.users.outlets.create.phone')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.phone')"
                        />

                        <x-admin::form.control-group.error control-name="phone" />
                    </x-admin::form.control-group>

                    <!-- Website -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.website')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="website"
                            :value="old('website')"
                            rules="required|url"
                            :label="trans('pos::app.admin.users.outlets.create.website')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.website')"
                        />

                        <x-admin::form.control-group.error control-name="website" />
                    </x-admin::form.control-group>

                    <!-- Customer Care Number -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.customer-care-number')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="customer_care_number"
                            :value="old('customer_care_number')"
                            rules="required|phone"
                            :label="trans('pos::app.admin.users.outlets.create.customer-care-number')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.customer-care-number')"
                        />

                        <x-admin::form.control-group.error control-name="customer_care_number" />
                    </x-admin::form.control-group>

                    <!-- GST Number -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.gst-number')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="gst_number"
                            :value="old('gst_number')"
                            rules="required"
                            :label="trans('pos::app.admin.users.outlets.create.gst-number')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.gst-number')"
                        />

                        <x-admin::form.control-group.error control-name="gst_number" />
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.users.outlets.create.receipt')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.receipt')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="receipt_id"
                            :value="old('receipt_id')"
                            rules="required"
                            :label="trans('pos::app.admin.users.outlets.create.receipt')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.create.receipt')"
                        >
                            <option value="">
                                @lang('pos::app.admin.users.outlets.create.select-receipt')
                            </option>

                            @foreach ($receipts as $receipt)
                                <option value="{{ $receipt->id }}">
                                    {{ $receipt->title }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="receipt_id" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.users.outlets.create.store-address')
                    </p>

                    <!-- Address -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.address')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="address"
                            :value="old('address')"
                            rules="required|address"
                            :label="trans('pos::app.admin.users.outlets.create.address')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.address')"
                        />

                        <x-admin::form.control-group.error control-name="address" />
                    </x-admin::form.control-group>

                    <!-- Country -->
                    <v-create-country></v-create-country>

                    <!-- City -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required text-gray-800 dark:text-white">
                            @lang('pos::app.admin.users.outlets.create.city')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="city"
                            :value="old('city')"
                            rules="required"
                            :label="trans('pos::app.admin.users.outlets.create.city')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.city')"
                        />

                        <x-admin::form.control-group.error control-name="city" />
                    </x-admin::form.control-group>

                    <!-- PostCode -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required text-gray-800 dark:text-white">
                            @lang('pos::app.admin.users.outlets.create.postcode')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="postcode"
                            :value="old('postcode')"
                            rules="required|postcode"
                            :label="trans('pos::app.admin.users.outlets.create.postcode')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.postcode')"
                        />

                        <x-admin::form.control-group.error control-name="postcode" />
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.users.outlets.create.inventory')
                    </p>

                    <!-- inventory source -->
                    <x-admin::form.control-group class="w-full">
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.inventory-source')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="inventory_source_id"
                            :value="old('inventory_source_id')"
                            rules="required"
                            :label="trans('pos::app.admin.users.outlets.create.inventory-source')"
                            :placeholder="trans('pos::app.admin.users.outlets.create.select-inventory-source')"
                        >
                            <option value="">
                                @lang('pos::app.admin.users.outlets.create.select-inventory-source')
                            </option>

                            @foreach ($inventorySource as $source)
                                <option value="{{ $source->id }}">
                                    {{ $source->name }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="inventory_source_id" />
                    </x-admin::form.control-group>

                    <!-- Low Stock Qty -->
                    <x-admin::form.control-group class="w-full">
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.outlets.create.low-stock-qty')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="low_stock_qty"
                            :value="old('low_stock_qty')"
                            rules="required|numeric|min:0"
                            :label="trans('pos::app.admin.users.outlets.create.low-stock-qty')"
                            :placeholder="10"
                        />

                        <x-admin::form.control-group.error control-name="low_stock_qty" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-create-country-template"
        >
            <!-- Country -->
            <x-admin::form.control-group class="w-full">
                <x-admin::form.control-group.label>
                    @lang('pos::app.admin.users.outlets.create.country')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="country"
                    rules="required"
                    ::value="country"
                    v-model="country"
                    :label="trans('pos::app.admin.users.outlets.create.country')"
                >
                    <option value="">
                        @lang('pos::app.admin.users.outlets.create.select-country')
                    </option>

                    @foreach (core()->countries() as $country)
                        <option 
                            {{ $country->code === config('app.default_country') ? 'selected' : '' }}  
                            value="{{ $country->code }}"
                        >
                            {{ $country->name }}
                        </option>
                    @endforeach
                </x-admin::form.control-group.control>

                <x-admin::form.control-group.error control-name="country" />
            </x-admin::form.control-group>

            <!-- State -->
            <x-admin::form.control-group class="w-full">
                <x-admin::form.control-group.label class="required">
                    @lang('pos::app.admin.users.outlets.create.state')
                </x-admin::form.control-group.label>

                <template v-if="haveStates()">
                    <x-admin::form.control-group.control
                        type="select"
                        id="state"
                        name="state"
                        rules="required"
                        ::value="state"
                        v-model="state"
                        :label="trans('pos::app.admin.users.outlets.create.state')"
                        :placeholder="trans('pos::app.admin.users.outlets.create.state')"
                    >
                        <option 
                            v-for='(state, index) in countryStates[country]'
                            :value="state.code"
                            v-text="state.default_name"
                        >
                        </option>
                    </x-admin::form.control-group.control>
                </template>

                <template v-else>
                    <x-admin::form.control-group.control
                        type="text"
                        name="state"
                        v-model="state"
                        rules="required"
                        :label="trans('pos::app.admin.users.outlets.create.state')"
                        :placeholder="trans('pos::app.admin.users.outlets.create.state')"
                    />
                </template>

                <x-admin::form.control-group.error control-name="state" />
            </x-admin::form.control-group>
        </script>

        <script type="module">
            app.component('v-create-country', {
                template: '#v-create-country-template',

                data() {
                    return {
                        country: @json(old('country')),

                        state: @json(old('state')),

                        countryStates: @json(core()->groupedStatesByCountries()),
                    }
                },

                methods: {
                    haveStates() {
                        return !!this.countryStates[this.country]?.length;
                    },
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
