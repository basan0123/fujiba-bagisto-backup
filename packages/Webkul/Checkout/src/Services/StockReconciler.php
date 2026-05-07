<?php

namespace Webkul\Checkout\Services;

use Illuminate\Support\Facades\Event;
use Webkul\Checkout\Contracts\Cart as CartContract;
use Webkul\Checkout\Contracts\CartItem;
use Webkul\Checkout\Facades\Cart;

class StockReconciler
{
    /**
     * Walk every cart item and bring its quantity within available stock.
     *
     *  - qty > available  → clamp qty down to available
     *  - available == 0   → remove the item
     *  - sufficient stock → leave alone
     *
     * Returns a structured report so the caller can flash a message and
     * decide whether to continue to checkout. Never blocks; never redirects.
     *
     * Extension seam for preorder: each item dispatches
     * `checkout.cart.stock.reconcile.item.before` with the item, the
     * available qty, and the requested qty. A listener can set
     * `$item->additional['skip_stock_reconcile'] = true` to opt the line
     * out (e.g. once preorder support lands).
     */
    public function reconcile(CartContract $cart): array
    {
        $adjusted = [];
        $removed = [];

        foreach ($cart->items as $item) {
            $requested = (int) $item->quantity;
            $available = $this->availableQuantity($item);

            Event::dispatch('checkout.cart.stock.reconcile.item.before', [
                'item'      => $item,
                'available' => $available,
                'requested' => $requested,
            ]);

            if (! empty($item->additional['skip_stock_reconcile'])) {
                continue;
            }

            if ($available === null || $requested <= $available) {
                continue;
            }

            $name = $this->itemName($item);

            if ($available <= 0) {
                Cart::removeItem($item->id);
                $removed[] = ['name' => $name];

                continue;
            }

            Cart::updateItems(['qty' => [$item->id => $available]]);
            $adjusted[] = [
                'name' => $name,
                'from' => $requested,
                'to'   => $available,
            ];
        }

        return [
            'adjusted' => $adjusted,
            'removed'  => $removed,
            'changed'  => ! empty($adjusted) || ! empty($removed),
        ];
    }

    /**
     * Available stock for a cart item.
     *
     *  - null  → the type does not manage stock (e.g. backorders, virtual)
     *            and we should not clamp.
     *  - int   → the integer stock available right now.
     *
     * Configurable parents read stock from the chosen child variant; bundle
     * and grouped types don't have a single clamp value, so we treat them
     * as binary (0 = unsellable, otherwise unbounded).
     */
    protected function availableQuantity(CartItem $item): ?int
    {
        $type = $item->product->type ?? null;

        if (in_array($type, ['bundle', 'grouped'], true)) {
            return $item->getTypeInstance()->isItemHaveQuantity($item) ? null : 0;
        }

        $stockProduct = $type === 'configurable' && $item->child
            ? $item->child->product
            : $item->product;

        if (! $stockProduct) {
            return null;
        }

        if (! $stockProduct->manage_stock) {
            return null;
        }

        if ((bool) core()->getConfigData('catalog.inventory.stock_options.back_orders')) {
            return null;
        }

        return (int) $stockProduct->getTypeInstance()->totalQuantity();
    }

    protected function itemName(CartItem $item): string
    {
        return $item->name ?: ($item->product->name ?? trans('shop::app.checkout.cart.item.product'));
    }
}
