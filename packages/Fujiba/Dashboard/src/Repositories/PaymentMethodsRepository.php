<?php

namespace Fujiba\Dashboard\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Payment Methods donut = order count + revenue grouped by payment method.
 *
 * Channel filter: code-string at the boundary (matches W1 envelope).
 *   '' or null  → all channels
 *
 * Mode: 'orders' filters by orders.created_at; 'payments' by orders.paid_at
 *  (matches BusinessOverview convention).
 */
class PaymentMethodsRepository
{
    /**
     * @param  string|null  $channelCode
     * @param  string       $mode  'orders' | 'payments'
     * @return array{methods: array<int, array<string, mixed>>, totals: array<string, int|float>}
     */
    public function summary(?string $channelCode, Carbon $start, Carbon $end, string $mode = 'orders'): array
    {
        $dateColumn = $mode === 'payments' ? 'paid_at' : 'created_at';

        $rows = DB::table('order_payment as p')
            ->join('orders as o', 'o.id', '=', 'p.order_id')
            ->select([
                'p.method',
                DB::raw('COALESCE(MAX(p.method_title), p.method) as method_title'),
                DB::raw('COUNT(DISTINCT o.id) as orders_count'),
                DB::raw('COALESCE(SUM(o.grand_total), 0) as revenue'),
            ])
            ->whereBetween("o.{$dateColumn}", [$start, $end])
            ->when($channelCode, function ($q) use ($channelCode) {
                $channelId = DB::table('channels')->where('code', $channelCode)->value('id');
                if ($channelId) {
                    $q->where('o.channel_id', $channelId);
                }
            })
            ->groupBy('p.method')
            ->orderByDesc('orders_count')
            ->get();

        $totalOrders  = (int) $rows->sum('orders_count');
        $totalRevenue = (float) $rows->sum('revenue');

        $methods = $rows->map(function ($r) use ($totalOrders, $totalRevenue) {
            $orders  = (int) $r->orders_count;
            $revenue = (float) $r->revenue;

            return [
                'method'         => $r->method,
                'method_title'   => $r->method_title ?: $r->method,
                'orders_count'   => $orders,
                'revenue'        => $revenue,
                'orders_share'   => $totalOrders  > 0 ? round($orders / $totalOrders * 100, 1)  : 0.0,
                'revenue_share'  => $totalRevenue > 0 ? round($revenue / $totalRevenue * 100, 1) : 0.0,
            ];
        })->values()->all();

        return [
            'methods' => $methods,
            'totals'  => [
                'orders_count' => $totalOrders,
                'revenue'      => $totalRevenue,
            ],
        ];
    }
}
