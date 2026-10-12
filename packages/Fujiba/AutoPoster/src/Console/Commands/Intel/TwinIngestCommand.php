<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Fujiba\AutoPoster\Services\Intel\TwinReadinessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * WS-L: pick up new owner shoots dropped into /root/incoming/twin-shoots/<SKU or public code>/,
 * re-run the fabric twin audit for those products (with every photo they have) and re-tier them.
 * A file is new when its sha256 has never been logged as a 'shoot' input in fi_twin_attempts.
 * Staging only — never writes Bagisto product tables; uploads are left where they are.
 */
class TwinIngestCommand extends Command
{
    protected $signature = 'fujiba:intel:twin-ingest
        {--tenant=1bd1181f-fcd5-4238-a12d-a207d6ea4006}
        {--root=/root/incoming/twin-shoots : upload root (one sub-folder per SKU)}
        {--drive-report= : optional owner Drive photo_report.csv}
        {--force : re-audit folders even without new files}
        {--dry-run : list what would be processed}';

    protected $description = 'Process new fabric-twin shoot uploads per SKU folder and re-tier (staging only)';

    public function handle(): int
    {
        if (function_exists('tenancy') && ! tenancy()->initialized) {
            tenancy()->initialize($this->option('tenant'));
        }
        $root = rtrim((string) $this->option('root'), '/');
        if (! is_dir($root)) {
            $this->info("no upload root {$root} — nothing to do");

            return self::SUCCESS;
        }
        $svc = new TwinReadinessService($this->option('drive-report') ?: null);
        $todo = [];
        foreach (scandir($root) ?: [] as $name) {
            $dir = "{$root}/{$name}";
            if ($name === '' || $name[0] === '.' || ! is_dir($dir)) {
                continue;
            }
            $files = [];
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $f) {
                if ($f->isFile() && in_array(strtolower($f->getExtension()), TwinReadinessService::IMAGE_EXT, true) && $f->getSize() > 0) {
                    $files[] = $f->getPathname();
                }
            }
            if (! $files) {
                continue;
            }
            $pid = $svc->resolveFolder($name);
            if (! $pid) {
                $this->warn("{$name}: folder does not match one SKU / public code — skipped");

                continue;
            }
            $shas = [];
            foreach ($files as $f) {
                $shas[$f] = hash_file('sha256', $f);
            }
            $seen = DB::table('fi_twin_attempts')->where('product_id', $pid)->where('input_kind', 'shoot')
                ->whereIn('input_sha256', array_values($shas))->distinct()->pluck('input_sha256')->flip()->all();
            $new = array_filter($shas, fn ($h) => ! isset($seen[$h]));
            $this->line(sprintf('%s -> product %d: %d file(s), %d new', $name, $pid, count($files), count($new)));
            if ($new || $this->option('force')) {
                $todo[$pid] = array_map(fn ($f) => ['key' => 'shoot_'.substr($shas[$f], 0, 12), 'path' => $f, 'kind' => 'shoot',
                    'sha256' => $shas[$f]], array_keys($shas));
            }
        }
        if (! $todo || $this->option('dry-run')) {
            $this->info($todo ? 'dry-run: would re-audit '.count($todo).' product(s)' : 'no new uploads');

            return self::SUCCESS;
        }
        $runId = 'ingest-'.now()->format('Ymd-His');
        try {
            $ctx = $svc->context(array_keys($todo), $todo);
            $res = $svc->run($ctx, $runId);
            [$rows, $atts] = $svc->persist($ctx, $res, $runId);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        foreach (DB::table('fi_product_twin_readiness')->whereIn('product_id', array_keys($todo))->get(['product_id', 'sku', 'tier', 'reason']) as $r) {
            $this->line("  {$r->sku}: tier {$r->tier} — {$r->reason}");
        }
        $this->info("re-tiered {$rows} product(s), {$atts} attempt rows");

        return self::SUCCESS;
    }
}
