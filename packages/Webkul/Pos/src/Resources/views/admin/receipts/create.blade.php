<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.receipts.create.title')
    </x-slot:title>

    <x-admin::form
        :action="route('admin.pos.receipts.store')"
        enctype="multipart/form-data"
    >
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('pos::app.admin.receipts.create.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <!-- Back Button -->
                <a
                    href="{{ route('admin.pos.receipts.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('pos::app.admin.receipts.create.back-btn')
                </a>

                <!-- Save Button -->
                @if (bouncer()->hasPermission('pos.receipts.create'))
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('pos::app.admin.receipts.create.btn-title')
                    </button>
                @endif
            </div>
        </div>

        <!-- Full Panel -->
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.receipts.create.general.title')
                    </p>

                    <!--Receipt Title-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.general.receipt-title')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="title"
                            :value="old('title')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.general.receipt-title')"
                            :placeholder="trans('pos::app.admin.receipts.create.general.receipt-title')"
                        />

                        <x-admin::form.control-group.error control-name="title" />
                    </x-admin::form.control-group>

                    <!--Receipt Status-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="status"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Display Store name-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-outlet-name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_outlet_name"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Show Order Barcode Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.show-order-barcode')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="show_order_barcode"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Show Print Confirmation -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.show-print-confirmation')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="show_print_confirmation"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Display Date-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-date')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_date"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Display Order Id-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-order-id')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_order_id"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!--Receipt Order Id label-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.general.order-id-label')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="order_id_label"
                            :value="old('order_id_label')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.general.order-id-label')"
                            :placeholder="trans('pos::app.admin.receipts.create.general.order-id-label')"
                        />

                        <x-admin::form.control-group.error control-name="order_id_label" />
                    </x-admin::form.control-group>

                    <!-- Display Customer Name-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-customer-name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_customer_name"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Display Sub Total-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-sub-total')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_sub_total"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Display Sub Total label-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.general.sub-total-label')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="sub_total_label"
                            :value="old('sub_total_label')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.general.sub-total-label')"
                            :placeholder="trans('pos::app.admin.receipts.create.general.sub-total-label')"
                        />

                        <x-admin::form.control-group.error control-name="sub_total_label" />
                    </x-admin::form.control-group>

                    <!-- Display Tax-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-tax')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_tax"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Display Tax label-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.general.tax-label')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="tax_label"
                            :value="old('tax_label')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.general.tax-label')"
                            :placeholder="trans('pos::app.admin.receipts.create.general.tax-label')"
                        />

                        <x-admin::form.control-group.error control-name="tax_label" />
                    </x-admin::form.control-group>

                    <!-- Display Credit Amount-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-credit-amount')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_credit_amount"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Display Credit Amount label-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.general.credit-amount-label')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="credit_amount_label"
                            :value="old('credit_amount_label')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.general.credit-amount-label')"
                            :placeholder="trans('pos::app.admin.receipts.create.general.credit-amount-label')"
                        />

                        <x-admin::form.control-group.error control-name="credit_amount_label" />
                    </x-admin::form.control-group>

                    <!-- Display Change Amount-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-change-amount')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_change_amount"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Display Change Amount label-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.general.change-amount-label')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="credit_change_label"
                            :value="old('credit_change_label')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.general.change-amount-label')"
                            :placeholder="trans('pos::app.admin.receipts.create.general.change-amount-label')"
                        />

                        <x-admin::form.control-group.error control-name="credit_change_label" />
                    </x-admin::form.control-group>

                    <!-- Display Cashier Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-cashier-name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_cashier_name"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!-- Cashier Name label -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.general.cashier-name-label')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="cashier_label"
                            :value="old('cashier_label')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.general.cashier-name-label')"
                            :placeholder="trans('pos::app.admin.receipts.create.general.cashier-name-label')"
                        />

                        <x-admin::form.control-group.error control-name="cashier_label" />
                    </x-admin::form.control-group>

                    <!-- Display Outlet Address -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-outlet-address')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_outlet_address"
                            :value="1"
                        />
                    </x-admin::form.control-group>
                    
                    <!-- Grand Total Label -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.general.grand-total-label')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="grand_total_label"
                            :value="old('grand_total_label')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.general.grand-total-label')"
                            :placeholder="trans('pos::app.admin.receipts.create.general.grand-total-label')"
                        />

                        <x-admin::form.control-group.error control-name="grand_total_label" />
                    </x-admin::form.control-group>

                    <!-- Display Discount Amount -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.general.display-discount-amt')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_discount"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <!--Discount Label-->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.general.discount-amt-label')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="discount_label"
                            :value="old('discount_label')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.general.discount-amt-label')"
                            :placeholder="trans('pos::app.admin.receipts.create.general.discount-amt-label')"
                        />

                        <x-admin::form.control-group.error control-name="discount_label" />
                    </x-admin::form.control-group>
                </div>
            </div>
            
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.receipts.create.logo.title')
                    </p>

                    <!-- Display Logo -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.receipts.create.logo.display-logo')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="display_logo"
                            :value="1"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <div class="mt-5 flex w-[40%] flex-col gap-2">
                            <p class="font-medium text-gray-800 dark:text-white">
                                @lang('pos::app.admin.receipts.create.logo.upload-logo')
                            </p>

                            <x-admin::media.images name="logo" />
                        </div>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.logo.logo-width')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="logo_width"
                            :value="old('logo_width')"
                            rules="required|numeric"
                            :label="trans('pos::app.admin.receipts.create.logo.logo-width')"
                            :placeholder="trans('pos::app.admin.receipts.create.logo.logo-width')"
                        />

                        <x-admin::form.control-group.error control-name="logo_width" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.logo.logo-height')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="logo_height"
                            :value="old('logo_height')"
                            rules="required|numeric"
                            :label="trans('pos::app.admin.receipts.create.logo.logo-height')"
                            :placeholder="trans('pos::app.admin.receipts.create.logo.logo-height')"
                        />

                        <x-admin::form.control-group.error control-name="logo_height" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.logo.logo-alt')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="logo_alt"
                            :value="old('logo_alt')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.logo.logo-alt')"
                            :placeholder="trans('pos::app.admin.receipts.create.logo.logo-alt')"
                        />

                        <x-admin::form.control-group.error control-name="logo_alt" />
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.receipts.create.header.title')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.header.header-content')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="header_content"
                            :value="old('header_content')"
                            id="header_content"
                            class="header_content"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.header.header-content')"
                            :placeholder="trans('pos::app.admin.receipts.create.header.header-content')"
                            :tinymce="true"
                        />

                        <x-admin::form.control-group.error control-name="header_content" />
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.receipts.create.footer.title')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.receipts.create.footer.footer-content')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="footer_content"
                            id="footer_content"
                            class="footer_content"
                            :value="old('footer_content')"
                            rules="required"
                            :label="trans('pos::app.admin.receipts.create.footer.footer-content')"
                            :placeholder="trans('pos::app.admin.receipts.create.footer.footer-content')"
                            :tinymce="true"
                        />

                        <x-admin::form.control-group.error control-name="footer_content" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
