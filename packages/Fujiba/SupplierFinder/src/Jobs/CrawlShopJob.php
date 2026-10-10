<?php

namespace Fujiba\SupplierFinder\Jobs;

use Fujiba\SupplierFinder\Models\SourceShop;
use Fujiba\SupplierFinder\Services\CrawlService;
use Fujiba\SupplierFinder\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** One job per shop; a slow/blocked shop never holds up the others. Re-match runs separately. */
class CrawlShopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public int $shopId) {}

    public function handle(): void
    {
        TenantContext::enter();
        app(CrawlService::class)->crawl(SourceShop::findOrFail($this->shopId));
    }
}
