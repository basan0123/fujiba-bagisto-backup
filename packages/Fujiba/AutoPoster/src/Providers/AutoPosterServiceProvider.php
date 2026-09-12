<?php

namespace Fujiba\AutoPoster\Providers;

use Fujiba\AutoPoster\Console\Commands\DispatchPostsCommand;
use Fujiba\AutoPoster\Console\Commands\PlanPostsCommand;
use Fujiba\AutoPoster\Services\CaptionGenerator;
use Fujiba\AutoPoster\Services\ClaudeCaptionClient;
use Fujiba\AutoPoster\Services\PostPublisher;
use Fujiba\AutoPoster\Services\ProductSelectionService;
use Fujiba\AutoPoster\Services\QueueBuilder;
use Fujiba\AutoPoster\Services\SchedulePlanner;
use Fujiba\AutoPoster\Services\UrlResolver;
use Illuminate\Support\ServiceProvider;

class AutoPosterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // NOTE: migrations are NOT loaded here on purpose. They are tenant-only
        // and registered in config/tenancy.php migration_parameters --path;
        // loadMigrationsFrom() would pull them onto the CENTRAL database.

        $this->loadRoutesFrom(__DIR__ . '/../../routes/admin.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'autoposter');

        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'autoposter');

        $this->mergeConfigFrom(__DIR__ . '/../../config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(__DIR__ . '/../../config/acl.php', 'acl');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PlanPostsCommand::class,
                DispatchPostsCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/autoposter.php', 'autoposter');

        $this->app->singleton(UrlResolver::class);
        $this->app->singleton(SchedulePlanner::class);

        $this->app->singleton(ProductSelectionService::class, fn ($app) => new ProductSelectionService(
            $app->make(UrlResolver::class)
        ));

        $this->app->singleton(QueueBuilder::class, fn ($app) => new QueueBuilder(
            $app->make(ProductSelectionService::class),
            $app->make(UrlResolver::class)
        ));

        $this->app->singleton(PostPublisher::class, fn ($app) => new PostPublisher(
            $app->make(UrlResolver::class)
        ));

        $this->app->singleton(ClaudeCaptionClient::class);

        $this->app->singleton(CaptionGenerator::class, fn ($app) => new CaptionGenerator(
            $app->make(ClaudeCaptionClient::class)
        ));
    }
}
