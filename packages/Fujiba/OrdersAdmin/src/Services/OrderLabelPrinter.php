<?php

namespace Fujiba\OrdersAdmin\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\ShipmentRepository;

/**
 * Marks an order's shipping label as printed — the "fulfill this web order"
 * action. Shared by the admin REST controller (OrderLabelPrintedController) and
 * the POS GraphQL mutation so the shipment + COD-confirm side-effects can't
 * drift between the two surfaces.
 *
 * Flipping fujiba_label_printed does three things, idempotently:
 *   1. Creates a shipment for the still-unshipped portion so qty_shipped,
 *      product_inventories and product_ordered_inventories get decremented
 *      (otherwise stock stays reserved forever even though we consider it
 *      fulfilled).
 *   2. Sets fujiba_label_printed = 1.
 *   3. For COD orders, confirms payment (courier collects cash on delivery, so
 *      the label flip IS the payment moment): stamps payment_confirmed_at and
 *      flips the invoice to paid in one transaction. See
 *      feedback_cod_listener_transaction_wrap.md.
 */
class OrderLabelPrinter
{
    public function __construct(
        protected ShipmentRepository $shipmentRepository,
    ) {}

    /**
     * @return array{label_printed: bool, already: bool}
     */
    public function flip(Order $order): array
    {
        // Idempotent: skip the shipment-creation work if we've already done it.
        if ($order->fujiba_label_printed) {
            return ['label_printed' => true, 'already' => true];
        }

        $this->createShipmentForUnshipped($order);

        $order->update(['fujiba_label_printed' => 1]);

        $this->autoConfirmCodPayment($order);

        return ['label_printed' => true, 'already' => false];
    }

    /**
     * For COD orders, flip the pending invoice to paid and stamp the order's
     * payment_confirmed_at — in one transaction so the two can't drift.
     * Idempotent: re-runs are no-ops once invoice.state is already paid.
     */
    protected function autoConfirmCodPayment(Order $order): void
    {
        $method = optional($order->payment)->method;
        if ($method !== 'cashondelivery') {
            return;
        }

        DB::transaction(function () use ($order) {
            $now = Carbon::now();

            if (Schema::hasColumn('orders', 'payment_confirmed_at')
                && ! $order->payment_confirmed_at
            ) {
                $payload = ['payment_confirmed_at' => $now];

                if (Schema::hasColumn('orders', 'paid_at') && ! $order->paid_at) {
                    $payload['paid_at'] = $now;
                }

                $order->update($payload);
            }

            Invoice::where('order_id', $order->id)
                ->where('state', '!=', Invoice::STATUS_PAID)
                ->update([
                    'state'      => Invoice::STATUS_PAID,
                    'updated_at' => $now,
                ]);
        });
    }

    /**
     * Build and persist a shipment covering every order_item's remaining
     * qty_to_ship, using the highest-priority active inventory_source. No-ops
     * when there's nothing left to ship — the flag still flips so the UI
     * reflects what staff expect.
     */
    protected function createShipmentForUnshipped(Order $order): void
    {
        $sourceId = $this->resolveInventorySourceId();
        if (! $sourceId) {
            Log::warning('OrderLabelPrinter: no active inventory_source; flipping flag without shipment', [
                'order_id' => $order->id,
            ]);
            return;
        }

        $items = [];
        foreach ($order->items as $item) {
            $remaining = (int) ($item->qty_to_ship ?? 0);
            if ($remaining <= 0) {
                continue;
            }
            $items[$item->id] = [$sourceId => $remaining];
        }

        if (empty($items)) {
            return; // already fully shipped or fully canceled
        }

        try {
            $this->shipmentRepository->create([
                'order_id' => $order->id,
                'shipment' => [
                    'carrier_title' => 'Sagawa',
                    'track_number'  => null,
                    'source'        => $sourceId,
                    'items'         => $items,
                ],
            ]);
        } catch (\Throwable $e) {
            // Don't block the flip on a shipment-create failure (e.g. mixed
            // already-shipped items). The flag is still a useful UI marker.
            Log::error('OrderLabelPrinter: shipment create failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    protected function resolveInventorySourceId(): ?int
    {
        return DB::table('inventory_sources')
            ->where('status', 1)
            ->orderBy('priority')
            ->orderBy('id')
            ->value('id');
    }
}
