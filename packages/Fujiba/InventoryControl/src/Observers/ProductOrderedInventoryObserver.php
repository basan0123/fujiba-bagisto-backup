<?php

namespace Fujiba\InventoryControl\Observers;

use Fujiba\OrdersAdmin\Services\InventoryReconcileGuard;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Helpers\Indexers\Inventory as InventoryIndexer;
use Webkul\Product\Models\ProductOrderedInventory;

class ProductOrderedInventoryObserver
{
    public function saved(ProductOrderedInventory $row): void
    {
        $this->reindex($row);
    }

    public function deleted(ProductOrderedInventory $row): void
    {
        $this->reindex($row);
    }

    private function reindex(ProductOrderedInventory $row): void
    {
        // OrderUpdateService::reconcileStock suspends the observer for the
        // duration of one transaction so we can fire ONE consolidated
        // index refresh at the end instead of N reindexes (one per touched
        // line). Request-scoped — does not affect concurrent FPM workers.
        if (app(InventoryReconcileGuard::class)->isSuspended()) {
            return;
        }

        $product = $row->product;

        if (! $product) {
            return;
        }

        try {
            app(InventoryIndexer::class)->reindexRow(
                $product->loadMissing(['inventories', 'ordered_inventories', 'inventory_indices'])
            );
        } catch (\Throwable $e) {
            Log::warning('[InventoryControl] sync reindex failed (ordered)', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
