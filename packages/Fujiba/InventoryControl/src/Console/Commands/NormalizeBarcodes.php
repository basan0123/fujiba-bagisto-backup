<?php

namespace Fujiba\InventoryControl\Console\Commands;

use Fujiba\InventoryControl\Support\BarcodeNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NormalizeBarcodes extends Command
{
    protected $signature = 'pos:barcodes-normalize {--dry-run : show what would change without writing}';

    protected $description = 'Normalize pos_product_barcode rows so scanner output (ASCII digits) matches what was typed (Burmese/fullwidth digits, prefix junk).';

    public function handle(): int
    {
        $dbName = DB::connection()->getDatabaseName();

        if (Str::startsWith((string) $dbName, ['bagisto_central', 'central_'])) {
            $this->error("Refusing to run on central DB ({$dbName}). Use: php artisan tenants:run pos:barcodes-normalize");
            return self::FAILURE;
        }

        if (! Schema::hasTable('pos_product_barcode')) {
            $this->warn("pos_product_barcode missing on {$dbName} — skipping.");
            return self::SUCCESS;
        }

        $this->info("Tenant DB: {$dbName}");

        $rows = DB::table('pos_product_barcode')->get(['id', 'product_id', 'barcode', 'scan_value']);
        $total = $rows->count();

        $changed = 0;
        $skippedEmpty = 0;
        $collisions = 0;
        $dryRun = (bool) $this->option('dry-run');

        foreach ($rows as $row) {
            $newBarcode = BarcodeNormalizer::normalize((string) $row->barcode);
            $newScan = BarcodeNormalizer::normalize((string) $row->scan_value);

            $needsUpdate = ($newBarcode !== (string) $row->barcode) || ($newScan !== (string) $row->scan_value);

            if (! $needsUpdate) {
                continue;
            }

            if ($newBarcode === '' && $newScan === '') {
                $skippedEmpty++;
                $this->warn("  row id={$row->id} product_id={$row->product_id}: normalize → empty, leaving untouched (raw='{$row->barcode}')");
                continue;
            }

            // Detect a collision: another row already holds the normalized value.
            $collision = DB::table('pos_product_barcode')
                ->where('id', '!=', $row->id)
                ->where(function ($w) use ($newBarcode, $newScan) {
                    $w->where('barcode', $newBarcode)
                        ->orWhere('scan_value', $newBarcode)
                        ->orWhere('barcode', $newScan)
                        ->orWhere('scan_value', $newScan);
                })
                ->first(['id', 'product_id', 'barcode']);

            if ($collision) {
                $collisions++;
                $this->warn("  collision: row id={$row->id} product_id={$row->product_id} '{$row->barcode}' → '{$newBarcode}' already on row id={$collision->id} product_id={$collision->product_id}; skipping");
                continue;
            }

            $this->line("  row id={$row->id} product_id={$row->product_id}: '{$row->barcode}' → '{$newBarcode}'");

            if (! $dryRun) {
                DB::table('pos_product_barcode')
                    ->where('id', $row->id)
                    ->update([
                        'barcode'    => $newBarcode !== '' ? $newBarcode : $row->barcode,
                        'scan_value' => $newScan !== '' ? $newScan : $row->scan_value,
                        'updated_at' => now(),
                    ]);
            }

            $changed++;
        }

        $this->info(sprintf(
            "Done. total=%d changed=%d empty=%d collisions=%d%s",
            $total, $changed, $skippedEmpty, $collisions, $dryRun ? ' (DRY RUN)' : ''
        ));

        return self::SUCCESS;
    }
}
