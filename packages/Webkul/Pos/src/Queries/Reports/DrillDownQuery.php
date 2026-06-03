<?php

namespace Webkul\Pos\Queries\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * Phase E — five sales-report aggregators for the drill-down screens
 * (TopProducts / CashierPerf / PaymentBreakdown / TaxReport /
 * RangeReport). All scope to the current outlet via pos_order.outlet_id,
 * default to the last 30 days when no range is given, and exclude
 * voided/cancelled orders.
 */
class DrillDownQuery
{
    private const DEFAULT_DAYS = 30;
    private const COMPLETED_STATUSES = ['complete', 'completed', 'processing'];

    /* ----------------- §E1 top products ----------------- */
    public function byProduct($root, array $args, GraphQLContext $context): array
    {
        $self = $context->user();
        $outletId = (int) $self->outlet_id;
        [$from, $to] = $this->range($args);

        $first = max(1, min(100, (int) ($args['first'] ?? 25)));
        $page  = max(1, (int) ($args['page'] ?? 1));

        $base = DB::table('order_items as oi')
            ->join('orders as o',     'o.id', '=', 'oi.order_id')
            ->join('pos_order as po', 'po.order_id', '=', 'o.id')
            ->where('po.outlet_id', $outletId)
            ->whereIn('o.status', self::COMPLETED_STATUSES)
            ->whereBetween('o.created_at', [$from, $to]);

        $total = (clone $base)->distinct('oi.product_id')->count('oi.product_id');

        $rows = (clone $base)
            ->select(
                'oi.product_id',
                DB::raw('MIN(oi.sku)  as sku'),
                DB::raw('MIN(oi.name) as name'),
                DB::raw('SUM(oi.qty_ordered - COALESCE(oi.qty_refunded,0)) as qty'),
                DB::raw('SUM((oi.qty_ordered - COALESCE(oi.qty_refunded,0)) * oi.price) as revenue'),
                DB::raw('COUNT(DISTINCT oi.order_id) as order_count'),
            )
            ->groupBy('oi.product_id')
            ->orderByDesc('revenue')
            ->forPage($page, $first)
            ->get();

        $lastPage = max(1, (int) ceil($total / $first));

        return [
            'paginatorInfo' => [
                'count'        => $rows->count(),
                'currentPage'  => $page,
                'firstItem'    => $total > 0 ? (($page - 1) * $first + 1) : null,
                'hasMorePages' => $page < $lastPage,
                'lastItem'     => $total > 0 ? min($total, $page * $first) : null,
                'lastPage'     => $lastPage,
                'perPage'      => $first,
                'total'        => $total,
            ],
            'data' => $rows->map(fn ($r) => [
                'productId'  => (int) $r->product_id,
                'sku'        => $r->sku,
                'name'       => $r->name,
                'qty'        => (int) $r->qty,
                'revenue'    => (float) $r->revenue,
                'orderCount' => (int) $r->order_count,
            ])->all(),
        ];
    }

    /* ----------------- §E2 by cashier ----------------- */
    public function byCashier($root, array $args, GraphQLContext $context): array
    {
        $self = $context->user();
        $outletId = (int) $self->outlet_id;
        [$from, $to] = $this->range($args);

        $rows = DB::table('orders as o')
            ->join('pos_order as po', 'po.order_id', '=', 'o.id')
            ->leftJoin('pos_users as u', 'u.id', '=', 'po.user_id')
            ->where('po.outlet_id', $outletId)
            ->whereIn('o.status', self::COMPLETED_STATUSES)
            ->whereBetween('o.created_at', [$from, $to])
            ->select(
                DB::raw('po.user_id as pos_user_id'),
                DB::raw('MIN(u.firstname) as firstname'),
                DB::raw('MIN(u.lastname)  as lastname'),
                DB::raw('MIN(u.email)     as email'),
                DB::raw('COUNT(o.id) as order_count'),
                DB::raw('SUM(o.grand_total - COALESCE(o.grand_total_refunded,0)) as revenue'),
            )
            ->groupBy('po.user_id')
            ->orderByDesc('revenue')
            ->get();

        return $rows->map(function ($r) {
            $rev = (float) $r->revenue;
            $cnt = (int) $r->order_count;
            $name = trim(($r->firstname ?? '') . ' ' . ($r->lastname ?? '')) ?: ($r->email ?? "user#{$r->pos_user_id}");
            return [
                'posUserId'    => (int) $r->pos_user_id,
                'posUserName'  => $name,
                'orderCount'   => $cnt,
                'revenue'      => $rev,
                'avgTicket'    => $cnt > 0 ? round($rev / $cnt) : 0,
            ];
        })->all();
    }

    /* ----------------- §E3 payment breakdown ----------------- */
    public function byPayment($root, array $args, GraphQLContext $context): array
    {
        $self = $context->user();
        $outletId = (int) $self->outlet_id;
        [$from, $to] = $this->range($args);

        $rows = DB::table('order_payment as p')
            ->join('orders as o',     'o.id',        '=', 'p.order_id')
            ->join('pos_order as po', 'po.order_id', '=', 'o.id')
            ->where('po.outlet_id', $outletId)
            ->whereIn('o.status', self::COMPLETED_STATUSES)
            ->whereBetween('o.created_at', [$from, $to])
            ->select(
                'p.method',
                DB::raw('MIN(p.method_title) as title'),
                DB::raw('COUNT(DISTINCT o.id) as order_count'),
                DB::raw('SUM(o.grand_total - COALESCE(o.grand_total_refunded,0)) as revenue'),
            )
            ->groupBy('p.method')
            ->orderByDesc('revenue')
            ->get();

        return $rows->map(fn ($r) => [
            'method'     => (string) $r->method,
            'title'      => (string) ($r->title ?? $r->method),
            'orderCount' => (int) $r->order_count,
            'revenue'    => (float) $r->revenue,
        ])->all();
    }

