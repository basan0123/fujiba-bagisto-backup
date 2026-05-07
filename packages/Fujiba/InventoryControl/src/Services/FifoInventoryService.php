<?php

namespace Fujiba\InventoryControl\Services;

use Fujiba\InventoryControl\Models\InventoryBatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FifoInventoryService
{
    /**
     * Deduct qty from oldest batches first (FIFO by expiry, then created_at).
     *
     * Runs inside a transaction with row-level locks so two concurrent
     * checkouts on the same product don't both read the same remaining qty.
     *
     * @return array{consumed: array<int, array{batch_id:int, qty_taken:int}>, shortfall: int}
     */
    public function deduct(int $productId, int $qtyNeeded, ?int $channelId = null): array
    {
        if ($qtyNeeded <= 0) {
            return ['consumed' => [], 'shortfall' => 0];
        }

        return DB::transaction(function () use ($productId, $qtyNeeded, $channelId) {
            $batches = InventoryBatch::query()
                ->where('product_id', $productId)
                ->where('status', InventoryBatch::STATUS_ACTIVE)
                ->where('quantity_remaining', '>', 0)
                ->when($channelId, fn ($q) => $q->where('channel_id', $channelId))
                ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            $consumed = [];
            $remaining = $qtyNeeded;

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($batch->quantity_remaining, $remaining);
                $batch->quantity_remaining -= $take;

                if ($batch->quantity_remaining === 0) {
                    $batch->status = InventoryBatch::STATUS_DEPLETED;
                }

                $batch->save();

                $consumed[] = ['batch_id' => $batch->id, 'qty_taken' => $take];
                $remaining -= $take;
            }

            if ($remaining > 0) {
                Log::warning('FIFO batch shortfall — product_inventories may be out of sync with inventory_batches', [
                    'product_id'  => $productId,
                    'channel_id'  => $channelId,
                    'qty_needed'  => $qtyNeeded,
                    'shortfall'   => $remaining,
                ]);
            }

            return ['consumed' => $consumed, 'shortfall' => $remaining];
        });
    }

    /**
     * Receive new stock — create a new batch.
     */
    public function receive(array $data): InventoryBatch
    {
        return InventoryBatch::create([
            'product_id'          => $data['product_id'],
            'channel_id'          => $data['channel_id'] ?? null,
            'inventory_source_id' => $data['inventory_source_id'] ?? null,
            'batch_number'        => $data['batch_number'] ?? null,
            'quantity_received'   => $data['quantity'],
            'quantity_remaining'  => $data['quantity'],
            'cost_price'          => $data['cost_price'] ?? null,
            'manufacture_date'    => $data['manufacture_date'] ?? null,
            'expiry_date'         => $data['expiry_date'] ?? null,
            'status'              => InventoryBatch::STATUS_ACTIVE,
        ]);
    }

    /**
     * Mark a single batch as expired/removed — preserves quantity_remaining
     * so reports of "lost stock" stay accurate.
     */
    public function markRemoved(InventoryBatch $batch, string $status): InventoryBatch
    {
        if (! in_array($status, [InventoryBatch::STATUS_EXPIRED, InventoryBatch::STATUS_REMOVED], true)) {
            throw new \InvalidArgumentException("Invalid removal status: {$status}");
        }

        $batch->status = $status;
        $batch->save();

        return $batch;
    }

    /**
     * Sweep — flip every active batch with expiry_date < today to status=expired.
     * Intended for a daily scheduled command; safe to run repeatedly.
     */
    public function sweepExpired(): int
    {
        return InventoryBatch::query()
            ->where('status', InventoryBatch::STATUS_ACTIVE)
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::today())
            ->update(['status' => InventoryBatch::STATUS_EXPIRED]);
    }
}
