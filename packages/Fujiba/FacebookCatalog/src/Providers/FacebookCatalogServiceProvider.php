<?php

namespace Fujiba\FacebookCatalog\Providers;

use Fujiba\FacebookCatalog\Listeners\OnProductChanged;
use Fujiba\FacebookCatalog\Services\BatchSyncService;
use Fujiba\FacebookCatalog\Services\ProductPayloadBuilder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FacebookCatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'facebook_catalog');

        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'facebook_catalog');

        $this->mergeConfigFrom(__DIR__.'/../../config/menu.php', 'menu.admin');

        Event::listen('catalog.product.update.after', [OnProductChanged::class, 'handle']);
        Event::listen('catalog.product.create.after', [OnProductChanged::class, 'handle']);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/fujiba-facebook-catalog.php', 'fujiba-facebook-catalog');

        $this->app->singleton(ProductPayloadBuilder::class);
        $this->app->bind(BatchSyncService::class, function ($app) {
            return new BatchSyncService($app->make(ProductPayloadBuilder::class));
        });
    }
}
