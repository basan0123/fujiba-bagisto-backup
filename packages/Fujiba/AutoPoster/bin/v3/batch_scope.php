<?php
/**
 * V3 FREE batch scope (READ-ONLY: no DB writes). Called by bin/v3/batch.py:
 *   cd /var/www/bagisto && V3B_OUT=<file.json> php artisan tinker --execute="require '<bin>/v3/batch_scope.php';"
 *
 * Every product_flat row (channel default, locale en, status 1) is listed with its fail
 * reasons (empty = renderable). Same fail-closed identity rules as bin/v2/precheck_gap.php:
 * our public code only (^F[A-Z]-\d+[A-Z]?-?[A-Z]$ — Cosmo "Fb_…", fans and free-text names
 * are skipped), en+ja rows, same price, no active special price, available = inventory −
 * reserved > 0, primary photo on disk == fi_catalog_images sha256 with no dup flag, not
 * blocked by DuplicateImageGuard, code not shared. Material only from the product's own
 * catalog "Fabric:" line (JA only via the verified map; never 細布). Size only from
 * fi_product_staging_attributes source='owner_fill_in'.
 */
use Fujiba\AutoPoster\Services\Intel\DuplicateImageGuard;
use Illuminate\Support\Facades\DB;

$tenant = '1bd1181f-fcd5-4238-a12d-a207d6ea4006';
if (! tenancy()->initialized) {
    tenancy()->initialize($tenant);
}
$root = storage_path('app/public/tenants/1/'.$tenant);
$outFile = (string) getenv('V3B_OUT');
if (! $outFile) {
    throw new RuntimeException('set V3B_OUT');
}
$materialJa = ['Hosonuno (100% cotton)' => '綿100%'];
$blocked = app(DuplicateImageGuard::class)->blocked();
$codeRx = '/^(F[A-Z]-\d+[A-Z]?)-?([A-Z])$/';
$now = now('Asia/Tokyo');
$fabricOf = function (?string $html): string {
    $txt = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", (string) $html));

    return preg_match('/Fabric:\s*([^\n|]+)/i', $txt, $m) ? trim($m[1]) : '';
};
$priceActive = function ($row) use ($now): bool {
    if (! ((float) ($row->special_price ?? 0) > 0)) {
        return false;
    }
    $from = $row->special_price_from ? \Carbon\Carbon::parse($row->special_price_from) : null;
    $to = $row->special_price_to ? \Carbon\Carbon::parse($row->special_price_to)->endOfDay() : null;

    return (! $from || $from->lte($now)) && (! $to || $to->gte($now));
};

$out = [];
foreach (DB::table('product_flat')->where('channel', 'default')->where('locale', 'en')->where('status', 1)->orderBy('product_id')->get() as $en) {
    $pid = (int) $en->product_id;
    $name = trim((string) $en->name);
    $fail = [];
    $kind = 'fabric';
    if (preg_match('/\bfan\b/i', $name)) { $kind = 'fan'; $fail[] = 'not fabric (fan)'; }
    elseif (preg_match('/^F[Bb][-_ ]/', $name)) { $fail[] = 'supplier (Cosmo) name, no public code'; }
    elseif (! preg_match($codeRx, $name, $mm)) { $fail[] = 'name is not a clean public code'; }
    $series = isset($mm[1]) && preg_match($codeRx, $name) ? $mm[1] : null;
    $ja = DB::table('product_flat')->where('product_id', $pid)->where('channel', 'default')->where('locale', 'ja')->first();
    $qty = (int) DB::table('product_inventories')->where('product_id', $pid)->sum('qty');
    $reserved = (int) DB::table('product_ordered_inventories')->where('product_id', $pid)->sum('qty');
    $rel = DB::table('product_images')->where('product_id', $pid)->orderBy('position')->orderBy('id')->value('path');
    $file = $rel ? $root.'/'.$rel : null;
    $sha = $file && is_file($file) ? hash_file('sha256', $file) : null;
    [$w, $h] = $sha ? (@getimagesize($file) ?: [null, null]) : [null, null];
    $fi = $rel ? DB::table('fi_catalog_images')->where('product_id', $pid)->where('path', $rel)->first() : null;
    if (! $ja) $fail[] = 'no ja product_flat row';
    elseif ((int) $ja->status !== 1) $fail[] = 'ja status '.$ja->status;
    if ((int) $en->visible_individually !== 1) $fail[] = 'not visible_individually';
    $price = (int) round((float) $en->price);
    if (! ($price > 0)) $fail[] = 'no price';
    if ($ja && (int) round((float) $ja->price) !== $price) $fail[] = 'en/ja price differ';
    if ($priceActive($en) || ($ja && $priceActive($ja))) $fail[] = 'special price active (ambiguous price)';
    if ($qty - $reserved < 1) $fail[] = 'sold out (available '.($qty - $reserved).')';
    if (! $sha) $fail[] = 'primary photo missing on disk';
    elseif (! $fi) $fail[] = 'primary photo not in fi_catalog_images';
    elseif ($fi->sha256 !== $sha) $fail[] = 'fi_catalog_images sha256 != file sha256';
    if ($fi && ($fi->exact_dup_of || $fi->near_dup_of)) $fail[] = 'fi_catalog_images dup flag';
    if (isset($blocked[$pid])) $fail[] = 'DuplicateImageGuard: '.$blocked[$pid];
    $fab = $fabricOf($en->description);
    $size = ['width_cm' => null, 'length_m' => null];
    foreach (DB::table('fi_product_staging_attributes')->where('product_id', $pid)->where('source', 'owner_fill_in')
        ->whereIn('attribute', ['width_cm', 'length_m'])->get() as $a) {
        if (is_numeric($a->value)) $size[$a->attribute] = (float) $a->value;
    }
    $out[] = ['product_id' => $pid, 'sku' => $en->sku, 'name' => $name, 'kind' => $kind, 'series' => $series,
        'price' => $price, 'qty' => $qty, 'reserved' => $reserved, 'available' => $qty - $reserved,
        'image' => $file, 'image_rel' => $rel, 'image_sha256' => $sha, 'width' => $w, 'height' => $h,
        'material' => $fab !== '' ? ['en' => $fab, 'ja' => $materialJa[$fab] ?? null] : null,
        'size' => $size, 'fail' => $fail];
}
// an enabled code used by two renderable products is ambiguous -> neither is shown
$cnt = array_count_values(array_map(fn ($r) => $r['name'], array_filter($out, fn ($r) => ! $r['fail'])));
foreach ($out as &$r) {
    if (! $r['fail'] && $cnt[$r['name']] > 1) $r['fail'][] = 'code shared by another enabled product';
}
unset($r);
file_put_contents($outFile, json_encode(['built_at' => $now->toIso8601String(), 'tenant' => $tenant, 'products' => $out],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
echo count($out)." rows, ".count(array_filter($out, fn ($r) => ! $r['fail']))." renderable\n";
