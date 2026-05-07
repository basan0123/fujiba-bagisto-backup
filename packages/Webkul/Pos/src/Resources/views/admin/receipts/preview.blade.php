<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="title" content="Preview Receipt / Fujiba Admin" />
    <meta name="robots" content="NOINDEX,NOFOLLOW" />
    <meta name="viewport" content="width=1024" />
    <meta name="format-detection" content="telephone=no" />
    <title>{{ $posReceipt->title ?? trans('pos::app.admin.receipts.preview.title') }}</title>

    <link
        rel="icon"
        type="image/x-icon"
        href="{{ core()->getCurrentChannel()->favicon_url }}"
    />

    <link
        rel="shortcut icon"
        type="image/x-icon"
        href="{{ core()->getCurrentChannel()->favicon_url }}"
    />

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    @bagistoVite(['src/Resources/assets/css/admin.css'], 'pos')
</head>

<body data-container="body">
    <div class="flex justify-center bg-[#f5f5f5]">
        <div class="bg-white w-1/3 my-4 p-5 border">
            <div class="flex gap-3">
                <div class="w-1/3">
                    @if ($posReceipt->display_logo)
                        <img
                            src="{{ asset('storage/' . $posReceipt->logo) }}"
                            class="rounded"
                            height="{{ $posReceipt->logo_height }}"
                            width="{{ $posReceipt->logo_width }}"
                            alt="{{ $posReceipt->logo_alt }}"
                        />
                    @endif
                </div>

                <div>
                    @if ($posReceipt->display_outlet_address)
                        <div class="grid">
                            @if ($posReceipt->display_outlet_name)
                                <span class="text-xl font-medium">
                                    {{ $posReceipt->outlet?->name }}
                                </span>
                            @endif

                            @if ($posReceipt->display_outlet_address)
                                <span>
                                    <pre>-------------</pre>
                                </span>
                            @endif

                            <span>
                                <b>@lang('pos::app.admin.receipts.preview.email'): </b>

                                <span>{{$posReceipt->outlet?->pos_user?->email}}</span>
                            </span>

                            <span>
                                <b>@lang('pos::app.admin.receipts.preview.phone'): </b>

                                <span>xxxxxxxxxx</span>
                            </span>

                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-5">
                {!! $posReceipt->header_content !!}
            </div>

            <div class="border-2 border-black"></div>

            <div class="grid my-2">
                @if ($posReceipt->display_date)
                    <div class="flex justify-between">
                        <span class="text-base font-semibold">
                            @lang('pos::app.admin.receipts.preview.date')
                        </span>

                        <span class="text-base">
                            {{ $posReceipt->updated_at }}
                        </span>
                    </div>
                @endif

                @if ($posReceipt->display_order_id)
                    <div class="flex justify-between">
                        <span class="text-base font-semibold">
                            {{ $posReceipt->order_id_label ?? trans('pos::app.admin.receipts.preview.order-id') }}
                        </span>

                        <span class="text-base font-semibold">
                            #xxxx
                        </span>
                    </div>
                @endif

                @if ($posReceipt->display_cashier_name)
                    <div class="flex justify-between">
                        <span class="text-base font-semibold">
                            {{ $posReceipt->cashier_label ?? trans('pos::app.admin.receipts.preview.cashier') }}
                        </span>

                        <span class="text-base">
                            John Doe
                        </span>
                    </div>
                @endif

                @if ($posReceipt->display_customer_name)
                    <div class="flex justify-between">
                        <span class="text-base font-semibold">
                            @lang('pos::app.admin.receipts.preview.customer')
                        </span>

                        <span class="text-base">
                            <i class="fa fa-id-card"></i>

                            @lang('pos::app.admin.receipts.preview.customer-name')
                        </span>
                    </div>
                @endif

                <div class="flex justify-between">
                    <span class="text-base font-semibold"></span>

                    <span class="text-base">
                        <i class="fa fa-phone"></i>

                        @lang('pos::app.admin.receipts.preview.customer-phone')
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-base font-semibold"></span>

                    <span class="text-base">
                        <i class="fa fa-envelope"></i>

                        @lang('pos::app.admin.receipts.preview.customer-email')
                    </span>
                </div>
            </div>

            <div class="border-2 border-black"></div>

            @php
                $currencySymbol = core()->getBaseCurrency()->symbol;
            @endphp

            <div class="grid gap-5 my-2">
                <table>
                    <tr class="text-left">
                        <th class="px-2">
                            @lang('pos::app.admin.receipts.preview.product')
                        </th>

                        <th class="px-2">
                            @lang('pos::app.admin.receipts.preview.qty')
                        </th>

                        <th class="px-2">
                            @lang('pos::app.admin.receipts.preview.price')
                        </th>

                        <th class="px-2">
                            @lang('pos::app.admin.receipts.preview.amount')
                        </th>
                    </tr>

                    <tr v-for="(item, index) in orderData.items" :key="index">
                        <td class="px-2">
                            <div class="grid">Men Black T-Shirt
                                <span class="text-base">
                                    <span>
                                        <i> <b>size</b>: XL , </i>
                                    </span>
                                </span>
                            </div>
                        </td>

                        <td class="px-2">1</td>

                        <td class="px-2">
                            <span>
                                {{ $currencySymbol }} 10
                            </span>
                        </td>

                        <td class="px-2">
                            <span>
                                {{ $currencySymbol }} 10
                            </span>
                        </td>
                    </tr>
                </table>

                <table>
                    <tr>
                        <td class="px-2">@lang('pos::app.admin.receipts.preview.total-qty')</td>

                        <td class="px-2">1</td>
                        @if ($posReceipt->display_sub_total)
                            <td class="px-2">
                                {{ $posReceipt->sub_total_label ?? trans('pos::app.admin.receipts.preview.sub-total') }}
                            </td>

                            <td class="px-2">
                                <span>
                                    {{ $currencySymbol }} 10
                                </span>
                            </td>
                        @endif
                    </tr>

                    @if ($posReceipt->display_discount)
                        <tr>
                            <td colspan="2"></td>

                            <td class="px-2">
                                {{ $posReceipt->discount_label ?? trans('pos::app.admin.receipts.preview.discount') }}
                            </td>

                            <td class="px-2">
                                <span>
                                    {{ $currencySymbol }} 01
                                </span>
                            </td>
                        </tr>
                    @endif

                    @if ($posReceipt->display_tax)
                        <tr>
                            <td colspan="2"></td>

                            <td class="px-2">
                                {{ $posReceipt->tax_label ?? trans('pos::app.admin.receipts.preview.tax') }}
                            </td>

                            <td class="px-2">
                                <span>
                                    {{ $currencySymbol }} 02
                                </span>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td colspan="2"></td>

                        <td class="px-2 font-semibold">
                            {{ $posReceipt->grand_total_label ?? trans('pos::app.admin.receipts.preview.grand-total') }}
                        </td>

                        <td class="px-2 font-semibold">
                            <span>
                                {{ $currencySymbol }} 11
                            </span>
                        </td>
                    </tr>

                    @if ($posReceipt->display_change_amount)
                        <tr>
                            <td colspan="2"></td>

                            <td class="px-2">
                                {{ $posReceipt->credit_change_label ?? trans('pos::app.admin.receipts.preview.change-amount') }}
                            </td>

                            <td class="px-2">
                                <span>
                                    {{ $currencySymbol }} 09
                                </span>
                            </td>
                        </tr>
                    @endif
                </table>
            </div>

            <div class="border-2 border-black"></div>

            <div class="my-2">
                {!! $posReceipt->footer_content !!}
            </div>
        </div>
    </div>
</body>
</html>
