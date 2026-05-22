<?php

namespace Fujiba\Dashboard\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\Models\Channel;

/**
 * Shipping Performance widget data source.
 *
 *   collected     = SUM(orders.shipping_amount) within period/channel.
 *   actual_cost   = SUM(order_shipping_costs.carrier_cost) joined on orders in period/channel.
 *   margin        = collected − actual_cost (positive = we made money on shipping).
 *   matched_count = orders in period that have an actual-cost row.
 *   pending_count = orders in period without an actual-cost row (carrier invoice not in yet).
 *
 *   free_orders     = COUNT(DISTINCT srl.order_id) where srl.free_shipping_applied = 1
 *                     AND orders in period/channel.
 *   subsidy_yen     = SUM(srl.shipping_total) on the same rows
 *                     (shipping_total in srl = what WOULD have been charged → effective subsidy).
 *   by_rule         = breakdown grouped by free_shipping_thresholds.label (NO hardcoded labels).
 *
 * Multi-tenant safe: reads only from current-tenant DB tables (Eloquent default connection).
 * Channel-scoped: '' or null = all channels.
 *
 * dateColumn follows the dashboard mode toggle (orders=created_at, payments=paid_at)
 * for consistency with [[BusinessOverviewRepository]].
 */
class ShippingPerformanceRepository
{
    private const CACHE_TTL_SECONDS = 300;

    public function getSummary(?string $channelCode, Carbon $start, Carbon $end, string $mode = 'orders'): array
    {
        $channelKey = $channelCode === null || $channelCode === '' ? 'all' : "ch_{$channelCode}";

        $key = sprintf(
            'fujiba.dashboard.shipping_performance.%s.%s.%s_%s.v1',
            $channelKey,
            $mode,
            $start->format('Ymd'),
            $end->format('Ymd'),
        );

        return Cache::remember(
            $key,
            self::CACHE_TTL_SECONDS,
            fn () => $this->compute($channelCode, $start, $end, $mode)
        );
    }

    public function getFetchedAt(): string
    {
        return Carbon::now()->toIso8601String();
    }

    private function compute(?string $channelCode, Carbon $start, Carbon $end, string $mode): array
    {
        $dateColumn = $mode === 'payments' ? 'paid_at' : 'created_at';
        $startStr   = $start->copy()->startOfDay()->toDateTimeString();
        $endStr     = $end->copy()->endOfDay()->toDateTimeString();

        $channelId = null;
        if ($channelCode !== null && $channelCode !== '') {
            $channelId = (int) (Channel::where('code', $channelCode)->value('id') ?? 0) ?: null;
        }

        $hasCostsTable = Schema::hasTable('order_shipping_costs');
        $hasLogsTable  = Schema::hasTable('shipping_rule_logs');
        $hasThresholds = Schema::hasTable('free_shipping_thresholds');

        $cost = $this->fetchCostMetrics($channelId, $startStr, $endStr, $dateColumn, $hasCostsTable);
        $free = $this->fetchFreeShippingMetrics($channelId, $startStr, $endStr, $dateColumn, $hasLogsTable, $hasThresholds);

        return [
            'date_column' => $dateColumn,
            'cost'        => $cost,
            'free'        => $free,
        ];
    }

    private function fetchCostMetrics(?int $channelId, string $startStr, string $endStr, string $dateColumn, bool $hasCostsTable): array
    {
        $params = [$startStr, $endStr];
        $where  = "WHERE o.{$dateColumn} BETWEEN ? AND ?";

        if ($channelId !== null) {
            $where .= " AND o.channel_id = ?";
            $params[] = $channelId;
        }

        $collectedRow = DB::selectOne(
            "SELECT COALESCE(SUM(o.shipping_amount), 0) AS collected,
                    COUNT(*) AS order_count
             FROM orders o
             {$where}",
            $params
        );

        $collected  = (float) ($collectedRow->collected ?? 0);
        $orderCount = (int)   ($collectedRow->order_count ?? 0);

        if (! $hasCostsTable) {
            return [
                'collected'     => $collected,
                'actual'        => 0.0,
                'margin'        => $collected,
                'order_count'   => $orderCount,
                'matched_count' => 0,
                'pending_count' => $orderCount,
                'coverage_pct'  => 0.0,
            ];
        }

        $costRow = DB::selectOne(
            "SELECT COALESCE(SUM(osc.carrier_cost), 0) AS actual,
                    COUNT(DISTINCT osc.order_id)       AS matched
             FROM orders o
             INNER JOIN order_shipping_costs osc ON osc.order_id = o.id
             {$where}",
            $params
        );

        $actual  = (float) ($costRow->actual ?? 0);
        $matched = (int)   ($costRow->matched ?? 0);
        $pending = max(0, $orderCount - $matched);

        return [
            'collected'     => $collected,
            'actual'        => $actual,
            'margin'        => $collected - $actual,
            'order_count'   => $orderCount,
            'matched_count' => $matched,
            'pending_count' => $pending,
            'coverage_pct'  => $orderCount > 0 ? round($matched / $orderCount * 100, 1) : 0.0,
        ];
    }

