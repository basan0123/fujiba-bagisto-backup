<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Fujiba\AutoPoster\Services\Intel\CsvSheet;
use Fujiba\AutoPoster\Services\Intel\OwnerSheetValues;
use Fujiba\AutoPoster\Services\Intel\PublicCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Read the owner's filled width/length sheet into fi_product_staging_attributes
 * (attribute width_cm / length_m, source owner_fill_in, status proposed,
 * confidence 1.0). Never touches Bagisto product tables — a human approves
 * the staging rows before anything is published.
 *
 * Validation: width 80–150 cm, length 0.5–5 m; anything else rejects the
 * whole row with a reason. Rows with both cells empty are skipped silently.
 */
class WidthLengthImportCommand extends Command
{
    protected $signature = 'fujiba:intel:width-length-import {csv : the filled fill-in sheet}
        {--tenant=1bd1181f-fcd5-4238-a12d-a207d6ea4006}
        {--dry-run : validate and show what would be written, write nothing}';

    protected $description = 'Import owner-measured width_cm / length_m into fi_product_staging_attributes (staging only)';

    public function handle(): int
    {
        if (function_exists('tenancy') && ! tenancy()->initialized) {
            tenancy()->initialize($this->option('tenant'));
        }
        $path = (string) $this->argument('csv');
        $dry = (bool) $this->option('dry-run');

        try {
            $sheet = CsvSheet::read($path);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $missing = array_diff(['product_id', 'width_cm', 'length_m'], $sheet['header']);
        if ($missing) {
            $this->error('missing column(s): '.implode(', ', $missing));

            return self::FAILURE;
        }

        $ids = array_filter(array_map(fn ($r) => ctype_digit($r['product_id'] ?? '') ? (int) $r['product_id'] : null, $sheet['rows']));
        $skus = DB::table('products')->whereIn('id', $ids ?: [0])->pluck('sku', 'id');

        $seen = [];
        $accepted = [];
        $rejected = [];
        $empty = 0;
        foreach ($sheet['rows'] as $r) {
            $line = $r['_line'];
            $w = $r['width_cm'] ?? '';
            $l = $r['length_m'] ?? '';
            if (trim($w) === '' && trim($l) === '') {
                $empty++;

                continue;
            }
            $pidRaw = $r['product_id'] ?? '';
            if (! ctype_digit($pidRaw)) {
                $rejected[] = [$line, $pidRaw, 'product_id missing or not a number'];

                continue;
            }
            $pid = (int) $pidRaw;
            if (! isset($skus[$pid])) {
                $rejected[] = [$line, $pid, 'unknown product_id'];

                continue;
            }
            if (isset($seen[$pid])) {
                $rejected[] = [$line, $pid, "duplicate product_id (first filled on line {$seen[$pid]})"];

                continue;
            }
            $sheetSku = trim($r['sku'] ?? '');
            if ($sheetSku !== '' && ! str_contains($sheetSku, PublicCode::MASK) && strcasecmp($sheetSku, (string) $skus[$pid]) !== 0) {
                $rejected[] = [$line, $pid, 'sku mismatch: sheet '.PublicCode::mask($sheetSku).' vs catalog '.PublicCode::mask($skus[$pid]).' — wrong row?'];

                continue;
            }

            [$wv, $we] = OwnerSheetValues::width($w);
            [$lv, $le] = OwnerSheetValues::length($l);
            if ($we || $le) {
                $rejected[] = [$line, $pid, implode('; ', array_filter([$we, $le]))];

                continue;
            }
            $seen[$pid] = $line;
            $accepted[] = ['line' => $line, 'product_id' => $pid, 'width_cm' => $wv, 'length_m' => $lv, 'notes' => trim($r['notes'] ?? '')];
        }

        $this->info(sprintf('%s: %d data rows | %d empty (skipped) | %d accepted | %d rejected',
            basename($path), count($sheet['rows']), $empty, count($accepted), count($rejected)));
        if ($accepted) {
            $this->table(['line', 'product_id', 'width_cm', 'length_m', 'notes'], array_map(fn ($a) => [
                $a['line'], $a['product_id'], $a['width_cm'] === null ? '-' : OwnerSheetValues::fmt($a['width_cm']),
                $a['length_m'] === null ? '-' : OwnerSheetValues::fmt($a['length_m']), mb_strimwidth($a['notes'], 0, 40, '…'),
            ], $accepted));
        }
        if ($rejected) {
            $this->warn('rejected rows (fix the sheet and re-run):');
            $this->table(['line', 'product_id', 'reason'], $rejected);
        }

        if ($dry) {
            $this->warn(sprintf('dry-run: would write %d staging value(s); nothing written',
                array_sum(array_map(fn ($a) => ($a['width_cm'] !== null) + ($a['length_m'] !== null), $accepted))));

            return self::SUCCESS;
        }

        $now = now();
        $written = 0;
        DB::transaction(function () use ($accepted, $path, $now, &$written) {
            foreach ($accepted as $a) {
                foreach (['width_cm' => $a['width_cm'], 'length_m' => $a['length_m']] as $attr => $val) {
                    if ($val === null) {
                        continue;
                    }
                    $key = ['product_id' => $a['product_id'], 'attribute' => $attr, 'source' => 'owner_fill_in'];
                    $data = [
                        'value'         => OwnerSheetValues::fmt($val),
                        'confidence'    => 1.0,
                        'status'        => 'proposed',
                        'evidence'      => mb_substr('owner fill-in sheet '.basename($path)." line {$a['line']}"
                            .($a['notes'] !== '' ? '; notes: '.PublicCode::mask($a['notes']) : ''), 0, 1000),
                        'internal_only' => 0,
                        'updated_at'    => $now,
                    ];
                    if (DB::table('fi_product_staging_attributes')->where($key)->exists()) {
                        DB::table('fi_product_staging_attributes')->where($key)->update($data);
                    } else {
                        DB::table('fi_product_staging_attributes')->insert($key + $data + ['created_at' => $now]);
                    }
                    $written++;
                }
            }
        });
        $this->info("written: {$written} staging value(s) (fi_product_staging_attributes, source owner_fill_in)");

        return self::SUCCESS;
    }
}
