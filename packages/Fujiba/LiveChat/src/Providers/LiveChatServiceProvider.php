<?php

namespace Fujiba\LiveChat\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class LiveChatServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Routes/admin.php');
        $this->loadRoutesFrom(__DIR__ . '/../../Routes/shop.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'fujiba_livechat');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'fujiba_livechat');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/livechat.php', 'fujiba_livechat');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/admin-menu.php', 'menu.admin');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Fujiba\LiveChat\Console\Commands\SyncChatpionSubscribersCommand::class,
                \Fujiba\LiveChat\Console\Commands\DispatchAutoFlowCommand::class,
                \Fujiba\LiveChat\Console\Commands\DispatchAbandonedCartCommand::class,
                \Fujiba\LiveChat\Console\Commands\SyncFlowsCommand::class,
            ]);

            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('fujiba:dispatch-auto-flow', ['--all'])
                    ->everyFifteenMinutes()
                    ->withoutOverlapping(20)
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/auto-flow-dispatch.log'));

                // Abandoned-cart Phase 1 — disabled-by-default per channel via
                // livechat_settings.abandoned_cart_enabled. The schedule fires
                // every 15 min but the command no-ops if no channel is enabled.
                $schedule->command('fujiba:dispatch-abandoned-cart', ['--all'])
                    ->everyFifteenMinutes()
                    ->withoutOverlapping(20)
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/abandoned-cart-dispatch.log'));

                // Daily refresh of the local chatpion_import flow-definition
                // tables (messenger_bot + messenger_bot_postback) so the live
                // chat flow picker's offline fallback isn't stale. Flow defs
                // only — does NOT sync products/orders, so it can't re-trigger
                // the phantom-reservation incident. Runs early morning JST.
                $schedule->command('fujiba:sync-flows')
                    ->dailyAt('04:30')
                    ->withoutOverlapping(30)
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/sync-flows.log'));
            });
        }
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }
}