    private function fetchFreeShippingMetrics(?int $channelId, string $startStr, string $endStr, string $dateColumn, bool $hasLogsTable, bool $hasThresholds): array
    {
        if (! $hasLogsTable) {
            return [
                'orders'      => 0,
                'subsidy'     => 0.0,
                'avg_subsidy' => 0.0,
                'by_rule'     => [],
            ];
        }

        $params = [$startStr, $endStr];
        $where  = "WHERE o.{$dateColumn} BETWEEN ? AND ?
                     AND srl.free_shipping_applied = 1
                     AND srl.order_id IS NOT NULL";

        if ($channelId !== null) {
            $where .= " AND o.channel_id = ?";
            $params[] = $channelId;
        }

        // Latest log per order (an order can recalc multiple times during checkout).
        $totals = DB::selectOne(
            "SELECT COUNT(DISTINCT srl.order_id)      AS orders,
                    COALESCE(SUM(latest.subsidy), 0)  AS subsidy
             FROM shipping_rule_logs srl
             INNER JOIN orders o ON o.id = srl.order_id
             INNER JOIN (
                 SELECT order_id, MAX(id) AS max_id
                 FROM shipping_rule_logs
                 WHERE free_shipping_applied = 1
                 GROUP BY order_id
             ) keep ON keep.order_id = srl.order_id AND keep.max_id = srl.id
             INNER JOIN (
                 SELECT id, shipping_total AS subsidy FROM shipping_rule_logs
             ) latest ON latest.id = srl.id
             {$where}",
            $params
        );

        $orders  = (int)   ($totals->orders ?? 0);
        $subsidy = (float) ($totals->subsidy ?? 0);

        $byRule = [];
        if ($hasThresholds && $orders > 0) {
            $byRuleRows = DB::select(
                "SELECT COALESCE(fst.label, ?) AS rule_label,
                        COUNT(DISTINCT srl.order_id) AS orders,
                        COALESCE(SUM(srl.shipping_total), 0) AS subsidy
                 FROM shipping_rule_logs srl
                 INNER JOIN orders o ON o.id = srl.order_id
                 INNER JOIN (
                     SELECT order_id, MAX(id) AS max_id
                     FROM shipping_rule_logs
                     WHERE free_shipping_applied = 1
                     GROUP BY order_id
                 ) keep ON keep.order_id = srl.order_id AND keep.max_id = srl.id
                 LEFT JOIN free_shipping_thresholds fst
                        ON JSON_CONTAINS(srl.rules_applied, JSON_QUOTE(CONCAT('free_shipping:', fst.id)))
                 {$where}
                 GROUP BY fst.id, fst.label
                 ORDER BY orders DESC",
                array_merge([__('fujiba::app.dashboard.shipping_performance.rule_other')], $params)
            );

            foreach ($byRuleRows as $row) {
                $byRule[] = [
                    'label'   => (string) $row->rule_label,
                    'orders'  => (int)    $row->orders,
                    'subsidy' => (float)  $row->subsidy,
                ];
            }
        }

        return [
            'orders'      => $orders,
            'subsidy'     => $subsidy,
            'avg_subsidy' => $orders > 0 ? round($subsidy / $orders, 2) : 0.0,
            'by_rule'     => $byRule,
        ];
    }
}
