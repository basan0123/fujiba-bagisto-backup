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

class InvoiceMutation extends Controller
{
    public function __construct(
        protected QualifiedInvoiceMapper $mapper,
        protected InvoiceRepository $invoiceRepository,
        protected OrderRepository $orderRepository,
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

        try {
            $invoice = $this->invoiceRepository->create($invoiceData);
        } catch (\Throwable $e) {
            throw new CustomException($e->getMessage());
        }

        return [
            'success'   => true,
            'message'   => trans('pos::app.outlet.invoice.generated'),
            'invoiceId' => (int) $invoice->id,
        ];
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

        // Update the most recent shipment for this order if any
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

    protected function renderPdfToDisk(int $invoiceId, array $data): string
    {
        $pdf = Pdf::loadView('pos::emails.qualified-invoice-pdf', ['invoice' => $data])
            ->setPaper('a4', 'portrait');

        $relativePath = 'pos-invoices/'.$invoiceId.'.pdf';
        Storage::disk('public')->put($relativePath, $pdf->output());

        return $relativePath;
    }
}
