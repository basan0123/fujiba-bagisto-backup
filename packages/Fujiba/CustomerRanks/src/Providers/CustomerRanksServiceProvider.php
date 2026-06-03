<?php

namespace Fujiba\CustomerRanks\Providers;

use Fujiba\CustomerRanks\Console\BackfillKimonoRankCommand;
use Fujiba\CustomerRanks\Console\CreateKimonoGroupCommand;
use Fujiba\CustomerRanks\Listeners\OrderStatusListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CustomerRanksServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/ranks.php', 'ranks');

        // Auto-promote when an order reaches a qualifying status.
        Event::listen(
            'sales.order.update-status.after',
            [OrderStatusListener::class, 'handle'],
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateKimonoGroupCommand::class,
                BackfillKimonoRankCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        //
    }
}
