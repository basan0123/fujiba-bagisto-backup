<?php

namespace App\Console\Commands;

use App\Services\Fujiba\ProductNameParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ParseFujibaNamesCommand extends Command
{
    protected $signature = 'fujiba:parse-names
        {--connection=chatpion_stage : Connection holding ecommerce_product}
        {--table=ecommerce_product : Table to read/write}
        {--batch=500 : Rows per batch}
        {--force : Re-parse rows whose parser_version already matches current}
        {--report-unmapped-letters : Print a tally of color letters seen}
        {--apply : Persist writes (otherwise dry-run)}';

    protected $description = 'Decode chatpion_stage.ecommerce_product.original_product_name into fujiba_decoded_json.';

    public function handle(ProductNameParser $parser): int
    {
        $conn   = (string) $this->option('connection');
        $table  = (string) $this->option('table');
        $batch  = max(1, (int) $this->option('batch'));
        $force  = (bool) $this->option('force');
        $apply  = (bool) $this->option('apply');
        $report = (bool) $this->option('report-unmapped-letters');

        if (! Schema::connection($conn)->hasTable($table)) {
            $this->error("Table {$conn}.{$table} not found.");
            return self::FAILURE;
        }

        if (! Schema::connection($conn)->hasColumn($table, 'fujiba_decoded_json')) {
            $this->error("Column {$table}.fujiba_decoded_json missing — run the migration first.");
            return self::FAILURE;
        }

        $version = ProductNameParser::VERSION;
        $stats   = ['scanned' => 0, 'parsed' => 0, 'skipped' => 0, 'unparseable' => 0, 'shapes' => [], 'letters' => []];

        try {
            DB::connection($conn)->table($table)
                ->select(['id', 'original_product_name', 'fujiba_decoded_json'])
                ->orderBy('id')
                ->chunkById($batch, function ($rows) use ($conn, $table, $parser, $force, $apply, $version, &$stats) {
                    foreach ($rows as $row) {
                        $stats['scanned']++;

                        if (! $force && $row->fujiba_decoded_json) {
                            $existing = json_decode((string) $row->fujiba_decoded_json, true);
                            if (is_array($existing) && ($existing['parser_version'] ?? null) === $version) {
                                $stats['skipped']++;
                                continue;
                            }
                        }

                        $decoded = $parser->parse($row->original_product_name);
                        $stats['parsed']++;
                        $shape = $decoded['source_shape'];
                        $stats['shapes'][$shape] = ($stats['shapes'][$shape] ?? 0) + 1;

                        if ($shape === ProductNameParser::SHAPE_UNPARSEABLE) {
                            $stats['unparseable']++;
                        } elseif (isset($decoded['color_letter'])) {
                            $l = $decoded['color_letter'];
                            $stats['letters'][$l] = ($stats['letters'][$l] ?? 0) + 1;
                        }

                        if ($apply) {
                            DB::connection($conn)->table($table)
                                ->where('id', $row->id)
                                ->update(['fujiba_decoded_json' => json_encode($decoded, JSON_UNESCAPED_UNICODE)]);
                        }
                    }
                }, 'id');
        } catch (Throwable $e) {
            $this->error('FAILED: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->line(sprintf(
            'scanned=%d parsed=%d skipped=%d unparseable=%d %s',
            $stats['scanned'], $stats['parsed'], $stats['skipped'], $stats['unparseable'],
            $apply ? '(applied)' : '(dry-run)'
        ));

        if (! empty($stats['shapes'])) {
            $this->line('shapes: '.json_encode($stats['shapes']));
        }

        if ($report && ! empty($stats['letters'])) {
            ksort($stats['letters']);
            $this->line('letters: '.json_encode($stats['letters']));
        }

        return self::SUCCESS;
    }
}
