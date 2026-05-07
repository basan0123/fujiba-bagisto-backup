<?php

namespace Webkul\Komoju\Providers;

use Illuminate\Support\ServiceProvider;

class KomojuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/system.php', 'core');
        $this->mergeConfigFrom(__DIR__.'/../Config/paymentmethods.php', 'payment_methods');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'komoju');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'komoju');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }
}
