<?php

namespace Webkul\Pos\Queries\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Services\QualifiedInvoiceMapper;
use Webkul\Sales\Repositories\InvoiceRepository;

class InvoiceQuery extends Controller
{
    public function __construct(
        protected InvoiceRepository $invoiceRepository,
        protected QualifiedInvoiceMapper $mapper,
    ) {}

    /**
     * Paginated list. Scoped to invoices whose underlying order belongs to
     * the cashier's outlet (via pos_order.outlet_id).
     */
    public function listInvoices($query, array $input, GraphQLContext $context)
    {
        $outletId = $context->user()->outlet_id;
        $channelId = (int) DB::table('pos_outlets')->where('id', $outletId)->value('channel_id');

        // Scope: invoices for orders on the outlet's channel OR invoices
        // for orders that originated at this POS outlet (pos_order.outlet_id).
        // Covers both web orders on the channel the cashier serves AND
        // POS-rung orders that may have been booked under a different channel
        // (e.g. quick-sale on the cashier's outlet).
        $qb = $this->invoiceRepository->query()
            ->select('invoices.*')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->leftJoin('pos_order', 'pos_order.order_id', '=', 'invoices.order_id')
            ->where(function ($w) use ($channelId, $outletId) {
                $w->where('orders.channel_id', $channelId)
                  ->orWhere('pos_order.outlet_id', $outletId);
            });

        $filter = $input['filter'] ?? null;
        if ($filter === 'paid') {
            $qb->where('invoices.state', 'paid');
        } elseif ($filter === 'pending') {
            $qb->where('invoices.state', 'pending');
        }

        if (! empty($input['orderId'])) {
            $qb->where('invoices.order_id', (int) $input['orderId']);
        }

        return $qb->orderBy('invoices.created_at', 'desc');
    }

    /**
     * Resolver for a single invoice — maps it to the qualified-invoice DTO.
     */
    public function getInvoice($root, array $args, GraphQLContext $context): ?array
    {
        $outletId = $context->user()->outlet_id;
        $channelId = (int) DB::table('pos_outlets')->where('id', $outletId)->value('channel_id');

        $row = DB::table('invoices')
            ->select('invoices.*')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->leftJoin('pos_order', 'pos_order.order_id', '=', 'invoices.order_id')
            ->where(function ($w) use ($channelId, $outletId) {
                $w->where('orders.channel_id', $channelId)
                  ->orWhere('pos_order.outlet_id', $outletId);
            })
            ->where('invoices.id', (int) $args['id'])
            ->first();

        if (! $row) {
            return null;
        }

        return $this->mapper->map((int) $row->id);
    }

    /**
     * Orders that have items left to invoice (qty_ordered > qty_invoiced)
     * AND belong to this outlet. Powers the "+ create invoice" picker
     * in the native app — users can only invoice orders that aren't
     * fully invoiced yet.
     */
    public function listInvoiceableOrders($root, array $args, GraphQLContext $context): array
    {
        $outletId = $context->user()->outlet_id;
        $channelId = (int) DB::table('pos_outlets')->where('id', $outletId)->value('channel_id');
        $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));

        $rows = DB::table('orders')
            ->select(
                'orders.id',
                'orders.increment_id as incrementId',
                'orders.grand_total as grandTotal',
                'orders.status',
                'orders.created_at as createdAt',
                'orders.customer_first_name as firstName',
                'orders.customer_last_name as lastName',
                'orders.customer_email as customerEmail',
                DB::raw('(SELECT SUM(qty_ordered - qty_invoiced) FROM order_items WHERE order_id = orders.id) as qtyToInvoice'),
            )
            ->leftJoin('pos_order', 'pos_order.order_id', '=', 'orders.id')
            ->where(function ($w) use ($channelId, $outletId) {
                $w->where('orders.channel_id', $channelId)
                  ->orWhere('pos_order.outlet_id', $outletId);
            })
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('order_items')
                  ->whereColumn('order_items.order_id', 'orders.id')
                  ->whereRaw('order_items.qty_ordered > order_items.qty_invoiced');
            })
            ->orderBy('orders.created_at', 'desc')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'id'             => (int) $r->id,
            'incrementId'    => $r->incrementId,
            'grandTotal'     => (float) $r->grandTotal,
            'status'         => $r->status,
            'createdAt'      => $r->createdAt,
            'customerName'   => trim(($r->firstName ?? '').' '.($r->lastName ?? '')) ?: null,
            'customerEmail'  => $r->customerEmail,
            'qtyToInvoice'   => (int) ($r->qtyToInvoice ?? 0),
        ])->all();
    }
}
