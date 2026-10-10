<?php

namespace Fujiba\SupplierFinder\Providers;

use Fujiba\SupplierFinder\Console\CrawlCommand;
use Fujiba\SupplierFinder\Console\MatchCommand;
use Fujiba\SupplierFinder\Console\ReportCommand;
use Fujiba\SupplierFinder\Console\SeedCommand;
use Fujiba\SupplierFinder\Services\CodeDecoder;
use Fujiba\SupplierFinder\Services\CrawlService;
use Fujiba\SupplierFinder\Services\Grouper;
use Fujiba\SupplierFinder\Services\Matcher;
use Fujiba\SupplierFinder\Services\PoliteHttp;
use Illuminate\Support\ServiceProvider;

/**
 * Tenant tables are migrated with:
 *   php artisan tenants:migrate --tenants=<kimono tenant> \
 *     --path=packages/Fujiba/SupplierFinder/database/migrations/tenant --realpath
 */
class SupplierFinderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/supplier-finder.php', 'supplier-finder');

        $cfg = fn () => config('supplier-finder');
        $this->app->singleton(PoliteHttp::class, fn () => new PoliteHttp($cfg()['http']));
        $this->app->singleton(CodeDecoder::class);
        $this->app->bind(CrawlService::class, fn ($a) => new CrawlService($a->make(PoliteHttp::class), $cfg()));
        $this->app->bind(Grouper::class, fn ($a) => new Grouper($a->make(CodeDecoder::class), $cfg()));
        $this->app->bind(Matcher::class, fn ($a) => new Matcher($a->make(\Fujiba\SupplierFinder\Services\ReferenceCatalog::class), $cfg()));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SeedCommand::class, CrawlCommand::class, MatchCommand::class, ReportCommand::class]);
        }
    }
}
