<?php

namespace Webkul\Shipping\Handlers;

use Webkul\Shipping\DTOs\CartDTO;
use Webkul\Shipping\Models\FixedShippingFee;
use Webkul\Shipping\Services\ShippingContext;

class FixedShippingHandler implements ShippingHandlerInterface
{
    public function getPriority(): int { return 50; }

    public function supports(CartDTO $cart): bool { return true; }

    public function handle(CartDTO $cart, ShippingContext $ctx): void
    {
        if ($ctx->freeShippingApplied) return;
        if ($ctx->baseShipping > 0) return;

        $fee = FixedShippingFee::globalOrChannel($cart->channelId)
            ->active()->byPriority()->first();

        if ($fee) {
            $ctx->baseShipping   = $fee->fee_amount;
            $ctx->appliedRules[] = 'fixed_fee:' . $fee->id;
            $ctx->breakdown[]    = ['label' => $fee->label, 'amount' => $fee->fee_amount];
            return;
        }

        $fallback = (float) config('shipping-zones.default_flat_rate', 650);
        if ($fallback > 0) {
            $ctx->baseShipping   = $fallback;
            $ctx->appliedRules[] = 'fixed_fee:config_fallback';
            $ctx->breakdown[]    = ['label' => '送料', 'amount' => $fallback];
        }
    }
}
