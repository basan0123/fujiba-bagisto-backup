<?php

namespace Webkul\Shipping\Handlers;

use Webkul\Shipping\DTOs\CartDTO;
use Webkul\Shipping\Models\WeightShippingRate;
use Webkul\Shipping\Services\ShippingContext;

class WeightShippingHandler implements ShippingHandlerInterface
{
    public function getPriority(): int { return 35; }

    public function supports(CartDTO $cart): bool
    {
        return $cart->totalWeight > 0;
    }

    public function handle(CartDTO $cart, ShippingContext $ctx): void
    {
        if ($ctx->freeShippingApplied) return;
        if ($ctx->baseShipping > 0) return;

        $rate = WeightShippingRate::globalOrChannel($cart->channelId)
            ->active()
            ->where(fn($q) => $q->whereNull('prefecture_code')
                ->orWhere('prefecture_code', $cart->prefectureCode))
            ->where('weight_from', '<=', $cart->totalWeight)
            ->where('weight_to', '>=', $cart->totalWeight)
            ->orderBy('priority')
            ->first();

        if ($rate) {
            $ctx->baseShipping   = $rate->shipping_price;
            $ctx->appliedRules[] = 'weight:' . $cart->totalWeight . 'kg';
            $ctx->breakdown[]    = [
                'label'  => ($rate->label ?: '重量送料') . ' ' . $cart->totalWeight . 'kg',
                'amount' => $rate->shipping_price,
            ];
        }
    }
}
