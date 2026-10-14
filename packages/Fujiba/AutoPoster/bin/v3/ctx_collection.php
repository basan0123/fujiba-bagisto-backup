<?php
/**
 * Family 8 'collection' context (READ-ONLY). Run AFTER bin/v3/ctx.php wrote <dir>/ctx.json:
 *   cd /var/www/bagisto && V3_OUT=<dir> php artisan tinker --execute="require '<bin>/v3/ctx_collection.php';"
 * Adds the raw catalog descriptions (product_flat en + ja, tags stripped) to every product and
 * series member. Parsing ("Color: X (Name)", "Pattern:", "Fabric:") is done in Python
 * (families.catalog_fields) so it is unit-tested; nothing is translated or invented here.
 */
use Illuminate\Support\Facades\DB;

if (! tenancy()->initialized) {
    tenancy()->initialize('1bd1181f-fcd5-4238-a12d-a207d6ea4006');
}
$out = rtrim((string) getenv('V3_OUT'), '/');
$ctx = json_decode(file_get_contents($out.'/ctx.json'), true);
$desc = function (int $pid, string $loc) {
    $h = (string) DB::table('product_flat')->where('product_id', $pid)->where('locale', $loc)->value('description');
    // keep line structure: block tags -> newline before stripping
    $h = preg_replace('#<(br|/p|/div|/li)[^>]*>#i', "\n", $h);
    return trim(html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
};
foreach ($ctx['products'] as &$p) {
    $p['description_en'] = $desc($p['product_id'], 'en');
    $p['description_ja'] = $desc($p['product_id'], 'ja');
    foreach ($p['series_members'] as &$m) {
        $m['description_en'] = $desc($m['product_id'], 'en');
        $m['description_ja'] = $desc($m['product_id'], 'ja');
    }
    unset($m);
}
unset($p);
file_put_contents($out.'/ctx.json', json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo count($ctx['products'])." products augmented with catalog descriptions\n";
