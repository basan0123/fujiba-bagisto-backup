<?php

namespace Fujiba\SagawaDelivery\Listeners;

use Fujiba\SagawaDelivery\Services\ShipmentService;
use Illuminate\Support\Facades\Log;

class SaveDeliveryToOrder
{
    /**
     * Handle checkout.order.save.after.
     * Persists delivery_date/delivery_time on the order AND creates the
     * internal sagawa_shipments row. Runs synchronously inside the checkout
     * request, so request() data is still available.
     *
     * @param  \Webkul\Sales\Models\Order  $order
     */
    public function handle($order): void
    {
        $date = request('delivery_date');
        $time = request('delivery_time');

        if ($date || $time) {
            try {
                $order->delivery_date = $date ?: null;
                $order->delivery_time = $time ?: null;
                $order->saveQuietly();
            } catch (\Throwable $e) {
                Log::warning('[SagawaDelivery] Could not save delivery fields: '.$e->getMessage());
            }
        }

        try {
            app(ShipmentService::class)->createFromOrder($order);
        } catch (\Throwable $e) {
            // Shipment creation must not block order placement. Log + continue.
            Log::error('[SagawaDelivery] Shipment create failed for order '.($order->id ?? '?').': '.$e->getMessage());
        }
    }
}
