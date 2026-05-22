<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = \App\Models\Tenant::find('1bd1181f-fcd5-4238-a12d-a207d6ea4006');
tenancy()->initialize($tenant);

$apply = in_array('--apply', $_SERVER['argv'] ?? [], true);

$orderId = 1560;
foreach ($_SERVER['argv'] ?? [] as $i => $a) {
    if ($i === 0) continue;
    if ($a === '--apply') continue;
    if (is_numeric($a)) { $orderId = (int) $a; break; }
}
$o = \Webkul\Sales\Models\Order::find($orderId);
if (!$o) { echo "no order $orderId\n"; exit(1); }

$channelId = (int) $o->channel_id;
$shipment  = $o->shipments->first();
if (!$shipment) { echo "no shipment on 1560\n"; exit(1); }
$sourceId = (int) $shipment->inventory_source_id;

echo "Order #{$o->id} channel=$channelId source=$sourceId apply=" . ($apply ? 'YES' : 'no') . "\n";

\DB::beginTransaction();
try {
    foreach ($o->items as $item) {
        $product = $item->product;
        if (!$product) {
            echo "  item#{$item->id}: no product (deleted?), skipping\n";
            continue;
        }
        $qty = (int) $item->qty_shipped;
        if ($qty <= 0) {
            echo "  item#{$item->id}: qty_shipped=0, skip\n";
            continue;
        }

        // Deduct product_inventories at the shipment source
        $inv = \Webkul\Product\Models\ProductInventory::where('product_id', $product->id)
            ->where('inventory_source_id', $sourceId)
            ->where('vendor_id', 0)
            ->first();
        if ($inv) {
            $before = (int) $inv->qty;
            $after  = max(0, $before - $qty);
            echo "  prod#{$product->id} sku={$product->sku} inv@src{$sourceId}: $before -> $after (-$qty)\n";
            $inv->qty = $after;
            if ($apply) $inv->save();
        } else {
            echo "  prod#{$product->id}: no inventory row at src=$sourceId\n";
        }

        // Clear ordered_inventories for this channel
        $ord = \Webkul\Product\Models\ProductOrderedInventory::where('product_id', $product->id)
            ->where('channel_id', $channelId)
            ->first();
        if ($ord) {
            $before = (int) $ord->qty;
            $after  = max(0, $before - $qty);
            echo "    ord@ch{$channelId}: $before -> $after (-$qty)\n";
            $ord->qty = $after;
            if ($apply) $ord->save();
        }
    }

    if ($apply) {
        \DB::commit();
        echo "COMMIT\n";
    } else {
        \DB::rollBack();
        echo "DRY RUN (rolled back)\n";
    }
} catch (\Throwable $e) {
    \DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    throw $e;
}
