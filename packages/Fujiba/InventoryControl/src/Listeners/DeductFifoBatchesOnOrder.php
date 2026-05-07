<?php

namespace Fujiba\InventoryControl\Listeners;

use Fujiba\InventoryControl\Services\FifoInventoryService;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Models\Product;
use Webkul\Sales\Contracts\Order;

class DeductFifoBatchesOnOrder
{
    public function __construct(private FifoInventoryService $fifo) {}

    /**
     * Fired by 'checkout.order.save.after'. Deducts FIFO batches for any
     * line item whose product has has_expiry_date = true. Bagisto has
     * already decremented product_inventories.qty by this point — batches
     * are an FIFO ledger underneath that total, not the source of truth.
     */
    public function handle(Order $order): void
    {
        $channelId = $order->channel_id;

        foreach ($order->items as $item) {
            $productId = $item->product_id;

            if (! $productId) {
                continue;
            }

            $product = Product::find($productId);

            if (! $product || ! $product->has_expiry_date) {
                continue;
            }

            $qty = (int) $item->qty_ordered;

            if ($qty <= 0) {
                continue;
            }

            try {
                $this->fifo->deduct($productId, $qty, $channelId);
            } catch (\Throwable $e) {
                Log::error('FIFO deduction failed for order item', [
                    'order_id'   => $order->id,
                    'item_id'    => $item->id,
                    'product_id' => $productId,
                    'qty'        => $qty,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}
