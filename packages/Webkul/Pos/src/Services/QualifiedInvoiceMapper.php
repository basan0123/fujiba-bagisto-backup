<?php

namespace Webkul\Pos\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Core\Repositories\CoreConfigRepository;

/**
 * Single source of truth for mapping a Webkul invoice + its underlying
 * order/items into the qualified-invoice (適格請求書) shape exposed via
 * GraphQL and rendered into the PDF Blade template.
 *
 * Mapping intentionally lives here (not in the resolver/PDF controller)
 * so the email body, GraphQL response, and PDF render from the same
 * struct — no chance of "the email totals differ from the PDF totals".
 */
class QualifiedInvoiceMapper
{
    public function __construct(protected CoreConfigRepository $configRepository) {}

    /**
     * Build the qualified-invoice payload for the given invoice id.
     * Returns null when the invoice doesn't exist.
     */
    public function map(int $invoiceId): ?array
    {
        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        if (! $invoice) {
            return null;
        }

        $order = DB::table('orders')->where('id', $invoice->order_id)->first();
        if (! $order) {
            return null;
        }

        $items = DB::table('invoice_items')
            ->where('invoice_id', $invoiceId)
            ->get();

        $itemRows = [];
        $taxBreakdown = []; // ratePercent => ['baseAmount' => x, 'taxAmount' => y]

        foreach ($items as $item) {
            $orderItem = DB::table('order_items')->where('id', $item->order_item_id)->first();
            $taxPercent = $orderItem ? (float) $orderItem->tax_percent : 0.0;
            $taxKey = (string) (int) round($taxPercent);

            $itemRows[] = [
                'id'            => (int) $item->id,
                'sku'           => $item->sku,
                'name'          => $item->name,
                'qty'           => (int) $item->qty,
                'price'         => (float) $item->price,
                'taxPercent'    => $taxPercent,
                'taxAmount'     => (float) $item->tax_amount,
                'total'         => (float) $item->total,
                'totalInclTax'  => (float) $item->total_incl_tax,
            ];

            if (! isset($taxBreakdown[$taxKey])) {
                $taxBreakdown[$taxKey] = [
                    'ratePercent' => $taxPercent,
                    'baseAmount'  => 0.0,
                    'taxAmount'   => 0.0,
                ];
            }
            $taxBreakdown[$taxKey]['baseAmount'] += (float) $item->total;
            $taxBreakdown[$taxKey]['taxAmount']  += (float) $item->tax_amount;
        }

        // Sort breakdown rows descending by rate so 10% appears above 8%.
        $breakdownRows = array_values($taxBreakdown);
        usort($breakdownRows, fn ($a, $b) => $b['ratePercent'] <=> $a['ratePercent']);

        $billingAddress = DB::table('addresses')
            ->where('order_id', $order->id)
            ->where('address_type', 'order_billing')
            ->first();

        $issuer = $this->resolveIssuer((int) ($order->channel_id ?? 0), (int) ($invoice->order_id));

        // Shipping status: most-recent shipment.status for this order, or
        // 'pending' when nothing has been shipped yet. Used by the native
        // InvoicePreview to render the shipping-status control with the
        // correct initial value (so the segmented control isn't always
        // "pending" by default).
        $shipmentStatus = DB::table('shipments')
            ->where('order_id', $invoice->order_id)
            ->orderByDesc('id')
            ->value('status') ?? 'pending';

        // Normalise to the small enum the native control uses.
        $shippingStatus = match ($shipmentStatus) {
            'delivered' => 'delivered',
            'shipped'   => 'shipped',
            default     => 'pending',
        };

        // Payment status derived from the invoice state ('paid' / 'pending' /
        // 'canceled' → 'refunded'). The native control uses this to
        // pre-select the segment when the preview loads.
        $paymentStatus = match ($invoice->state) {
            'paid'     => 'paid',
            'canceled' => 'refunded',
            default    => 'pending',
        };

        return [
            'id'                         => (int) $invoice->id,
            'incrementId'                => $invoice->increment_id,
            'orderId'                    => (int) $invoice->order_id,
            'orderIncrementId'           => $order->increment_id ?? null,
            'state'                      => $invoice->state,
            'paymentStatus'              => $paymentStatus,
            'shippingStatus'             => $shippingStatus,
            'emailSent'                  => (bool) $invoice->email_sent,
            'createdAt'                  => (string) $invoice->created_at,
            'grandTotal'                 => (float) $invoice->grand_total,
            'subTotal'                   => (float) $invoice->sub_total,
            'taxAmount'                  => (float) $invoice->tax_amount,
            'shippingAmount'             => (float) $invoice->shipping_amount,
            'discountAmount'             => (float) $invoice->discount_amount,
            'currencyCode'               => $invoice->order_currency_code,

            'issuerName'                 => $issuer['name'],
            'issuerAddress'              => $issuer['address'],
            'issuerPhone'                => $issuer['phone'],
            'issuerRegistrationNumber'   => $issuer['registration_number'],

            'recipientName'              => trim(($order->customer_first_name ?? '').' '.($order->customer_last_name ?? '')) ?: ($order->customer_email ?? ''),
            'recipientEmail'             => $order->customer_email,
            'recipientPhone'             => $billingAddress->phone ?? null,
            'recipientAddress'           => $this->formatAddress($billingAddress),

            'items'                      => $itemRows,
            'taxBreakdown'               => $breakdownRows,
        ];
    }

    /**
     * Look up issuer block. Per-channel core_config_data, then global
     * fallback. Configured by admin in Settings → 適格請求書.
     */
    protected function resolveIssuer(int $channelId, int $orderId): array
    {
        $outlet = DB::table('pos_order')
            ->leftJoin('pos_outlets', 'pos_outlets.id', '=', 'pos_order.outlet_id')
            ->where('pos_order.order_id', $orderId)
            ->select('pos_outlets.name', 'pos_outlets.address', 'pos_outlets.phone', 'pos_outlets.email')
            ->first();

        $get = function (string $key) use ($channelId): ?string {
            if ($channelId > 0) {
                $row = DB::table('core_config')
                    ->where('code', $key)
                    ->where('channel_code', $this->channelCode($channelId))
                    ->first();
                if ($row && $row->value !== null && $row->value !== '') {
                    return $row->value;
                }
            }
            $row = DB::table('core_config')->where('code', $key)->first();
            return $row->value ?? null;
        };

        return [
            'name'                => $get('fujiba.invoice.issuer.name') ?? $outlet->name ?? null,
            'address'             => $get('fujiba.invoice.issuer.address') ?? $outlet->address ?? null,
            'phone'               => $get('fujiba.invoice.issuer.phone') ?? $outlet->phone ?? null,
            'registration_number' => $get('fujiba.invoice.issuer.registration_number'),
        ];
    }

    protected function channelCode(int $channelId): string
    {
        $row = DB::table('channels')->where('id', $channelId)->first();
        return $row->code ?? 'default';
    }

    protected function formatAddress($address): ?string
    {
        if (! $address) {
            return null;
        }
        $parts = array_filter([
            $address->postcode ? '〒'.$address->postcode : null,
            $address->state ?? null,
            $address->city ?? null,
            $address->address ?? null,
        ]);
        return implode(' ', $parts) ?: null;
    }
}
