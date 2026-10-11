<?php
/**
 * V2 "gap" colour-set cards — pre-render identity check (READ-ONLY: no DB writes).
 *
 *   cd /var/www/bagisto && GAP_SERIES=FV-67,FV-89,FV-75 GAP_OUT=/path/to/preview/dir \
 *     php artisan tinker --execute="require '/path/to/bin/v2/precheck_gap.php';"
 *
 * For every series it writes <series>.precheck.json and, only when the card passes,
 * H_<series>.manifest.json (compose.py template H input) into GAP_OUT.
 *
 * Fail closed. A member is SHOWN only if all of these hold:
 *   - name is one of OUR public codes: ^F[A-Z]-\d+[A-Z]?-?[A-Z]$ (no Cosmo / Fan / supplier codes)
 *   - product_flat channel default: en AND ja rows exist, status = 1, visible_individually = 1,
 *     same price in both locales, price > 0, no active special price
 *   - available = SUM(product_inventories.qty) - SUM(product_ordered_inventories.qty) > 0
 *   - primary photo (product_images, lowest position) exists on disk; its sha256 equals the
 *     fi_catalog_images row for that path, which has no exact_dup_of / near_dup_of
 *   - not blocked by DuplicateImageGuard
 *   - its name is not shared with another enabled member (ambiguous code)
 * The card fails if fewer than 3 members are shown, or ANY member of the series (shown or
 * not) is blocked by DuplicateImageGuard.
 * Material: printed only when every SHOWN member's catalog description has the same
 * non-empty "Fabric:" value; JA text only from the verified mapping below, else omitted.
 */

use Fujiba\AutoPoster\Services\Intel\DuplicateImageGuard;
use Illuminate\Support\Facades\DB;

$tenant = '1bd1181f-fcd5-4238-a12d-a207d6ea4006';
if (! tenancy()->initialized) {
    tenancy()->initialize($tenant);
}
$root = storage_path('app/public/tenants/1/'.$tenant);
$outDir = rtrim((string) getenv('GAP_OUT'), '/');
$wanted = array_values(array_filter(array_map('trim', explode(',', (string) getenv('GAP_SERIES')))));
if (! $outDir || ! is_dir($outDir) || ! $wanted) {
    throw new RuntimeException('set GAP_SERIES and an existing GAP_OUT directory');
}
// JA wording for catalog "Fabric:" values. Owner 2026-10-12: do NOT use 細布 until the
// catalog term is confirmed — show the fibre content only.
$materialJa = ['Hosonuno (100% cotton)' => '綿100%'];
$guard = app(DuplicateImageGuard::class);
$blockedAll = $guard->blocked();
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

