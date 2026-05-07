<?php

namespace Webkul\Pos\Queries\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SalesReportQuery extends Controller
{
    /**
     * The outlet ID.
     *
     * @var int
     */
    protected $outletId;

    /**
     * The start date of the report.
     *
     * @var object
     */
    protected $startDate;

    /**
     * The end date of the report.
     *
     * @var object
     */
    protected $endDate;

    /**
     * The previous period start date.
     *
     * @var object
     */
    protected $previousStartDate;

    /**
     * The previous period end date.
     *
     * @var object
     */
    protected $previousEndDate;

    /**
     * Get sales report
     */
    public function __invoke($rootValue, array $args, GraphQLContext $context)
    {
        $this->outletId = $context->user()->outlet_id;

        $this->setStartEndDate($args);

        return [
            'reports' => [
                'orders_count'        => $this->getOrdersCount(),
                'avg_order_value'     => $this->getAverage(
                    type: 'base_grand_total',
                    name: trans('pos::app.outlet.reports.average-order-value'),
                    round: false,
                ),
                'avg_items_per_order' => $this->getAverage(
                    type: 'total_item_count',
                    name: trans('pos::app.outlet.reports.average-items-per-order'),
                    round: true,
                ),
                'discount_offers'     => $this->getProgress(
                    type: 'base_discount_amount',
                    name: trans('pos::app.outlet.reports.discounted-offers'),
                    round: false,
                ),
                'cash_payments'       => $this->getProgress(
                    type: 'cash_total',
                    name: trans('pos::app.outlet.reports.cash-payments'),
                    round: false,
                ),
                'other_payments'      => $this->getProgress(
                    type: 'card_total',
                    name: trans('pos::app.outlet.reports.other-payments'),
                    round: false,
                ),
            ],
        ];
    }

    /**
     * Get order count
     */
    protected function getOrdersCount()
    {
        $currentPeriodData = DB::table('pos_order')
            ->select(
                DB::raw('COUNT(order_id) as count'),
                DB::raw('DATE(created_at) as date')
            )
            ->where('outlet_id', $this->outletId)
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'), 'asc')
            ->get();

        $previousPeriodData = DB::table('pos_order')
            ->select(
                DB::raw('COUNT(order_id) as count'),
                DB::raw('DATE(created_at) as date')
            )
            ->where('outlet_id', $this->outletId)
            ->whereDate('created_at', '>=', $this->previousStartDate)
            ->whereDate('created_at', '<=', $this->previousEndDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'), 'asc')
            ->get();

        return $this->getChartDetails(
            $currentPeriodData,
            $previousPeriodData,
            type: 'count',
            name: trans('pos::app.outlet.reports.orders'),
            round: true
        );
    }

    /**
     * Get average items per order
     */
    protected function getAverage(
        string $type,
        string $name = '',
        bool $round = false
    ): array {
        $currentPeriodData = DB::table('pos_order')
            ->join('orders', 'pos_order.order_id', '=', 'orders.id')
            ->select(
                DB::raw("SUM({$type}) / COUNT(orders.id) as average_items"),
                DB::raw('DATE(orders.created_at) as date')
            )
            ->where('outlet_id', $this->outletId)
            ->whereDate('orders.created_at', '>=', $this->startDate)
            ->whereDate('orders.created_at', '<=', $this->endDate)
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->orderBy(DB::raw('DATE(orders.created_at)'), 'asc')
            ->get();

        $previousPeriodData = DB::table('pos_order')
            ->join('orders', 'pos_order.order_id', '=', 'orders.id')
            ->select(
                DB::raw("SUM({$type}) / COUNT(orders.id) as average_items"),
                DB::raw('DATE(orders.created_at) as date')
            )
            ->where('outlet_id', $this->outletId)
            ->whereDate('orders.created_at', '>=', $this->previousStartDate)
            ->whereDate('orders.created_at', '<=', $this->previousEndDate)
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->orderBy(DB::raw('DATE(orders.created_at)'), 'asc')
            ->get();

        return $this->getChartDetails(
            $currentPeriodData,
            $previousPeriodData,
            type: 'average_items',
            name: $name,
            round: $round
        );
    }

    /**
     * Get payment report
     */
    protected function getProgress(
        string $type,
        string $name = '',
        bool $round = false
    ): array {
        $currentPeriodData = DB::table('pos_order')
            ->select(
                DB::raw("SUM({$type}) as total"),
                DB::raw('DATE(created_at) as date')
            )
            ->where('outlet_id', $this->outletId)
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'), 'asc')
            ->get();

        $previousPeriodData = DB::table('pos_order')
            ->select(
                DB::raw("SUM({$type}) as total"),
                DB::raw('DATE(created_at) as date')
            )
            ->where('outlet_id', $this->outletId)
            ->whereDate('created_at', '>=', $this->previousStartDate)
            ->whereDate('created_at', '<=', $this->previousEndDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'), 'asc')
            ->get();

        return $this->getChartDetails(
            $currentPeriodData,
            $previousPeriodData,
            type: 'total',
            name: $name,
            round: $round
        );
    }

    /**
     * Get chart details for the report
     */
    protected function getChartDetails(
        object $currentData,
        object $previousData,
        string $type,
        string $name = '',
        bool $round = false
    ): array {
        $currentTotal = $currentData->sum($type);
        $previousTotal = $previousData->sum($type);

        $progress = $previousTotal > 0
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100)
            : 100;

        $goingUp = $currentTotal > $previousTotal;

        $seriesData = $currentData->pluck($type)->map(function ($value) use ($round) {
            return $round
                ? round($value)
                : number_format(core()->convertPrice($value), 2, '.', '');
        })->toArray();

        $days = $currentData->pluck('date')->map(function ($date) {
            return Carbon::parse($date)->format('D');
        })->toArray();

        $total = $round
            ? round($currentTotal)
            : number_format(core()->convertPrice($currentTotal), 2, '.', '');

        return [
            'name'     => $name,
            'total'    => $total,
            'progress' => "{$progress} %",
            'goingUp'  => $goingUp,
            'series'   => [
                [
                    'name' => $name,
                    'data' => $seriesData,
                ],
            ],
            'labels'    => $days,
        ];
    }

    /**
     * Set start and end date
     */
    protected function setStartEndDate(array $args): void
    {
        if (
            ! empty($args['start_date'])
            && ! empty($args['end_date'])
        ) {
            $this->startDate = Carbon::parse($args['start_date']);
            $this->endDate = Carbon::parse($args['end_date']);
        } else {
            $this->startDate = Carbon::now()->startOfWeek();
            $this->endDate = Carbon::now();
        }

        $this->previousStartDate = $this->startDate->copy()->subDays($this->startDate->diffInDays($this->endDate));

        $this->previousEndDate = $this->startDate->copy()->subDay();
    }
}
