<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.sales.orders.view.title', ['order_id' => $order->increment_id])
    </x-slot>

    <!-- Header -->
    <div class="grid">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            {!! view_render_event('bagisto.admin.sales.order.title.before', ['order' => $order]) !!}

            <div class="flex items-center gap-2.5">
                <p class="text-xl font-bold leading-6 text-gray-800 dark:text-white">
                    @lang('admin::app.sales.orders.view.title', ['order_id' => $order->increment_id])
                </p>

                <!-- Order Status -->
                <span class="label-{{ $order->status }} text-sm mx-1.5">
                    {{ $order->status_label }}
                </span>
            </div>

            {!! view_render_event('bagisto.admin.sales.order.title.after', ['order' => $order]) !!}

            <!-- Back Button -->
            <a
                href="{{ route('admin.sales.orders.index') }}"
                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
            >
                @lang('admin::app.account.edit.back-btn')
            </a>
        </div>
    </div>

    <div class="flex-wrap items-center justify-between mt-5 gap-x-1 gap-y-2">
        <div class="flex gap-1.5">
            {!! view_render_event('bagisto.admin.sales.order.page_action.before', ['order' => $order]) !!}

            <a
                href="{{ route('admin.sales.orders.receipt', $order->id) }}"
                target="_blank"
                rel="noopener"
                class="transparent-button px-1 py-1.5 hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                title="レシート (Simple Check View) — packing/fabric checking"
            >
                <span class="text-2xl icon-sales-orders" role="presentation"></span>
                🧾 レシート
            </a>

            @if (
                $order->canReorder()
                && bouncer()->hasPermission('sales.orders.create')
                && core()->getConfigData('sales.order_settings.reorder.admin')
            )
                <a
                    href="{{ route('admin.sales.orders.reorder', $order->id) }}"
                    class="transparent-button px-1 py-1.5 hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    <span class="text-2xl icon-cart"></span>

                    @lang('admin::app.sales.orders.view.reorder')
                </a>
            @endif

            @if (
                $order->canInvoice()
                && bouncer()->hasPermission('sales.invoices.create')
                && $order->payment->method !== 'paypal_standard'
            )
                @include('admin::sales.invoices.create')
            @endif

            @if (
                $order->canShip()
                && bouncer()->hasPermission('sales.shipments.create')
            )
                @include('admin::sales.shipments.create')
            @endif

            @if (
                $order->canShip()
                && bouncer()->hasPermission('sales.shipments.create')
            )
                <form
                    method="POST"
                    ref="quickShipForm"
                    action="{{ route('admin.sales.orders.quick_ship', $order->id) }}"
                >
                    @csrf
                </form>

                <div
                    class="transparent-button px-1 py-1.5 hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                    v-on:click="$emitter.emit('open-confirm-modal', {
                        message: '請求書を自動作成し、全商品を発送済みにします。よろしいですか？',
                        agree: () => { this.$refs['quickShipForm'].submit() }
                    })"
                >
                    <span class="text-2xl icon-shipping" role="presentation" tabindex="0"></span>

                    <a href="javascript:void(0);">発送済みにする</a>
                </div>
            @endif

            @if (
                $order->canRefund()
                && bouncer()->hasPermission('sales.refunds.create')
            )
                @include('admin::sales.refunds.create')
            @endif

            @if (
                $order->canCancel()
                && bouncer()->hasPermission('sales.orders.cancel')
            )
               <form
                    method="POST"
                    ref="cancelOrderForm"
                    action="{{ route('admin.sales.orders.cancel', $order->id) }}"
                >
                    @csrf
                </form>

                <div
                    class="transparent-button px-1 py-1.5 hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                    @click="$emitter.emit('open-confirm-modal', {
                        message: '@lang('admin::app.sales.orders.view.cancel-msg')',
                        agree: () => {
                            this.$refs['cancelOrderForm'].submit()
                        }
                    })"
                >
                    <span
                        class="text-2xl icon-cancel"
                        role="presentation"
                        tabindex="0"
                    >
                    </span>

                    <a href="javascript:void(0);">
                        @lang('admin::app.sales.orders.view.cancel')
                    </a>
                </div>
            @endif

            {!! view_render_event('bagisto.admin.sales.order.page_action.after', ['order' => $order]) !!}
        </div>

        <!-- Order details -->
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <!-- Left Component -->
            <div class="flex flex-col flex-1 gap-2 max-xl:flex-auto">
                {!! view_render_event('bagisto.admin.sales.order.left_component.before', ['order' => $order]) !!}

                <div class="bg-white rounded box-shadow dark:bg-gray-900">
                    <div class="flex justify-between p-4">
                        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('Order Items') ({{ count($order->items) }})
                        </p>

                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.sales.orders.view.grand-total', ['grand_total' => core()->formatBasePrice($order->base_grand_total)])
                        </p>
                    </div>

                    <!-- Order items -->
                    <div class="grid">
                        @foreach ($order->items as $item)
                            {!! view_render_event('bagisto.admin.sales.order.list.before', ['order' => $order]) !!}

                            <div class="flex justify-between gap-2.5 border-b border-slate-300 px-4 py-6 dark:border-gray-800">
                                <div class="flex gap-2.5">
                                    @if($item?->product?->base_image_url)
                                        <img
                                            class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded"
                                            src="{{ $item?->product->base_image_url }}"
                                        >
                                    @else
                                        <div class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded border border-dashed border-gray-300 dark:border-gray-800 dark:mix-blend-exclusion dark:invert">
                                            <img src="{{ bagisto_asset('images/product-placeholders/front.svg') }}">

                                            <p class="absolute bottom-1.5 w-full text-center text-[6px] font-semibold text-gray-400">
                                                @lang('admin::app.sales.invoices.view.product-image')
                                            </p>
                                        </div>
                                    @endif

                                    <div class="grid place-content-start gap-1.5">
                                        @php
                                            $tempType = 'normal';
                                            if ($item->product) {
                                                try {
                                                    $coolVal = $item->product->Cool;
                                                    if ($coolVal) {
                                                        $coolOptMap = cache()->remember('cool_option_map', 300, function () {
                                                            $map = [];
                                                            $rows = \DB::table('attribute_options')
                                                                ->join('attribute_option_translations', 'attribute_options.id', '=', 'attribute_option_translations.attribute_option_id')
                                                                ->where('attribute_options.attribute_id', function ($q) { $q->select('id')->from('attributes')->where('code', 'Cool')->limit(1); })
                                                                ->where('attribute_option_translations.locale', 'en')
                                                                ->select('attribute_options.id', 'attribute_option_translations.label')->get();
                                                            foreach ($rows as $r) {
                                                                $l = strtolower(trim($r->label));
                                                                if (str_contains($l, 'cool')) $map[$r->id] = 'cool';
                                                                elseif (str_contains($l, 'freez') || str_contains($l, 'frozen')) $map[$r->id] = 'frozen';
                                                                else $map[$r->id] = 'normal';
                                                            }
                                                            return $map;
                                                        });
                                                        foreach (array_map('intval', explode(',', (string) $coolVal)) as $cid) {
                                                            $ct = $coolOptMap[$cid] ?? 'normal';
                                                            if ($ct === 'frozen') { $tempType = 'frozen'; break; }
                                                            if ($ct === 'cool') $tempType = 'cool';
                                                        }
                                                    }
                                                } catch (\Exception $e) {}
                                            }
                                        @endphp
                                        <p
                                            class="text-base font-semibold text-gray-800 break-all dark:text-white"
                                            v-pre
                                        >
                                            @if($tempType === 'cool')<span title="クール便">❄️</span>@endif
                                            @if($tempType === 'frozen')<span title="冷凍便">⚠️❄️</span>@endif
                                            {{ $item->name }}
                                        </p>

                                        <div class="flex flex-col place-items-start gap-1.5">
                                            <p class="text-gray-600 dark:text-gray-300">
                                                {{ core()->formatBasePrice($item->base_price) }} ×
                                                @if($item->qty_ordered > 1)
                                                    <span class="text-red-600 text-lg font-bold">{{ $item->qty_ordered }}</span>
                                                @else
                                                    {{ $item->qty_ordered }}
                                                @endif
                                            </p>

                                            @if (isset($item->additional['attributes']))
                                                @foreach ($item->additional['attributes'] as $attribute)
                                                    <p
                                                        class="text-gray-600 dark:text-gray-300"
                                                        v-pre
                                                    >
                                                        @if (
                                                            ! isset($attribute['attribute_type'])
                                                            || $attribute['attribute_type'] !== 'file'
                                                        )
                                                            {{ $attribute['attribute_name'] }} : {{ $attribute['option_label'] }}
                                                        @else
                                                            {{ $attribute['attribute_name'] }} :

                                                            <a
                                                                href="{{ Storage::url($attribute['option_label']) }}"
                                                                class="text-blue-600 hover:underline"
                                                                download="{{ File::basename($attribute['option_label']) }}"
                                                            >
                                                                {{ File::basename($attribute['option_label']) }}
                                                            </a>
                                                        @endif
                                                    </p>
                                                @endforeach
                                            @endif

                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.sku', ['sku' => $item->sku])
                                            </p>

                                            <p class="text-gray-600 dark:text-gray-300">
                                                {{ $item->qty_ordered ? trans('admin::app.sales.orders.view.item-ordered', ['qty_ordered' => $item->qty_ordered]) : '' }}

                                                {{ $item->qty_invoiced ? trans('admin::app.sales.orders.view.item-invoice', ['qty_invoiced' => $item->qty_invoiced]) : '' }}

                                                {{ $item->qty_shipped ? trans('admin::app.sales.orders.view.item-shipped', ['qty_shipped' => $item->qty_shipped]) : '' }}

                                                {{ $item->qty_refunded ? trans('admin::app.sales.orders.view.item-refunded', ['qty_refunded' => $item->qty_refunded]) : '' }}

                                                {{ $item->qty_canceled ? trans('admin::app.sales.orders.view.item-canceled', ['qty_canceled' => $item->qty_canceled]) : '' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-1 place-content-start">
                                    <div class="">
                                        <p class="flex items-center justify-end text-base font-semibold text-gray-800 gap-x-1 dark:text-white">
                                            {{ core()->formatBasePrice($item->base_total + $item->base_tax_amount - $item->base_discount_amount) }}
                                        </p>
                                    </div>

                                    <div class="flex flex-col place-items-start items-end gap-1.5">
                                        @if (core()->getConfigData('sales.taxes.sales.display_prices') == 'including_tax')
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.price', ['price' => core()->formatBasePrice($item->base_price_incl_tax)])
                                            </p>
                                        @elseif (core()->getConfigData('sales.taxes.sales.display_prices') == 'both')
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.price-excl-tax', ['price' => core()->formatBasePrice($item->base_price)])
                                            </p>

                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.price-incl-tax', ['price' => core()->formatBasePrice($item->base_price_incl_tax)])
                                            </p>
                                        @else
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.price', ['price' => core()->formatBasePrice($item->base_price)])
                                            </p>
                                        @endif

                                        <p class="text-gray-600 dark:text-gray-300">
                                            @lang('admin::app.sales.orders.view.tax', [
                                                'percent' => number_format($item->tax_percent, 2) . '%',
                                                'tax'     => core()->formatBasePrice($item->base_tax_amount)
                                            ])
                                        </p>

                                        @if ($order->base_discount_amount > 0)
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.discount', ['discount' => core()->formatBasePrice($item->base_discount_amount)])
                                            </p>
                                        @endif

                                        @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.sub-total', ['sub_total' => core()->formatBasePrice($item->base_total_incl_tax)])
                                            </p>
                                        @elseif (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'both')
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.sub-total-excl-tax', ['sub_total' => core()->formatBasePrice($item->base_total)])
                                            </p>

                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.sub-total-incl-tax', ['sub_total' => core()->formatBasePrice($item->base_total_incl_tax)])
                                            </p>
                                        @else
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.sub-total', ['sub_total' => core()->formatBasePrice($item->base_total)])
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.list.after', ['order' => $order]) !!}

                        @endforeach
                    </div>

                    <div class="flex justify-end flex-auto p-4 mt-4">
                        <div class="grid gap-2 text-sm max-w-max">

                            {!! view_render_event('bagisto.admin.sales.order.view.subtotal.before') !!}

                            <!-- Sub Total -->
                            @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
                                <div class="flex justify-between w-full gap-x-5">
                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.summary-sub-total-incl-tax')
                                    </p>

                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice($order->base_sub_total_incl_tax) }}
                                    </p>
                                </div>
                            @elseif (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'both')
                                <div class="flex justify-between w-full gap-x-5">
                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.summary-sub-total-excl-tax')
                                    </p>

                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice($order->base_sub_total) }}
                                    </p>
                                </div>

                                <div class="flex justify-between w-full gap-x-5">
                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.summary-sub-total-incl-tax')
                                    </p>

                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice($order->base_sub_total_incl_tax) }}
                                    </p>
                                </div>
                            @else
                                <div class="flex justify-between w-full gap-x-5">
                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.summary-sub-total')
                                    </p>

                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice($order->base_sub_total) }}
                                    </p>
                                </div>
                            @endif

                            {!! view_render_event('bagisto.admin.sales.order.view.subtotal.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.shipping.before') !!}

                            <!-- Shipping And Handling -->
                            @if ($haveStockableItems = $order->haveStockableItems())
                                @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
                                    <div class="flex justify-between w-full gap-x-5">
                                        <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                            @lang('admin::app.sales.orders.view.shipping-and-handling-incl-tax')
                                        </p>

                                        <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                            {{ core()->formatBasePrice($order->base_shipping_amount_incl_tax) }}
                                        </p>
                                    </div>
                                @elseif (core()->getConfigData('sales.taxes.sales.display_shipping_amount') == 'both')
                                    <div class="flex justify-between w-full gap-x-5">
                                        <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                            @lang('admin::app.sales.orders.view.shipping-and-handling-excl-tax')
                                        </p>

                                        <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                            {{ core()->formatBasePrice($order->base_shipping_amount) }}
                                        </p>
                                    </div>

                                    <div class="flex justify-between w-full gap-x-5">
                                        <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                            @lang('admin::app.sales.orders.view.shipping-and-handling-incl-tax')
                                        </p>

                                        <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                            {{ core()->formatBasePrice($order->base_shipping_amount_incl_tax) }}
                                        </p>
                                    </div>
                                @else
                                    <div class="flex justify-between w-full gap-x-5">
                                        <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                            @lang('admin::app.sales.orders.view.shipping-and-handling')
                                        </p>

                                        <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                            {{ core()->formatBasePrice($order->base_shipping_amount) }}
                                        </p>
                                    </div>
                                @endif
                            @endif

                            {!! view_render_event('bagisto.admin.sales.order.view.shipping.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.tax-amount.before') !!}

                            <!-- Tax Amount -->
                            <div class="flex justify-between w-full gap-x-5">
                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.summary-tax')
                                </p>

                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    {{ core()->formatBasePrice($order->base_tax_amount) }}
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.tax-amount.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.discount.before') !!}

                            <!-- Discount -->
                            <div class="flex justify-between w-full gap-x-5">
                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.summary-discount')
                                </p>

                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    {{ core()->formatBasePrice($order->base_discount_amount) }}
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.discount.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.grand-total.before') !!}

                            <!-- Grand Total -->
                            <div class="flex justify-between w-full gap-x-5">
                                <p class="text-base font-semibold !leading-5 text-gray-800 dark:text-white">
                                    @lang('admin::app.sales.orders.view.summary-grand-total')
                                </p>

                                <p class="text-base font-semibold !leading-5 text-gray-800 dark:text-white">
                                    {{ core()->formatBasePrice($order->base_grand_total) }}
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.grand-total.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.total-paid.before') !!}

                            <!-- Total Paid -->
                            <div class="flex justify-between w-full gap-x-5">
                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.total-paid')
                                </p>

                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    {{ core()->formatBasePrice($order->base_grand_total_invoiced) }}
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.total-paid.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.total-refunded.before') !!}

                            <!-- Total Refund -->
                            <div class="flex justify-between w-full gap-x-5">
                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.total-refund')
                                </p>

                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    {{ core()->formatBasePrice($order->base_grand_total_refunded) }}
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.total-refunded.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.total-due.before') !!}

                            <!-- Total Due -->
                            <div class="flex justify-between w-full font-semibold gap-x-5">
                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.total-due')
                                </p>

                                @if($order->status !== 'canceled')
                                    <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice($order->base_total_due) }}
                                    </p>
                                @else
                                    <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice(0.00) }}
                                    </p>
                                @endif
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.total-due.after') !!}

                        </div>
                    </div>
                </div>

                <!-- Customer's comment form -->
                <div class="bg-white rounded box-shadow dark:bg-gray-900">
                    <p class="p-4 pb-0 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.sales.orders.view.comments')
                    </p>

                    <x-admin::form action="{{ route('admin.sales.orders.comment', $order->id) }}">
                        <div class="p-4">
                            <div class="mb-2.5">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        id="comment"
                                        name="comment"
                                        rules="required"
                                        :label="trans('admin::app.sales.orders.view.comments')"
                                        :placeholder="trans('admin::app.sales.orders.view.write-your-comment')"
                                        rows="3"
                                    />

                                    <x-admin::form.control-group.error control-name="comment" />
                                </x-admin::form.control-group>
                            </div>

                            <div class="flex items-center justify-between">
                                <label
                                    class="flex w-max cursor-pointer select-none items-center gap-1 p-1.5"
                                    for="customer_notified"
                                >
                                    <input
                                        type="checkbox"
                                        name="customer_notified"
                                        id="customer_notified"
                                        value="1"
                                        class="hidden peer"
                                    >

                                    <span
                                        class="text-2xl rounded-md cursor-pointer icon-uncheckbox peer-checked:icon-checked peer-checked:text-blue-600"
                                        role="button"
                                        tabindex="0"
                                    >
                                    </span>

                                    <p class="flex items-center font-semibold text-gray-600 cursor-pointer gap-x-1 hover:text-gray-800 dark:text-gray-300 dark:hover:text-gray-100">
                                        @lang('admin::app.sales.orders.view.notify-customer')
                                    </p>
                                </label>

                                <button
                                    type="submit"
                                    class="secondary-button"
                                    aria-label="{{ trans('admin::app.sales.orders.view.submit-comment') }}"
                                >
                                    @lang('admin::app.sales.orders.view.submit-comment')
                                </button>
                            </div>

                            <!-- Notification Controls -->
                            <v-order-notification></v-order-notification>
                        </div>
                    </x-admin::form>

                    <span class="block w-full border-b dark:border-gray-800"></span>

                    <!-- Comment List -->
                    @foreach ($order->comments()->orderBy('id', 'desc')->get() as $comment)
                        <div class="grid gap-1.5 p-4">
                            <p 
                                class="text-base leading-6 text-gray-800 break-all dark:text-white"
                                v-pre
                            >
                                {{ $comment->comment }}
                            </p>

                            <!-- Notes List Title and Time -->
                            <p class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                                @if ($comment->customer_notified)
                                    <span class="text-2xl text-blue-600 bg-blue-100 rounded-full icon-done h-fit"></span>

                                    @lang('admin::app.sales.orders.view.customer-notified', ['date' => core()->formatDate($comment->created_at, 'Y-m-d H:i:s a')])
                                @else
                                    <span class="text-2xl text-red-600 bg-red-100 rounded-full icon-cancel-1 h-fit"></span>

                                    @lang('admin::app.sales.orders.view.customer-not-notified', ['date' => core()->formatDate($comment->created_at, 'Y-m-d H:i:s a')])
                                @endif
                            </p>
                        </div>

                        <span class="block w-full border-b dark:border-gray-800"></span>
                    @endforeach
                </div>

                {!! view_render_event('bagisto.admin.sales.order.left_component.after', ['order' => $order]) !!}
            </div>

            <!-- Right Component -->
            <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
                {!! view_render_event('bagisto.admin.sales.order.right_component.before', ['order' => $order]) !!}

                <!-- Customer and address information -->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.customer')
                        </p>
                    </x-slot>

                    <x-slot:content v-pre>
                        <div class="{{ $order->billing_address ? 'pb-4' : '' }}">
                            <div class="flex flex-col gap-1.5">
                                <p 
                                    class="font-semibold text-gray-800 dark:text-white"
                                    v-pre
                                >
                                    {{ $order->customer_full_name }}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.customer_full_name.after', ['order' => $order]) !!}

                                <p
                                    class="text-gray-600 dark:text-gray-300"
                                    v-pre
                                >
                                    {{ $order->customer_email }}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.customer_email.after', ['order' => $order]) !!}

                                <p 
                                    class="text-gray-600 dark:text-gray-300"
                                    v-pre
                                >
                                    @lang('admin::app.sales.orders.view.customer-group') : {{ $order->is_guest ? core()->getGuestCustomerGroup()?->name : ($order->customer->group->name ?? '') }}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.customer_group.after', ['order' => $order]) !!}
                            </div>
                        </div>

                        <!-- Billing Address -->
                        @if ($order->billing_address)
                            <span class="block w-full border-b dark:border-gray-800"></span>

                            <div class="{{ $order->shipping_address ? 'pb-4' : '' }}">

                                <div class="flex items-center justify-between">
                                    <p class="py-4 text-base font-semibold text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.billing-address')
                                    </p>
                                </div>

                                @include ('admin::sales.address', ['address' => $order->billing_address])

                                {!! view_render_event('bagisto.admin.sales.order.billing_address.after', ['order' => $order]) !!}
                            </div>
                        @endif

                        <!-- Shipping Address -->
                        @if ($order->shipping_address)
                            <span class="block w-full border-b dark:border-gray-800"></span>

                            <div class="flex items-center justify-between">
                                <p class="py-4 text-base font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.shipping-address')
                                </p>
                            </div>

                            @include ('admin::sales.address', ['address' => $order->shipping_address])

                            {!! view_render_event('bagisto.admin.sales.order.shipping_address.after', ['order' => $order]) !!}
                        @endif
                    </x-slot>
                </x-admin::accordion>

                <!-- Order Information -->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.order-information')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <div class="flex justify-start w-full gap-5">
                            <div class="flex flex-col gap-y-1.5">
                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.order-date')
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.order-status')
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.channel')
                                </p>

                                @if ($order->delivery_date || $order->delivery_time)
                                    <p class="text-gray-600 dark:text-gray-300">
                                        📦 配送希望日
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        🕐 配送時間帯
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-col gap-y-1.5">
                                {!! view_render_event('bagisto.admin.sales.order.created_at.before', ['order' => $order]) !!}

                                <!-- Order Date -->
                                <p class="text-gray-600 dark:text-gray-300">
                                    {{core()->formatDate($order->created_at) }}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.created_at.after', ['order' => $order]) !!}

                                <!-- Order Status -->
                                <p class="text-gray-600 dark:text-gray-300">
                                    {{$order->status_label}}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.status_label.after', ['order' => $order]) !!}

                                <!-- Order Channel -->
                                <p class="text-gray-600 dark:text-gray-300">
                                    {{$order->channel_name}}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.channel_name.after', ['order' => $order]) !!}

                                @if ($order->delivery_date || $order->delivery_time)
                                    <!-- Delivery Date -->
                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y年m月d日') : '—' }}
                                    </p>

                                    <!-- Delivery Time -->
                                    <p class="text-gray-600 dark:text-gray-300">
                                        @php
                                            $slots = config('sagawa.time_slots', []);
                                            $timeLabel = $order->delivery_time ? ($slots[$order->delivery_time] ?? $order->delivery_time) : '—';
                                        @endphp
                                        {{ $timeLabel }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </x-slot>
                </x-admin::accordion>

                <!-- Payment and Shipping Information-->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.payment-and-shipping')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <v-payment-method-switch></v-payment-method-switch>

                        <!-- Shipping Method and Price Details -->
                        @if ($order->shipping_address)
                            <span class="block w-full mt-4 border-b dark:border-gray-800"></span>

                            <div class="pt-4">
                                <p 
                                    class="font-semibold text-gray-800 dark:text-white"
                                    v-pre
                                >
                                    {{ $order->shipping_title }}
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.shipping-method')
                                </p>

                                <p class="pt-4 font-semibold text-gray-800 dark:text-white">
                                    {{ core()->formatBasePrice($order->base_shipping_amount) }}
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.shipping-price')
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.shipping-method.after', ['order' => $order]) !!}
                        @endif
                    </x-slot>
                </x-admin::accordion>

                <!-- Invoice Information-->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.invoices') ({{ count($order->invoices) }})
                        </p>
                    </x-slot>

                    <x-slot:content>
                        @forelse ($order->invoices as $index => $invoice)
                            <div class="grid gap-y-2.5">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-white">
                                        @lang('admin::app.sales.orders.view.invoice-id', ['invoice' => $invoice->increment_id ?? $invoice->id])
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ core()->formatDate($invoice->created_at, 'd M, Y H:i:s a') }}
                                    </p>
                                </div>

                                <div class="flex gap-2.5">
                                    <a
                                        href="{{ route('admin.sales.invoices.view', $invoice->id) }}"
                                        class="text-sm text-blue-600 transition-all hover:underline"
                                    >
                                        @lang('admin::app.sales.orders.view.view')
                                    </a>

                                    <a
                                        href="{{ route('admin.sales.invoices.print', $invoice->id) }}"
                                        class="text-sm text-blue-600 transition-all hover:underline"
                                    >
                                        @lang('admin::app.sales.orders.view.download-pdf')
                                    </a>
                                </div>
                            </div>

                            @if ($index < count($order->invoices) - 1)
                                <span class="block w-full mt-4 mb-4 border-b dark:border-gray-800"></span>
                            @endif
                        @empty
                            <p class="text-gray-600 dark:text-gray-300">
                                @lang('admin::app.sales.orders.view.no-invoice-found')
                            </p>
                        @endforelse
                    </x-slot>
                </x-admin::accordion>

                <!-- Shipment Information-->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.shipments') ({{ count($order->shipments) }})
                        </p>
                    </x-slot>

                    <x-slot:content>
                        @forelse ($order->shipments as $shipment)
                            <div class="grid gap-y-2.5">
                                <div>
                                    <!-- Shipment Id -->
                                    <p class="font-semibold text-gray-800 dark:text-white">
                                        @lang('admin::app.sales.orders.view.shipment', ['shipment' => $shipment->id])
                                    </p>

                                    <!-- Shipment Created -->
                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ core()->formatDate($shipment->created_at, 'd M, Y H:i:s a') }}
                                    </p>
                                </div>

                                <div class="flex gap-2.5">
                                    <a
                                        href="{{ route('admin.sales.shipments.view', $shipment->id) }}"
                                        class="text-sm text-blue-600 transition-all hover:underline"
                                    >
                                        @lang('admin::app.sales.orders.view.view')
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-600 dark:text-gray-300">
                                @lang('admin::app.sales.orders.view.no-shipment-found')
                            </p>
                        @endforelse
                    </x-slot>
                </x-admin::accordion>

                <!-- Refund Information -->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.refund')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        @forelse ($order->refunds as $refund)
                            <div class="grid gap-y-2.5">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-white">
                                        @lang('admin::app.sales.orders.view.refund-id', ['refund' => $refund->id])
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ core()->formatDate($refund->created_at, 'd M, Y H:i:s a') }}
                                    </p>

                                    <p class="mt-4 font-semibold text-gray-800 dark:text-white">
                                        @lang('admin::app.sales.orders.view.name')
                                    </p>

                                    <p 
                                        class="text-gray-600 dark:text-gray-300"
                                        v-pre
                                    >
                                        {{ $refund->order->customer_full_name }}
                                    </p>

                                    <p class="mt-4 font-semibold text-gray-800 dark:text-white">
                                        @lang('admin::app.sales.orders.view.status')
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.refunded')

                                        <span class="font-semibold text-gray-800 dark:text-white">
                                            {{ core()->formatBasePrice($refund->base_grand_total) }}
                                        </span>
                                    </p>
                                </div>

                                <div class="flex gap-2.5">
                                    <a
                                        href="{{ route('admin.sales.refunds.view', $refund->id) }}"
                                        class="text-sm text-blue-600 transition-all hover:underline"
                                    >
                                        @lang('admin::app.sales.orders.view.view')
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-600 dark:text-gray-300">
                                @lang('admin::app.sales.orders.view.no-refund-found')
                            </p>
                        @endforelse
                    </x-slot>
                </x-admin::accordion>

                {!! view_render_event('bagisto.admin.sales.order.right_component.after', ['order' => $order]) !!}
            </div>
        </div>
    </div>

    @pushOnce('scripts')
        {{-- ===== Payment Method Switch Component ===== --}}
        <script type="text/x-template" id="v-payment-method-switch-template">
            <div>
                <!-- Current Payment method -->
                <p class="font-semibold text-gray-800 dark:text-white" v-text="methodLabel"></p>
                <p class="text-gray-600 dark:text-gray-300">@lang('admin::app.sales.orders.view.payment-method')</p>

                @if ($order->base_cod_fee > 0)
                <p class="mt-1 text-sm text-orange-600">
                    代引手数料: {{ core()->formatBasePrice($order->base_cod_fee) }}
                </p>
                @endif

                <!-- Currency -->
                <p class="pt-4 font-semibold text-gray-800 dark:text-white" v-pre>{{ $order->order_currency_code }}</p>
                <p class="text-gray-600 dark:text-gray-300">@lang('admin::app.sales.orders.view.currency')</p>

                @php $additionalDetails = \Webkul\Payment\Payment::getAdditionalDetails($order->payment->method); @endphp
                @if (! empty($additionalDetails))
                    <p class="pt-4 font-semibold text-gray-800 dark:text-white" v-pre>{{ $additionalDetails['title'] }}</p>
                    <p class="text-gray-600 dark:text-gray-300" v-pre>{{ $additionalDetails['value'] }}</p>
                @endif

                {!! view_render_event('bagisto.admin.sales.order.payment-method.after', ['order' => $order]) !!}

                <!-- Switch Payment Button -->
                @if (in_array($order->payment->method, ['cashondelivery', 'moneytransfer']) && ! in_array($order->status, ['completed', 'canceled', 'closed']))
                <div class="mt-4 pt-4 border-t dark:border-gray-800">
                    <button
                        type="button"
                        class="text-sm px-3 py-1.5 rounded border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white transition-colors"
                        @click="showSwitchConfirm = true"
                        :disabled="isSwitching"
                    >
                        <span v-if="isSwitching" class="animate-spin inline-block w-3 h-3 border-2 border-current border-r-transparent rounded-full mr-1"></span>
                        @{{ switchButtonLabel }}
                    </button>

                    <!-- Confirmation Dialog -->
                    <div v-if="showSwitchConfirm" class="mt-3 p-3 rounded bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                        <p class="text-sm text-gray-800 dark:text-gray-200 font-medium mb-1">@{{ confirmTitle }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400" v-if="codFeeDisplay">代引き手数料: <strong>@{{ codFeeDisplay }}</strong></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">新しい合計: <strong>@{{ newTotalDisplay }}</strong></p>
                        <div class="flex gap-2 mt-3">
                            <button
                                type="button"
                                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                                @click="doSwitch"
                            >確認する</button>
                            <button
                                type="button"
                                class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300"
                                @click="showSwitchConfirm = false"
                            >キャンセル</button>
                        </div>
                    </div>

                    <p v-if="switchMessage" class="mt-2 text-sm" :class="switchSuccess ? 'text-green-600' : 'text-red-600'" v-text="switchMessage"></p>
                </div>
                @endif
            </div>
        </script>

        {{-- ===== Order Notification Component ===== --}}
        <script type="text/x-template" id="v-order-notification-template">
            <div class="mt-4 pt-4 border-t dark:border-gray-800">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">顧客に通知</p>
                <div class="flex gap-3 mb-3">
                    <label class="flex items-center gap-1.5 cursor-pointer text-sm">
                        <input type="checkbox" v-model="notifyMessenger" class="rounded border-gray-300">
                        <span class="text-gray-600 dark:text-gray-300">Messenger</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer text-sm">
                        <input type="checkbox" v-model="notifyEmail" class="rounded border-gray-300">
                        <span class="text-gray-600 dark:text-gray-300">Email</span>
                    </label>
                </div>

                <div class="mb-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">テンプレート</label>
                    <select v-model="selectedTemplate" @change="applyTemplate" class="w-full border rounded px-2 py-1 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <option value="">-- 選択 --</option>
                        <option value="processing">承認</option>
                        <option value="shipped">発送済み</option>
                        <option value="delivered">配達済み</option>
                        <option value="completed">完了</option>
                        <option value="canceled">キャンセル</option>
                    </select>
                </div>

                <textarea
                    v-model="message"
                    rows="3"
                    class="w-full border rounded px-2 py-1 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600"
                    placeholder="メッセージ内容..."
                ></textarea>

                <div class="flex items-center gap-2 mt-2">
                    <button
                        type="button"
                        class="text-sm px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                        @click="send"
                        :disabled="isSending || (!notifyMessenger && !notifyEmail)"
                    >
                        <span v-if="isSending" class="animate-spin inline-block w-3 h-3 border-2 border-current border-r-transparent rounded-full mr-1"></span>
                        送信
                    </button>
                    <p v-if="resultMessage" class="text-xs" :class="resultSuccess ? 'text-green-600' : 'text-red-600'" v-text="resultMessage"></p>
                </div>
            </div>
        </script>

        <script type="module">
            // ===== Payment Method Switch =====
            app.component('v-payment-method-switch', {
                template: '#v-payment-method-switch-template',
                data() {
                    return {
                        currentMethod: '{{ $order->payment->method }}',
                        methodLabel: '{{ core()->getConfigData("sales.payment_methods." . $order->payment->method . ".title") ?? $order->payment->method }}',
                        showSwitchConfirm: false,
                        isSwitching: false,
                        switchMessage: '',
                        switchSuccess: false,
                        codFeeUnder: {{ (float)(core()->getConfigData('sales.payment_methods.cashondelivery.cod_fee_under_10000') ?: 330) }},
                        codFeeAbove: {{ (float)(core()->getConfigData('sales.payment_methods.cashondelivery.cod_fee_10000_and_above') ?: 440) }},
                        baseAmount: {{ $order->base_grand_total - $order->base_cod_fee }},
                        currentCodFee: {{ $order->base_cod_fee ?? 0 }},
                    }
                },
                computed: {
                    targetMethod() {
                        return this.currentMethod === 'cashondelivery' ? 'moneytransfer' : 'cashondelivery';
                    },
                    switchButtonLabel() {
                        return this.currentMethod === 'cashondelivery'
                            ? '銀行振込に変更'
                            : '代金引換に変更';
                    },
                    confirmTitle() {
                        return this.currentMethod === 'cashondelivery'
                            ? '代金引換 → 銀行振込 に変更します'
                            : '銀行振込 → 代金引換 に変更します';
                    },
                    codFee() {
                        return this.baseAmount < 10000 ? this.codFeeUnder : this.codFeeAbove;
                    },
                    codFeeDisplay() {
                        return this.targetMethod === 'cashondelivery' ? `¥${this.codFee.toLocaleString()}` : '';
                    },
                    newTotal() {
                        return this.targetMethod === 'cashondelivery'
                            ? this.baseAmount + this.codFee
                            : this.baseAmount;
                    },
                    newTotalDisplay() {
                        return `¥${this.newTotal.toLocaleString()}`;
                    }
                },
                methods: {
                    doSwitch() {
                        this.isSwitching = true;
                        this.switchMessage = '';

                        this.$axios.post("{{ route('admin.sales.orders.switch_payment', $order->id) }}", {
                            payment_method: this.targetMethod,
                        })
                        .then(response => {
                            this.switchSuccess = true;
                            this.switchMessage = `支払方法を「${response.data.method_label}」に変更しました。`;
                            this.currentMethod = response.data.new_method;
                            this.methodLabel = response.data.method_label;
                            this.currentCodFee = response.data.cod_fee_applied;
                            this.baseAmount = response.data.new_grand_total - response.data.cod_fee_applied;
                            this.showSwitchConfirm = false;
                            // Reload page after short delay to reflect updated totals
                            setTimeout(() => window.location.reload(), 1500);
                        })
                        .catch(error => {
                            this.switchSuccess = false;
                            this.switchMessage = error.response?.data?.message || 'エラーが発生しました。';
                        })
                        .finally(() => {
                            this.isSwitching = false;
                        });
                    }
                }
            });

            // ===== Order Notification =====
            app.component('v-order-notification', {
                template: '#v-order-notification-template',
                data() {
                    return {
                        notifyMessenger: true,
                        notifyEmail: true,
                        selectedTemplate: '',
                        message: '',
                        isSending: false,
                        resultMessage: '',
                        resultSuccess: false,
                    }
                },
                methods: {
                    applyTemplate() {
                        const orderId = '{{ $order->increment_id }}';
                        const templates = {
                            processing: `ご注文 #${orderId} が承認されました。`,
                            shipped:    `ご注文 #${orderId} が発送されました。`,
                            delivered:  `ご注文 #${orderId} が配達されました。`,
                            completed:  `ご注文 #${orderId} が完了しました。ありがとうございました。`,
                            canceled:   `ご注文 #${orderId} はキャンセルされました。`,
                        };
                        this.message = templates[this.selectedTemplate] || '';
                    },
                    send() {
                        if (!this.message.trim()) {
                            this.resultSuccess = false;
                            this.resultMessage = 'メッセージを入力してください。';
                            return;
                        }
                        this.isSending = true;
                        this.resultMessage = '';

                        this.$axios.post("{{ route('admin.sales.orders.send_notification', $order->id) }}", {
                            notify_messenger: this.notifyMessenger,
                            notify_email: this.notifyEmail,
                            message: this.message,
                        })
                        .then(response => {
                            this.resultSuccess = true;
                            const parts = [];
                            if (response.data.messenger_sent) parts.push('Messenger');
                            if (response.data.email_sent) parts.push('Email');
                            this.resultMessage = parts.length
                                ? `${parts.join(' + ')} で送信しました。`
                                : '送信先が見つかりませんでした。';
                        })
                        .catch(() => {
                            this.resultSuccess = false;
                            this.resultMessage = '送信エラーが発生しました。';
                        })
                        .finally(() => {
                            this.isSending = false;
                        });
                    }
                }
            });
        </script>
    @endPushOnce

    {{-- ===== ZIP Autocomplete + Copy Billing to Shipping (Task 4) ===== --}}
    @pushOnce('scripts')
        <script type="module">
            document.addEventListener('DOMContentLoaded', function() {
                // ZIP code autocomplete for Japan addresses
                document.querySelectorAll('input[name*="postcode"]').forEach(function(input) {
                    input.addEventListener('blur', function() {
                        const zip = this.value.replace(/[-\s]/g, '');
                        if (zip.length !== 7 || !/^\d{7}$/.test(zip)) return;

                        const form = this.closest('form') || this.closest('[class*="address"]');
                        if (!form) return;

                        fetch(`https://zipcloud.ibsregion.jp/api/search?zipcode=${zip}`)
                            .then(r => r.json())
                            .then(data => {
                                if (!data.results || !data.results[0]) return;
                                const r = data.results[0];
                                // Try to fill state and city fields in the same form/section
                                const stateInput = form.querySelector('input[name*="state"], select[name*="state"]');
                                const cityInput  = form.querySelector('input[name*="city"]');
                                const addrInput  = form.querySelector('input[name*="address"]');

                                if (stateInput) {
                                    if (stateInput.tagName === 'SELECT') {
                                        for (const opt of stateInput.options) {
                                            if (opt.text.includes(r.address1) || opt.value === r.address1) {
                                                stateInput.value = opt.value;
                                                stateInput.dispatchEvent(new Event('change', { bubbles: true }));
                                                break;
                                            }
                                        }
                                    } else {
                                        stateInput.value = r.address1;
                                        stateInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    }
                                }
                                if (cityInput) {
                                    cityInput.value = r.address2 + r.address3;
                                    cityInput.dispatchEvent(new Event('input', { bubbles: true }));
                                }
                            })
                            .catch(() => {});
                    });
                });
            });
        </script>
    @endPushOnce
</x-admin::layouts>
