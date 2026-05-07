<?php

namespace Modules\FujibaLiveChat\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FujibaLiveChatServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/fujiba_livechat.php',
            'fujiba_livechat'
        );
    }

    /**
     * Bootstrap the module.
     */
    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');

        // Views  (blade namespace: fujiba_livechat)
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'fujiba_livechat');

        // Migrations (run per-tenant via artisan migrate)
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Publish config
        $this->publishes([
            __DIR__.'/../Config/fujiba_livechat.php' => config_path('fujiba_livechat.php'),
        ], 'fujiba-livechat-config');

        // Order status event → push Chatpion notification
        Event::listen(
            'sales.order.update-status.after',
            \Modules\FujibaLiveChat\Listeners\NotifyOrderUpdate::class
        );
    }
}
