<?php

namespace Fujiba\Dashboard\Repositories;

use Carbon\Carbon;
use Fujiba\Dashboard\Support\JapanPrefectures;
use Illuminate\Support\Facades\DB;

/**
 * Orders by region (Japan choropleth) = order count + revenue grouped
 * by shipping address state, mapped to canonical 47-prefecture codes.
 *
 * Address.state arrives as free-form Japanese (e.g. "東京都").
 * JapanPrefectures::codeFromState() normalizes to a 2-digit code.
 * Rows that don't resolve (e.g. "myanmar", typos) go into 'other'.
 *
 * Channel filter: code-string at the boundary (matches W1 envelope).
 * Mode: 'orders' filters by orders.created_at; 'payments' by orders.paid_at.
 */
class OrdersByRegionRepository
{
    /**
     * @return array{
     *   prefectures: array<int, array{code:string, name_ja:string, name_en:string, orders:int, revenue:float}>,
     *   max_orders: int,
     *   total_orders: int,
     *   total_revenue: float,
     *   unmapped: array{orders:int, revenue:float, samples:array<int,string>}
     * }
     */
    public function summary(?string $channelCode, Carbon $start, Carbon $end, string $mode = 'orders'): array
    {
        $dateColumn = $mode === 'payments' ? 'paid_at' : 'created_at';

        $rows = DB::table('addresses as a')
            ->join('orders as o', 'o.id', '=', 'a.order_id')
            ->select([
                'a.state',
                DB::raw('COUNT(DISTINCT o.id) as orders_count'),
                DB::raw('COALESCE(SUM(o.grand_total), 0) as revenue'),
            ])
            ->where('a.address_type', 'order_shipping')
            ->whereBetween("o.{$dateColumn}", [$start, $end])
            ->when($channelCode, function ($q) use ($channelCode) {
                $channelId = DB::table('channels')->where('code', $channelCode)->value('id');
                if ($channelId) {
                    $q->where('o.channel_id', $channelId);
                }
            })
            ->groupBy('a.state')
            ->get();

        // Initialize all 47 prefectures with zero counts so the map
        // always shows the full country (zero-fill).
        $byCode = [];
        foreach (JapanPrefectures::PREFECTURES as $code => $names) {
            $byCode[$code] = [
                'code'    => $code,
                'name_ja' => $names['ja'],
                'name_en' => $names['en'],
                'orders'  => 0,
                'revenue' => 0.0,
            ];
        }

        $unmappedOrders  = 0;
        $unmappedRevenue = 0.0;
        $unmappedSamples = [];

        foreach ($rows as $row) {
            $code = JapanPrefectures::codeFromState($row->state);
            $orders = (int) $row->orders_count;
            $revenue = (float) $row->revenue;

            if ($code !== null) {
                $byCode[$code]['orders']  += $orders;
                $byCode[$code]['revenue'] += $revenue;
            } else {
                $unmappedOrders  += $orders;
                $unmappedRevenue += $revenue;
                if (count($unmappedSamples) < 5 && $row->state !== null && $row->state !== '') {
                    $unmappedSamples[] = (string) $row->state;
                }
            }
        }

        $prefectures = array_values($byCode);
        $totalOrders = array_sum(array_column($prefectures, 'orders'));
        $totalRevenue = array_sum(array_column($prefectures, 'revenue'));
        $maxOrders = $prefectures ? max(array_column($prefectures, 'orders')) : 0;

        return [
            'prefectures'   => $prefectures,
            'max_orders'    => $maxOrders,
            'total_orders'  => $totalOrders,
            'total_revenue' => $totalRevenue,
            'unmapped'      => [
                'orders'  => $unmappedOrders,
                'revenue' => $unmappedRevenue,
                'samples' => $unmappedSamples,
            ],
        ];
    }

    /**
     * Sales-by-state ranking — same query shape as summary() but ranked by
     * revenue and trimmed to non-zero rows. Powers the horizontal bar chart.
     *
     * @return array{
     *   rows: array<int, array{code:string, name_ja:string, name_en:string, orders:int, revenue:float}>,
     *   max_revenue: float,
     *   total_revenue: float,
     *   total_orders: int,
     *   unmapped_revenue: float,
     *   unmapped_orders: int
     * }
     */
    public function salesByState(?string $channelCode, Carbon $start, Carbon $end, string $mode = 'orders'): array
    {
        $summary = $this->summary($channelCode, $start, $end, $mode);

        $rows = array_values(array_filter(
            $summary['prefectures'],
            fn ($p) => $p['revenue'] > 0 || $p['orders'] > 0
        ));

        usort($rows, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        $maxRevenue = $rows ? (float) $rows[0]['revenue'] : 0.0;

        return [
            'rows'             => $rows,
            'max_revenue'      => $maxRevenue,
            'total_revenue'    => (float) $summary['total_revenue'],
            'total_orders'     => (int) $summary['total_orders'],
            'unmapped_revenue' => (float) $summary['unmapped']['revenue'],
            'unmapped_orders'  => (int) $summary['unmapped']['orders'],
        ];
    }
}
