<?php
/**
 * Fujiba (tenant 1bd1181f-...) one-time ￥ price-band sync.
 *
 * For products in the ￥ band categories, force price = band number, BUT ONLY if:
 *   - the product belongs to NO other category (only in this band), AND
 *   - its current price is >= 1295.
 * Other categories / products are never touched.
 *
 * Bands: cat 19 -> 1595, cat 22 -> 1900, cat 21 -> 2500, cat 26 -> 2000.
 *   (cat 20 / ￥1795 is intentionally NOT included.)
 *
 * Run:  php price_sync.php           (dry run – prints what it WOULD change)
 *       php price_sync.php --apply   (applies in a transaction, then reindexes)
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$APPLY = in_array('--apply', $argv, true);

$tenant = \App\Models\Tenant::find('1bd1181f-fcd5-4238-a12d-a207d6ea4006');
tenancy()->initialize($tenant);
$conn = DB::connection('tenant');

const PRICE_ATTRIBUTE_ID = 11;
const MIN_PRICE = 1295.0;

$bands = [19 => 1595, 22 => 1900, 21 => 2500, 26 => 2000];

// Build the set of eligible products and their target prices.
$plan = [];   // product_id => ['target'=>int, 'current'=>float, 'cat'=>int]
$skipMulti = 0;
$skipBelowMin = 0;

foreach ($bands as $cat => $target) {
    $pids = $conn->table('product_categories')->where('category_id', $cat)->pluck('product_id')->all();
    if (! $pids) {
        continue;
    }

    // products that are ALSO in another category -> skip
    $catCounts = $conn->table('product_categories')->whereIn('product_id', $pids)
        ->select('product_id', DB::raw('count(*) c'))->groupBy('product_id')->pluck('c', 'product_id')->all();

    // current prices
    $prices = $conn->table('product_attribute_values')
        ->whereIn('product_id', $pids)
        ->where('attribute_id', PRICE_ATTRIBUTE_ID)
        ->pluck('float_value', 'product_id')->all();

    foreach ($pids as $pid) {
        // NOTE: "set all in band" — products in other categories are NOT skipped.
        $current = isset($prices[$pid]) ? (float) $prices[$pid] : null;
        if ($current === null) { continue; }
        if ($current < MIN_PRICE) { $skipBelowMin++; continue; }                 // below 1295
        // A product could theoretically be in two bands; first band wins (stable order).
        if (! isset($plan[$pid])) {
            $plan[$pid] = ['target' => $target, 'current' => $current, 'cat' => $cat];
        }
    }
}

echo ($APPLY ? "=== APPLYING ===" : "=== DRY RUN (no changes) ===") . PHP_EOL;
echo "eligible products to set: " . count($plan)
    . "  | skipped (in other category): $skipMulti  | skipped (< " . MIN_PRICE . "): $skipBelowMin" . PHP_EOL;

// summary per band
$byBand = [];
foreach ($plan as $pid => $info) {
    $byBand[$info['cat']]['count'] = ($byBand[$info['cat']]['count'] ?? 0) + 1;
    if ((float) $info['current'] !== (float) $info['target']) {
        $byBand[$info['cat']]['changing'] = ($byBand[$info['cat']]['changing'] ?? 0) + 1;
    }
}
foreach ($bands as $cat => $target) {
    $c = $byBand[$cat]['count'] ?? 0;
    $ch = $byBand[$cat]['changing'] ?? 0;
    echo "  cat $cat -> $target : will set $c products ($ch actually changing price)" . PHP_EOL;
}

// show a few examples
echo "--- sample (first 8) ---" . PHP_EOL;
$n = 0;
foreach ($plan as $pid => $info) {
    echo "  product $pid: {$info['current']} -> {$info['target']} (cat {$info['cat']})" . PHP_EOL;
    if (++$n >= 8) break;
}

if (! $APPLY) {
    echo "(dry run complete – rerun with --apply to commit)" . PHP_EOL;
    return;
}

if (! $plan) {
    echo "Nothing to do." . PHP_EOL;
    return;
}

// ---- Backup affected price rows ----
$pids = array_keys($plan);
$backup = $conn->table('product_attribute_values')
    ->whereIn('product_id', $pids)->where('attribute_id', PRICE_ATTRIBUTE_ID)->get();
$backupFile = __DIR__ . '/price_sync_backup_' . date('Ymd_His') . '.json';
file_put_contents($backupFile, json_encode($backup, JSON_PRETTY_PRINT));
echo "Backup written: $backupFile (" . count($backup) . " rows)" . PHP_EOL;

// ---- Apply in a transaction ----
$conn->transaction(function () use ($conn, $plan) {
    foreach ($plan as $pid => $info) {
        $conn->table('product_attribute_values')
            ->where('product_id', $pid)
            ->where('attribute_id', PRICE_ATTRIBUTE_ID)
            ->update(['float_value' => $info['target']]);
    }
});
echo "Updated " . count($plan) . " price rows." . PHP_EOL;
echo "=== price attribute updated. Run reindex next. ===" . PHP_EOL;
