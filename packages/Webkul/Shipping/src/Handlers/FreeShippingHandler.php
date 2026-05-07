<?php

namespace Webkul\Shipping\Handlers;

use Webkul\Shipping\DTOs\CartDTO;
use Webkul\Shipping\Models\FreeShippingThreshold;
use Webkul\Shipping\Services\ShippingContext;

class FreeShippingHandler implements ShippingHandlerInterface
{
    public function getPriority(): int { return 30; }

    public function supports(CartDTO $cart): bool { return true; }

    public function handle(CartDTO $cart, ShippingContext $ctx): void
    {
        $thresholds = FreeShippingThreshold::globalOrChannel($cart->channelId)
            ->active()->byPriority()
            ->orderBy('threshold_amount', 'desc')
            ->get();

        $prefCode = $cart->prefectureCode;

        // Prefecture-specific rules first, then general (null) rules
        $matched = $thresholds
            ->filter(fn ($t) => ! empty($t->prefecture_codes) && in_array($prefCode, $t->prefecture_codes))
            ->first(fn ($t) => $cart->subtotalExcludingExclusions >= $t->threshold_amount);

        if (! $matched) {
            $hasPrefRule = $thresholds->contains(fn ($t) => ! empty($t->prefecture_codes) && in_array($prefCode, $t->prefecture_codes));

            if (! $hasPrefRule) {
                $matched = $thresholds
                    ->filter(fn ($t) => empty($t->prefecture_codes))
                    ->first(fn ($t) => $cart->subtotalExcludingExclusions >= $t->threshold_amount);
            }
        }

        if ($matched) {
            $ctx->freeShippingApplied = true;
            $ctx->freeShippingReason  = $matched->label;
            $ctx->baseShipping        = 0;

            if ($matched->apply_to_cool) {
                $ctx->coolFee = 0;
            }

            if ($matched->apply_to_frozen) {
                $ctx->frozenFee = 0;
            }

            $ctx->appliedRules[] = 'free_shipping:' . $matched->id;
            $ctx->breakdown[]    = ['label' => $matched->label, 'amount' => 0];
            return;
        }

        // Not free — find the best base_shipping_fee rule for this prefecture
        $feeRule = $thresholds
            ->filter(fn ($t) => ! empty($t->prefecture_codes) && in_array($prefCode, $t->prefecture_codes) && $t->base_shipping_fee > 0)
            ->first();

        if (! $feeRule) {
            $hasPrefRule = $thresholds->contains(fn ($t) => ! empty($t->prefecture_codes) && in_array($prefCode, $t->prefecture_codes));

            if (! $hasPrefRule) {
                $feeRule = $thresholds
                    ->filter(fn ($t) => empty($t->prefecture_codes) && $t->base_shipping_fee > 0)
                    ->first();
            }
        }

        if ($feeRule && $feeRule->base_shipping_fee > 0) {
            $ctx->baseShipping   = $feeRule->base_shipping_fee;
            $ctx->appliedRules[] = 'base_fee:' . $feeRule->id;
            $ctx->breakdown[]    = ['label' => '送料', 'amount' => $feeRule->base_shipping_fee];
        }
    }
}
