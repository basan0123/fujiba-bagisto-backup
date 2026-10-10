<?php
/**
 * V2 TEST pre-render identity check (READ-ONLY — no DB writes).
 *
 *   cd /var/www/bagisto && PRECHECK_OUT=/path/precheck.json \
 *     php artisan tinker --execute="require '/path/to/bin/v2/precheck.php';"
 *
 * Per SKU, fail closed: product_flat (channel default, locale en) sku/name/status=1/price,
 * stock = sum(product_inventories.qty) > 0, knowledge row product_id+sku match,
 * knowledge image_hash == sha256-chain of the current product_images (KnowledgeCommand::images
 * formula), closeup image is the product's own photo, not in DuplicateImageGuard blocked list.
 */

use Fujiba\AutoPoster\Services\Intel\DuplicateImageGuard;
use Illuminate\Support\Facades\DB;

$tenant = '1bd1181f-fcd5-4238-a12d-a207d6ea4006';
if (! tenancy()->initialized) {
    tenancy()->initialize($tenant);
}
$root = storage_path('app/public/tenants/1/'.$tenant);
$skus = [1976 => 'KIMONO-2549', 1988 => 'KIMONO-2561', 2045 => 'KIMONO-2618', 2191 => 'KIMONO-2773', 2517 => 'KIMONO-3162'];
$names = [1976 => 'FV-81-A', 1988 => 'FV-10-A', 2045 => 'FV-73-C', 2191 => 'FV-59-A', 2517 => 'FV-61-C'];
$guard = app(DuplicateImageGuard::class);
$out = [];

foreach ($skus as $pid => $sku) {
    $fail = [];
    $flat = DB::table('product_flat')->where('product_id', $pid)->where('channel', 'default')->where('locale', 'en')->first();
    if (! $flat) {
        $fail[] = 'no product_flat default/en row';
    } else {
        if ($flat->sku !== $sku) $fail[] = "sku {$flat->sku} != {$sku}";
        if ((int) $flat->status !== 1) $fail[] = "status {$flat->status}";
        if (! ($flat->price > 0)) $fail[] = 'no price';
        if (trim((string) $flat->name) === '') $fail[] = 'empty name';
    }
    $stock = (int) DB::table('product_inventories')->where('product_id', $pid)->sum('qty');
    if ($stock < 1) $fail[] = "stock {$stock}";

    $k = DB::table('autoposter_pattern_knowledge')->where('product_id', $pid)->first();
    $closeup = $k ? json_decode($k->closeup, true) : null;
    if (! $k) {
        $fail[] = 'no knowledge row';
    } else {
        if ($k->sku !== $sku) $fail[] = "knowledge sku {$k->sku} != {$sku}";
        if ($k->name !== $names[$pid]) $fail[] = "knowledge name {$k->name} != {$names[$pid]}";
    }

    // current photos, same resolution + hash chain as KnowledgeCommand::images()
    $h = hash_init('sha256');
    $imgs = [];
    foreach (DB::table('product_images')->where('product_id', $pid)->orderBy('position')->pluck('path') as $path) {
        $f = $root.'/'.$path;
        $jpg = preg_replace('/\.webp$/', '.jpg', $f);
        $f = is_file($jpg) ? $jpg : $f;
        if (! is_file($f)) continue;
        $sha = hash_file('sha256', $f);
        $imgs[] = ['path' => $f, 'sha256' => $sha];
        hash_update($h, $sha);
    }
    $chain = hash_final($h);
    if (! $imgs) $fail[] = 'no product images on disk';
    if ($k && $chain !== $k->image_hash) $fail[] = 'image_hash mismatch (photos changed since knowledge import)';

    $src = $closeup ? $root.'/'.$closeup['image'] : null;
    $srcSha = $src && is_file($src) ? hash_file('sha256', $src) : null;
    if (! $srcSha) $fail[] = 'closeup image missing';
    elseif (! in_array($srcSha, array_column($imgs, 'sha256'), true)) $fail[] = 'closeup image is not one of this product\'s photos';
    if (strpos((string) ($closeup['image'] ?? ''), "product/{$pid}/") !== 0) $fail[] = 'closeup path not under product/'.$pid;

    $blocked = $guard->reason($pid);
    if ($blocked) $fail[] = $blocked;

    $out[] = [
        'product_id' => $pid, 'sku' => $flat->sku ?? null, 'knowledge_name' => $k->name ?? null,
        'name' => $flat->name ?? null, 'status' => isset($flat) ? (int) $flat->status : null,
        'price' => isset($flat) ? (int) round((float) $flat->price) : null, 'price_raw' => $flat->price ?? null,
        'stock' => $stock,
        'primary_image' => $imgs[0] ?? null, 'closeup' => $closeup ? ['path' => $src, 'sha256' => $srcSha] + $closeup : null,
        'images_chain_sha256' => $chain, 'knowledge_image_hash' => $k->image_hash ?? null,
        'duplicate_guard' => $blocked ?: 'clear',
        'pass' => ! $fail, 'fail' => $fail,
    ];
}

$set = DB::table('autoposter_settings')->first();
$doc = ['checked_at' => now('Asia/Tokyo')->toIso8601String(), 'tenant' => $tenant,
    'messenger_destination' => $set->messenger_destination ?? null, 'skus' => $out];
$json = json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($p = getenv('PRECHECK_OUT')) file_put_contents($p, $json);
echo $json, "\n";
