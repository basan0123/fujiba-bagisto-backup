<?php

namespace Fujiba\Notification\Services;

use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Refund;
use Webkul\Sales\Models\Shipment;

/**
 * The single place that decides WHICH customer notification a Bagisto order
 * event means, and what makes it unique.
 *
 * Two different kinds of event reach us and must not be confused:
 *
 *   DOCUMENT events — an invoice / shipment / refund row was actually created.
 *   One message per document (a second shipment is a second parcel).
 *
 *   STATUS events — sales.order.update-status.after, fired on every status
 *   save. Core ALSO fires it as a side effect of creating an invoice,
 *   shipment or refund (InvoiceRepository / ShipmentRepository /
 *   RefundRepository → OrderRepository::updateOrderStatus), so one admin
 *   click can produce both kinds. Status messages are therefore once per
 *   order per status, and 'processing' yields to the document messages.
 *
 * | Bagisto event                     | Type                   | Unique per |
 * |-----------------------------------|------------------------|------------|
 * | checkout.order.save.after         | order.placed           | order      |
 * | sales.invoice.save.after          | order.invoiced         | invoice    |
 * | sales.shipment.save.after         | order.shipped          | shipment   |
 * | sales.refund.save.after           | order.refunded         | refund     |
 * | sales.order.cancel.after          | order.cancelled        | order      |
 * | update-status → pending_payment   | order.pending_payment  | order      |
 * | update-status → processing        | order.processing       | order      |
 * | update-status → completed         | order.completed        | order      |
 * | update-status → canceled          | order.cancelled        | order      |
 * | update-status → pending/closed/fraud | (nothing)           |            |
 *
 * There is no "shipped" order status in Bagisto, so a shipped message only
 * ever comes from a real shipment. "Payment failed" has no order to attach
 * to: Komoju fails before the cart becomes an order.
 */
class OrderNotificationMatrix
{
    public const TRIGGER_DOCUMENT = 'document';

    public const TRIGGER_STATUS = 'status';

    public const STATUS_EVENT = 'sales.order.update-status.after';

    /** Bagisto event → [notification type, trigger kind]. */
    public const EVENTS = [
        'checkout.order.save.after' => ['order.placed', self::TRIGGER_DOCUMENT],
        'sales.invoice.save.after'  => ['order.invoiced', self::TRIGGER_DOCUMENT],
        'sales.shipment.save.after' => ['order.shipped', self::TRIGGER_DOCUMENT],
        'sales.refund.save.after'   => ['order.refunded', self::TRIGGER_DOCUMENT],
        'sales.order.cancel.after'  => ['order.cancelled', self::TRIGGER_DOCUMENT],
        self::STATUS_EVENT          => [null, self::TRIGGER_STATUS],
    ];

    /** Order status → notification type, for status-change events. */
    public const STATUS_TYPES = [
        Order::STATUS_PENDING_PAYMENT => 'order.pending_payment',
        Order::STATUS_PROCESSING      => 'order.processing',
        Order::STATUS_COMPLETED       => 'order.completed',
        Order::STATUS_CANCELED        => 'order.cancelled',
    ];

    /** Types introduced with the matrix — gated by the rollout flag. */
    public const NEW_STATUS_TYPES = ['order.pending_payment', 'order.processing'];

    /**
     * @return array{type: string, trigger: string, scope: string}|null
     */
    public function decide(string $bagistoEvent, mixed $payload, Order $order): ?array
    {
        if (! isset(self::EVENTS[$bagistoEvent])) {
            return null;
        }

        [$type, $trigger] = self::EVENTS[$bagistoEvent];

        if ($trigger === self::TRIGGER_STATUS) {
            $type = self::STATUS_TYPES[$order->status] ?? null;

            if (! $type) {
                return null;
            }
        }

        return [
            'type'    => $type,
            'trigger' => $trigger,
            'scope'   => $this->scope($payload),
        ];
    }

    /**
     * Persistent identity of one notification to one recipient. The recipient
     * is hashed so long emails can't overflow the 191-char unique index.
     */
    public function key(Order $order, array $decision, string $channel, string $recipient): string
    {
        return sprintf(
            'order:%d:%s:%s:%s:%s',
            $order->id,
            substr($decision['type'], strlen('order.')),
            $decision['scope'],
            $channel,
            sha1($recipient),
        );
    }

    /**
     * Document events are unique per document; everything else once per order.
     */
    protected function scope(mixed $payload): string
    {
        return match (true) {
            $payload instanceof Invoice  => 'invoice:'.$payload->id,
            $payload instanceof Shipment => 'shipment:'.$payload->id,
            $payload instanceof Refund   => 'refund:'.$payload->id,
            default                      => 'once',
        };
    }
}
