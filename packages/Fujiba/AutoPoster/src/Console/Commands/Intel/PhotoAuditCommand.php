<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Fujiba\AutoPoster\Services\Intel\PhotoAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * M — audit product photography and write the owner-facing reshoot list.
 *
 * Shadow-safe: measures and records only. Never touches a product, an image,
 * or the posting queue.
 */
class PhotoAuditCommand extends Command
{
    protected $signature = 'fujiba:intel:photo-audit
        {--limit=100 : How many products to audit this run}
        {--channel=default}
        {--report : Also write docs/intelligence/reshoot-list.md and .csv}';

    protected $description = 'Measure product photo quality and flag products needing a reshoot';

    public function handle(PhotoAuditService $service): int
    {
        if (config('fujiba_intel.mode') === 'off') {
            $this->warn('fujiba_intel.mode=off — nothing to do.');

            return self::SUCCESS;
        }

        // In-stock first: a bad photo on something we can actually sell costs
        // real money, a bad photo on a sold-out line costs nothing today.
        $ids = DB::table('product_flat as f')
            ->join('product_images as i', 'i.product_id', '=', 'f.product_id')
            ->leftJoin(DB::raw('(SELECT product_id, SUM(qty) q FROM product_inventories GROUP BY product_id) s'),
                's.product_id', '=', 'f.product_id')
            ->where('f.channel', $this->option('channel'))
            ->where('f.locale', 'ja')
            ->where('f.price', '>', 0)
            ->orderByRaw('(COALESCE(s.q,0) > 0) DESC, f.product_id')
            ->distinct()
            ->limit((int) $this->option('limit'))
            ->pluck('f.product_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $this->info('Auditing '.count($ids).' products…');
        $r = $service->auditProducts($ids, (string) $this->option('channel'));

        $this->line(sprintf('  analysed=%d  cache_hits=%d  reshoot_flagged=%d',
            $r['analysed'], $r['cache_hits'], $r['reshoot']));

        if ($this->option('report')) {
            $this->writeReport($service);
        }

        return self::SUCCESS;
    }

    protected function writeReport(PhotoAuditService $service): void
    {
        $rows = $service->reshootList(500);
        $dir = base_path('docs/intelligence');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $md = ["# Reshoot list\n",
            'Generated '.now()->toDateTimeString()." — measured locally (cv2), no AI.\n",
            "Priority HIGH = in stock, so a poor photo is costing sales today.\n",
            "| Priority | SKU | Stock | Sold 12mo | Score | Problem |",
            '|---|---|---|---|---|---|'];

        $csv = ["priority,sku,product_id,stock,sold_12mo,score,reason,recommended_setup"];

        foreach ($rows as $r) {
            $md[] = sprintf('| %s | %s | %d | %d | %.2f | %s |',
                $r['priority'], $r['sku'] ?? '—', $r['stock'], $r['sold_12mo'],
                $r['score'], $r['reason'] ?? '');

            $csv[] = sprintf('%s,"%s",%d,%d,%d,%.4f,"%s","%s"',
                $r['priority'], $r['sku'] ?? '', $r['product_id'], $r['stock'],
                $r['sold_12mo'], $r['score'],
                str_replace('"', "'", (string) $r['reason']),
                str_replace('"', "'", (string) $r['setup']));
        }

        if (! $rows) {
            $md[] = '| — | — | — | — | — | no reshoot needed |';
        }

        file_put_contents($dir.'/reshoot-list.md', implode("\n", $md)."\n");
        file_put_contents($dir.'/reshoot-list.csv', implode("\n", $csv)."\n");

        $this->info('  wrote docs/intelligence/reshoot-list.{md,csv} ('.count($rows).' rows)');
    }
}
