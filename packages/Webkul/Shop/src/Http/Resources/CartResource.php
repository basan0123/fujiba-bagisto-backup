<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Tax\Facades\Tax;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $taxes = collect(Tax::getTaxRatesWithAmount($this, true))->map(function ($rate) {
            return core()->currency($rate ?? 0);
        });

        $shippingBreakdown = $this->getShippingBreakdown();

        return [
            'id' => $this->id,
            'is_guest' => $this->is_guest,
            'customer_id' => $this->customer_id,
            'items_count' => $this->items_count,
            'items_qty' => $this->items_qty,
            'applied_taxes' => $taxes,
            'tax_total' => $this->tax_total,
            'formatted_tax_total' => core()->formatPrice($this->tax_total),
            'sub_total_incl_tax' => $this->sub_total_incl_tax,
            'sub_total' => $this->sub_total,
            'formatted_sub_total_incl_tax' => core()->formatPrice($this->sub_total_incl_tax),
            'formatted_sub_total' => core()->formatPrice($this->sub_total),
            'coupon_code' => $this->coupon_code,
            'discount_amount' => $this->discount_amount,
            'formatted_discount_amount' => core()->formatPrice($this->discount_amount),
            'shipping_method' => $this->shipping_method,
            'shipping_amount' => $this->shipping_amount,
            'formatted_shipping_amount' => core()->formatPrice($this->shipping_amount),
            'shipping_amount_incl_tax' => $this->shipping_amount_incl_tax,
            'formatted_shipping_amount_incl_tax' => core()->formatPrice($this->shipping_amount_incl_tax),
            'cool_fee' => $shippingBreakdown['cool_fee'],
            'formatted_cool_fee' => core()->formatPrice($shippingBreakdown['cool_fee']),
            'frozen_fee' => $shippingBreakdown['frozen_fee'],
            // Shipping fees are admin-set in JPY and must NOT be converted
            // by display currency. formatBasePrice() forces base-currency
            // (JPY) output regardless of the customer's chosen currency.
            'formatted_frozen_fee' => core()->formatBasePrice($shippingBreakdown['frozen_fee']),
            'base_shipping_fee' => $shippingBreakdown['base_shipping'],
            'formatted_base_shipping_fee' => core()->formatBasePrice($shippingBreakdown['base_shipping']),
            'grand_total' => $this->grand_total,
            'formatted_grand_total' => core()->formatPrice($this->grand_total),
            'cod_fee' => $this->getCodFee(),
            // COD fee is admin-set in JPY and must NOT be converted by the
            // current display currency. formatBasePrice() always renders in
            // the base currency (JPY) so the displayed amount matches the
            // amount actually charged.
            'cod_fee_formatted' => $this->getCodFee() > 0
                ? core()->formatBasePrice($this->getCodFee())
                : null,
            'items' => CartItemResource::collection($this->items),
            'billing_address' => new AddressResource($this->billing_address),
            'shipping_address' => new AddressResource($this->shipping_address),
            'have_stockable_items' => $this->haveStockableItems(),
            'payment_method' => $this->payment?->method,
            'payment_method_title' => core()->getConfigData('sales.payment_methods.'.$this->payment?->method.'.title'),
        ];
    }

    private function getShippingBreakdown(): array
    {
        $default = ['cool_fee' => 0, 'frozen_fee' => 0, 'base_shipping' => 0, 'breakdown' => []];

        $rate = $this->selected_shipping_rate;
        if (! $rate || empty($rate->method_description)) {
            return $default;
        }

        $data = json_decode($rate->method_description, true);
        if (! is_array($data)) {
            return $default;
        }

        return array_merge($default, $data);
    }

    /**
     * Resolve the COD fee for this cart using the shop's checkout-rules
     * service. Returns 0 when payment is not COD or service unavailable.
     */
    private function getCodFee(): float
    {
        $method = $this->payment?->method;
        if ($method !== 'cashondelivery') {
            return 0.0;
        }

        try {
            $svc = app(\Webkul\Checkout\Services\CheckoutRulesService::class);
            return (float) $svc->getCODFee((float) $this->sub_total, $method);
        } catch (\Throwable) {
            return 0.0;
        }
    }
}