$summary = [];
foreach ($wanted as $series) {
    $members = [];
    $cardFail = [];
    $rows = DB::table('product_flat')->where('channel', 'default')->where('locale', 'en')->get();
    foreach ($rows as $en) {
        $name = trim((string) $en->name);
        if (! preg_match($codeRx, $name, $mm) || $mm[1] !== $series) {
            continue;
        }
        $pid = (int) $en->product_id;
        $ja = DB::table('product_flat')->where('product_id', $pid)->where('channel', 'default')->where('locale', 'ja')->first();
        $qty = (int) DB::table('product_inventories')->where('product_id', $pid)->sum('qty');
        $reserved = (int) DB::table('product_ordered_inventories')->where('product_id', $pid)->sum('qty');
        $rel = DB::table('product_images')->where('product_id', $pid)->orderBy('position')->orderBy('id')->value('path');
        $file = $rel ? $root.'/'.$rel : null;
        $sha = $file && is_file($file) ? hash_file('sha256', $file) : null;
        [$w, $h] = $sha ? (@getimagesize($file) ?: [null, null]) : [null, null];
        $fi = $rel ? DB::table('fi_catalog_images')->where('product_id', $pid)->where('path', $rel)->first() : null;

        $fail = [];
        if ((int) $en->status !== 1) $fail[] = 'en status '.$en->status;
        if (! $ja) $fail[] = 'no ja product_flat row';
        elseif ((int) $ja->status !== 1) $fail[] = 'ja status '.$ja->status;
        if ((int) $en->visible_individually !== 1) $fail[] = 'not visible_individually';
        $price = (int) round((float) $en->price);
        if (! ($price > 0)) $fail[] = 'no price';
        if ($ja && (int) round((float) $ja->price) !== $price) $fail[] = 'en/ja price differ';
        if ($priceActive($en) || ($ja && $priceActive($ja))) $fail[] = 'special price active (ambiguous price)';
        if ($qty - $reserved < 1) $fail[] = "available ".($qty - $reserved)." (qty {$qty}, reserved {$reserved})";
        if (! $sha) $fail[] = 'primary photo missing on disk';
        if (! $fi) $fail[] = 'primary photo not in fi_catalog_images (duplicate guard cannot vouch)';
        elseif ($fi->sha256 !== $sha) $fail[] = 'fi_catalog_images sha256 != file sha256';
        if ($fi && ($fi->exact_dup_of || $fi->near_dup_of)) $fail[] = 'fi_catalog_images dup flag';
        if (isset($blockedAll[$pid])) {
            $fail[] = $blockedAll[$pid];
            $cardFail[] = "member {$name} ({$pid}) blocked by DuplicateImageGuard";
        }
        $members[] = [
            'product_id' => $pid, 'sku' => $en->sku, 'name' => $name, 'letter' => $mm[2],
            'status_en' => (int) $en->status, 'status_ja' => $ja ? (int) $ja->status : null,
            'visible_individually' => (int) $en->visible_individually,
            'price' => $price, 'price_ja' => $ja ? (int) round((float) $ja->price) : null,
            'special_price' => (float) $en->special_price,
            'qty' => $qty, 'reserved' => $reserved, 'available' => $qty - $reserved,
            'image' => ['path' => $file, 'rel' => $rel, 'sha256' => $sha, 'width' => $w, 'height' => $h,
                'fi_catalog_images' => $fi ? ['sha256_match' => $fi->sha256 === $sha, 'exact_dup_of' => $fi->exact_dup_of,
                    'near_dup_of' => $fi->near_dup_of, 'analysis_status' => $fi->analysis_status] : null],
            'duplicate_guard' => $blockedAll[$pid] ?? 'clear',
            'fabric_text' => $fabricOf($en->description),
            'fail' => $fail,
        ];
    }
    usort($members, fn ($a, $b) => [$a['name'], $a['product_id']] <=> [$b['name'], $b['product_id']]);
    // an enabled code used by two products is ambiguous -> neither is shown
    $enabledNames = array_count_values(array_column(array_filter($members, fn ($m) => ! $m['fail']), 'name'));
    foreach ($members as &$m) {
        if (! $m['fail'] && $enabledNames[$m['name']] > 1) {
            $m['fail'][] = 'code shared by another enabled product';
        }
        $m['shown'] = ! $m['fail'];
    }
    unset($m);
    $shown = array_values(array_filter($members, fn ($m) => $m['shown']));
    if (count($shown) < 3) $cardFail[] = 'only '.count($shown).' member(s) pass (need >= 3)';
    if (count($shown) > 6) $shown = array_slice($shown, 0, 6);   // template holds 3-6 tiles

    $fabrics = array_unique(array_map(fn ($m) => $m['fabric_text'], $shown));
    $material = null;
    $materialNote = 'omitted: shown members do not all state the same Fabric';
    if ($shown && count($fabrics) === 1 && reset($fabrics) !== '') {
        $matEn = reset($fabrics);
        $material = ['en' => $matEn, 'ja' => $materialJa[$matEn] ?? null];
        $materialNote = 'every shown member states "Fabric: '.$matEn.'"'.($material['ja'] ? '' : ' (no verified JA wording, JA omitted)');
    }
    $pass = ! $cardFail;
    $doc = [
        'checked_at' => $now->toIso8601String(), 'tenant' => $tenant, 'series' => $series,
        'pass' => $pass, 'fail' => $cardFail, 'tiles' => count($shown),
        'complete_series' => count($shown) === count($members),
        'material' => $material, 'material_note' => $materialNote,
        'members' => $members,
    ];
    file_put_contents("{$outDir}/{$series}.precheck.json", json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    if ($pass) {
        $manifest = ['series' => $series, 'material' => $material, 'products' => [['id' => $shown[0]['product_id'], 'name' => $series]],
            'set' => array_map(fn ($m) => ['id' => $m['product_id'], 'name' => $m['name'], 'letter' => $m['letter'], 'price' => $m['price'],
                'image' => $m['image']['path'], 'sha256' => $m['image']['sha256'], 'box' => [0, 0, $m['image']['width'], $m['image']['height']]], $shown)];
        file_put_contents("{$outDir}/H_{$series}.manifest.json", json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
    $summary[] = ['series' => $series, 'pass' => $pass, 'tiles' => count($shown), 'complete' => $doc['complete_series'],
        'material' => $material, 'fail' => $cardFail,
        'members' => array_map(fn ($m) => "{$m['name']}#{$m['product_id']} ".($m['shown'] ? 'SHOWN' : 'skip: '.implode('; ', $m['fail'])), $members)];
}
echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
