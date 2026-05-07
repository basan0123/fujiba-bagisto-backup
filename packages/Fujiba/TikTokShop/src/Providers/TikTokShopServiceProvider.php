<?php

namespace Fujiba\TikTokShop\Providers;

use Fujiba\TikTokShop\Listeners\OnProductChanged;
use Fujiba\TikTokShop\Services\TikTokAuthService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class TikTokShopServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/webhook.php');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'tiktok');

        $this->mergeConfigFrom(__DIR__.'/../../config/menu.php', 'menu.admin');

        // Stock sync listener. See SPRINT_2_DISCOVERY.md item G — Bagisto has
        // no dedicated stock event in this build, so we dispatch on every
        // product-update event but gate by tiktok_product_map existence.
        Event::listen('catalog.product.update.after', [OnProductChanged::class, 'handle']);
        Event::listen('catalog.product.create.after', [OnProductChanged::class, 'handle']);

        // Tenant migrations are NOT loaded here — see config/tenancy.php
        // migration_parameters['--path'] (master prompt §0.2).
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/fujiba-tiktok.php', 'fujiba-tiktok');

        $this->app->singleton(TikTokAuthService::class);
    }
}
