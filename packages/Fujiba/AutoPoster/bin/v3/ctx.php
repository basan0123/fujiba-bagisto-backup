<?php
/**
 * V3 product context (READ-ONLY). Run AFTER bin/v2/precheck_gap.php wrote <series>.precheck.json:
 *   cd /var/www/bagisto && V3_OUT=<dir> V3_PRODUCTS=2075,2035,1963 \
 *     php artisan tinker --execute="require '<bin>/v3/ctx.php';"
 * Writes <dir>/ctx.json. A product is included only if its precheck row has no fail.
 * Size: ONLY fi_product_staging_attributes source='owner_fill_in' (width_cm / length_m) or
 * "Width: N cm" / "Length: N m" in the catalog description; otherwise null (renderers print
 * "size not confirmed" and draw no tape).
 */
use Illuminate\Support\Facades\DB;

if (! tenancy()->initialized) {
    tenancy()->initialize('1bd1181f-fcd5-4238-a12d-a207d6ea4006');
}
$out = rtrim((string) getenv('V3_OUT'), '/');
$want = array_map('intval', array_filter(explode(',', (string) getenv('V3_PRODUCTS'))));
$res = ['built_at' => now('Asia/Tokyo')->toIso8601String(), 'products' => [], 'rejected' => []];
foreach (glob($out.'/*.precheck.json') as $f) {
    $pc = json_decode(file_get_contents($f), true);
    $shown = array_values(array_filter($pc['members'], fn ($m) => ! $m['fail']));
    foreach ($pc['members'] as $m) {
        if (! in_array($m['product_id'], $want, true)) continue;
        if ($m['fail']) { $res['rejected'][] = [$m['name'], $m['fail']]; continue; }
        $size = ['width_cm' => null, 'length_m' => null];
        $src = [];
        foreach (DB::table('fi_product_staging_attributes')->where('product_id', $m['product_id'])
            ->where('source', 'owner_fill_in')->whereIn('attribute', ['width_cm', 'length_m'])->get() as $a) {
            if (is_numeric($a->value)) { $size[$a->attribute] = (float) $a->value; $src[$a->attribute] = 'owner_fill_in'; }
        }
        $txt = strip_tags((string) DB::table('product_flat')->where('product_id', $m['product_id'])->where('locale', 'en')->value('description'));
        if ($size['width_cm'] === null && preg_match('/Width:\s*([\d.]+)\s*cm/i', $txt, $mm)) { $size['width_cm'] = (float) $mm[1]; $src['width_cm'] = 'catalog'; }
        if ($size['length_m'] === null && preg_match('/Length:\s*([\d.]+)\s*m\b/i', $txt, $mm)) { $size['length_m'] = (float) $mm[1]; $src['length_m'] = 'catalog'; }
        $res['products'][] = [
            'product_id' => $m['product_id'], 'name' => $m['name'], 'price' => $m['price'], 'image' => $m['image']['path'],
            'image_sha256' => $m['image']['sha256'], 'material' => $pc['material'] ?? null,
            'size' => $size, 'size_source' => $src,
            'series' => $pc['series'], 'series_members' => array_map(fn ($s) => ['product_id' => $s['product_id'],
                'name' => $s['name'], 'price' => $s['price'], 'image' => $s['image']['path']], $shown),
        ];
    }
}
file_put_contents($out.'/ctx.json', json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo count($res['products']).' products, '.count($res['rejected'])." rejected\n";
