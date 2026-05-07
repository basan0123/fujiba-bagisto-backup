<?php

namespace Fujiba\Dashboard\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Helpers\Reporting\Customer as CustomerReporting;
use Webkul\Admin\Helpers\Reporting\Sale as SaleReporting;

/**
 * Business Overview = period-scoped flow metrics for a channel (or tenant-wide).
 *
 *   revenue       = DELEGATED to Webkul\Admin\Helpers\Reporting\Sale::getTotalSales()
 *                   so this widget always agrees with the existing 全体の概要 KPI.
 *                   See CONTEXT.md "BO revenue delegates to Sale::getTotalSales".
 *   cogs          = SUM(qty_invoiced × current cost) for matching orders, EAV cost LEFT JOIN
 *   delivery_fees = SUM(orders.shipping_amount)
 *   expenses      = SUM(pos_cash_movements where type='expense')
 *                   + per-month-prorated SUM(pos_fixed_expense_applications)
 *   profit        = revenue − cogs − delivery_fees − expenses
 *
 * dateColumn is plumbed in from the request's ?mode= toggle:
 *   mode=payments → dateColumn = 'paid_at'
 *   mode=orders   → dateColumn = 'created_at' (default)
 * All 6 KPIs use the SAME dateColumn — internal consistency rule.
 *
 * Channel: code-string at the boundary (matches Sale::setChannel signature).
 *   '' or null → all channels (tenant-wide)
 *   'default'  → kimono
 *   'mmfood'   → mmfood
 */
