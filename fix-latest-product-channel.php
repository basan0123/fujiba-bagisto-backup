<?php

/**
 * Fix ONLY the single latest product: set its NULL/blank product_flat.channel
 * to 'default' (the fujiba.co.jp / kimono channel). Non-destructive, one product only.
 *
 * NOTE: `tinker <file>` does NOT honour --host; it runs in the default DB context.
 * Run it tenant-scoped so it targets the fujiba tenant DB, e.g.:
 *   cd /var/www/bagisto && php artisan tenants:run tinker --tenants=<fujiba-id> -- fix-latest-product-channel.php
 */

use Illuminate\Support\Facades\DB;

$latest = DB::table('products')->orderByDesc('id')->first(['id', 'sku']);

if (! $latest) {
    echo "No products found.".PHP_EOL;
    return;
}

$rows = DB::table('product_flat')
    ->where('product_id', $latest->id)
    ->where(function ($q) {
        $q->whereNull('channel')->orWhere('channel', '');
    })
    ->update(['channel' => 'default']);

echo "Latest product id={$latest->id} sku={$latest->sku}: set channel='default' on {$rows} flat row(s).".PHP_EOL;
echo "Reload /admin/catalog/products to verify.".PHP_EOL;
