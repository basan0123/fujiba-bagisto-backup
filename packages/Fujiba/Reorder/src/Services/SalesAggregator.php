<?php

namespace Fujiba\Reorder\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAggregator
{
    /**
     * Sum order_items.qty_invoiced grouped by product_id for the given
     * channel and window in days.
     *
     * @return array<int,int>  product_id => qty_invoiced total
     */
    public function forChannel(int $channelId, int $windowDays, array $productIds = []): array
    {
        $since = Carbon::now()->subDays(max(1, $windowDays));

        $q = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.channel_id', $channelId)
            ->where('orders.created_at', '>=', $since)
            ->whereNotNull('order_items.product_id')
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.qty_invoiced) AS qty')
            )
            ->groupBy('order_items.product_id');

        if (! empty($productIds)) {
            $q->whereIn('order_items.product_id', $productIds);
        }

        return $q->pluck('qty', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * All-time sum of order_items.qty_invoiced grouped by product_id for the
     * given channel — no date window. Used for the Reorder page's displayed
     * "Sale Count" column; the suggested-qty math still uses the windowed
     * forChannel() so avg-daily stays meaningful.
     *
     * @return array<int,int>  product_id => qty_invoiced total
     */
    public function allTimeForChannel(int $channelId, array $productIds = []): array
    {
        $q = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.channel_id', $channelId)
            ->whereNotNull('order_items.product_id')
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.qty_invoiced) AS qty')
            )
            ->groupBy('order_items.product_id');

        if (! empty($productIds)) {
            $q->whereIn('order_items.product_id', $productIds);
        }

        return $q->pluck('qty', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
