<?php

namespace Fujiba\InvoicesAdmin\Listeners;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;

/**
 * COD auto-confirmation. Two entry points:
 *   - handle($invoice) on sales.invoice.save.after: stamps order.payment_confirmed_at
 *     when Bagisto's GenerateInvoice creates the COD invoice as paid.
 *   - handleShipment($shipment) on sales.shipment.save.after: covers the
 *     "Create Shipment" admin path (Order View > Ship). When the parcel goes
 *     out, the courier collects cash, so we flip the COD invoice to paid +
 *     stamp payment_confirmed_at atomically. The OrderLabelPrintedController
 *     handles the "Mark Shipped" dropdown path the same way directly.
 *
 * Bank-transfer (moneytransfer) and cash invoices stay manual.
 */
class AutoConfirmCodPayment
{
    public function handle($invoice): void
    {
        if (! $invoice || ! $invoice->order) {
            return;
        }

        $method = optional($invoice->order->payment)->method;

        if ($method !== 'cashondelivery') {
            return;
        }

        if (! Schema::hasColumn('orders', 'payment_confirmed_at')) {
            return;
        }

        if ($invoice->order->payment_confirmed_at) {
            return;
        }

        $now = Carbon::now();

        $payload = ['payment_confirmed_at' => $now];

        // Mirror onto paid_at so dashboard Payments mode (which filters by
        // paid_at, written historically only by SyncChatpion) sees in-app
        // confirmations too. Only stamp when not already set so we don't
        // overwrite a real ChatPion-sourced paid_at.
        if (Schema::hasColumn('orders', 'paid_at') && ! $invoice->order->paid_at) {
            $payload['paid_at'] = $now;
        }

        $invoice->order->update($payload);
    }

    /**
     * Fires after any shipment is created/updated. If the parent order is COD,
     * flip any unpaid invoices to paid + stamp payment_confirmed_at in one
     * transaction. Mirrors OrderLabelPrintedController::autoConfirmCodPayment so
     * both shipping paths converge on the same state.
     */
    public function handleShipment($shipment): void
    {
        if (! $shipment || ! $shipment->order) {
            return;
        }

        $order = $shipment->order;

        if (optional($order->payment)->method !== 'cashondelivery') {
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
}
