<?php
/**
 * Tenant-aware price reindex for the ￥ band products.
 * `php artisan indexer:index` fails in this multi-tenant app (no channel context),
 * so we initialize the fujiba tenant and reindex just the affected products.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;

$tenant = \App\Models\Tenant::find('1bd1181f-fcd5-4238-a12d-a207d6ea4006');
tenancy()->initialize($tenant);
$conn = DB::connection('tenant');

$bandCats = [19, 22, 21, 26];

$pids = $conn->table('product_categories')
    ->whereIn('category_id', $bandCats)
    ->distinct()->pluck('product_id')->all();

echo "products to reindex: " . count($pids) . PHP_EOL;

$productRepository = app(\Webkul\Product\Repositories\ProductRepository::class);
$indexer = app(PriceIndexer::class);

$done = 0;
foreach (array_chunk($pids, 100) as $chunk) {
    $products = $productRepository->with(['price_indices', 'attribute_family', 'attribute_values'])
        ->findWhereIn('id', $chunk);
    $indexer->reindexBatch($products);
    $done += count($chunk);
    echo "reindexed $done / " . count($pids) . PHP_EOL;
}

echo "=== verify index now ===" . PHP_EOL;
$targets = [19 => 1595, 22 => 1900, 21 => 2500, 26 => 2000];
foreach ($targets as $cat => $t) {
    $cpids = $conn->table('product_categories')->where('category_id', $cat)->pluck('product_id')->all();
    $idx = $conn->table('product_price_indices')->whereIn('product_id', $cpids)
        ->select('min_price')->distinct()->pluck('min_price');
    echo "cat $cat -> $t : index min_price = [" . implode(',', $idx->all()) . "]" . PHP_EOL;
}
