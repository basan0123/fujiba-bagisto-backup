<?php

namespace Fujiba\OrdersAdmin\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderCommentRepository;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;

/**
 * Updates an order's status, running the FULL cancel flow (stock return +
 * cart restore) when transitioning to canceled. Shared by the admin REST
 * controller and the POS GraphQL mutation so the stock accounting can never
 * diverge between the two.
 */
class OrderStatusUpdater
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderCommentRepository $orderCommentRepository,
        protected OrderItemRepository $orderItemRepository,
    ) {}

    /**
     * Apply a new status. When $customerMessage is non-empty a
     * customer-notified comment is recorded.
     */
    public function update(Order $order, string $newStatus, ?string $customerMessage = null): void
    {
        // Cancelling must run the real cancel flow — Bagisto's stock
        // accounting (product_ordered_inventories, inventory indexer) only
        // updates when items get qty_canceled set and returnQtyToProduct
        // Inventory() runs. A bare status flip would leave the storefront
        // seeing the units as still taken.
        if ($newStatus === Order::STATUS_CANCELED && $order->status !== Order::STATUS_CANCELED) {
            $this->cancelWithStockReturn($order);
        } else {
            $this->orderRepository->updateOrderStatus($order, $newStatus);
        }

        if ($customerMessage = trim((string) $customerMessage)) {
            $comment = $this->orderCommentRepository->create([
                'order_id'          => $order->id,
                'comment'           => $customerMessage,
                'customer_notified' => 1,
            ]);

            Event::dispatch('sales.order.comment.create.after', $comment);
        }
    }

    /**
     * Cancel order, restoring pending stock and the customer's cart.
     *
     * Bagisto's OrderRepository::cancel() short-circuits when items have
     * qty_to_cancel = 0 (i.e. already invoiced). For the admin "cancel" use-
     * case we want it to work even on invoiced-but-unshipped orders, so we
     * mirror the cancel path manually: bump qty_canceled to cover the
     * remaining-not-shipped portion, restore ordered_inventories, and re-
     * activate the originating cart so the customer can re-checkout.
     */
    public function cancelWithStockReturn(Order $order): void
    {
        Event::dispatch('sales.order.cancel.before', $order);

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $items = $item->getTypeInstance()->isComposite()
                    ? $item->children->all()
                    : [$item];

                foreach ($items as $orderItem) {
                    $remaining = max(
                        0,
                        $orderItem->qty_ordered - $orderItem->qty_canceled - $orderItem->qty_shipped - $orderItem->qty_refunded
                    );

                    if ($remaining <= 0) {
                        continue;
                    }

                    $this->orderItemRepository->returnQtyToProductInventory($orderItem);

                    $orderItem->qty_canceled += $remaining;
                    $orderItem->save();

                    if ($orderItem->parent && $orderItem->parent->qty_ordered) {
                        $parentRemaining = max(
                            0,
                            $orderItem->parent->qty_ordered - $orderItem->parent->qty_canceled - $orderItem->parent->qty_shipped - $orderItem->parent->qty_refunded
                        );

                        if ($parentRemaining > 0) {
                            $orderItem->parent->qty_canceled += $parentRemaining;
                            $orderItem->parent->save();
                        }
                    }
                }
            }

            $order->status = Order::STATUS_CANCELED;
            $order->save();

            // Stale invoice state would still read "pending" otherwise — flip
            // open invoices to canceled so the invoices grid stops listing
            // them as outstanding.
            DB::table('invoices')
                ->where('order_id', $order->id)
                ->whereIn('state', ['pending', 'pending_payment'])
                ->update(['state' => 'canceled']);

            $this->restoreCustomerCart($order);
        });

        // Inventory reindex listener hooks on this; runs on the queue.
        Event::dispatch('sales.order.cancel.after', $order);
        Event::dispatch('sales.order.update-status.after', $order);
    }

    /**
     * Re-activate the originating cart for the customer (or create a new one)
     * so they see the cancelled items waiting in their cart.
     *
     * Each order_items.additional JSON carries the `cart_id` it was placed
     * from. Cart rows are kept (just deactivated) at order placement, so we
     * can flip is_active back on. If the cart row is gone, fall back to
     * creating a fresh cart for the customer with the item lines copied.
     */
    protected function restoreCustomerCart(Order $order): void
    {
        if (! $order->customer_id) {
            return;
        }

        $cartIds = collect($order->items)
            ->map(fn ($i) => $i->additional['cart_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        // Deactivate any existing active carts for this customer so the
        // restored one becomes the single live cart.
        DB::table('cart')
            ->where('customer_id', $order->customer_id)
            ->where('is_active', 1)
            ->update(['is_active' => 0]);

        $restored = false;

        foreach ($cartIds as $cartId) {
            $cart = DB::table('cart')->where('id', $cartId)->first();

            if (! $cart || $cart->customer_id != $order->customer_id) {
                continue;
            }

            DB::table('cart')->where('id', $cartId)->update(['is_active' => 1]);
            $restored = true;
            break;
        }

        // Fallback: original cart row missing — create a new cart and copy
        // the order lines into cart_items so the customer sees the items.
        if (! $restored) {
            $cartId = DB::table('cart')->insertGetId([
                'customer_id'         => $order->customer_id,
                'customer_email'      => $order->customer_email,
                'customer_first_name' => $order->customer_first_name,
                'customer_last_name'  => $order->customer_last_name,
                'channel_id'          => $order->channel_id,
                'is_active'           => 1,
                'is_guest'            => 0,
                'items_count'         => count($order->items),
                'items_qty'           => collect($order->items)->sum('qty_ordered'),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            foreach ($order->items as $item) {
                DB::table('cart_items')->insert([
                    'cart_id'      => $cartId,
                    'product_id'   => $item->product_id,
                    'sku'          => $item->sku,
                    'name'         => $item->name,
                    'type'         => $item->type,
                    'quantity'     => $item->qty_ordered,
                    'price'        => $item->price,
                    'base_price'   => $item->base_price,
                    'total'        => $item->price * $item->qty_ordered,
                    'base_total'   => $item->base_price * $item->qty_ordered,
                    'weight'       => $item->weight,
                    'total_weight' => ($item->weight ?? 0) * $item->qty_ordered,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }
}
