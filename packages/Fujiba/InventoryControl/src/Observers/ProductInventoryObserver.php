<?php

namespace Fujiba\InventoryControl\Observers;

use Illuminate\Support\Facades\Log;
use Webkul\Product\Helpers\Indexers\Inventory as InventoryIndexer;
use Webkul\Product\Models\ProductInventory;

class ProductInventoryObserver
{
    public function saved(ProductInventory $inventory): void
    {
        $this->reindex($inventory);
    }

    public function deleted(ProductInventory $inventory): void
    {
        $this->reindex($inventory);
    }

    private function reindex(ProductInventory $inventory): void
    {
        // See ProductOrderedInventoryObserver — request-scoped guard so the
        // OrderUpdateService pipeline can defer reindex to a single pass.
        if (app(\Fujiba\OrdersAdmin\Services\InventoryReconcileGuard::class)->isSuspended()) {
            return;
        }

        $product = $inventory->product;

        if (! $product) {
            return;
        }

        try {
            app(InventoryIndexer::class)->reindexRow(
                $product->loadMissing(['inventories', 'ordered_inventories', 'inventory_indices'])
            );
        } catch (\Throwable $e) {
            Log::warning('[InventoryControl] sync reindex failed', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
