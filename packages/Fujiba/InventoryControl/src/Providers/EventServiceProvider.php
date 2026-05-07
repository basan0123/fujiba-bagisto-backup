<?php

namespace Fujiba\InventoryControl\Providers;

use Fujiba\InventoryControl\Listeners\DeductFifoBatchesOnOrder;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'checkout.order.save.after' => [
            DeductFifoBatchesOnOrder::class,
        ],
    ];
}
