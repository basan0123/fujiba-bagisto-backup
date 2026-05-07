<?php

namespace Webkul\Shipping\Handlers;

use Webkul\Shipping\DTOs\CartDTO;
use Webkul\Shipping\Models\CoolFeeSetting;
use Webkul\Shipping\Services\ShippingContext;

class CoolFeeHandler implements ShippingHandlerInterface
{
    public function getPriority(): int { return 20; }

    public function supports(CartDTO $cart): bool { return true; }

    public function handle(CartDTO $cart, ShippingContext $ctx): void
    {
        $setting = CoolFeeSetting::globalOrChannel($cart->channelId)
            ->active()->byPriority()->first();

        if (! $setting) return;

        $coolFee   = 0;
        $frozenFee = 0;
        $hasCool   = false;
        $hasFrozen = false;

        foreach ($cart->items as $item) {
            if ($item->excludeFromCoolFee) continue;

            if ($item->temperatureType === 'cool' && $setting->cool_enabled) {
                $hasCool = true;
                if ($setting->fee_per_item) {
                    $coolFee += $setting->cool_box_fee * $item->quantity;
                } else {
                    $coolFee = $setting->cool_box_fee;
                }
            }

            if ($item->temperatureType === 'frozen' && $setting->frozen_enabled) {
                $hasFrozen = true;
                if ($setting->fee_per_item) {
                    $frozenFee += $setting->frozen_box_fee * $item->quantity;
                } else {
                    $frozenFee = $setting->frozen_box_fee;
                }
            }
        }

        if ($setting->max_fee_cap) {
            $coolFee   = min($coolFee, $setting->max_fee_cap);
            $frozenFee = min($frozenFee, $setting->max_fee_cap);
        }

        $ctx->coolFee   = $coolFee;
        $ctx->frozenFee = $frozenFee;

        if ($hasCool) {
            $ctx->appliedRules[] = 'cool_fee';
            $ctx->breakdown[]    = ['label' => 'クール便料金', 'amount' => $coolFee];
        }
        if ($hasFrozen) {
            $ctx->appliedRules[] = 'frozen_fee';
            $ctx->breakdown[]    = ['label' => '冷凍便料金', 'amount' => $frozenFee];
        }
    }
}
