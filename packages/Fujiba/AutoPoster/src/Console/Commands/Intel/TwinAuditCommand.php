<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Fujiba\AutoPoster\Services\Intel\TwinReadinessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * WS-L: audit every product's photos for an exact fabric twin and try to build a tileable
 * repeat from real pixels (deterministic geometry only). Writes fi_product_twin_readiness
 * (tier A/B/C + reason) and fi_twin_attempts (every attempt). Staging only: Bagisto product
 * tables are never written. Each python chunk takes the shared heavy-job lock.
 */
class TwinAuditCommand extends Command
{
    protected $signature = 'fujiba:intel:twin-audit
        {--tenant=1bd1181f-fcd5-4238-a12d-a207d6ea4006}
        {--product=* : only these product ids}
        {--limit=0 : only the first N products}
        {--chunk=40 : products per python run}
        {--drive-report= : owner Drive photo_report.csv (matched rows used as extra photos)}
        {--shoot-list : write storage/app/intel/twin/shoot_list.csv afterwards}
        {--shoot-list-only : only (re)write the shoot list from stored rows}
        {--dry-run : build the photo inventory and print counts, run nothing}';

    protected $description = 'Fabric twin readiness: audit photos, build gated repeat tiles, tier A/B/C (staging only)';

    public function handle(): int
    {
        if (function_exists('tenancy') && ! tenancy()->initialized) {
            tenancy()->initialize($this->option('tenant'));
        }
        $svc = new TwinReadinessService($this->option('drive-report') ?: null);
        $csv = $svc->baseDir().'/shoot_list.csv';

        if ($this->option('shoot-list-only')) {
            $n = $svc->writeShootList($csv);
            $this->info("shoot list: {$n} rows -> {$csv}");

            return self::SUCCESS;
        }

        $ids = array_map('intval', (array) $this->option('product')) ?: $svc->catalogProductIds();
        if ((int) $this->option('limit') > 0) {
            $ids = array_slice($ids, 0, (int) $this->option('limit'));
        }
        $chunk = max(1, (int) $this->option('chunk'));
        $runBase = 'audit-'.now()->format('Ymd-His');
        $tot = ['rows' => 0, 'attempts' => 0, 'errors' => 0];

        foreach (array_chunk($ids, $chunk) as $i => $part) {
            $ctx = $svc->context($part);
            $imgs = array_sum(array_map(fn ($c) => count($c['images']), $ctx));
            $kinds = [];
            foreach ($ctx as $c) {
                foreach ($c['images'] as $im) {
                    $kinds[$im['kind']] = ($kinds[$im['kind']] ?? 0) + 1;
                }
            }
            $this->line(sprintf('chunk %d: %d products, %d photos %s', $i + 1, count($ctx), $imgs, json_encode($kinds)));
            if ($this->option('dry-run')) {
                continue;
            }
            $runId = $runBase.'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            try {
                $res = $svc->run($ctx, $runId);
            } catch (Throwable $e) {
                $this->error($e->getMessage());
                $tot['errors']++;

                continue;
            }
            [$r, $a] = $svc->persist($ctx, $res, $runId);
            $tot['rows'] += $r;
            $tot['attempts'] += $a;
        }
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $tiers = DB::table('fi_product_twin_readiness')->whereIn('product_id', $ids)
            ->selectRaw('tier, SUM(raised_by_step2) raised, COUNT(*) c')->groupBy('tier')->get();
        $this->table(['tier', 'products', 'raised by tile step'], $tiers->map(fn ($t) => [$t->tier, $t->c, $t->raised])->all());
        $this->info("readiness rows {$tot['rows']}, attempts {$tot['attempts']}, failed chunks {$tot['errors']}");
        if ($this->option('shoot-list')) {
            $n = $svc->writeShootList($csv);
            $this->info("shoot list: {$n} rows -> {$csv}");
        }

        return $tot['errors'] ? self::FAILURE : self::SUCCESS;
    }
}
