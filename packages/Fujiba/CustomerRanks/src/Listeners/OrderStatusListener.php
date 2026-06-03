<?php

namespace Fujiba\CustomerRanks\Listeners;

use Fujiba\CustomerRanks\Jobs\EvaluateCustomerRankJob;
use Illuminate\Support\Facades\Bus;

/**
 * Listens on sales.order.update-status.after. When an order belonging to a
 * registered customer reaches a qualifying status, queue a rank re-evaluation.
 *
 * We dispatch afterCommit so the status change is committed before the job's
 * count query runs (same reasoning as the Messenger notification race fix).
 */
class OrderStatusListener
{
    public function handle(mixed $order): void
    {
        $customerId = $order->customer_id ?? null;

        if (! $customerId) {
            return;
        }

        $statuses = config('ranks.kimono_plus3.qualifying_order_statuses', ['completed']);

        if (! in_array($order->status, $statuses, true)) {
            return;
        }

        Bus::dispatch(
            (new EvaluateCustomerRankJob((int) $customerId))->afterCommit()
        );
    }
}
