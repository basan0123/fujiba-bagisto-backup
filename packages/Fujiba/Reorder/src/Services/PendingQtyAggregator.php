<?php

namespace Fujiba\Reorder\Services;

use Illuminate\Support\Facades\DB;

class PendingQtyAggregator
{
    /**
     * pending_qty = SUM(qty_ordered - qty_shipped - qty_canceled)
     * across order_items joined to non-closed orders for the channel.
     *
     * @return array<int,int>  product_id => pending_qty
     */
    public function forChannel(int $channelId, array $productIds = []): array
    {
        $q = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.channel_id', $channelId)
            ->whereNotIn('orders.status', ['closed', 'canceled', 'completed'])
            ->whereNotNull('order_items.product_id')
            ->select(
                'order_items.product_id',
                DB::raw('SUM(GREATEST(COALESCE(order_items.qty_ordered,0)
                              - COALESCE(order_items.qty_shipped,0)
                              - COALESCE(order_items.qty_canceled,0), 0)) as pending')
            )
            ->groupBy('order_items.product_id');

        if (! empty($productIds)) {
            $q->whereIn('order_items.product_id', $productIds);
        }

        return $q->pluck('pending', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
