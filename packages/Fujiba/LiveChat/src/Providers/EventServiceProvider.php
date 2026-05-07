<?php

namespace Fujiba\LiveChat\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Fujiba\LiveChat\Listeners\OrderStatusNotifier;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'bagisto.shop.checkout.order.after'      => [OrderStatusNotifier::class],
        'bagisto.shop.order.update-status.after'  => [OrderStatusNotifier::class],
    ];
}
