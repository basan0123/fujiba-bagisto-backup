<?php

namespace Fujiba\LiveChat\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Customer order-status messages are sent by Fujiba\Notification
     * (OrderEventListener + OrderNotificationMatrix), with duplicate
     * protection and delivery logging. The old OrderStatusNotifier here
     * listened on 'bagisto.shop.order.update-status.after' /
     * 'bagisto.shop.checkout.order.after', which Bagisto never emits, so it
     * never sent anything — and wiring it to the real event would have
     * produced a second, unlogged copy of every message. Removed 2026-10-09.
     */
    protected $listen = [];
}
