<?php

namespace Webkul\Product\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Helpers\StockAuditRecorder;

class ProductInventoryRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return 'Webkul\Product\Contracts\ProductInventory';
    }

    /**
     * @param  Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function saveInventories(array $data, $product)
    {
        if (! isset($data['inventories'])) {
            return;
        }

        foreach ($data['inventories'] as $inventorySourceId => $qty) {
            $newQty = (int) ($qty ?? 0);
            $before = StockAuditRecorder::currentQty((int) $product->id, (int) $inventorySourceId);

            $this->updateOrCreate([
                'product_id' => $product->id,
                'inventory_source_id' => $inventorySourceId,
                'vendor_id' => $data['vendor_id'] ?? 0,
            ], [
                'qty' => $newQty,
            ]);

            StockAuditRecorder::record(
                (int) $product->id,
                (int) $inventorySourceId,
                $before,
                $newQty,
                'admin.product.save_inventories'
            );
        }
    }
}
