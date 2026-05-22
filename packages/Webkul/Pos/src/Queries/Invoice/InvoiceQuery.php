<?php

namespace Webkul\Pos\Queries\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Helpers\RoleGate;
use Webkul\Pos\Services\QualifiedInvoiceMapper;
use Webkul\Sales\Repositories\InvoiceRepository;

class InvoiceQuery extends Controller
{
    /** product_attribute_values.attribute_id for the 'cost' attribute. */
    protected const COST_ATTRIBUTE_ID = 12;

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
        $qb = $this->invoiceRepository->query()
            ->select('invoices.*')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->leftJoin('pos_order', 'pos_order.order_id', '=', 'invoices.order_id');

        $this->applyScopeAndFilters($qb, $input, $context);

        return $qb->orderBy('invoices.created_at', 'desc');
    }

    /**
     * Shared scope + filter builder used by both listInvoices and getStats so
     * the list and its summary numbers always agree.
     *
     * Scope: invoices for orders on the outlet's channel OR invoices for orders
     * that originated at this POS outlet. Owners may opt into all-outlets with
     * filter.allOutlets=true.
     *
     * Filters (all optional):
     *   status      : draft | created | shipped  (document status, via orders.status)
     *   payment     : unpaid | partial | paid     (derived from invoices.amount_paid)
     *   dateFrom/To : ISO date strings, on invoices.created_at
     *   customer    : substring match on name/email
     *   orderId     : single order
     */
    protected function applyScopeAndFilters($qb, array $input, GraphQLContext $context): void
    {
        $outletId  = $context->user()->outlet_id;
        $channelId = (int) DB::table('pos_outlets')->where('id', $outletId)->value('channel_id');

        $allOutlets = ! empty($input['allOutlets'])
            && RoleGate::atLeast($context->user(), RoleGate::OWNER);

        if (! $allOutlets) {
            $qb->where(function ($w) use ($channelId, $outletId) {
                $w->where('orders.channel_id', $channelId)
                  ->orWhere('pos_order.outlet_id', $outletId);
            });
        }

        // Document status maps onto orders.status.
        $status = $input['status'] ?? null;
        if ($status === 'draft') {
            $qb->where('orders.status', 'draft');
        } elseif ($status === 'created') {
            $qb->whereIn('orders.status', ['processing', 'pending', 'pending_payment']);
        } elseif ($status === 'shipped') {
            $qb->whereIn('orders.status', ['complete', 'completed']);
        }

        // Payment status derived from amount_paid vs grand_total.
        $payment = $input['payment'] ?? $input['filter'] ?? null;
        if ($payment === 'unpaid') {
            $qb->whereRaw('COALESCE(invoices.amount_paid,0) <= 0.0001');
        } elseif ($payment === 'partial') {
            $qb->whereRaw('COALESCE(invoices.amount_paid,0) > 0.0001')
               ->whereRaw('COALESCE(invoices.amount_paid,0) + 0.01 < invoices.grand_total');
        } elseif ($payment === 'paid') {
            $qb->whereRaw('COALESCE(invoices.amount_paid,0) + 0.01 >= invoices.grand_total');
        }

        if (! empty($input['dateFrom'])) {
            $qb->whereDate('invoices.created_at', '>=', $input['dateFrom']);
        }
        if (! empty($input['dateTo'])) {
            $qb->whereDate('invoices.created_at', '<=', $input['dateTo']);
        }

        if (! empty($input['customer'])) {
            $needle = '%'.$input['customer'].'%';
            $qb->where(function ($w) use ($needle) {
                $w->where('orders.customer_email', 'like', $needle)
                  ->orWhereRaw("CONCAT(COALESCE(orders.customer_first_name,''),' ',COALESCE(orders.customer_last_name,'')) like ?", [$needle]);
            });
        }

        if (! empty($input['orderId'])) {
            $qb->where('invoices.order_id', (int) $input['orderId']);
        }
    }

    /**
     * Summary numbers for the current filter: counts per status/payment bucket
     * plus revenue and (manager+ only) profit over the filtered set. Profit =
     * SUM(line revenue) - SUM(qty * cost). Cost is the 'cost' product attribute
     * (product_attribute_values.float_value, attribute_id=12); products with no
     * cost contribute 0 cost so profit is an upper bound there.
     */
    public function getStats($root, array $args, GraphQLContext $context): array
    {
        $input = $args['filter'] ?? [];

        // Base scoped query (no status/payment filter) so the per-bucket counts
        // reflect the WHOLE scoped set, not the currently-selected chip.
        $countInput = array_diff_key($input, array_flip(['status', 'payment', 'filter']));

        $statusCounts = [];
        foreach (['draft', 'created', 'shipped'] as $s) {
            $qb = $this->scopedInvoiceIds(array_merge($countInput, ['status' => $s]), $context);
            $statusCounts[$s] = (int) $qb->count();
        }

        $paymentCounts = [];
        foreach (['unpaid', 'partial', 'paid'] as $p) {
            $qb = $this->scopedInvoiceIds(array_merge($countInput, ['payment' => $p]), $context);
            $paymentCounts[$p] = (int) $qb->count();
        }

        // Revenue + profit over the CURRENT full filter (status+payment applied).
        $scoped = $this->scopedInvoiceIds($input, $context);
        $totalCount = (int) $scoped->count();
        $revenue = (float) $this->scopedInvoiceIds($input, $context)->sum('invoices.grand_total');
        $collected = (float) $this->scopedInvoiceIds($input, $context)->sum(DB::raw('COALESCE(invoices.amount_paid,0)'));

        $canSeeProfit = RoleGate::atLeast($context->user(), RoleGate::MANAGER);
        $profit = null;
        $cost   = null;
        if ($canSeeProfit) {
            // Sum cost over the order_items of the filtered invoices' orders.
            $ids = $this->scopedInvoiceIds($input, $context)->pluck('invoices.order_id')->unique()->values()->all();
            if (! empty($ids)) {
                $costRow = DB::table('order_items')
                    ->leftJoin('product_attribute_values as pav', function ($j) {
                        $j->on('pav.product_id', '=', 'order_items.product_id')
                          ->where('pav.attribute_id', '=', self::COST_ATTRIBUTE_ID);
                    })
                    ->whereIn('order_items.order_id', $ids)
                    ->whereNull('order_items.parent_id')
                    ->selectRaw('COALESCE(SUM(order_items.qty_ordered * COALESCE(pav.float_value,0)),0) as cost,
                                 COALESCE(SUM(order_items.base_total),0) as revenue')
                    ->first();
                $cost   = round((float) ($costRow->cost ?? 0), 2);
                $profit = round(((float) ($costRow->revenue ?? 0)) - $cost, 2);
            } else {
                $cost = 0.0;
                $profit = 0.0;
            }
        }

        return [
            'totalCount'    => $totalCount,
            'revenue'       => round($revenue, 2),
            'collected'     => round($collected, 2),
            'outstanding'   => round($revenue - $collected, 2),
            'profit'        => $profit,
            'cost'          => $cost,
            'canSeeProfit'  => $canSeeProfit,
            'statusCounts'  => [
                ['key' => 'draft',   'count' => $statusCounts['draft']],
                ['key' => 'created', 'count' => $statusCounts['created']],
                ['key' => 'shipped', 'count' => $statusCounts['shipped']],
            ],
            'paymentCounts' => [
                ['key' => 'unpaid',  'count' => $paymentCounts['unpaid']],
                ['key' => 'partial', 'count' => $paymentCounts['partial']],
                ['key' => 'paid',    'count' => $paymentCounts['paid']],
            ],
        ];
    }

    /**
     * Fresh scoped query (invoices joined to orders/pos_order) with filters
     * applied. Each call returns a new builder so callers can count/sum/pluck
     * independently without clobbering each other.
     */
    protected function scopedInvoiceIds(array $input, GraphQLContext $context)
    {
        $qb = DB::table('invoices')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->leftJoin('pos_order', 'pos_order.order_id', '=', 'invoices.order_id');

        $this->applyScopeAndFilters($qb, $input, $context);

        return $qb;
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
