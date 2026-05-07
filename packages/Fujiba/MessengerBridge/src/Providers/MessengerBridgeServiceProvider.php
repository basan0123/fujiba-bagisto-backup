<?php

namespace Fujiba\MessengerBridge\Providers;

use Fujiba\MessengerBridge\Console\Commands\PruneBridgeNoncesCommand;
use Fujiba\MessengerBridge\Console\Commands\SignBridgeTokenCommand;
use Fujiba\MessengerBridge\Services\TokenVerifier;
use Illuminate\Support\ServiceProvider;

class MessengerBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton-bind TokenVerifier with the env secret(s). PREVIOUS may
        // be empty (no rotation in progress) — TokenVerifier filters those.
        $this->app->singleton(TokenVerifier::class, function () {
            return new TokenVerifier([
                (string) env('MESSENGER_BRIDGE_SECRET', ''),
                (string) env('MESSENGER_BRIDGE_SECRET_PREVIOUS', ''),
            ]);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'messenger-bridge');

        // Dedicated log channel — written via Log::channel('messenger_bridge').
        // Configured here (instead of config/logging.php) so the package is
        // self-contained and removable.
        $this->app['config']->set('logging.channels.messenger_bridge', [
            'driver' => 'daily',
            'path'   => storage_path('logs/messenger-bridge.log'),
            'level'  => 'info',
            'days'   => 30,
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneBridgeNoncesCommand::class,
                SignBridgeTokenCommand::class,
            ]);

            // Daily nonce cleanup — keep the table small.
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                $schedule->command('messenger-bridge:prune-nonces')
                    ->dailyAt('03:00');
            });
        }
    }
}
