<?php

namespace Fujiba\InvoicesAdmin\Listeners;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * When a COD invoice is saved (auto-generated as paid by Bagisto's
 * GenerateInvoice listener), stamp the parent order's payment_confirmed_at.
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

        $invoice->order->update([
            'payment_confirmed_at' => Carbon::now(),
        ]);
    }
}
