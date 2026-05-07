<?php

namespace Webkul\Waste\Providers;

use Illuminate\Support\ServiceProvider;

class WasteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/admin-menu.php',
            'menu.admin'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin-routes.php');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'waste');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'waste');

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
