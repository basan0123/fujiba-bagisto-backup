<?php

namespace Webkul\Product\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockAuditRecorder
{
    public static function record(
        int $productId,
        int $inventorySourceId,
        ?int $qtyBefore,
        int $qtyAfter,
        string $sourceAction
    ): void {
        if (! Schema::hasTable('product_stock_audits')) {
            return;
        }

        $before = (int) ($qtyBefore ?? 0);
        $delta = $qtyAfter - $before;

        if ($delta === 0) {
            return;
        }

        $adminId = null;
        try {
            if ($u = auth()->guard('admin')->user()) {
                $adminId = $u->id;
            }
        } catch (\Throwable $e) {
            // No admin context (CLI, queue, etc.) — leave null.
        }

        DB::table('product_stock_audits')->insert([
            'product_id' => $productId,
            'inventory_source_id' => $inventorySourceId,
            'qty_before' => $before,
            'qty_after' => $qtyAfter,
            'delta' => $delta,
            'admin_id' => $adminId,
            'source_action' => $sourceAction,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Snapshot current qty for a (product, source) before a write.
     */
    public static function currentQty(int $productId, int $inventorySourceId): ?int
    {
        $row = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $inventorySourceId)
            ->value('qty');

        return $row === null ? null : (int) $row;
    }
}
