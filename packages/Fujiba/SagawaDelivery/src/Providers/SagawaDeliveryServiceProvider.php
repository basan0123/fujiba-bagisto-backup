<?php

namespace Fujiba\SagawaDelivery\Providers;

use Fujiba\SagawaDelivery\Services\CredentialResolver;
use Fujiba\SagawaDelivery\Services\SagawaShipmentBuilder;
use Illuminate\Support\ServiceProvider;

class SagawaDeliveryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/sagawa.php', 'sagawa');
        $this->mergeConfigFrom(__DIR__ . '/../Config/system.php', 'core');
        $this->mergeConfigFrom(__DIR__ . '/../Config/carriers.php', 'carriers');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'sagawa');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/shop.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'sagawa');
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);

        $this->mergeConfigFrom(__DIR__ . '/../Config/admin-menu.php', 'menu.admin');

        $this->app->singleton(CredentialResolver::class, fn () => new CredentialResolver());

        $this->app->singleton(SagawaShipmentBuilder::class, function ($app) {
            return new SagawaShipmentBuilder(
                sender: (array) config('sagawa.sender', []),
                options: (array) config('sagawa.defaults', []),
            );
        });
    }
}
