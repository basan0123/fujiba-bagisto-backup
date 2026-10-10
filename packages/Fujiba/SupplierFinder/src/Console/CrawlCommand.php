<?php

namespace Fujiba\SupplierFinder\Console;

use Fujiba\SupplierFinder\Jobs\CrawlShopJob;
use Fujiba\SupplierFinder\Models\SourceShop;
use Fujiba\SupplierFinder\Services\CrawlService;
use Fujiba\SupplierFinder\Support\TenantContext;
use Illuminate\Console\Command;

class CrawlCommand extends Command
{
    protected $signature = 'supplier-finder:crawl {shops?* : shop codes (default: all enabled)} {--queue : dispatch one job per shop instead of running inline} {--limit= : stop after N products (test runs; never marks items gone)}';

    protected $description = 'Crawl source shops (polite: robots.txt, 3s/domain)';

    public function handle(CrawlService $crawler): int
    {
        TenantContext::enter();
        $q = SourceShop::query();
        $this->argument('shops') ? $q->whereIn('code', $this->argument('shops')) : $q->where('enabled', true);

        foreach ($q->get() as $shop) {
            if ($this->option('queue')) {
                CrawlShopJob::dispatch($shop->id);
                $this->line("queued {$shop->code}");

                continue;
            }
            $this->info("== {$shop->code}");
            $run = $crawler->crawl($shop, fn ($n, $t) => $this->output->isVerbose() ? $this->line("  {$n} {$t}") : null,
                $this->option('limit') ? (int) $this->option('limit') : null);
            $this->line("  {$run->status}: ".json_encode($run->counts).' requests='.$run->requests.($run->error ? " error={$run->error}" : ''));
            $d = $run->diff;
            $this->line(sprintf('  diff: new %d, price %d, out-of-stock %d, gone %d', count($d['new']), count($d['price']), count($d['out_of_stock']), count($d['gone'])));
        }

        return 0;
    }
}
