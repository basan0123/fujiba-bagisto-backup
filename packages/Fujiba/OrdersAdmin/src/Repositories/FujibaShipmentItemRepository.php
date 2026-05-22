<?php

namespace Fujiba\OrdersAdmin\Repositories;

use Illuminate\Support\Facades\Event;
use Webkul\Sales\Repositories\ShipmentItemRepository;

class FujibaShipmentItemRepository extends ShipmentItemRepository
{
    public function updateProductInventory($data)
    {
        if (! $data['product']) {
            return;
        }

        $product = $data['product'];
        $qty     = (int) $data['qty'];

        // Bagisto core skips both deductions when manage_stock=0. On fujiba
        // every product still has real product_inventories + ordered_inventories
        // rows (ChatPion sync populates them) so we deduct regardless of the
        // attribute flag. Only the actual data presence gates the writes.
        $orderedInventory = $product->ordered_inventories()
            ->where('channel_id', $data['shipment']->order->channel->id)
            ->first();

        if ($orderedInventory) {
            $orderedQty = (int) $orderedInventory->qty - $qty;
            if ($orderedQty < 0) {
                $orderedQty = 0;
            }
            $orderedInventory->update(['qty' => $orderedQty]);
        }

        $inventory = $product->inventories()
            ->where('vendor_id', $data['vendor_id'] ?? 0)
            ->where('inventory_source_id', $data['shipment']->inventory_source_id)
            ->first();

        if (! $inventory) {
            return;
        }

        $newQty = (int) $inventory->qty - $qty;
        if ($newQty < 0) {
            $newQty = 0;
        }
        $inventory->update(['qty' => $newQty]);

        Event::dispatch('catalog.product.update.after', $product);
    }
}
