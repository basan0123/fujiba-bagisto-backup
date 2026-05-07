<?php

namespace Fujiba\Reorder\Services;

use Fujiba\Reorder\Models\ReorderTodo;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated 2026-04-27 — superseded by ReorderController::confirmReceive().
 * Only used by the legacy ReorderController::arrive() endpoint. Scheduled
 * for removal alongside ReorderTodo after 2026-05-11.
 */
class StockUpdateService
{
    public function __construct(private ChannelStockResolver $stockResolver) {}

    /**
     * Increment product_inventories.qty by $arrivedQty for the
     * primary source tied to the channel (first active one).
     * Runs inside a transaction.
     */
    public function markArrived(ReorderTodo $todo, int $arrivedQty): ReorderTodo
    {
        if ($arrivedQty <= 0) {
            return $todo;
        }

        $sourceIds = $this->stockResolver->sourceIdsForChannel((int) $todo->channel_id);

        if (empty($sourceIds)) {
            throw new \RuntimeException('No active inventory source linked to channel #'.$todo->channel_id);
        }

        $primarySourceId = $sourceIds[0];

        DB::transaction(function () use ($todo, $arrivedQty, $primarySourceId) {
            $row = DB::table('product_inventories')
                ->where('product_id', $todo->product_id)
                ->where('inventory_source_id', $primarySourceId)
                ->lockForUpdate()
                ->first();

            if ($row) {
                DB::table('product_inventories')
                    ->where('id', $row->id)
                    ->update(['qty' => (int) $row->qty + $arrivedQty]);
            } else {
                DB::table('product_inventories')->insert([
                    'product_id'          => $todo->product_id,
                    'inventory_source_id' => $primarySourceId,
                    'qty'                 => $arrivedQty,
                ]);
            }

            $todo->arrived_qty = (int) $todo->arrived_qty + $arrivedQty;
            $todo->status      = ReorderTodo::STATUS_ARRIVED;
            $todo->save();
        });

        return $todo->refresh();
    }
}
