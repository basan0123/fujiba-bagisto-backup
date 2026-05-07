<?php

namespace Fujiba\SagawaDelivery\Providers;

use Fujiba\SagawaDelivery\Listeners\SaveDeliveryToOrder;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings.
     * checkout.order.save.after fires synchronously inside the same HTTP request,
     * so request('delivery_date') / request('delivery_time') are still available.
     */
    protected $listen = [
        'checkout.order.save.after' => [
            SaveDeliveryToOrder::class,
        ],
    ];
}