    /* ----------------- §E4 tax buckets ----------------- */
    public function byTax($root, array $args, GraphQLContext $context): array
    {
        $self = $context->user();
        $outletId = (int) $self->outlet_id;
        [$from, $to] = $this->range($args);

        $rows = DB::table('order_items as oi')
            ->join('orders as o',     'o.id', '=', 'oi.order_id')
            ->join('pos_order as po', 'po.order_id', '=', 'o.id')
            ->where('po.outlet_id', $outletId)
            ->whereIn('o.status', self::COMPLETED_STATUSES)
            ->whereBetween('o.created_at', [$from, $to])
            ->select(
                DB::raw('ROUND(COALESCE(oi.tax_percent, 0), 2) as rate_percent'),
                DB::raw('SUM(oi.total - COALESCE(oi.tax_amount,0)) as base_amount'),
                DB::raw('SUM(COALESCE(oi.tax_amount, 0)) as tax_amount'),
            )
            ->groupBy('rate_percent')
            ->orderBy('rate_percent')
            ->get();

        return $rows->map(fn ($r) => [
            'ratePercent' => (float) $r->rate_percent,
            'baseAmount'  => (float) $r->base_amount,
            'taxAmount'   => (float) $r->tax_amount,
        ])->all();
    }

    /* ----------------- §E5 per-day series ----------------- */
    public function byDay($root, array $args, GraphQLContext $context): array
    {
        $self = $context->user();
        $outletId = (int) $self->outlet_id;
        [$from, $to] = $this->range($args, requireExplicit: true);

        // Day buckets from orders, then enrich with payment splits.
        $orderRows = DB::table('orders as o')
            ->join('pos_order as po', 'po.order_id', '=', 'o.id')
            ->where('po.outlet_id', $outletId)
            ->whereIn('o.status', self::COMPLETED_STATUSES)
            ->whereBetween('o.created_at', [$from, $to])
            ->select(
                DB::raw('DATE(o.created_at) as d'),
                DB::raw('COUNT(o.id) as order_count'),
                DB::raw('SUM(o.grand_total - COALESCE(o.grand_total_refunded,0)) as revenue'),
            )
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $cashByDay = DB::table('order_payment as p')
            ->join('orders as o',     'o.id', '=', 'p.order_id')
            ->join('pos_order as po', 'po.order_id', '=', 'o.id')
            ->where('po.outlet_id', $outletId)
            ->whereIn('o.status', self::COMPLETED_STATUSES)
            ->whereBetween('o.created_at', [$from, $to])
            ->select(
                DB::raw('DATE(o.created_at) as d'),
                DB::raw('SUM(CASE WHEN p.method = "cash" THEN o.grand_total ELSE 0 END) as cash_amount'),
                DB::raw('SUM(CASE WHEN p.method <> "cash" THEN o.grand_total ELSE 0 END) as other_amount'),
            )
            ->groupBy('d')
            ->get()
            ->keyBy(fn ($r) => (string) $r->d);

        return $orderRows->map(function ($r) use ($cashByDay) {
            $pay = $cashByDay->get((string) $r->d);
            return [
                'date'         => (string) $r->d,
                'orderCount'   => (int) $r->order_count,
                'revenue'      => (float) $r->revenue,
                'cashAmount'   => (float) ($pay->cash_amount ?? 0),
                'otherAmount'  => (float) ($pay->other_amount ?? 0),
            ];
        })->all();
    }

    /* ----------------- §E6 per-hour series ----------------- */
    public function byHour($root, array $args, GraphQLContext $context): array
    {
        $self = $context->user();
        $outletId = (int) $self->outlet_id;
        [$from, $to] = $this->range($args);

        $rows = DB::table('orders as o')
            ->join('pos_order as po', 'po.order_id', '=', 'o.id')
            ->where('po.outlet_id', $outletId)
            ->whereIn('o.status', self::COMPLETED_STATUSES)
            ->whereBetween('o.created_at', [$from, $to])
            ->select(
                DB::raw('HOUR(o.created_at) as h'),
                DB::raw('COUNT(o.id) as order_count'),
                DB::raw('SUM(o.grand_total - COALESCE(o.grand_total_refunded,0)) as revenue'),
            )
            ->groupBy('h')
            ->get()
            ->keyBy(fn ($r) => (int) $r->h);

        // Zero-fill all 24 hours so the chart has a fixed 0..23 x-axis.
        return array_map(function ($hour) use ($rows) {
            $r = $rows->get($hour);
            return [
                'hour'       => $hour,
                'orderCount' => (int) ($r->order_count ?? 0),
                'revenue'    => (float) ($r->revenue ?? 0),
            ];
        }, range(0, 23));
    }

    /** Default to last 30 days when no explicit range supplied. */
    private function range(array $args, bool $requireExplicit = false): array
    {
        if ($requireExplicit && (empty($args['dateFrom']) || empty($args['dateTo']))) {
            throw new \InvalidArgumentException('dateFrom and dateTo are required');
        }
        $to = !empty($args['dateTo'])
            ? Carbon::parse($args['dateTo'])->endOfDay()
            : Carbon::now()->endOfDay();
        $from = !empty($args['dateFrom'])
            ? Carbon::parse($args['dateFrom'])->startOfDay()
            : Carbon::now()->subDays(self::DEFAULT_DAYS)->startOfDay();
        return [$from, $to];
    }
}