class BusinessOverviewRepository
{
    private const ATTR_COST = 12;
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly SaleReporting $saleReporting,
        private readonly CustomerReporting $customerReporting,
    ) {}

    /**
     * @param  string|null  $channelCode  '' or null = all channels
     * @param  string       $mode         'orders' or 'payments'
     */
    public function getOverview(?string $channelCode, Carbon $start, Carbon $end, string $mode = 'orders'): array
    {
        $channelKey = $channelCode === null || $channelCode === '' ? 'all' : "ch_{$channelCode}";

        $key = sprintf(
            'fujiba.dashboard.business_overview.%s.%s.%s_%s.v2',
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

        $channelId = null;
        if ($channelCode !== null && $channelCode !== '') {
            $channel = \Webkul\Core\Models\Channel::where('code', $channelCode)->first();
            $channelId = $channel ? (int) $channel->id : null;
        }

        // Set up shared Sale/Customer state ONCE for all delegated calls.
        // Carbon\Carbon → Illuminate\Support\Carbon at the boundary.
        $startIlluminate = \Illuminate\Support\Carbon::instance($start);
        $endIlluminate   = \Illuminate\Support\Carbon::instance($end);
        $this->saleReporting->setChannel($channelCode ?: null);
        $this->saleReporting->setMode($mode);
        $this->saleReporting->setStartDate($startIlluminate);
        $this->saleReporting->setEndDate($endIlluminate);
        $this->customerReporting->setChannel($channelCode ?: null);
        $this->customerReporting->setStartDate($startIlluminate);
        $this->customerReporting->setEndDate($endIlluminate);

        $revenue        = (float) $this->saleReporting->getTotalSales($startIlluminate, $endIlluminate);
        $totalOrders    = (int)   $this->saleReporting->getTotalOrders($startIlluminate, $endIlluminate);
        $aov            = (float) ($this->saleReporting->getAverageSales($startIlluminate, $endIlluminate) ?? 0);
        $unpaidInvoices = (float) $this->saleReporting->getTotalPendingInvoicesAmount();
        $totalCustomers = (int)   $this->customerReporting->getTotalCustomers($startIlluminate, $endIlluminate);

        $cogs         = $this->fetchCogs($channelId, $start, $end, $dateColumn);
        $deliveryFees = $this->fetchDeliveryFees($channelId, $start, $end, $dateColumn);
        $cashExpenses = $this->fetchCashExpenses(
            $channelId,
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        );
        $proratedFixed = $this->fetchProratedFixedExpenses(
            $channelId,
            $start->copy()->startOfDay(),
            $end->copy()->endOfDay()
        );

        $inventoryCost = $this->fetchInventoryCost($channelId);
        $totalStock    = app(InventoryValueRepository::class)->getTotalStockQuantity($channelId);
        $openOrders    = $this->fetchOpenOrdersCount($channelId, $start, $end, $dateColumn);

        $totalExpenses = $cashExpenses + $proratedFixed['total'];
        $profit        = $revenue - $cogs - $deliveryFees - $totalExpenses;

        return [
            'revenue'                   => $revenue,
            'cogs'                      => $cogs,
            'delivery_fees'             => $deliveryFees,
            'cash_expenses'             => $cashExpenses,
            'fixed_expenses'            => $proratedFixed['total'],
            'fixed_breakdown'           => $proratedFixed['breakdown'],
            'expenses_total'            => $totalExpenses,
            'profit'                    => $profit,
            'inventory_cost'            => $inventoryCost,
            'fixed_expenses_this_month' => $proratedFixed['total'],
            'open_orders'               => $openOrders,
            'total_orders'              => $totalOrders,
            'total_customers'           => $totalCustomers,
            'aov'                       => $aov,
            'unpaid_invoices'           => $unpaidInvoices,
            'total_stock'               => $totalStock,
            'date_column'               => $dateColumn,
        ];
    }

    /**
     * Open orders = pending + processing within the period, channel-scoped.
     * Uses the SAME dateColumn as revenue/cogs/delivery for internal consistency.
     */
    private function fetchOpenOrdersCount(?int $channelId, Carbon $start, Carbon $end, string $dateColumn): int
    {
        $startStr = $start->copy()->startOfDay()->toDateTimeString();
        $endStr   = $end->copy()->endOfDay()->toDateTimeString();

        $sql = "SELECT COUNT(*) AS v
                FROM orders
                WHERE status IN ('pending', 'processing')
                  AND {$dateColumn} BETWEEN ? AND ?";
        $params = [$startStr, $endStr];

        if ($channelId !== null) {
            $sql .= " AND channel_id = ?";
            $params[] = $channelId;
        }

        return (int) (DB::selectOne($sql, $params)->v ?? 0);
    }

    private function fetchCogs(?int $channelId, Carbon $start, Carbon $end, string $dateColumn): float
    {
        $startStr = $start->copy()->startOfDay()->toDateTimeString();
        $endStr   = $end->copy()->endOfDay()->toDateTimeString();

        $sql = "SELECT COALESCE(SUM(oi.qty_invoiced * COALESCE(pav.float_value, 0)), 0) AS v
                FROM order_items oi
                INNER JOIN orders o
                        ON o.id = oi.order_id
                       AND o.{$dateColumn} BETWEEN ? AND ?";
        $params = [$startStr, $endStr];

        if ($channelId !== null) {
            $sql .= " AND o.channel_id = ?";
            $params[] = $channelId;
        }

        $sql .= " LEFT JOIN product_attribute_values pav
                       ON pav.product_id   = oi.product_id
                      AND pav.attribute_id = ?
                      AND pav.float_value IS NOT NULL
                      AND pav.float_value > 0
                  WHERE oi.qty_invoiced > 0";
        $params[] = self::ATTR_COST;

        return (float) (DB::selectOne($sql, $params)->v ?? 0);
    }

    private function fetchDeliveryFees(?int $channelId, Carbon $start, Carbon $end, string $dateColumn): float
    {
        $startStr = $start->copy()->startOfDay()->toDateTimeString();
        $endStr   = $end->copy()->endOfDay()->toDateTimeString();

        $sql = "SELECT COALESCE(SUM(shipping_amount), 0) AS v
                FROM orders
                WHERE {$dateColumn} BETWEEN ? AND ?";
        $params = [$startStr, $endStr];

        if ($channelId !== null) {
            $sql .= " AND channel_id = ?";
            $params[] = $channelId;
        }

        return (float) (DB::selectOne($sql, $params)->v ?? 0);
    }

    private function fetchCashExpenses(?int $channelId, string $startDate, string $endDate): float
    {
        if ($channelId === null) {
            $row = DB::selectOne(
                "SELECT COALESCE(SUM(amount), 0) AS v
                 FROM pos_cash_movements
                 WHERE type = 'expense'
                   AND movement_date BETWEEN ? AND ?",
                [$startDate, $endDate]
            );
            return (float) ($row->v ?? 0);
        }

        $row = DB::selectOne(
            "SELECT COALESCE(SUM(cm.amount), 0) AS v
             FROM pos_cash_movements cm
             INNER JOIN pos_outlets po
                     ON po.id = cm.outlet_id
             INNER JOIN channel_inventory_sources cis
                     ON cis.inventory_source_id = po.inventory_source_id
                    AND cis.channel_id = ?
             WHERE cm.type = 'expense'
               AND cm.movement_date BETWEEN ? AND ?",
            [$channelId, $startDate, $endDate]
        );

        return (float) ($row->v ?? 0);
    }

    /**
     * Fixed-expense applications, prorated per actual calendar-month length
     * (overlapDays / daysInMonth, NOT flat /30).
     */
    private function fetchProratedFixedExpenses(?int $channelId, Carbon $start, Carbon $end): array
    {
        $startMonth = $start->format('Y-m');
        $endMonth   = $end->format('Y-m');

        if ($channelId === null) {
            $rows = DB::select(
                "SELECT fa.month, COALESCE(SUM(fa.applied_amount), 0) AS month_total
                 FROM pos_fixed_expense_applications fa
                 WHERE fa.month BETWEEN ? AND ?
                   AND fa.status IN ('auto', 'manual')
                 GROUP BY fa.month",
                [$startMonth, $endMonth]
            );
        } else {
            $rows = DB::select(
                "SELECT fa.month, COALESCE(SUM(fa.applied_amount), 0) AS month_total
                 FROM pos_fixed_expense_applications fa
                 INNER JOIN pos_fixed_expenses fe
                         ON fe.id = fa.fixed_expense_id
                 INNER JOIN pos_outlets po
                         ON po.id = fe.outlet_id
                 INNER JOIN channel_inventory_sources cis
                         ON cis.inventory_source_id = po.inventory_source_id
                        AND cis.channel_id = ?
                 WHERE fa.month BETWEEN ? AND ?
                   AND fa.status IN ('auto', 'manual')
                 GROUP BY fa.month",
                [$channelId, $startMonth, $endMonth]
            );
        }

        $total = 0.0;
        $breakdown = [];

        foreach ($rows as $row) {
            [$year, $monthNum] = explode('-', $row->month);
            $monthStart  = Carbon::create((int) $year, (int) $monthNum, 1)->startOfDay();
            $monthEnd    = $monthStart->copy()->endOfMonth()->endOfDay();
            $daysInMonth = $monthStart->daysInMonth;

            $overlapStart = $monthStart->greaterThan($start) ? $monthStart : $start->copy();
            $overlapEnd   = $monthEnd->lessThan($end)        ? $monthEnd   : $end->copy();

            if ($overlapEnd->lessThan($overlapStart)) {
                continue;
            }

            $overlapDays = (int) $overlapStart->copy()->startOfDay()->diffInDays($overlapEnd->copy()->startOfDay()) + 1;
            $factor      = $overlapDays / $daysInMonth;
            $contribution = ((float) $row->month_total) * $factor;

            $total += $contribution;
            $breakdown[] = [
                'month'         => $row->month,
                'month_total'   => (float) $row->month_total,
                'overlap_days'  => $overlapDays,
                'days_in_month' => $daysInMonth,
                'factor'        => round($factor, 4),
                'contribution'  => round($contribution, 2),
            ];
        }

        return [
            'total'     => round($total, 2),
            'breakdown' => $breakdown,
        ];
    }

    private function fetchInventoryCost(?int $channelId): float
    {
        $invRepo = app(InventoryValueRepository::class);

        if ($channelId === null) {
            return (float) ($invRepo->getAllCompanyTotals()['cost']['value'] ?? 0);
        }

        return (float) ($invRepo->getChannelTotals($channelId)['cost']['value'] ?? 0);
    }
}
