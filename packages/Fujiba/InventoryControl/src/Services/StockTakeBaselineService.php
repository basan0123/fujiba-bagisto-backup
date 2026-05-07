<?php

namespace Fujiba\InventoryControl\Services;

use Fujiba\InventoryControl\Models\InventoryBatch;
use Fujiba\InventoryControl\Models\StockTakeBatch;
use Fujiba\InventoryControl\Models\StockTakeSession;
use Illuminate\Support\Facades\DB;

/**
 * Captures the per-batch baseline for every active batch belonging to a
 * has_expiry_date product at the session's inventory source. Runs at session
 * creation when advanced_mode is on. Sets baseline_status -> ready when done.
 *
 * Non-expiry products are deliberately NOT snapshotted here — they keep the
 * existing per-item lazy baseline (captured on first scan) for backwards
 * compatibility with the simple flow.
 */
class StockTakeBaselineService
{
    /**
     * @return int  number of batch rows snapshotted
     */
    public function snapshot(StockTakeSession $session): int
    {
        if (! $session->advanced_mode) {
            return 0;
        }

        $session->update(['baseline_status' => StockTakeSession::BASELINE_PENDING]);

        $count = DB::transaction(function () use ($session) {
            // All active batches at this source, only for products flagged for
            // expiry tracking. We do this in chunks to keep memory bounded
            // even on tenants with thousands of batches.
            $rows = 0;

            $query = InventoryBatch::query()
                ->join('products as p', 'p.id', '=', 'inventory_batches.product_id')
                ->where('p.has_expiry_date', 1)
                ->where('inventory_batches.inventory_source_id', $session->inventory_source_id)
                ->where('inventory_batches.status', InventoryBatch::STATUS_ACTIVE);

            // Channel scope — limit baseline to products mapped to the session's
            // channel (directly or via parent for variants). Mirrors the filter
            // in StockTakeController::productsForSession so the baseline and
            // the picker stay in agreement.
            if ($session->channel_id) {
                $direct = DB::table('product_channels')
                    ->where('channel_id', $session->channel_id)
                    ->pluck('product_id')->map(fn ($v) => (int) $v)->all();
                $variants = $direct
                    ? DB::table('products')->whereIn('parent_id', $direct)
                        ->pluck('id')->map(fn ($v) => (int) $v)->all()
                    : [];
                $allowed = array_values(array_unique(array_merge($direct, $variants)));
                if (empty($allowed)) {
                    return 0;
                }
                $query->whereIn('inventory_batches.product_id', $allowed);
            }

            $query->select([
                    'inventory_batches.id',
                    'inventory_batches.product_id',
                    'inventory_batches.batch_number',
                    'inventory_batches.expiry_date',
                    'inventory_batches.quantity_remaining',
                ])
                ->orderBy('inventory_batches.id')
                ->chunk(500, function ($batches) use ($session, &$rows) {
                    $now = now();
                    $payload = [];
                    foreach ($batches as $b) {
                        $payload[] = [
                            'session_id'         => $session->id,
                            'item_id'            => null,
                            'inventory_batch_id' => $b->id,
                            'product_id'         => $b->product_id,
                            'batch_number'       => $b->batch_number,
                            'expiry_date'        => $b->expiry_date,
                            'baseline_qty'       => (int) $b->quantity_remaining,
                            'counted_qty'        => null,
                            'difference'         => null,
                            'applied'            => false,
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ];
                    }
                    if ($payload) {
                        StockTakeBatch::insert($payload);
                        $rows += count($payload);
                    }
                });

            return $rows;
        });

        $session->update([
            'baseline_status' => StockTakeSession::BASELINE_READY,
            'baseline_count'  => $count,
        ]);

        return $count;
    }
}
