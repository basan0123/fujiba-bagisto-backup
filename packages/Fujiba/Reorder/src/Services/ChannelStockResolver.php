<?php

namespace Fujiba\Reorder\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\ChannelProxy;

class ChannelStockResolver
{
    /**
     * Sum qty from product_inventories across ALL inventory sources
     * linked to the given channel via channel_inventory_sources pivot.
     *
     * @return array<int,int>  product_id => summed qty
     */
    public function forChannel(int $channelId, array $productIds = []): array
    {
        $sourceIds = $this->sourceIdsForChannel($channelId);

        if (empty($sourceIds)) {
            return [];
        }

        $q = DB::table('product_inventories')
            ->whereIn('inventory_source_id', $sourceIds)
            ->select('product_id', DB::raw('SUM(qty) AS qty'))
            ->groupBy('product_id');

        if (! empty($productIds)) {
            $q->whereIn('product_id', $productIds);
        }

        return $q->pluck('qty', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** @return int[] */
    public function sourceIdsForChannel(int $channelId): array
    {
        $channel = ChannelProxy::modelClass()::query()->find($channelId);

        if (! $channel) {
            return [];
        }

        return $channel->inventory_sources()
            ->where('status', 1)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
