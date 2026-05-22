<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = \App\Models\Tenant::find('1bd1181f-fcd5-4238-a12d-a207d6ea4006');
tenancy()->initialize($tenant);

// Find every order that has a shipment row (=> qty_shipped should reflect the shipment),
// excluding #1560 since we already fixed it.
// For each such order, compute the *should-deduct* qty per product_id+inventory_source_id
// from shipment_items. The shipment-create path already wrote qty_shipped on order_items.
// We use shipment_items as the ground truth.

$apply = in_array('--apply', $_SERVER['argv'] ?? [], true);

$shipments = \DB::table('shipments')
    ->orderBy('id')
    ->get(['id', 'order_id', 'inventory_source_id', 'created_at']);

echo "Found " . count($shipments) . " shipment rows total\n";

$totalDeducted = 0;
$ordersTouched = [];

\DB::beginTransaction();
try {
    foreach ($shipments as $s) {
        if ((int) $s->order_id === 1560) continue; // already backfilled

        $items = \DB::table('shipment_items')
            ->where('shipment_id', $s->id)
            ->get(['product_id', 'qty']);

        if ($items->isEmpty()) continue;

        $order = \DB::table('orders')->where('id', $s->order_id)->first(['channel_id', 'status']);
        if (!$order) continue;

        foreach ($items as $si) {
            $qty = (int) $si->qty;
            if ($qty <= 0 || !$si->product_id) continue;

            $product = \Webkul\Product\Models\Product::find($si->product_id);
            if (!$product) continue;

            // Skip products that DO manage stock — for those the core path already deducted correctly.
            // Detect via attribute_values to match the core check.
            if ((bool) $product->manage_stock) continue;

            $inv = \Webkul\Product\Models\ProductInventory::where('product_id', $product->id)
                ->where('inventory_source_id', $s->inventory_source_id)
                ->where('vendor_id', 0)
                ->first();
            if (!$inv) continue;

            $before = (int) $inv->qty;
            $after = max(0, $before - $qty);
            if ($before === $after && $qty > 0 && $before === 0) {
                // already at 0 — can't deduct further; record but skip write
            }
            if ($before !== $after) {
                echo sprintf("  ord#%d ship#%d prod#%d sku=%s src=%d: %d -> %d (-%d)\n",
                    $s->order_id, $s->id, $product->id, $product->sku, $s->inventory_source_id, $before, $after, $qty);
                $inv->qty = $after;
                if ($apply) $inv->save();
                $totalDeducted += ($before - $after);
                $ordersTouched[$s->order_id] = true;
            }

            // Clear ordered_inventories for this channel/product if still > 0
            $ord = \Webkul\Product\Models\ProductOrderedInventory::where('product_id', $product->id)
                ->where('channel_id', $order->channel_id)
                ->first();
            if ($ord && (int) $ord->qty > 0) {
                $before = (int) $ord->qty;
                $after = max(0, $before - $qty);
                if ($before !== $after) {
                    echo sprintf("    ord_inv ch=%d: %d -> %d (-%d)\n", $order->channel_id, $before, $after, $qty);
                    $ord->qty = $after;
                    if ($apply) $ord->save();
                }
            }
        }
    }

    if ($apply) {
        \DB::commit();
        echo "\nCOMMIT — " . count($ordersTouched) . " orders touched, " . $totalDeducted . " units deducted total\n";
    } else {
        \DB::rollBack();
        echo "\nDRY RUN — " . count($ordersTouched) . " orders would be touched, " . $totalDeducted . " units would be deducted\n";
    }
} catch (\Throwable $e) {
    \DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    throw $e;
}
