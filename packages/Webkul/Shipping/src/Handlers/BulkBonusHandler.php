<?php

namespace Webkul\Shipping\Handlers;

use Webkul\Shipping\DTOs\CartDTO;
use Webkul\Shipping\Models\BulkBonusRule;
use Webkul\Shipping\Services\ShippingContext;

class BulkBonusHandler implements ShippingHandlerInterface
{
    public function getPriority(): int { return 10; }

    public function supports(CartDTO $cart): bool { return true; }

    public function handle(CartDTO $cart, ShippingContext $ctx): void
    {
        $rules = BulkBonusRule::globalOrChannel($cart->channelId)
            ->active()->valid()->byPriority()->get();

        foreach ($cart->items as $item) {
            $matching = $rules->filter(fn($r) =>
                (is_null($r->product_id)  || $r->product_id  === $item->productId) &&
                (is_null($r->category_id) || $r->category_id === $item->categoryId)
            )->sortByDesc('buy_qty');

            $totalFree  = 0;
            $appliedIds = [];

            foreach ($matching as $rule) {
                if ($item->quantity < $rule->buy_qty) continue;

                $freeQty = (int) floor($item->quantity / $rule->buy_qty) * $rule->free_qty;

                if ($rule->max_free_per_order) {
                    $freeQty = min($freeQty, $rule->max_free_per_order);
                }

                if ($rule->stackable) {
                    $totalFree  += $freeQty;
                    $appliedIds[] = $rule->id;
                } else {
                    $totalFree  = max($totalFree, $freeQty);
                    $appliedIds = [$rule->id];
                    break;
                }
            }

            if ($totalFree > 0) {
                $ctx->bonusItems[]   = [
                    'product_id' => $item->productId,
                    'qty'        => $totalFree,
                    'rule_ids'   => $appliedIds,
                ];
                $ctx->appliedRules[] = 'bulk_bonus:product_' . $item->productId;
            }
        }
    }
}
