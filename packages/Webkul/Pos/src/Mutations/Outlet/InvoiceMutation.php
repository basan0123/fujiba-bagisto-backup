<?php

namespace Webkul\Pos\Mutations\Outlet;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Helpers\RoleGate;
use Webkul\Pos\Mail\QualifiedInvoiceMail;
use Webkul\Pos\Services\QualifiedInvoiceMapper;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class InvoiceMutation extends Controller
{
    public function __construct(
        protected QualifiedInvoiceMapper $mapper,
        protected InvoiceRepository $invoiceRepository,
        protected OrderRepository $orderRepository,
        protected ShipmentRepository $shipmentRepository,
    ) {}

    /**
     * Generate a brand-new invoice from an order. Cashier+ allowed —
     * issuing an invoice is a routine cash-up action; refund/void stay
     * gated separately.
     *
     * Strategy: invoice every order_item at its current qty_to_invoice.
     * Bagisto's InvoiceRepository::create() handles totals, tax breakdown,
     * status transitions, and the sales.invoice.save.after event chain.
     */
    public function generate($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $orderId = (int) ($args['orderId'] ?? 0);
        if (! $orderId) {
            throw new CustomException(trans('pos::app.outlet.invoice.order-not-found'));
        }

        if (! $this->cashierOwnsOrder($context, $orderId)) {
            throw new CustomException(trans('pos::app.outlet.invoice.order-not-found'));
        }

        $order = $this->orderRepository->find($orderId);
        if (! $order) {
            throw new CustomException(trans('pos::app.outlet.invoice.order-not-found'));
        }

        // Build line-item qty map: every order_item gets its remaining
        // qty_to_invoice invoiced now.
        $invoiceData = ['order_id' => $orderId, 'invoice' => ['items' => []]];
        foreach ($order->items as $item) {
            $remaining = max(0, (int) $item->qty_ordered - (int) $item->qty_invoiced);
            $invoiceData['invoice']['items'][$item->id] = $remaining;
        }

        // If everything is already invoiced, refuse rather than minting a ¥0 invoice
        // (project memory: feedback_zero_yen_invoice_guard).
        if (array_sum($invoiceData['invoice']['items']) === 0) {
            throw new CustomException(trans('pos::app.outlet.invoice.already-exists'));
        }

        // A draft-sourced invoice has not been paid yet, so create it in the
        // 'pending' state — otherwise InvoiceRepository defaults to 'paid' and
        // the payment status would lie until the first addPayment. Normal
        // (non-draft) cash sales keep the default 'paid'.
        $isDraft = ($order->status ?? null) === 'draft';

        try {
            $invoice = $isDraft
                ? $this->invoiceRepository->create($invoiceData, 'pending')
                : $this->invoiceRepository->create($invoiceData);
        } catch (\Throwable $e) {
            throw new CustomException($e->getMessage());
        }

        // Draft → created: a draft order is parked at status='draft' with no
        // invoice. Generating the invoice advances it to 'processing' (the
        // "created/issued" document state). Stock was already reserved at draft
        // creation (product_ordered_inventories), per the locked decision; the
        // real sellable decrement happens later at shipment. Invoices created
        // from a normal (non-draft) order leave orders.status untouched.
        if ($isDraft) {
            DB::table('orders')->where('id', $orderId)->update([
                'status'     => 'processing',
                'updated_at' => now(),
            ]);
        }

        return [
            'success'   => true,
            'message'   => trans('pos::app.outlet.invoice.generated'),
            'invoiceId' => (int) $invoice->id,
        ];
    }

    /**
     * Record a (part-)payment against an invoice. Builds a pos_invoice_payments
     * ledger row, updates invoices.amount_paid, and derives the payment status
     * (unpaid / partial / paid). On reaching paid, stamps orders.paid_at +
     * payment_confirmed_at (Dashboard "Payments" mode reads paid_at — memory
     * feedback_paid_at_dual_writer_drift) and flips the invoice state to paid.
     *
     * Cashier+ may take a payment.
     */
    public function addPayment($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $invoiceId = (int) ($args['invoiceId'] ?? 0);
        if (! $this->cashierOwnsInvoice($context, $invoiceId)) {
            throw new CustomException(trans('pos::app.outlet.invoice.order-not-found'));
        }

        $amount = round((float) ($args['amount'] ?? 0), 4);
        if ($amount <= 0) {
            throw new CustomException(trans('pos::app.outlet.invoice.invalid-amount'));
        }

        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        if (! $invoice) {
            throw new CustomException(trans('pos::app.outlet.invoice.order-not-found'));
        }

        $grandTotal = round((float) $invoice->grand_total, 4);
        $alreadyPaid = round((float) ($invoice->amount_paid ?? 0), 4);
        $outstanding = round($grandTotal - $alreadyPaid, 4);

        // Allow a tiny rounding tolerance, otherwise reject overpayment.
        if ($amount - $outstanding > 0.01) {
            throw new CustomException(trans('pos::app.outlet.invoice.payment-exceeds'));
        }

        $now = now();
        $method = in_array($args['method'] ?? '', ['pos_cash', 'pos_card', 'bank_transfer', 'other'], true)
            ? $args['method'] : 'pos_cash';

        $result = DB::transaction(function () use ($invoice, $invoiceId, $amount, $alreadyPaid, $grandTotal, $method, $args, $context, $now) {
            DB::table('pos_invoice_payments')->insert([
                'invoice_id'  => $invoiceId,
                'order_id'    => (int) $invoice->order_id,
                'outlet_id'   => (int) $context->user()->outlet_id,
                'amount'      => $amount,
                'method'      => $method,
                'reference'   => $args['reference'] ?? null,
                'note'        => $args['note'] ?? null,
                'received_by' => (int) $context->user()->id,
                'paid_at'     => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $newPaid = round($alreadyPaid + $amount, 4);
            $fullyPaid = $newPaid + 0.01 >= $grandTotal;

            DB::table('invoices')->where('id', $invoiceId)->update([
                'amount_paid' => $newPaid,
                'state'       => $fullyPaid ? 'paid' : 'pending',
                'updated_at'  => $now,
            ]);

            if ($fullyPaid) {
                // Dual-write paid_at + payment_confirmed_at so Dashboard
                // Payments mode and the in-app confirm path agree.
                DB::table('orders')->where('id', $invoice->order_id)->update([
                    'paid_at'              => $now,
                    'payment_confirmed_at' => $now,
                    'updated_at'           => $now,
                ]);
            }

            return ['newPaid' => $newPaid, 'fullyPaid' => $fullyPaid];
        });

        return [
            'success'       => true,
            'message'       => trans('pos::app.outlet.invoice.payment-recorded'),
            'invoiceId'     => $invoiceId,
            'amountPaid'    => $result['newPaid'],
            'paymentStatus' => $this->derivePaymentStatus($grandTotal, $result['newPaid']),
        ];
    }

    /**
     * Derive the coarse payment status from totals. unpaid / partial / paid.
     */
    protected function derivePaymentStatus(float $grandTotal, float $amountPaid): string
    {
        if ($amountPaid <= 0.0001) {
            return 'unpaid';
        }
        if ($amountPaid + 0.01 >= $grandTotal) {
            return 'paid';
        }
        return 'partial';
    }

    /**
     * Set the order's payment status. Three values: paid / pending / refunded.
     * Cashier+ for paid/pending (routine), manager+ for refunded (irreversible-ish).
     *
     * Writes orders.status + stamps paid_at when transitioning to paid (this is
     * what the Dashboard "Payments" mode reads — see memory
     * feedback_paid_at_dual_writer_drift).
     */
    public function setPaymentStatus($root, array $args, GraphQLContext $context): array
    {
        $status = $args['status'] ?? '';
        if (! in_array($status, ['paid', 'pending', 'refunded'], true)) {
            throw new CustomException("Unknown payment status: {$status}");
        }

        if ($status === 'refunded') {
            RoleGate::requireManager($context->user());
        } else {
            RoleGate::requireCashier($context->user());
        }

        $orderId = (int) ($args['orderId'] ?? 0);
        if (! $this->cashierOwnsOrder($context, $orderId)) {
            throw new CustomException(trans('pos::app.outlet.invoice.order-not-found'));
        }

        $now = now();
        $update = ['updated_at' => $now];
        if ($status === 'paid') {
            $update['status']     = 'processing';
            $update['paid_at']    = $now;
        } elseif ($status === 'pending') {
            $update['status']     = 'pending_payment';
            $update['paid_at']    = null;
        } else {
            $update['status']     = 'refunded';
        }

        DB::table('orders')->where('id', $orderId)->update($update);

        // Cascade to invoices: paid → state=paid; pending → state=pending;
        // refunded → state=canceled (Bagisto's invoice states).
        if ($status === 'paid') {
            DB::table('invoices')->where('order_id', $orderId)->update(['state' => 'paid', 'updated_at' => $now]);
        } elseif ($status === 'pending') {
            DB::table('invoices')->where('order_id', $orderId)->update(['state' => 'pending', 'updated_at' => $now]);
        } else {
            DB::table('invoices')->where('order_id', $orderId)->update(['state' => 'canceled', 'updated_at' => $now]);
        }

        return [
            'success' => true,
            'message' => "Payment status set to {$status}.",
            'orderId' => $orderId,
            'status'  => $status,
        ];
    }

    /**
     * Set the order's fulfillment/shipping status. Three values:
     * pending / shipped / delivered. Cashier+ for all (warehouse staff).
     *
     * Bagisto schema: orders.status holds the high-level state; shipments
     * carry their own state. We update both so OrderListController +
     * fulfillment reports stay coherent.
     */
    public function setShippingStatus($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $status = $args['status'] ?? '';
        if (! in_array($status, ['pending', 'shipped', 'delivered'], true)) {
            throw new CustomException("Unknown shipping status: {$status}");
        }

        $orderId = (int) ($args['orderId'] ?? 0);
        if (! $this->cashierOwnsOrder($context, $orderId)) {
            throw new CustomException(trans('pos::app.outlet.invoice.order-not-found'));
        }

        $now = now();
        $orderUpdate = ['updated_at' => $now];
        if ($status === 'shipped') {
            $orderUpdate['status'] = 'complete';
        } elseif ($status === 'delivered') {
            $orderUpdate['status'] = 'complete';
        }
        // pending: leave orders.status alone (might still be pending_payment etc.)

        DB::table('orders')->where('id', $orderId)->update($orderUpdate);

        // When shipping and the order has NO shipment yet (the invoice-builder
        // case — drafts never auto-ship), create a real shipment now. This is
        // what performs the sellable-stock decrement (ShipmentItemRepository),
        // honouring the "deduct on shipped" decision. If a shipment already
        // exists (normal POS cash sale), we just flip its status.
        $hasShipment = DB::table('shipments')->where('order_id', $orderId)->exists();

        if ($status === 'shipped' && ! $hasShipment) {
            $this->createShipmentForOrder($orderId);
        }

        // Update the status of any (now-)existing shipment for this order.
        $shipmentStateMap = [
            'pending'   => 'pending',
            'shipped'   => 'shipped',
            'delivered' => 'delivered',
        ];
        DB::table('shipments')
            ->where('order_id', $orderId)
            ->update(['status' => $shipmentStateMap[$status], 'updated_at' => $now]);

        return [
            'success' => true,
            'message' => "Shipping status set to {$status}.",
            'orderId' => $orderId,
            'status'  => $status,
        ];
    }

    /**
     * Render the qualified-invoice PDF to disk and return its public URL.
     * Stored under storage/app/public/pos-invoices/{invoiceId}.pdf so
     * `php artisan storage:link` exposes it without auth.
     */

    /**
     * Render the qualified-invoice PDF to disk and return its public URL.
     * Stored under storage/app/public/pos-invoices/{invoiceId}.pdf so
     * `php artisan storage:link` exposes it without auth.
     */
    public function generatePdf($root, array $args, GraphQLContext $context): array
    {
        if (! $this->cashierOwnsInvoice($context, (int) $args['invoiceId'])) {
            return ['success' => false, 'message' => 'Invoice not visible to this outlet.', 'pdfUrl' => null];
        }

        $data = $this->mapper->map((int) $args['invoiceId']);
        if (! $data) {
            return ['success' => false, 'message' => 'Invoice not found.', 'pdfUrl' => null];
        }

        $path = $this->renderPdfToDisk((int) $args['invoiceId'], $data);

        return [
            'success' => true,
            'message' => 'PDF generated.',
            'pdfUrl'  => Storage::disk('public')->url($path),
        ];
    }

    /**
     * Render the PDF, attach it to a Mailable, and send to the supplied
     * email or fall back to the invoice's recipientEmail.
     */
    public function sendEmail($root, array $args, GraphQLContext $context): array
    {
        if (! $this->cashierOwnsInvoice($context, (int) $args['invoiceId'])) {
            return ['success' => false, 'message' => 'Invoice not visible to this outlet.', 'pdfUrl' => null];
        }

        $data = $this->mapper->map((int) $args['invoiceId']);
        if (! $data) {
            return ['success' => false, 'message' => 'Invoice not found.', 'pdfUrl' => null];
        }

        $to = $args['toEmail'] ?? $data['recipientEmail'];
        if (! $to || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'No valid recipient email.', 'pdfUrl' => null];
        }

        $path = $this->renderPdfToDisk((int) $args['invoiceId'], $data);
        $absolute = Storage::disk('public')->path($path);

        try {
            Mail::to($to)->send(new QualifiedInvoiceMail($data, $absolute));
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Mail send failed: '.$e->getMessage(), 'pdfUrl' => Storage::disk('public')->url($path)];
        }

        DB::table('invoices')->where('id', $data['id'])->update(['email_sent' => 1]);

        return [
            'success' => true,
            'message' => 'Sent to '.$to,
            'pdfUrl'  => Storage::disk('public')->url($path),
        ];
    }

    /**
     * Guard: ensure the invoice's order belongs to the cashier's outlet.
     * Mirrors the InvoiceQuery scoping so the mutation can't be used to
     * leak invoices across outlets.
     */
    protected function cashierOwnsInvoice(GraphQLContext $context, int $invoiceId): bool
    {
        $outletId = $context->user()->outlet_id;
        $channelId = (int) DB::table('pos_outlets')->where('id', $outletId)->value('channel_id');

        return (bool) DB::table('invoices')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->leftJoin('pos_order', 'pos_order.order_id', '=', 'invoices.order_id')
            ->where('invoices.id', $invoiceId)
            ->where(function ($w) use ($channelId, $outletId) {
                $w->where('orders.channel_id', $channelId)
                  ->orWhere('pos_order.outlet_id', $outletId);
            })
            ->exists();
    }

    /**
     * Same scoping as cashierOwnsInvoice but for orders directly.
     */
    protected function cashierOwnsOrder(GraphQLContext $context, int $orderId): bool
    {
        $outletId = $context->user()->outlet_id;
        $channelId = (int) DB::table('pos_outlets')->where('id', $outletId)->value('channel_id');

        return (bool) DB::table('orders')
            ->leftJoin('pos_order', 'pos_order.order_id', '=', 'orders.id')
            ->where('orders.id', $orderId)
            ->where(function ($w) use ($channelId, $outletId) {
                $w->where('orders.channel_id', $channelId)
                  ->orWhere('pos_order.outlet_id', $outletId);
            })
            ->exists();
    }

    /**
     * Create a shipment covering every order_item's remaining shippable qty
     * (qty_ordered - qty_shipped), constrained to stockable items. This is the
     * sellable-stock decrement step. Uses the outlet's inventory source as the
     * shipment source. Mirrors the OrderLabelPrinted flow (memory:
     * project_label_printed_reservation_fix — ship by qty_shipped, not
     * qty_invoiced) so partial-ship math stays correct.
     */
    protected function createShipmentForOrder(int $orderId): void
    {
        $order = $this->orderRepository->find($orderId);
        if (! $order) {
            return;
        }

        $inventorySourceId = (int) DB::table('pos_order')->where('order_id', $orderId)->value('outlet_id');
        $inventorySourceId = (int) (DB::table('pos_outlets')->where('id', $inventorySourceId)->value('inventory_source_id')
            ?: DB::table('inventory_sources')->value('id') ?: 1);

        $items = [];
        foreach ($order->items as $item) {
            if (! $item->isStockable()) {
                continue;
            }
            $remaining = max(0, (int) $item->qty_ordered - (int) $item->qty_shipped);
            if ($remaining > 0) {
                $items[$item->id][$inventorySourceId] = $remaining;
            }
        }

        if (empty($items)) {
            return;
        }

        $this->shipmentRepository->create([
            'order_id' => $orderId,
            'shipment' => [
                'carrier_title' => '',
                'track_number'  => (string) random_int(10000, 99999),
                'source'        => $inventorySourceId,
                'items'         => $items,
                'email_sent'    => 0,
            ],
        ]);
    }

    protected function renderPdfToDisk(int $invoiceId, array $data): string
    {
        $pdf = Pdf::loadView('pos::emails.qualified-invoice-pdf', ['invoice' => $data])
            ->setPaper('a4', 'portrait');

        $relativePath = 'pos-invoices/'.$invoiceId.'.pdf';
        Storage::disk('public')->put($relativePath, $pdf->output());

        return $relativePath;
    }
}
