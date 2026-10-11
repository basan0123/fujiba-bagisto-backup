<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Fujiba\AutoPoster\Services\Intel\CsvSheet;
use Fujiba\AutoPoster\Services\Intel\OwnerSheetValues;
use Fujiba\AutoPoster\Services\Intel\RestockCandidateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sold-out products that competitors are visibly selling → fi_restock_candidates
 * + storage/app/intel/restock/restock_candidates.csv (internal: competitor handles).
 * Reads Bagisto stock/sales; writes ONLY the fi_ staging table and the CSV.
 */
class RestockCandidatesCommand extends Command
{
    protected $signature = 'fujiba:intel:restock-candidates
        {--tenant=1bd1181f-fcd5-4238-a12d-a207d6ea4006}
        {--csv= : output CSV (default storage/app/intel/restock/restock_candidates.csv)}
        {--top=20 : rows to print}
        {--dry-run : compute and print, write nothing}';

    protected $description = 'Rank sold-out products with eye-verified competitor evidence (staging table + CSV)';

    public const CSV_HEADER = [
        'rank', 'product_id', 'sku', 'public_name', 'enabled', 'stock_qty', 'reserved_qty', 'available_qty', 'sold_out_reason',
        'competitors', 'distinct_competitors', 'times_seen', 'best_confidence', 'their_price_min', 'their_price_max',
        'their_price_note', 'our_price', 'last_sold_at', 'sold_qty_12m', 'sold_qty_all_time', 'sold_qty_12m_completed',
        'last_stock_change_at_proxy', 'last_restock_logged_at_proxy', 'evidence_score',
    ];

    public function handle(RestockCandidateService $svc): int
    {
        if (function_exists('tenancy') && ! tenancy()->initialized) {
            tenancy()->initialize($this->option('tenant'));
        }

        $res = $svc->compute();
        $rows = $res['rows'];
        $this->info(sprintf('evidence products: %d | sold out (candidates): %d | in stock, excluded: %d',
            $res['evidence_products'], count($rows), count($res['in_stock_excluded'])));
        foreach ($res['in_stock_excluded'] as $x) {
            $this->line("  in stock, not a candidate: #{$x['product_id']} {$x['name']} (available {$x['available_qty']})");
        }

        $this->table(
            ['#', 'sku', 'name', 'competitors', 'seen', 'their ¥', 'our ¥', 'avail', 'last sold', '12m/all', 'score'],
            array_map(fn ($r) => [
                $r['rank'], $r['sku'], $r['public_name'], $r['competitors'], $r['times_seen'],
                $r['their_price_min'] === null ? '-' : OwnerSheetValues::fmt($r['their_price_min']).($r['their_price_max'] != $r['their_price_min'] ? '–'.OwnerSheetValues::fmt($r['their_price_max']) : ''),
                OwnerSheetValues::fmt((float) $r['our_price']), $r['available_qty'].($r['enabled'] ? '' : ' (off)'),
                $r['last_sold_at'] ? substr($r['last_sold_at'], 0, 10) : '-', $r['sold_qty_12m'].'/'.$r['sold_qty_all_time'],
                OwnerSheetValues::fmt($r['evidence_score']),
            ], array_slice($rows, 0, (int) $this->option('top')))
        );

        if ($this->option('dry-run')) {
            $this->warn('dry-run: nothing written');

            return self::SUCCESS;
        }

        $now = now();
        DB::transaction(function () use ($rows, $now) {
            foreach ($rows as $r) {
                $data = $r;
                $data['evidence'] = json_encode($r['evidence'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $data['status'] = 'proposed';
                $data['internal_only'] = 1;
                $data['computed_at'] = $now;
                $data['updated_at'] = $now;
                if (DB::table('fi_restock_candidates')->where('product_id', $r['product_id'])->exists()) {
                    DB::table('fi_restock_candidates')->where('product_id', $r['product_id'])->update($data);
                } else {
                    DB::table('fi_restock_candidates')->insert($data + ['created_at' => $now]);
                }
            }
            // previous candidates that are no longer sold out / evidenced: keep the row, mark it
            DB::table('fi_restock_candidates')->whereNotIn('product_id', array_column($rows, 'product_id') ?: [0])
                ->update(['status' => 'not_candidate', 'rank' => null, 'computed_at' => $now, 'updated_at' => $now]);
        });

        $csv = $this->option('csv') ?: storage_path('app/intel/restock/restock_candidates.csv');
        $n = CsvSheet::write($csv, self::CSV_HEADER, array_map(fn ($r) => [
            'enabled'                      => $r['enabled'] ? 'Y' : 'N',
            'last_stock_change_at_proxy'   => $r['last_stock_change_at'],
            'last_restock_logged_at_proxy' => $r['last_restock_at'],
        ] + $r, $rows));
        clearstatcache(true, $csv);

        $this->info(sprintf('fi_restock_candidates: %d proposed rows | CSV %s (%d rows, %d bytes)',
            DB::table('fi_restock_candidates')->where('status', 'proposed')->count(), $csv, $n, filesize($csv)));

        return self::SUCCESS;
    }
}
