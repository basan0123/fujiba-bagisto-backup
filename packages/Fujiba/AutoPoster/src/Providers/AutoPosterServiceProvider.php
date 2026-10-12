<?php

namespace Fujiba\AutoPoster\Providers;

use Fujiba\AutoPoster\Console\Commands\DispatchPostsCommand;
use Fujiba\AutoPoster\Console\Commands\PlanPostsCommand;
use Fujiba\AutoPoster\Services\CaptionGenerator;
use Fujiba\AutoPoster\Services\ClaudeCaptionClient;
use Fujiba\AutoPoster\Services\ChatpionPostClient;
use Fujiba\AutoPoster\Services\PostCoverRenderer;
use Fujiba\AutoPoster\Services\CollectionPostBuilder;
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
                \Fujiba\AutoPoster\Console\Commands\RebuildCollectionsCommand::class,
                DispatchPostsCommand::class,
                \Fujiba\AutoPoster\Console\Commands\PublishInstagramCommand::class,
                \Fujiba\AutoPoster\Console\Commands\KnowledgeCommand::class,
                \Fujiba\AutoPoster\Console\Commands\V2DryRunCommand::class,
                // Intelligence layer (shadow-only; cannot publish).
                \Fujiba\AutoPoster\Console\Commands\Intel\PhotoAuditCommand::class,
                \Fujiba\AutoPoster\Console\Commands\Intel\ExplainCommand::class,
                \Fujiba\AutoPoster\Console\Commands\Intel\CatalogCommand::class,
                \Fujiba\AutoPoster\Console\Commands\Intel\ResearchCommand::class,
                // WS-J staging-only intel (restock shortlist, catalog issues, owner sheets).
                \Fujiba\AutoPoster\Console\Commands\Intel\RestockCandidatesCommand::class,
                \Fujiba\AutoPoster\Console\Commands\Intel\CatalogIssuesCommand::class,
                \Fujiba\AutoPoster\Console\Commands\Intel\WidthLengthSheetCommand::class,
                \Fujiba\AutoPoster\Console\Commands\Intel\WidthLengthImportCommand::class,
                \Fujiba\AutoPoster\Console\Commands\Intel\CompetitorMetricsCommand::class,
                // WS-L fabric twin readiness (staging only).
                \Fujiba\AutoPoster\Console\Commands\Intel\TwinAuditCommand::class,
                \Fujiba\AutoPoster\Console\Commands\Intel\TwinIngestCommand::class,
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

        $this->app->singleton(ChatpionPostClient::class);
        $this->app->singleton(PostCoverRenderer::class, fn ($app) => new PostCoverRenderer(
            $app->make(UrlResolver::class)
        ));

        $this->app->singleton(PostPublisher::class, fn ($app) => new PostPublisher(
            $app->make(UrlResolver::class),
            $app->make(ChatpionPostClient::class),
            $app->make(CaptionGenerator::class),
            $app->make(PostCoverRenderer::class),
            $app->make(CollectionPostBuilder::class),
        ));

        $this->app->singleton(ClaudeCaptionClient::class);

        $this->app->singleton(CaptionGenerator::class, fn ($app) => new CaptionGenerator(
            $app->make(ClaudeCaptionClient::class)
        ));
    }
}
