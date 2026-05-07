<?php

namespace Fujiba\PwaFrontend\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class PwaFrontendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/system.php', 'core');

        // Register the PWA messenger autologin route in `register()` (not
        // `boot`) so it lands BEFORE Webkul/PWA's `pwa/{any?}` SPA catch-all
        // route — otherwise the catch-all swallows the URL and the SPA HTML
        // is returned instead of the controller running.
        $this->loadRoutesFrom(__DIR__.'/../Routes/shop.php');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'pwa-frontend');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Fujiba\PwaFrontend\Console\Commands\SeedKimonoSeoCommand::class,
                \Fujiba\PwaFrontend\Console\Commands\SeedKimonoSeoTenantCommand::class,
            ]);
        }

        // Inject the per-channel brand CSS into every Bagisto Blade
        // storefront page via the standard view-render-event hook. The PWA
        // SPA pulls the same fields directly in master.blade.
        Event::listen('bagisto.shop.layout.head.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('pwa-frontend::partials.brand-css');
            // SEO: canonical + hreflang on every storefront page (skip-list
            // inside the partial covers checkout/account/admin).
            $viewRenderEventManager->addTemplate('pwa-frontend::seo.head-meta');
            // Organization JSON-LD: emits only on shop.home.index (guard
            // inside the partial). Cheap no-op on every other page.
            $viewRenderEventManager->addTemplate('pwa-frontend::seo.head-organization');
        });

        // Product JSON-LD + breadcrumb: hooked to the product page event so
        // we get $product passed in via view_render_event data.
        Event::listen('bagisto.shop.products.view.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('pwa-frontend::seo.body-product-schema');
            $viewRenderEventManager->addTemplate('pwa-frontend::seo.body-breadcrumb');
        });

        // Category breadcrumb: re-fetches $category from the request slug.
        // Bagisto's category event doesn't pass the model — the partial
        // resolves it itself.
        Event::listen('bagisto.shop.categories.view.banner_path.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('pwa-frontend::seo.body-breadcrumb');
        });

        // Shipping & COD must not be converted by exchange rate.
        // Bagisto's flat-rate carrier multiplies the admin-set JPY rate by
        // the current currency rate when assigning $rate->price, so a US-
        // dollar customer sees "$5.50 shipping" for what should be "¥500".
        //
        // We force `price = base_price` at two points:
        //   1. The model's `saving` lifecycle event covers the rate-listing
        //      step (before the user selects a method) AND the post-select
        //      persistence path.
        //   2. The cart `collect.totals.before` event covers the case where
        //      the rate was created in-memory only.
        \Webkul\Checkout\Models\CartShippingRate::saving(function ($rate) {
            try {
                if ($rate->base_price !== null) {
                    $rate->price = $rate->base_price;
                }
                if ($rate->base_price_incl_tax !== null) {
                    $rate->price_incl_tax = $rate->base_price_incl_tax;
                }
            } catch (\Throwable) {}
        });

        Event::listen('checkout.cart.collect.totals.before', function ($cart) {
            try {
                if (! $cart || ! $cart->selected_shipping_rate) return;
                $rate = $cart->selected_shipping_rate;
                $rate->price          = $rate->base_price;
                $rate->price_incl_tax = $rate->base_price_incl_tax;
                $rate->save();
            } catch (\Throwable) {}
        });

        // Auto-apply admin-configured bulk-bonus rules.
        //
        // The original Webkul\Shipping\Handlers\BulkBonusHandler computes
        // results but the carrier never persists them, so customers never
        // saw the promised discount. We replace that path with a generic,
        // 100%-admin-driven evaluator:
        //
        //   - Rules live in `bulk_bonus_rules`. Each rule can be:
        //       * discount_type=free_items       → free_qty × unit_price off
        //       * discount_type=percent_off_order→ percent_off% off entire cart
        //   - Optional `min_product_price` filters out items below that price
        //   - `replaces_lower_tiers=true` makes a rule the SOLE active tier
        //     for that line/cart (other matching rules are ignored)
        //   - `block_coupons=true` strips any manual coupon when this rule
        //     fires and toasts the customer that the coupon was removed
        //   - `channel_id=null` matches every channel; otherwise per-channel
        //
        // Math runs on every collect-totals pass so qty changes re-evaluate
        // automatically. No PHP changes required to add new tiers — admins
        // just add rows in /admin/shipping/bulk-bonus.
        // We listen on `collect.totals.after` instead of `before` because
        // Webkul\CartRule\Listeners\Cart@applyCartRules — registered on the
        // same `before` event — does `$item->discount_amount = 0` on every
        // pass and reapplies only its own (cart-rule-driven) discount. Any
        // bulk-bonus discount we wrote during `before` would be silently
        // wiped before the cart sum runs at line 866 of Webkul\Checkout\Cart.
        // Running on `after` means: cart-rule discounts are already in,
        // grand_total has already been summed, and we layer our bulk-bonus
        // discount on top — updating both per-item fields (so order +
        // invoice carry the figure) AND the cart-level totals.
        Event::listen('checkout.cart.collect.totals.after', function ($cart) {
            try {
                if (! $cart || ! $cart->items || ! $cart->items->count()) return;

                $rules = \Webkul\Shipping\Models\BulkBonusRule::query()
                    ->where(function ($q) use ($cart) {
                        $q->whereNull('channel_id')
                          ->orWhere('channel_id', $cart->channel_id);
                    })
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                    })
                    ->orderBy('priority')
                    ->get();

                // Always reset bulk-bonus discount on every pass so removed
                // rules / qty drops cleanly clear out previous discounts.
                foreach ($cart->items as $item) {
                    $additional = \Fujiba\PwaFrontend\Providers\PwaFrontendServiceProvider::extractAdditional($item);
                    if (isset($additional['bulk_bonus'])) {
                        unset($additional['bulk_bonus']);
                        $item->additional = $additional;
                        $item->discount_amount      = 0;
                        $item->base_discount_amount = 0;
                        $item->save();
                    }
                }

                if ($rules->isEmpty()) return;

                $blockCoupons = false;
                $cartLevelPercentRule = null;
                $cartLevelPercentBaseTotal = 0.0;
                $cartLevelPercentTotal     = 0.0;

                $cartLevelFixedRule        = null;
                $cartLevelFixedAmount      = 0.0;
                $cartLevelFixedBaseAmount  = 0.0;
                $cartLevelFixedTotalRow    = 0.0;
                $cartLevelFixedBaseRow     = 0.0;

                // ── Pass 1: determine the dominant cart-level tier (if any).
                // The cart-level "X% off entire order" rules apply to the
                // whole cart sub-total (qualifying items only). We pick the
                // single highest-priority percent rule whose `buy_qty` is met
                // by the qualifying-item count. `replaces_lower_tiers` on
                // such a rule means lower-tier per-item bonuses are skipped.
                $qualifyingTotalQty = 0;
                $qualifyingTotalRow = 0.0;
                $qualifyingBaseRow  = 0.0;

                $percentRules = $rules->where('discount_type', \Webkul\Shipping\Models\BulkBonusRule::DISCOUNT_PERCENT)
                    ->sortByDesc('buy_qty');

                if ($percentRules->isNotEmpty()) {
                    foreach ($cart->items as $item) {
                        $unit = (float) $item->price;
                        // Find any percent-rule's min_product_price filter
                        // before counting this item. We accept items that
                        // satisfy at least one percent rule's threshold so
                        // the most permissive rule still gets evaluated.
                        $minOk = $percentRules->contains(function ($r) use ($unit) {
                            return is_null($r->min_product_price) || $unit >= (float) $r->min_product_price;
                        });
                        if (! $minOk) continue;
                        $qualifyingTotalQty += (int) $item->quantity;
                        $qualifyingTotalRow += (float) $item->total;
                        $qualifyingBaseRow  += (float) $item->base_total;
                    }

                    foreach ($percentRules as $rule) {
                        if ($qualifyingTotalQty < $rule->buy_qty) continue;
                        if (! is_null($rule->percent_off) && $rule->percent_off > 0) {
                            $cartLevelPercentRule = $rule;
                            // Recompute the base/total restricted to items
                            // that meet THIS rule's price floor.
                            $rowTotal = 0.0; $rowBase = 0.0;
                            foreach ($cart->items as $item) {
                                if (! is_null($rule->min_product_price)
                                    && (float) $item->price < (float) $rule->min_product_price) continue;
                                $rowTotal += (float) $item->total;
                                $rowBase  += (float) $item->base_total;
                            }
                            $cartLevelPercentTotal     = $rowTotal;
                            $cartLevelPercentBaseTotal = $rowBase;
                            if ($rule->block_coupons) $blockCoupons = true;
                            break;
                        }
                    }
                }

                // ── Pass 1b: fixed_per_tier (flat-yen ladder).
                //
                // For every full multiple of `buy_qty` qualifying items
                // bought, take `fixed_amount` off the order. Independent of
                // unit price — that's the point of this discount type. The
                // sum is then spread proportionally across qualifying items
                // (same mechanism the percent pass uses) so it lands in
                // cart_item.discount_amount and flows through to order +
                // invoice without further work.
                //
                // We pick the single highest-priority fixed rule whose
                // buy_qty threshold is met. Lower-priority fixed rules and
                // free-items per-item rules are skipped only if the chosen
                // rule has replaces_lower_tiers=true.
                $fixedRules = $rules->where('discount_type', \Webkul\Shipping\Models\BulkBonusRule::DISCOUNT_FIXED_PER_TIER)
                    ->sortByDesc('buy_qty');

                if ($fixedRules->isNotEmpty()) {
                    foreach ($fixedRules as $rule) {
                        if (! $rule->fixed_amount || $rule->fixed_amount <= 0) continue;
                        if (! $rule->buy_qty      || $rule->buy_qty      <= 0) continue;

                        // Count qualifying items for THIS rule (price floor
                        // is per-rule, not pooled across rules).
                        $qty = 0; $rowTotal = 0.0; $rowBase = 0.0;
                        foreach ($cart->items as $item) {
                            if (! is_null($rule->min_product_price)
                                && (float) $item->price < (float) $rule->min_product_price) continue;
                            $qty      += (int) $item->quantity;
                            $rowTotal += (float) $item->total;
                            $rowBase  += (float) $item->base_total;
                        }

                        $tiers = (int) floor($qty / (int) $rule->buy_qty);
                        if ($tiers <= 0) continue;

                        $cartLevelFixedRule       = $rule;
                        $cartLevelFixedAmount     = round($tiers * (float) $rule->fixed_amount, 4);
                        // Use the same multiplier for base — fixed_amount is
                        // expressed in the channel's primary currency, and
                        // Bagisto's order pipeline reads base_discount_amount
                        // as the canonical figure.
                        $cartLevelFixedBaseAmount = $cartLevelFixedAmount;
                        $cartLevelFixedTotalRow   = $rowTotal;
                        $cartLevelFixedBaseRow    = $rowBase;
                        if ($rule->block_coupons) $blockCoupons = true;
                        break;
                    }
                }

                // ── Pass 2: per-item free-items rules. Skipped entirely if
                // a `replaces_lower_tiers=true` cart-level percent or fixed
                // rule has already taken over.
                $skipPerItem = ($cartLevelPercentRule && $cartLevelPercentRule->replaces_lower_tiers)
                    || ($cartLevelFixedRule && $cartLevelFixedRule->replaces_lower_tiers);

                if (! $skipPerItem) {
                    foreach ($cart->items as $item) {
                        $productId  = $item->product_id;
                        $categoryId = optional($item->product?->categories?->first())->id ?? 0;
                        $unitPrice  = (float) $item->price;
                        $unitBase   = (float) $item->base_price;

                        $matching = $rules
                            ->where('discount_type', \Webkul\Shipping\Models\BulkBonusRule::DISCOUNT_FREE_ITEMS)
                            ->filter(function ($r) use ($productId, $categoryId, $unitPrice) {
                                if (! is_null($r->product_id)         && $r->product_id  !== $productId)  return false;
                                if (! is_null($r->category_id)        && $r->category_id !== $categoryId) return false;
                                if (! is_null($r->min_product_price)  && $unitPrice < (float) $r->min_product_price) return false;
                                return true;
                            })
                            ->sortByDesc('buy_qty')
                            ->values();

                        if ($matching->isEmpty()) continue;

                        // Honour `replaces_lower_tiers`: if any matching rule
                        // has it set, only that single highest-tier rule fires.
                        $replacing = $matching->firstWhere('replaces_lower_tiers', true);
                        $rulesToWalk = $replacing ? collect([$replacing]) : $matching;

                        $totalFree    = 0;
                        $appliedRules = [];
                        $blockHere    = false;

                        foreach ($rulesToWalk as $rule) {
                            if ($item->quantity < $rule->buy_qty) continue;

                            $freeQty = (int) floor($item->quantity / max(1, (int) $rule->buy_qty)) * (int) $rule->free_qty;
                            if ($rule->max_free_per_order) {
                                $freeQty = min($freeQty, (int) $rule->max_free_per_order);
                            }
                            if ($freeQty <= 0) continue;

                            if ($rule->block_coupons) $blockHere = true;

                            if ($replacing) {
                                $totalFree    = $freeQty;
                                $appliedRules = [$rule->id];
                                break;
                            } elseif ($rule->stackable) {
                                $totalFree     += $freeQty;
                                $appliedRules[] = $rule->id;
                            } else {
                                if ($freeQty > $totalFree) {
                                    $totalFree    = $freeQty;
                                    $appliedRules = [$rule->id];
                                }
                                break;
                            }
                        }

                        if ($totalFree <= 0) continue;

                        $discount     = round($totalFree * $unitPrice, 4);
                        $baseDiscount = round($totalFree * $unitBase, 4);

                        $additional = \Fujiba\PwaFrontend\Providers\PwaFrontendServiceProvider::extractAdditional($item);
                        $additional['bulk_bonus'] = [
                            'rule_ids' => array_values(array_unique($appliedRules)),
                            'type'     => 'free_items',
                            'free_qty' => $totalFree,
                            'amount'   => $discount,
                        ];

                        $item->discount_amount      = $discount;
                        $item->base_discount_amount = $baseDiscount;
                        $item->additional           = $additional;
                        $item->save();

                        if ($blockHere) $blockCoupons = true;
                    }
                }

                // ── Apply cart-level percent-off (if any) by spreading the
                // discount proportionally across qualifying items. We write
                // additional discount on top of any per-item amount, but in
                // practice the `replaces_lower_tiers` flag on the percent
                // rule has already prevented the per-item pass from running.
                if ($cartLevelPercentRule && $cartLevelPercentTotal > 0) {
                    $pct       = (float) $cartLevelPercentRule->percent_off;
                    $totalCut  = round($cartLevelPercentTotal     * $pct / 100, 4);
                    $totalBase = round($cartLevelPercentBaseTotal * $pct / 100, 4);

                    // Spread proportionally so the sum matches exactly. We
                    // accumulate the rounding remainder onto the last item.
                    $assigned = 0.0; $assignedBase = 0.0;
                    $qualifying = $cart->items->filter(function ($item) use ($cartLevelPercentRule) {
                        if (! is_null($cartLevelPercentRule->min_product_price)
                            && (float) $item->price < (float) $cartLevelPercentRule->min_product_price) return false;
                        return true;
                    })->values();
                    $count = $qualifying->count();

                    foreach ($qualifying as $idx => $item) {
                        $weight     = $cartLevelPercentTotal     > 0 ? ((float) $item->total      / $cartLevelPercentTotal)     : 0;
                        $weightBase = $cartLevelPercentBaseTotal > 0 ? ((float) $item->base_total / $cartLevelPercentBaseTotal) : 0;
                        $cut     = ($idx === $count - 1) ? round($totalCut  - $assigned,     4) : round($totalCut  * $weight,     4);
                        $cutBase = ($idx === $count - 1) ? round($totalBase - $assignedBase, 4) : round($totalBase * $weightBase, 4);

                        $assigned     += $cut;
                        $assignedBase += $cutBase;

                        $additional = \Fujiba\PwaFrontend\Providers\PwaFrontendServiceProvider::extractAdditional($item);
                        $existing   = $additional['bulk_bonus'] ?? null;
                        $additional['bulk_bonus'] = [
                            'rule_ids' => array_values(array_unique(array_merge(
                                $existing['rule_ids'] ?? [], [$cartLevelPercentRule->id]
                            ))),
                            'type'     => 'percent_off_order',
                            'percent'  => $pct,
                            'amount'   => round(($existing['amount'] ?? 0) + $cut, 4),
                        ];

                        $item->discount_amount      = round((float) $item->discount_amount      + $cut,     4);
                        $item->base_discount_amount = round((float) $item->base_discount_amount + $cutBase, 4);
                        $item->additional           = $additional;
                        $item->save();
                    }

                    if ($cartLevelPercentRule->block_coupons) $blockCoupons = true;
                }

                // ── Spread the fixed_per_tier amount across qualifying items
                // so cart_item.discount_amount carries the right share. Same
                // proportional spread + last-item remainder pattern as the
                // percent pass. Stacks on top of any existing per-item
                // discount (in practice, replaces_lower_tiers will have
                // suppressed those if the admin wanted exclusivity).
                if ($cartLevelFixedRule && $cartLevelFixedAmount > 0 && $cartLevelFixedTotalRow > 0) {
                    $totalCut  = $cartLevelFixedAmount;
                    $totalBase = $cartLevelFixedBaseAmount;

                    $assigned = 0.0; $assignedBase = 0.0;
                    $qualifying = $cart->items->filter(function ($item) use ($cartLevelFixedRule) {
                        if (! is_null($cartLevelFixedRule->min_product_price)
                            && (float) $item->price < (float) $cartLevelFixedRule->min_product_price) return false;
                        return true;
                    })->values();
                    $count = $qualifying->count();

                    foreach ($qualifying as $idx => $item) {
                        $weight     = $cartLevelFixedTotalRow > 0 ? ((float) $item->total      / $cartLevelFixedTotalRow) : 0;
                        $weightBase = $cartLevelFixedBaseRow  > 0 ? ((float) $item->base_total / $cartLevelFixedBaseRow)  : 0;
                        $cut     = ($idx === $count - 1) ? round($totalCut  - $assigned,     4) : round($totalCut  * $weight,     4);
                        $cutBase = ($idx === $count - 1) ? round($totalBase - $assignedBase, 4) : round($totalBase * $weightBase, 4);

                        $assigned     += $cut;
                        $assignedBase += $cutBase;

                        $additional = \Fujiba\PwaFrontend\Providers\PwaFrontendServiceProvider::extractAdditional($item);
                        $existing   = $additional['bulk_bonus'] ?? null;
                        $additional['bulk_bonus'] = [
                            'rule_ids' => array_values(array_unique(array_merge(
                                $existing['rule_ids'] ?? [], [$cartLevelFixedRule->id]
                            ))),
                            'type'         => 'fixed_per_tier',
                            'fixed_amount' => (float) $cartLevelFixedRule->fixed_amount,
                            'buy_qty'      => (int) $cartLevelFixedRule->buy_qty,
                            'amount'       => round(($existing['amount'] ?? 0) + $cut, 4),
                        ];

                        $item->discount_amount      = round((float) $item->discount_amount      + $cut,     4);
                        $item->base_discount_amount = round((float) $item->base_discount_amount + $cutBase, 4);
                        $item->additional           = $additional;
                        $item->save();
                    }

                    if ($cartLevelFixedRule->block_coupons) $blockCoupons = true;
                }

                // We listen on `collect.totals.after`, so the cart-level
                // totals (discount_amount, grand_total, base_*) have already
                // been summed by Bagisto. We just wrote new per-item
                // discount_amounts above; reflect that on the cart totals
                // too so the storefront/checkout/order all show the same
                // figure. Per-item writes flow naturally into order_items
                // when the order is placed; the cart-level update here is
                // only for the live cart summary on the current page.
                $bbItemDiscountTotal     = 0.0;
                $bbItemBaseDiscountTotal = 0.0;
                foreach ($cart->items as $item) {
                    $additional = \Fujiba\PwaFrontend\Providers\PwaFrontendServiceProvider::extractAdditional($item);
                    if (! empty($additional['bulk_bonus']['amount'])) {
                        $bbItemDiscountTotal     += (float) $additional['bulk_bonus']['amount'];
                        // base/non-base are equal for fixed_per_tier (single
                        // currency); for free_items / percent_off we mirror
                        // what was written to base_discount_amount on the line.
                        $bbItemBaseDiscountTotal += (float) $additional['bulk_bonus']['amount'];
                    }
                }

                if ($bbItemDiscountTotal > 0) {
                    $cart->discount_amount      = round((float) $cart->discount_amount      + $bbItemDiscountTotal,     2);
                    $cart->base_discount_amount = round((float) $cart->base_discount_amount + $bbItemBaseDiscountTotal, 2);
                    $cart->grand_total          = round((float) $cart->grand_total          - $bbItemDiscountTotal,     2);
                    $cart->base_grand_total     = round((float) $cart->base_grand_total     - $bbItemBaseDiscountTotal, 2);
                    $cart->save();
                }

                // ── Coupon blocking. Strip any active coupon from the cart
                // (and child cart_items) when a bulk rule with block_coupons
                // is in effect, and emit a session flash so the customer
                // sees why their coupon went away.
                if ($blockCoupons && ! empty($cart->coupon_code)) {
                    try {
                        $cart->coupon_code = null;
                        $cart->save();
                        \Webkul\Checkout\Models\CartItem::where('cart_id', $cart->id)
                            ->update([
                                'coupon_code'           => null,
                                'discount_percent'      => 0,
                            ]);
                        if (function_exists('session') && session()) {
                            // The translation key is optional. Falls back to
                            // the English string if the locale file doesn't
                            // define it (Laravel returns the key string when
                            // missing, which would look ugly in a toast).
                            $msg = __('shop::app.checkout.cart.bulk-bonus.coupon-removed');
                            if (str_starts_with($msg, 'shop::')) {
                                $msg = 'Coupon removed because a bulk discount is active.';
                            }
                            session()->flash('warning', $msg);
                        }
                    } catch (\Throwable) {}
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Bulk-bonus auto-apply failed: ' . $e->getMessage());
            }
        });
    }

    /**
     * Cart items store `additional` as either a JSON string or an already-
     * decoded array depending on whether the model has been touched yet
     * this request. Normalise to an array so callers can mutate it safely.
     */
    public static function extractAdditional($item): array
    {
        $raw = $item->additional ?? null;
        if (is_array($raw)) return $raw;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}
