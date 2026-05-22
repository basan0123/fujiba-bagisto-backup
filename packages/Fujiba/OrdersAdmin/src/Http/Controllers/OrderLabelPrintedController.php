<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class OrderLabelPrintedController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected ShipmentRepository $shipmentRepository,
    ) {}

    public function flip(int $id): JsonResponse
    {
        if (! bouncer()->hasPermission('sales.orders.edit')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $order = $this->orderRepository->findOrFail($id);

        // Idempotent: skip the shipment-creation work if we've already done it.
        if ($order->fujiba_label_printed) {
            return response()->json([
                'message' => 'Label already marked as printed.',
                'data'    => ['label_printed' => true],
            ]);
        }

        // Create a shipment for the still-unshipped portion so qty_shipped,
        // product_inventories, and product_ordered_inventories get decremented.
        // Without this the per-row "shipped" dropdown leaves stock reserved
        // forever even though the admin considers the order fulfilled.
        $this->createShipmentForUnshipped($order);

        $order->update(['fujiba_label_printed' => 1]);

        // COD = courier collects cash on delivery, so flipping label_printed
        // is also the moment payment is confirmed. Stamp payment_confirmed_at
        // and flip the invoice to paid atomically — see
        // feedback_cod_listener_transaction_wrap.md.
        $this->autoConfirmCodPayment($order);

        return response()->json([
            'message' => 'Label marked as printed.',
            'data'    => ['label_printed' => true],
        ]);
    }

    /**
     * For COD orders, flip the pending invoice to paid and stamp the order's
     * payment_confirmed_at — in one transaction so the two sides can't drift.
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

                // Mirror onto paid_at for dashboard Payments mode; don't
                // overwrite an existing ChatPion-sourced value.
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
     * Build and persist a shipment that covers every order_item's remaining
     * qty_to_ship. Uses the order's first active inventory_source (priority
     * order). Silently no-ops when there's nothing left to ship — the flag
     * still flips so the UI reflects what the admin expects.
     */
    protected function createShipmentForUnshipped(Order $order): void
    {
        $sourceId = $this->resolveInventorySourceId();
        if (! $sourceId) {
            Log::warning('OrderLabelPrinted: no active inventory_source; flipping flag without shipment', [
                'order_id' => $order->id,
            ]);
            return;
        }

        $items = [];
        foreach ($order->items as $item) {
            // qty_to_ship lives on the model as a derived attribute. Composite
            // items (configurable/bundle) carry their unshipped qty on the
            // parent row, which is what ShipmentRepository expects keyed by
            // order_item.id.
            $remaining = (int) ($item->qty_to_ship ?? 0);
            if ($remaining <= 0) {
                continue;
            }
            $items[$item->id] = [$sourceId => $remaining];
        }

        if (empty($items)) {
            return; // nothing to do — already fully shipped or fully canceled
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
            // Don't block the label-printed flip on a shipment-create failure
            // (e.g. an order with mixed already-shipped items). The flag is
            // still useful as a UI marker; surface the error in logs for ops.
            Log::error('OrderLabelPrinted: shipment create failed', [
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
