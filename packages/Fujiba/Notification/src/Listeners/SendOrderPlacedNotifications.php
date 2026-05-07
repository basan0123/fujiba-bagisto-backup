<?php

namespace Fujiba\Notification\Listeners;

use Webkul\Sales\Models\Order;

/**
 * @deprecated Replaced by OrderEventListener (handles all 5 lifecycle events).
 *             Tombstone — kept until the refactor is confirmed working in
 *             production, then delete this file. The provider no longer
 *             registers it; this class will not fire on any event.
 *             Forwards to OrderEventListener for any direct/legacy callers.
 *
 * TODO: delete after first successful prod deploy
 */
class SendOrderPlacedNotifications
{
    public const EVENT = 'order.placed';

    public function handle(Order $order): void
    {
        app(OrderEventListener::class)->handle('checkout.order.save.after', $order);
    }
}
