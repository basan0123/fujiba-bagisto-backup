<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tenant = \App\Models\Tenant::find('1bd1181f-fcd5-4238-a12d-a207d6ea4006');
tenancy()->initialize($tenant);
$conn = DB::connection('tenant');

$ids = [18, 19, 20, 21, 22, 26];

echo "=== categories ===" . PHP_EOL;
foreach ($conn->table('categories')->whereIn('id', $ids)->get(['id','status','parent_id','position']) as $c) {
    echo "id={$c->id} status={$c->status} parent={$c->parent_id} pos={$c->position}" . PHP_EOL;
}

echo "=== translations (locale,name,slug) ===" . PHP_EOL;
foreach ($conn->table('category_translations')->whereIn('category_id', $ids)->orderBy('category_id')->orderBy('locale')->get() as $t) {
    echo "cat={$t->category_id} [{$t->locale}] name=\"{$t->name}\" slug=\"{$t->slug}\"" . PHP_EOL;
}

echo "=== product counts (product_categories) ===" . PHP_EOL;
foreach ($ids as $id) {
    $n = $conn->table('product_categories')->where('category_id', $id)->count();
    echo "cat=$id products=$n" . PHP_EOL;
}

echo "=== overlap: products in BOTH 18 and 19 ===" . PHP_EOL;
$in18 = $conn->table('product_categories')->where('category_id', 18)->pluck('product_id')->all();
$in19 = $conn->table('product_categories')->where('category_id', 19)->pluck('product_id')->all();
$overlap = array_intersect($in18, $in19);
echo "in18=" . count($in18) . " in19=" . count($in19) . " overlap=" . count($overlap) . PHP_EOL;

echo "=== existing slugs that could collide with new ￥ slugs ===" . PHP_EOL;
foreach (['1595','1795','2500','1900','2000'] as $s) {
    foreach (['￥'.$s, $s] as $cand) {
        $hit = $conn->table('category_translations')->where('slug', $cand)->pluck('category_id')->all();
        if ($hit) echo "slug \"$cand\" already used by cat(s): " . implode(',', $hit) . PHP_EOL;
    }
}
