<?php

namespace Fujiba\OrdersAdmin\Providers;

use Fujiba\OrdersAdmin\Console\Commands\RebuildOrderedInventories;
use Fujiba\OrdersAdmin\Console\Commands\StandardizeProductImagesCommand;
use Illuminate\Support\ServiceProvider;

class OrdersAdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin-api.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'orders-admin');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'orders-admin');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RebuildOrderedInventories::class,
                StandardizeProductImagesCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__ . '/../Config/carriers.php', 'carriers');
        $this->mergeConfigFrom(__DIR__ . '/../Config/menu.php', 'menu.admin');
    }
}
