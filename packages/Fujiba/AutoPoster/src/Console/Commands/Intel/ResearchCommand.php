<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Fujiba\AutoPoster\Services\Intel\ProductResearchService;
use Illuminate\Console\Command;

/**
 * Gather traceable evidence (supplier + reference pages) for product SKUs.
 * Output to the console masks supplier excerpts — supplier codes are secret.
 */
class ResearchCommand extends Command
{
    protected $signature = 'fujiba:intel:research {skus* : one or more SKUs}
        {--tenant=1bd1181f-fcd5-4238-a12d-a207d6ea4006}
        {--dry-run : fetch + report, write nothing to the DB}';

    protected $description = 'Research supplier/cultural evidence for SKUs into fi_research_sources + fi_facts';

    public function handle(): int
    {
        if (function_exists('tenancy') && ! tenancy()->initialized) {
            tenancy()->initialize($this->option('tenant'));
        }
        $svc = ProductResearchService::make();
        $rows = [];
        foreach ($this->argument('skus') as $sku) {
            $r = $svc->research($sku, (bool) $this->option('dry-run'));
            $rows[] = [$r['sku'], $r['supplier'], $r['facts'], $r['match'], implode('; ', $r['notes'])];
            foreach ($r['fact_rows'] ?? [] as $f) {
                $this->line(sprintf('  %s [%s %.2f] %s :: %s', $r['sku'], $f[3], $f[4], $f[0],
                    $f[7] ? '(internal, masked)' : mb_substr($f[2], 0, 80)));
            }
        }
        $this->table(['sku', 'supplier', 'facts', 'best match', 'notes'], $rows);

        return self::SUCCESS;
    }
}
