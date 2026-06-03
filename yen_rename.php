<?php
/**
 * Fujiba (tenant 1bd1181f-...) Yen price-band category rename + merge.
 *
 *  - Rename categories to "￥<number>" (display name) with ASCII slug "<number>yen".
 *  - Merge old 1295Yen (id 18) into ￥1595 (id 19): move id 18's products to id 19,
 *    then DISABLE id 18.
 *
 * Order matters: id 20 gives up slug "1595yen" (becomes 1795yen) BEFORE id 19 claims it.
 *
 * Run:  php yen_rename.php          (dry run – prints what it WOULD do)
 *       php yen_rename.php --apply  (performs the change in a transaction)
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$APPLY = in_array('--apply', $argv, true);

$tenant = \App\Models\Tenant::find('1bd1181f-fcd5-4238-a12d-a207d6ea4006');
tenancy()->initialize($tenant);
$conn = DB::connection('tenant');

// id => [new display name, new ascii slug]. Ordered so slug "1595yen" is freed before reuse.
$renames = [
    20 => ['￥1795', '1795yen'],
    19 => ['￥1595', '1595yen'],
    21 => ['￥2500', '2500yen'],
    22 => ['￥1900', '1900yen'],
    26 => ['￥2000', '2000yen'],
];
$MERGE_FROM = 18;   // old 1295Yen -> move products into id 19, then disable
$MERGE_INTO = 19;

// ---- Backup affected rows ----
$affectedIds = array_merge(array_keys($renames), [$MERGE_FROM]);
$backup = [
    'category_translations' => $conn->table('category_translations')->whereIn('category_id', $affectedIds)->get(),
    'categories'            => $conn->table('categories')->whereIn('id', $affectedIds)->get(['id','status']),
    'product_categories_18' => $conn->table('product_categories')->where('category_id', $MERGE_FROM)->get(),
];
$backupFile = __DIR__ . '/yen_rename_backup_' . date('Ymd_His') . '.json';
file_put_contents($backupFile, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Backup written: $backupFile" . PHP_EOL;

echo ($APPLY ? "=== APPLYING ===" : "=== DRY RUN (no changes) ===") . PHP_EOL;

$run = function () use ($conn, $renames, $MERGE_FROM, $MERGE_INTO, $APPLY) {
    foreach ($renames as $id => [$name, $slug]) {
        echo "cat $id -> name=\"$name\" slug=\"$slug\" (all locales)" . PHP_EOL;
        if ($APPLY) {
            $conn->table('category_translations')
                ->where('category_id', $id)
                ->update(['name' => $name, 'slug' => $slug]);
        }
    }

    // Merge: move products of MERGE_FROM into MERGE_INTO (skip ones already linked)
    $fromProducts = $conn->table('product_categories')->where('category_id', $MERGE_FROM)->pluck('product_id')->all();
    $alreadyIn    = $conn->table('product_categories')->where('category_id', $MERGE_INTO)->pluck('product_id')->all();
    $toMove       = array_values(array_diff($fromProducts, $alreadyIn));
    echo "merge: cat $MERGE_FROM has " . count($fromProducts) . " products; "
        . count($toMove) . " will be added to cat $MERGE_INTO; then cat $MERGE_FROM disabled." . PHP_EOL;
    if ($APPLY && $toMove) {
        $rows = array_map(fn ($pid) => ['product_id' => $pid, 'category_id' => $MERGE_INTO], $toMove);
        foreach (array_chunk($rows, 200) as $chunk) {
            $conn->table('product_categories')->insert($chunk);
        }
    }
    if ($APPLY) {
        $conn->table('categories')->where('id', $MERGE_FROM)->update(['status' => 0]);
    }
};

if ($APPLY) {
    $conn->transaction($run);
    echo "=== DONE ===" . PHP_EOL;
} else {
    $run();
    echo "(dry run complete – rerun with --apply to commit)" . PHP_EOL;
}

// ---- Verify / show resulting state ----
echo "=== RESULTING STATE ===" . PHP_EOL;
foreach ($conn->table('categories')->whereIn('id', array_merge(array_keys($renames), [$MERGE_FROM]))->orderBy('id')->get(['id','status']) as $c) {
    $t = $conn->table('category_translations')->where('category_id', $c->id)->where('locale','en')->first();
    $pc = $conn->table('product_categories')->where('category_id', $c->id)->count();
    echo "cat {$c->id} status={$c->status} name=\"{$t->name}\" slug=\"{$t->slug}\" products=$pc" . PHP_EOL;
}
