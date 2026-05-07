<?php

namespace Fujiba\InvoicesAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Models\Invoice;

class InvoiceSummaryController extends Controller
{
    /**
     * Aggregate invoice totals for the listing header.
     * Honors the same filters Bagisto's datagrid accepts (created_at range,
     * channel_id, state) so the count reflects what the user sees.
     */
    public function summary(Request $request): JsonResponse
    {
        if (! bouncer()->hasPermission('sales.invoices')) {
            return new JsonResponse(['message' => 'Forbidden.'], 403);
        }

        $q = DB::table('invoices')
            ->leftJoin('orders', 'invoices.order_id', '=', 'orders.id');

        if ($from = $request->query('from')) {
            $q->where('invoices.created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $q->where('invoices.created_at', '<=', $to);
        }
        if ($channelId = $request->query('channel_id')) {
            $q->where('orders.channel_id', $channelId);
        }

        $base = (clone $q);

        $stateCounts = (clone $q)
            ->selectRaw('invoices.state as state, COUNT(*) as c, COALESCE(SUM(invoices.base_grand_total),0) as total')
            ->groupBy('invoices.state')
            ->pluck('c', 'state')
            ->toArray();

        $stateTotals = (clone $q)
            ->selectRaw('invoices.state as state, COALESCE(SUM(invoices.base_grand_total),0) as total')
            ->groupBy('invoices.state')
            ->pluck('total', 'state')
            ->toArray();

        $totalCount = (int) (clone $base)->count();
        $totalAmount = (float) (clone $base)->sum('invoices.base_grand_total');

        return new JsonResponse([
            'data' => [
                'count'        => $totalCount,
                'total_amount' => $totalAmount,
                'paid_count'   => (int) ($stateCounts[Invoice::STATUS_PAID] ?? 0),
                'paid_amount'  => (float) ($stateTotals[Invoice::STATUS_PAID] ?? 0),
                'pending_count'  => (int) ($stateCounts[Invoice::STATUS_PENDING] ?? 0)
                                  + (int) ($stateCounts[Invoice::STATUS_PENDING_PAYMENT] ?? 0),
                'pending_amount' => (float) ($stateTotals[Invoice::STATUS_PENDING] ?? 0)
                                  + (float) ($stateTotals[Invoice::STATUS_PENDING_PAYMENT] ?? 0),
                'overdue_count'  => (int) ($stateCounts[Invoice::STATUS_OVERDUE] ?? 0),
                'overdue_amount' => (float) ($stateTotals[Invoice::STATUS_OVERDUE] ?? 0),
            ],
        ]);
    }

    /**
     * Returns chat metadata (subscriber_id, channel_id, customer name) for a
     * batch of invoice ids — used by the listing page to enable the 💬 button
     * per row without modifying the core datagrid query.
     */
    public function chatMetadata(Request $request): JsonResponse
    {
        if (! bouncer()->hasPermission('sales.invoices')) {
            return new JsonResponse(['message' => 'Forbidden.'], 403);
        }

        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->take(200)
            ->values();

        if ($ids->isEmpty()) {
            return new JsonResponse(['data' => []]);
        }

        // Tenant DBs store the Chatpion subscriber as `chatpion_subscribe_id`;
        // the older central/dev migration named it `messenger_subscriber_id`.
        // Pick whichever column actually exists on this connection.
        $subCol = \Illuminate\Support\Facades\Schema::hasColumn('customers', 'chatpion_subscribe_id')
            ? 'customers.chatpion_subscribe_id'
            : (\Illuminate\Support\Facades\Schema::hasColumn('customers', 'messenger_subscriber_id')
                ? 'customers.messenger_subscriber_id'
                : null);

        // Fallback to orders.customer_first_name / customer_last_name when the
        // customer record is missing (guest checkouts).
        $rows = DB::table('invoices')
            ->leftJoin('orders', 'invoices.order_id', '=', 'orders.id')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->whereIn('invoices.id', $ids->all())
            ->get(array_filter([
                'invoices.id as invoice_id',
                'orders.id as order_id',
                'orders.channel_id as channel_id',
                'customers.id as customer_id',
                $subCol ? DB::raw($subCol . ' as subscriber_id') : DB::raw('NULL as subscriber_id'),
                DB::raw('CONCAT(COALESCE(customers.first_name, orders.customer_first_name, ""), " ", COALESCE(customers.last_name, orders.customer_last_name, "")) as customer_name'),
            ]))
            ->map(fn ($r) => [
                'invoice_id'    => (int) $r->invoice_id,
                'order_id'      => (int) $r->order_id,
                'channel_id'    => (int) $r->channel_id,
                'customer_id'   => $r->customer_id ? (int) $r->customer_id : null,
                'subscriber_id' => $r->subscriber_id,
                'customer_name' => trim($r->customer_name) ?: null,
            ])
            ->values();

        return new JsonResponse(['data' => $rows]);
    }
}
