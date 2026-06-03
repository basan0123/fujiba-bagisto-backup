<?php

namespace Webkul\Admin\Helpers\Reporting;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\RefundRepository;

class Sale extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderItemRepository $orderItemRepository,
        protected InvoiceRepository $invoiceRepository,
        protected RefundRepository $refundRepository
    ) {
        parent::__construct();
    }

    /**
     * Retrieves total orders and their progress.
     *
     * @return array
     */
    public function getTotalOrdersProgress()
    {
        return [
            'previous' => $previous = $this->getTotalOrders($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getTotalOrders($this->startDate, $this->endDate),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Returns previous orders over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getPreviousTotalOrdersOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalOrdersOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    /**
     * Returns current orders over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getCurrentTotalOrdersOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalOrdersOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    /**
     * Retrieves total orders
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function getTotalOrders($startDate, $endDate): int
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->dateColumn, [$startDate, $endDate])
            ->count();
    }

    /**
     * Returns orders over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getTotalOrdersOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'COUNT(*)',
            $period
        );
    }

    /**
     * Retrieves today orders and their progress.
     */
    public function getTodayOrdersProgress(): array
    {
        return [
            'previous' => $previous = $this->getTotalOrders(now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'current' => $current = $this->getTotalOrders(now()->today(), now()->endOfDay()),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves orders
     *
     * @return array
     */
    public function getTodayOrders()
    {
        return $this->orderRepository
            ->resetModel()
            ->with(['addresses', 'payment', 'items'])
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('orders.created_at', [now()->today(), now()->endOfDay()])
            ->get();
    }

    /**
     * Retrieves total sales and their progress.
     */
    public function getTotalSalesProgress(): array
    {
        return [
            'previous' => $previous = $this->getTotalSales($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getTotalSales($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves sub total sales and their progress.
     */
    public function getSubTotalSalesProgress(): array
    {
        return [
            'previous' => $previous = $this->getSubTotalSales($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getSubTotalSales($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves today sales and their progress.
     */
    public function getTodaySalesProgress(): array
    {
        return [
            'previous' => $previous = $this->getTotalSales(now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'current' => $current = $this->getTotalSales(now()->today(), now()->endOfDay()),
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves total sales
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function getTotalSales($startDate, $endDate): float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->dateColumn, [$startDate, $endDate])
            ->sum(DB::raw('base_grand_total_invoiced - base_grand_total_refunded'));
    }

    /**
     * Retrieves sub total sales
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function getSubTotalSales($startDate, $endDate): float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->dateColumn, [$startDate, $endDate])
            ->sum(DB::raw('base_sub_total_invoiced - base_sub_total_refunded'));
    }

    /**
     * Returns previous sales over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getPreviousTotalSalesOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalSalesOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    /**
     * Returns current sales over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getCurrentTotalSalesOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalSalesOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    /**
     * Returns sales over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getTotalSalesOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'SUM(base_grand_total_invoiced - base_grand_total_refunded)',
            $period
        );
    }

    /**
     * Retrieves average sales and their progress.
     */
    public function getAverageSalesProgress(): array
    {
        return [
            'previous' => $previous = $this->getAverageSales($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getAverageSales($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves average sales
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function getAverageSales($startDate, $endDate): ?float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->dateColumn, [$startDate, $endDate])
            ->avg(DB::raw('base_grand_total_invoiced - base_grand_total_refunded'));
    }

    /**
     * Returns previous average sales over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getPreviousAverageSalesOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getAverageSalesOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    /**
     * Returns current average sales over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getCurrentAverageSalesOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getAverageSalesOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    /**
     * Returns average sales over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getAverageSalesOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'AVG(base_grand_total_invoiced - base_grand_total_refunded)',
            $period
        );
    }

    /**
     * Retrieves refunds and their progress.
     */
    public function getRefundsProgress(): array
    {
        return [
            'previous' => $previous = $this->getRefunds($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getRefunds($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves refunds
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function getRefunds($startDate, $endDate): float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->dateColumn, [$startDate, $endDate])
            ->sum(DB::raw('base_grand_total_refunded'));
    }

    /**
     * Returns previous refunds over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getPreviousRefundsOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getRefundsOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    /**
     * Returns current refunds over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getCurrentRefundsOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getRefundsOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    /**
     * Returns refunds over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getRefundsOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'SUM(base_grand_total_refunded)',
            $period
        );
    }

    /**
     * Retrieves tax collected and their progress.
     */
    public function getTaxCollectedProgress(): array
    {
        return [
            'previous' => $previous = $this->getTaxCollected($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getTaxCollected($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves tax collected
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function getTaxCollected($startDate, $endDate): float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->dateColumn, [$startDate, $endDate])
            ->sum(DB::raw('base_tax_amount_invoiced - base_tax_amount_refunded'));
    }

    /**
     * Returns previous tax collected over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getPreviousTaxCollectedOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTaxCollectedOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    /**
     * Returns current tax collected over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getCurrentTaxCollectedOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTaxCollectedOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    /**
     * Returns tax collected over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getTaxCollectedOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'SUM(base_tax_amount_invoiced - base_tax_amount_refunded)',
            $period
        );
    }

    /**
     * Returns top tax categories
     *
     * @param  int  $limit
     */
    public function getTopTaxCategories($limit = null): Collection
    {
        $tablePrefix = DB::getTablePrefix();

        return $this->orderItemRepository
            ->resetModel()
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('tax_categories', 'order_items.tax_category_id', '=', 'tax_categories.id')
            ->select('tax_categories.id as tax_category_id', 'tax_categories.name')
            ->addSelect(DB::raw("SUM({$tablePrefix}order_items.base_tax_amount_invoiced - {$tablePrefix}order_items.base_tax_amount_refunded) as total"))
            ->whereIn('orders.channel_id', $this->channelIds)
            ->whereBetween('order_items.created_at', [$this->startDate, $this->endDate])
            ->whereNotNull('tax_category_id')
            ->groupBy('tax_category_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * Retrieves shipping collected and their progress.
     */
    public function getShippingCollectedProgress(): array
    {
        return [
            'previous' => $previous = $this->getShippingCollected($this->lastStartDate, $this->lastEndDate),
            'current' => $current = $this->getShippingCollected($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    /**
     * Retrieves shipping collected
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function getShippingCollected($startDate, $endDate): float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->dateColumn, [$startDate, $endDate])
            ->sum(DB::raw('base_shipping_invoiced - base_shipping_refunded'));
    }

    /**
     * Returns previous shipping collected over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getPreviousShippingCollectedOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getShippingCollectedOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    /**
     * Returns current shipping collected over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getCurrentShippingCollectedOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getShippingCollectedOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    /**
     * Returns shipping collected over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function getShippingCollectedOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'SUM(base_shipping_invoiced - base_shipping_refunded)',
            $period
        );
    }

    /**
     * Returns top shipping methods
     *
     * @param  int  $limit
     */
    public function getTopShippingMethods($limit = null): Collection
    {
        return $this->orderRepository
            ->resetModel()
            ->select('shipping_title as title')
            ->addSelect(DB::raw('SUM(base_shipping_invoiced - base_shipping_refunded) as total'))
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->dateColumn, [$this->startDate, $this->endDate])
            ->whereNotNull('shipping_method')
            ->groupBy('shipping_method')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * Returns top payment methods
     *
     * @param  int  $limit
     */
    public function getTopPaymentMethods($limit = null): Collection
    {
        return $this->orderRepository
            ->resetModel()
            ->leftJoin('order_payment', 'orders.id', '=', 'order_payment.order_id')
            ->select('method', 'method_title as title')
            ->addSelect(DB::raw('COUNT(*) as total'))
            ->addSelect(DB::raw('SUM(base_grand_total) as base_total'))
            ->whereIn('orders.channel_id', $this->channelIds)
            ->whereBetween('orders.'.$this->dateColumn, [$this->startDate, $this->endDate])
            ->groupBy('method')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * Gets the total amount of pending invoices.
     */
    public function getTotalPendingInvoicesAmount(): float
    {
        return $this->invoiceRepository->getTotalPendingInvoicesAmount();
    }

    /**
     * Retrieves total unique cart users
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function getTotalUniqueOrdersUsers($startDate, $endDate): int
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->dateColumn, [$startDate, $endDate])
            ->groupBy(DB::raw('CONCAT(customer_email, "-", customer_id)'))
            ->get()
            ->count();
    }

    /**
     * Returns over time stats.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $valueColumn
     * @param  string  $period
     */
    public function getOverTimeStats($startDate, $endDate, $valueColumn, $period = 'auto'): array
    {
        $config = $this->getTimeInterval($startDate, $endDate, $period);

        $groupColumn = $config['group_column'];

        $results = $this->orderRepository
            ->resetModel()
            ->select(
                DB::raw("$groupColumn AS date"),
                DB::raw("$valueColumn AS total"),
                DB::raw('COUNT(*) AS count')
            )
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->dateColumn, [$startDate, $endDate])
            ->groupBy('date')
            ->get();

        foreach ($config['intervals'] as $interval) {
            $total = $results->where('date', $interval['filter'])->first();

            $stats[] = [
                'label' => $interval['start'],
                'total' => $total?->total ?? 0,
                'count' => $total?->count ?? 0,
            ];
        }

        return $stats ?? [];
    }

    /* =====================================================================
     |  Fujiba custom reporting queries (location, monthly, products, etc.)
     |  All respect the active channel + date range + orders/payments mode.
     * ==================================================================== */

    /**
     * Month-by-month sales table for the selected range.
     * Returns rows of { ym, label, orders, sales, aov }.
     */
    public function getMonthlySalesTable(): array
    {
        $col = $this->dateColumn;

        $rows = $this->orderRepository
            ->resetModel()
            ->select(
                DB::raw("DATE_FORMAT($col, '%Y-%m') as ym"),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(base_grand_total - base_grand_total_refunded) as sales')
            )
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($col, [$this->startDate, $this->endDate])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        return $rows->map(function ($r) {
            $orders = (int) $r->orders;
            $sales = (float) $r->sales;

            return [
                'ym'             => $r->ym,
                'label'          => $r->ym,
                'orders'         => $orders,
                'sales'          => $sales,
                'formatted_sales'=> core()->formatBasePrice($sales),
                'aov'            => $orders ? $sales / $orders : 0,
                'formatted_aov'  => core()->formatBasePrice($orders ? $sales / $orders : 0),
            ];
        })->toArray();
    }

    /**
     * Top selling products for the range, ranked by net revenue.
     * Returns { product_id, name, sku, qty, revenue }.
     */
    public function getTopSellingProducts($limit = 10): array
    {
        $col = $this->dateColumn;

        return $this->orderItemRepository
            ->resetModel()
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'order_items.product_id',
                DB::raw('MAX(order_items.name) as name'),
                DB::raw('MAX(order_items.sku) as sku'),
                DB::raw('SUM(order_items.qty_ordered - order_items.qty_refunded) as qty'),
                DB::raw('SUM(order_items.base_total - order_items.base_amount_refunded) as revenue')
            )
            ->whereIn('orders.channel_id', $this->channelIds)
            ->whereBetween('orders.'.$col, [$this->startDate, $this->endDate])
            ->whereNull('order_items.parent_id')
            ->groupBy('order_items.product_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'product_id'        => $r->product_id,
                'name'              => $r->name,
                'sku'               => $r->sku,
                'qty'               => (int) $r->qty,
                'revenue'           => (float) $r->revenue,
                'formatted_revenue' => core()->formatBasePrice((float) $r->revenue),
            ])->toArray();
    }

    /**
     * Customer location breakdown from order shipping addresses.
     * Grouped by prefecture/state (most meaningful for a JP store), with the
     * country attached. Returns { country, state, orders, sales }.
     */
    public function getCustomerLocations($limit = 15): array
    {
        $col = $this->dateColumn;

        $rows = $this->orderRepository
            ->resetModel()
            ->leftJoin('addresses', function ($join) {
                $join->on('addresses.order_id', '=', 'orders.id')
                    ->where('addresses.address_type', '=', 'order_shipping');
            })
            ->select(
                DB::raw("NULLIF(TRIM(addresses.state), '') as state"),
                DB::raw("NULLIF(TRIM(addresses.country), '') as country"),
                DB::raw('COUNT(DISTINCT orders.id) as orders'),
                DB::raw('SUM(orders.base_grand_total - orders.base_grand_total_refunded) as sales')
            )
            ->whereIn('orders.channel_id', $this->channelIds)
            ->whereBetween('orders.'.$col, [$this->startDate, $this->endDate])
            ->groupBy('country', 'state')
            ->orderByDesc('sales')
            ->limit($limit)
            ->get();

        // Orders without a shipping address are in-store/POS or pickup; label
        // them clearly instead of leaving a bare dash.
        $unknown = trans('admin::app.reporting.sales.index.location-unknown');

        return $rows->map(fn ($r) => [
            'country'         => $r->country ?: '—',
            'state'           => $r->state ?: $unknown,
            'orders'          => (int) $r->orders,
            'sales'           => (float) $r->sales,
            'formatted_sales' => core()->formatBasePrice((float) $r->sales),
        ])->toArray();
    }

    /**
     * New vs returning customers in the range.
     * A customer counts as "new" when their first-ever order falls inside the
     * range; otherwise an order from them is "returning". Guests count as new.
     * Returns { new_customers, returning_customers, new_orders, returning_orders }.
     */
    public function getNewVsReturningCustomers(): array
    {
        $col = $this->dateColumn;

        $channelIn = implode(',', array_map('intval', $this->channelIds ?: [0]));
        // Server-generated datetime → safe to quote into the CASE expressions.
        $start = DB::getPdo()->quote($this->startDate->toDateTimeString());

        // Per-customer first-ever order date (channel-scoped, all time).
        $firstSub = "(SELECT customer_id, MIN($col) AS first_at
                      FROM orders
                      WHERE channel_id IN ($channelIn) AND customer_id IS NOT NULL
                      GROUP BY customer_id) AS fo";

        // An order is "returning" when the customer's first-ever order predates
        // the range start; guests and first-timers are "new". Classify and
        // aggregate entirely in SQL — no PHP loop over every order.
        $ret = "(orders.is_guest = 0 AND fo.first_at < $start)";

        $row = $this->orderRepository
            ->resetModel()
            ->leftJoin(DB::raw($firstSub), 'fo.customer_id', '=', 'orders.customer_id')
            ->select(
                DB::raw("SUM(CASE WHEN $ret THEN 1 ELSE 0 END) AS returning_orders"),
                DB::raw("SUM(CASE WHEN NOT $ret THEN 1 ELSE 0 END) AS new_orders"),
                DB::raw("COUNT(DISTINCT CASE WHEN $ret THEN orders.customer_id END) AS returning_customers"),
                DB::raw("COUNT(DISTINCT CASE WHEN NOT $ret THEN orders.customer_id END) AS new_customers")
            )
            ->whereIn('orders.channel_id', $this->channelIds)
            ->whereBetween('orders.'.$col, [$this->startDate, $this->endDate])
            ->first();

        return [
            'new_customers'       => (int) ($row->new_customers ?? 0),
            'returning_customers' => (int) ($row->returning_customers ?? 0),
            'new_orders'          => (int) ($row->new_orders ?? 0),
            'returning_orders'    => (int) ($row->returning_orders ?? 0),
        ];
    }

    /**
     * Sales heatmap by weekday (0=Sun..6=Sat) x hour (0..23).
     * Returns a flat list of { dow, hour, orders, sales } for populated cells.
     */
    public function getSalesHeatmap(): array
    {
        $col = $this->dateColumn;

        return $this->orderRepository
            ->resetModel()
            ->select(
                DB::raw("(DAYOFWEEK($col) - 1) as dow"),
                DB::raw("HOUR($col) as hour"),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(base_grand_total - base_grand_total_refunded) as sales')
            )
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($col, [$this->startDate, $this->endDate])
            ->groupBy('dow', 'hour')
            ->get()
            ->map(fn ($r) => [
                'dow'    => (int) $r->dow,
                'hour'   => (int) $r->hour,
                'orders' => (int) $r->orders,
                'sales'  => (float) $r->sales,
            ])->toArray();
    }

    /**
     * Sales by channel/store for the range. Ignores the channel filter so all
     * stores can be compared side by side. Returns { channel, orders, sales }.
     */
    public function getSalesByChannel(): array
    {
        $col = $this->dateColumn;

        $names = core()->getAllChannels()->pluck('name', 'id');

        $rows = $this->orderRepository
            ->resetModel()
            ->select(
                'channel_id',
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(base_grand_total - base_grand_total_refunded) as sales')
            )
            ->whereBetween($col, [$this->startDate, $this->endDate])
            ->groupBy('channel_id')
            ->orderByDesc('sales')
            ->get();

        return $rows->map(fn ($r) => [
            'channel'         => $names[$r->channel_id] ?? ('#'.$r->channel_id),
            'orders'          => (int) $r->orders,
            'sales'           => (float) $r->sales,
            'formatted_sales' => core()->formatBasePrice((float) $r->sales),
        ])->toArray();
    }

    /**
     * Per-product revenue/qty for the active range, as a base subquery.
     * Aggregating once per product first keeps the later attribute joins tiny
     * (distinct products, not every order_item row) — the key to fast
     * category/brand breakdowns.
     */
    protected function productRevenueSub()
    {
        $col = $this->dateColumn;

        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.base_total - order_items.base_amount_refunded) as revenue'),
                DB::raw('SUM(order_items.qty_ordered - order_items.qty_refunded) as qty')
            )
            ->whereIn('orders.channel_id', $this->channelIds)
            ->whereBetween('orders.'.$col, [$this->startDate, $this->endDate])
            ->whereNull('order_items.parent_id')
            ->whereNotNull('order_items.product_id')
            ->groupBy('order_items.product_id');
    }

    /**
     * Sales split by product category for the range, ranked by revenue.
     * Pre-aggregates per product, then maps products → categories.
     */
    public function getSalesByCategory($limit = 10): array
    {
        $locale = app()->getLocale();

        $rows = DB::query()
            ->fromSub($this->productRevenueSub(), 'pr')
            ->leftJoin('product_categories', 'pr.product_id', '=', 'product_categories.product_id')
            ->leftJoin('category_translations', function ($join) use ($locale) {
                $join->on('product_categories.category_id', '=', 'category_translations.category_id')
                    ->where('category_translations.locale', '=', $locale);
            })
            ->select(
                DB::raw("COALESCE(NULLIF(category_translations.name, ''), '—') as label"),
                DB::raw('SUM(pr.revenue) as revenue'),
                DB::raw('SUM(pr.qty) as qty')
            )
            ->groupBy('label')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'label'             => $r->label,
            'qty'               => (int) $r->qty,
            'revenue'           => (float) $r->revenue,
            'formatted_revenue' => core()->formatBasePrice((float) $r->revenue),
        ])->toArray();
    }

    /**
     * Sales split by brand attribute for the range, ranked by revenue.
     * Brand is a select attribute: option id in product_attribute_values.integer_value.
     */
    public function getSalesByBrand($limit = 10): array
    {
        $locale = app()->getLocale();

        $brandAttrId = DB::table('attributes')->where('code', 'brand')->value('id');

        if (! $brandAttrId) {
            return [];
        }

        $rows = DB::query()
            ->fromSub($this->productRevenueSub(), 'pr')
            ->leftJoin('product_attribute_values as brand_attr', function ($join) use ($brandAttrId) {
                $join->on('pr.product_id', '=', 'brand_attr.product_id')
                    ->where('brand_attr.attribute_id', '=', $brandAttrId);
            })
            ->leftJoin('attribute_option_translations as brand_trans', function ($join) use ($locale) {
                $join->on('brand_trans.attribute_option_id', '=', 'brand_attr.integer_value')
                    ->where('brand_trans.locale', '=', $locale);
            })
            ->select(
                DB::raw("COALESCE(NULLIF(brand_trans.label, ''), NULLIF(brand_attr.text_value, ''), '—') as label"),
                DB::raw('SUM(pr.revenue) as revenue'),
                DB::raw('SUM(pr.qty) as qty')
            )
            ->groupBy('label')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'label'             => $r->label,
            'qty'               => (int) $r->qty,
            'revenue'           => (float) $r->revenue,
            'formatted_revenue' => core()->formatBasePrice((float) $r->revenue),
        ])->toArray();
    }
}
