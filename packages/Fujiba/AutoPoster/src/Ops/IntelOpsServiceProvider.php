<?php

namespace Fujiba\AutoPoster\Ops;

use Fujiba\AutoPoster\Ops\Guard\LoadGuard;
use Fujiba\AutoPoster\Ops\Insights\ConfigMetaInsightsTokenProvider;
use Fujiba\AutoPoster\Ops\Insights\GraphClient;
use Fujiba\AutoPoster\Ops\Insights\HttpGraphClient;
use Fujiba\AutoPoster\Ops\Insights\MetaInsightsTokenProvider;
use Fujiba\AutoPoster\Ops\Insights\PublishedPostSource;
use Illuminate\Support\ServiceProvider;

/** Standalone so it can be registered without touching AutoPosterServiceProvider. */
class IntelOpsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/fujiba_intel.php', 'fujiba_intel');

        $this->app->bind(MetaInsightsTokenProvider::class, ConfigMetaInsightsTokenProvider::class);
        $this->app->bind(GraphClient::class, fn () => new HttpGraphClient((string) config('fujiba_intel.graph_version', 'v25.0')));
        $this->app->bind(PublishedPostSource::class, fn () => new PublishedPostSource((int) config('fujiba_intel.insights_lookback_days', 28)));
        $this->app->singleton(LoadGuard::class, fn ($app) => new LoadGuard(
            $app['cache']->store(config('fujiba_intel.cache_store')),
            (array) config('fujiba_intel.guard', []),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Console\IntelHealthCommand::class, Console\ImportInsightsCommand::class]);
        }
    }
}
