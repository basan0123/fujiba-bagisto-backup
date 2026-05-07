<?php

namespace Webkul\Shipping\Providers;

use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        include __DIR__ . '/../Http/helpers.php';
        $this->registerConfig();
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/carriers.php', 'carriers');
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/system.php', 'core');
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/admin-menu.php', 'menu.admin');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin-routes.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api-routes.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'shipping');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'shipping');

        // Register as TENANT migrations so they run on every tenant DB
        if ($this->isTenantContext()) {
            $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        }
    }

    private function isTenantContext(): bool
    {
        // Works with stancl/tenancy
        if (function_exists('tenant') && app()->bound('currentTenant') && tenant()) {
            return true;
        }
        return true; // load always — tenancy handles routing
    }
}
