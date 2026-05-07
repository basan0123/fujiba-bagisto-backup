<?php

namespace Fujiba\Reorder\Providers;

use Illuminate\Support\ServiceProvider;

class ReorderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'reorder');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'reorder');

        $this->mergeConfigFrom(__DIR__.'/../Config/system.php', 'core');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
    }

    public function register(): void
    {
        //
    }
}
