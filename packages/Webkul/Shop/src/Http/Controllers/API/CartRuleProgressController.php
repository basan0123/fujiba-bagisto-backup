<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Checkout\Facades\Cart;
use Webkul\Shop\Http\Controllers\Controller;

/**
 * Returns "how close is the customer to triggering a cart_rule" data
 * so the shop cart page can show a progress banner.
 *
 * Reads thresholds straight from the active cart_rules' `conditions` JSON
 * — no hardcoded numbers. Channel + customer-group filtered. Localised
 * labels resolved client-side from translation strings.
 */
class CartRuleProgressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cart = Cart::getCart();

        $cartItemsQty = 0;
        $maxLineQty   = 0;
        $cartId       = null;

        if ($cart) {
            $cartId       = $cart->id;
            $cartItemsQty = (int) $cart->items_qty;
            foreach ($cart->items as $item) {
                $maxLineQty = max($maxLineQty, (int) $item->quantity);
            }
        }

        $channel       = core()->getCurrentChannel();
        $channelId     = $channel?->id;
        $customerGroup = $this->resolveCustomerGroupId();
        $now           = now();

        // Request-host allow-list — popup fires only when the customer is on
        // a kimono.* hostname. Channels' hostname column is shared across
        // tenant aliases, so we read the live HTTP host instead.
        // Override via CRP_ALLOWED_HOSTS="host1,host2"; default = kimono.*.
        $allowedHosts = array_filter(array_map('trim', explode(
            ',',
            (string) env('CRP_ALLOWED_HOSTS', 'kimono.fujiba.co.jp,kimono.snssale.com')
        )));
        $requestHost = $request->getHost();

        if (! empty($allowedHosts) && ! in_array($requestHost, $allowedHosts, true)) {
            return new JsonResponse([
                'cart_id'        => $cartId,
                'cart_items_qty' => $cartItemsQty,
                'max_line_qty'   => $maxLineQty,
                'channel'        => $channel?->code,
                'rules'          => [],
            ]);
        }

        $rules = DB::table('cart_rules')
            ->where('status', 1)
            ->where(fn ($q) => $q->whereNull('starts_from')->orWhere('starts_from', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_till')->orWhere('ends_till', '>=', $now))
            ->where('coupon_type', 1) // 1 = no-coupon (auto-apply)
            ->whereExists(function ($q) use ($channelId) {
                $q->select(DB::raw(1))
                    ->from('cart_rule_channels')
                    ->whereColumn('cart_rule_channels.cart_rule_id', 'cart_rules.id')
                    ->where('cart_rule_channels.channel_id', $channelId);
            })
            ->whereExists(function ($q) use ($customerGroup) {
                $q->select(DB::raw(1))
                    ->from('cart_rule_customer_groups')
                    ->whereColumn('cart_rule_customer_groups.cart_rule_id', 'cart_rules.id')
                    ->where('cart_rule_customer_groups.customer_group_id', $customerGroup);
            })
            ->orderBy('sort_order')
            ->get(['id', 'name', 'description', 'action_type', 'discount_amount', 'discount_quantity', 'discount_step', 'conditions']);

        $progress = [];

        foreach ($rules as $rule) {
            $analysis = $this->nextTier($rule, $cartItemsQty, $maxLineQty);
            if (! $analysis) {
                continue;
            }

            $progress[] = [
                'rule_id'         => (int) $rule->id,
                'name'            => $rule->name,
                'description'     => $rule->description,
                'action_type'     => $rule->action_type,
                'discount_amount' => (float) $rule->discount_amount,
                'current'         => $analysis['current'],
                'target'          => $analysis['target'],
                'remaining'       => $analysis['remaining'],
                'attribute'       => $analysis['attribute'],
                'tier_index'      => $analysis['tier_index'],
            ];
        }

        // Show the closest-to-trigger rule first.
        usort($progress, fn ($a, $b) => $a['remaining'] <=> $b['remaining']);

        return new JsonResponse([
            'cart_id'        => $cartId,
            'cart_items_qty' => $cartItemsQty,
            'max_line_qty'   => $maxLineQty,
            'channel'        => $channel?->code,
            'rules'          => $progress,
        ]);
    }

    /**
     * Tier-aware progress for a single rule, **always evaluated against the
     * total cart quantity** (Option A).
     *
     * Even if the rule's `conditions` JSON uses `cart_item|quantity` (single
     * line), we treat the threshold as a goal on the total cart. This gives
     * the customer a delightful "add 1 more!" experience regardless of
     * product mix. Note: this means the popup may promise a discount that
     * the rule engine won't actually grant (e.g. customer has 11 spread
     * across products but the rule needs 11 on one line). The admin should
     * configure rules with `cart|items_qty` conditions to keep the promise.
     *
     * Algorithm:
     *   1. Find the lowest qty threshold T0 in conditions (any qty attribute).
     *   2. With step S = max(1, discount_step), tier targets are T0, T0+S, T0+2S…
     *   3. Pick the smallest target T_k >= cart_items_qty and report
     *      (target, remaining=T_k-cart_items_qty, tier_index=k).
     */
    private function nextTier($rule, int $cartItemsQty, int $maxLineQty): ?array
    {
        $conditions = json_decode($rule->conditions ?: '[]', true);
        if (! is_array($conditions)) {
            return null;
        }

        // Flatten one level of nesting (Bagisto sometimes wraps groups).
        $flat = [];
        foreach ($conditions as $c) {
            if (isset($c['conditions']) && is_array($c['conditions'])) {
                foreach ($c['conditions'] as $inner) {
                    $flat[] = $inner;
                }
            } else {
                $flat[] = $c;
            }
        }

        // Find the lowest qty threshold across all qty-related conditions.
        // We accept BOTH cart|items_qty and cart_item|quantity as inputs to
        // the tier base, but always measure progress against the TOTAL cart.
        $base0 = null;
        foreach ($flat as $c) {
            $attr = $c['attribute'] ?? null;
            if (($c['attribute_type'] ?? '') !== 'integer') continue;
            if (! in_array($attr, ['cart|items_qty', 'cart_item|quantity'], true)) continue;

            $val = (int) ($c['value'] ?? 0);
            $op  = $c['operator'] ?? '==';
            if ($val <= 0) continue;

            $minSatisfying = match ($op) {
                '>=', '==' => $val,
                '>'        => $val + 1,
                default    => null,
            };
            if ($minSatisfying === null) continue;

            if ($base0 === null || $minSatisfying < $base0) {
                $base0 = $minSatisfying;
            }
        }

        if ($base0 === null) {
            return null;
        }

        $current = $cartItemsQty;                                 // ← Option A: total cart
        $step    = max(1, (int) ($rule->discount_step ?? 0));

        if ($current < $base0) {
            $tierIndex = 0;
            $target    = $base0;
        } else {
            $tierIndex = (int) floor(($current - $base0) / $step) + 1;
            $target    = $base0 + $tierIndex * $step;
        }

        return [
            'attribute'  => 'cart|items_qty',                      // popup wording: "Add N more items"
            'current'    => $current,
            'target'     => $target,
            'remaining'  => $target - $current,
            'tier_index' => $tierIndex,
        ];
    }

    private function resolveCustomerGroupId(): int
    {
        if (auth()->guard('customer')->check()) {
            $customer = auth()->guard('customer')->user();
            $groupId  = $customer->customer_group_id ?? null;
            if ($groupId) {
                return (int) $groupId;
            }
        }

        $guestGroupId = DB::table('customer_groups')->where('code', 'guest')->value('id');
        return (int) ($guestGroupId ?? 1);
    }
}
