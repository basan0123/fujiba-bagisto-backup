<?php

namespace Webkul\Shipping\Handlers;

use Webkul\Shipping\DTOs\CartDTO;
use Webkul\Shipping\Models\PrefectureShippingRate;
use Webkul\Shipping\Models\ShippingZone;
use Webkul\Shipping\Services\ShippingContext;

class PrefectureShippingHandler implements ShippingHandlerInterface
{
    public function getPriority(): int { return 40; }

    public function supports(CartDTO $cart): bool
    {
        return ! empty($cart->prefectureCode);
    }

    public function handle(CartDTO $cart, ShippingContext $ctx): void
    {
        if ($ctx->freeShippingApplied) return;

        $totalQty = collect($cart->items)
            ->reject(fn($i) => $i->excludeFromFreeShipping)
            ->sum(fn($i) => $i->quantity);

        // ── 1. Try zone rate first ──────────────────────────────────────────
        $zone = ShippingZone::active()
            ->globalOrChannel($cart->channelId)
            ->whereHas('prefectures', fn($q) => $q->where('prefecture_code', $cart->prefectureCode))
            ->orderByRaw('channel_id IS NULL ASC') // prefer channel-specific over global
            ->first();

        if ($zone) {
            $zoneRate = $zone->rates()
                ->where('qty_from', '<=', $totalQty)
                ->where('qty_to',   '>=', $totalQty)
                ->where('is_active', true)
                ->first();

            if ($zoneRate) {
                $ctx->baseShipping   = $zoneRate->shipping_price;
                $ctx->appliedRules[] = 'zone:' . $zone->zone_name;
                $ctx->breakdown[]    = [
                    'label'  => $zone->zone_name . ' ' . $totalQty . '個',
                    'amount' => $zoneRate->shipping_price,
                ];
                return;
            }
        }

        // ── 2. Fall back to individual prefecture rate ──────────────────────
        $rate = PrefectureShippingRate::globalOrChannel($cart->channelId)
            ->active()
            ->forPrefecture($cart->prefectureCode)
            ->where('qty_from', '<=', $totalQty)
            ->where('qty_to',   '>=', $totalQty)
            ->orderBy('priority')
            ->first();

        if ($rate) {
            $ctx->baseShipping   = $rate->shipping_price;
            $ctx->appliedRules[] = 'prefecture:' . $rate->prefecture_code;
            $ctx->breakdown[]    = [
                'label'  => $rate->prefecture_name_ja . ' ' . $totalQty . '個',
                'amount' => $rate->shipping_price,
            ];
        }
    }
}

