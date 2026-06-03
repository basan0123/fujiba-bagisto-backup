<?php

namespace Fujiba\OrdersAdmin\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Webkul\Sales\Models\Order;

/**
 * Confirms (or un-confirms) payment on an order — stamps payment_confirmed_at
 * and an optional payment_note. Shared by the admin REST controller
 * (OrderPaymentConfirmController) and the POS GraphQL mutation.
 *
 * Mirrors onto paid_at for the dashboard "Payments" mode WITHOUT clobbering a
 * ChatPion-sourced value: on confirm we only stamp paid_at when it's empty; on
 * un-confirm we clear it only if it matches the confirmation we're reverting
 * (i.e. we set it, not the sync). See [[OrderLabelPrinter]] for the COD path
 * that confirms payment as a side-effect of the label flip.
 */
class OrderPaymentConfirmer
{
    /**
     * @return array{payment_confirmed_at: ?string, payment_note: ?string}
     */
    public function confirm(Order $order, bool $confirmed, ?string $note = null): array
    {
        $now = $confirmed ? Carbon::now() : null;

        $payload = [
            'payment_confirmed_at' => $now,
            'payment_note'         => $note,
        ];

        if (Schema::hasColumn('orders', 'paid_at')) {
            if ($confirmed) {
                if (! $order->paid_at) {
                    $payload['paid_at'] = $now;
                }
            } elseif ($order->paid_at && $order->payment_confirmed_at
                && (string) $order->paid_at === (string) $order->payment_confirmed_at
            ) {
                $payload['paid_at'] = null;
            }
        }

        $order->update($payload);

        $fresh = $order->fresh();

        return [
            'payment_confirmed_at' => $fresh->payment_confirmed_at
                ? Carbon::parse($fresh->payment_confirmed_at)->toIso8601String()
                : null,
            'payment_note' => $fresh->payment_note,
        ];
    }
}
