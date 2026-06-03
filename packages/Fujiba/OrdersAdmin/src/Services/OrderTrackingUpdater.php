<?php

namespace Fujiba\OrdersAdmin\Services;

use Fujiba\OrdersAdmin\Exceptions\OrderEditException;
use Webkul\Sales\Models\Order;

/**
 * Sets the tracking number on an order's latest shipment. Shared by the admin
 * REST controller (OrderTrackingController) and the POS GraphQL mutation so the
 * "no shipment yet" rule can't drift between the two surfaces.
 *
 * A tracking number has nowhere to live until a shipment exists — the admin
 * creates one via the per-row "shipped" flow or the label-printed action
 * ([[OrderLabelPrinter]]). We throw OrderEditException (mapped to an inline
 * {success:false} on the POS) rather than silently creating a shipment, so the
 * staffer makes the fulfillment decision explicitly.
 */
class OrderTrackingUpdater
{
    /** @return mixed the freshened shipment row */
    public function update(Order $order, string $trackingNumber)
    {
        $shipment = $order->shipments()->latest('id')->first();

        if (! $shipment) {
            throw new OrderEditException(
                trans('OrdersAdmin::app.tracking.no-shipment'),
            );
        }

        $shipment->update(['track_number' => $trackingNumber]);

        return $shipment->fresh();
    }
}
