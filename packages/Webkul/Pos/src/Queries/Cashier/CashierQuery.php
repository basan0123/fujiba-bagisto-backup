<?php

namespace Webkul\Pos\Queries\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Repositories\OrderRepository;
use Webkul\Pos\Repositories\UserDrawerRepository;
use Webkul\Sales\Models\Order;

class CashierQuery extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected UserDrawerRepository $userDrawerRepository,
        protected OrderRepository $orderRepository
    ) {}

    /**
     * Get the payment details.
     */
    public function getDrawerDetails(mixed $rootValue, array $args, GraphQLContext $context): array
    {
        $drawer = $this->getTodaysDrawer($context->user());

        if (! $drawer) {
            return [];
        }

        $order = $this->getTodaysOrdersDetails($context->user());

        $openingAmount = $drawer->opening_amount;
        $cashPaymentSale = $order->cash_payment_sale ?? 0;
        $otherPaymentSale = $order->other_payment_sale ?? 0;

        // Petty-cash in/out recorded today for this cashier. `income`
        // (cash in) adds to the drawer; `expense` (cash out) subtracts.
        // Without this the expected figure ignored movements and the
        // close-out variance never reconciled.
        $cashMovementsTotal = $this->getTodaysCashMovementsTotal($context->user());

        $expectedDrawerAmount = $openingAmount + $cashPaymentSale + $cashMovementsTotal;
        $differenceAmount = $cashPaymentSale + $otherPaymentSale;

        return [
            'opening_amount'         => core()->convertPrice($openingAmount),
            'cash_payment_sale'      => core()->convertPrice($cashPaymentSale),
            'other_payment_sale'     => core()->convertPrice($otherPaymentSale),
            'cash_movements_total'   => core()->convertPrice($cashMovementsTotal),
            'expected_drawer_amount' => core()->convertPrice($expectedDrawerAmount),
            'difference_amount'      => core()->convertPrice($differenceAmount),
            'remark'                 => $drawer->remark,
        ];
    }

    /**
     * Sum today's petty-cash movements for the cashier as a signed total:
     * income (cash in) positive, expense (cash out) negative.
     */
    protected function getTodaysCashMovementsTotal(object $user): float
    {
        $sums = DB::table('pos_cash_movements')
            ->where('user_id', $user->id)
            ->whereDate('movement_date', today()->toDateString())
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense")
            ->first();

        return (float) ($sums->income ?? 0) - (float) ($sums->expense ?? 0);
    }

    /**
     * Get the total sales of the cashier.
     */
    public function getTodaySales($rootValue, array $args, GraphQLContext $context): array
    {
        $drawer = $this->getTodaysDrawer($context->user());

        $order = $this->getTodaysOrdersDetails($context->user());

        $query = $this->orderRepository->getModel()
            ->leftJoin('orders', 'orders.id', '=', 'pos_order.order_id')
            ->leftJoin('order_payment', 'order_payment.order_id', '=', 'pos_order.order_id')
            ->whereDate('pos_order.created_at', today()->toDateString())
            ->where('user_id', $context->user()->id)
            ->select(
                'pos_order.order_id',
                'order_payment.method_title as payment_mode',
                'orders.base_grand_total as order_total',
                'pos_order.created_at'
            );

        $this->applyFilters($query, $args, 'order_id', 'pos_order.order_id');
        $this->applyFilters($query, $args, 'payment_mode', 'order_payment.method');
        $this->applyFilters($query, $args, 'order_total', 'orders.base_grand_total');
        $this->applyFilters($query, $args, 'time', 'pos_order.created_at', 'time');

        $paginated = $query->orderByDesc('pos_order.created_at')
            ->paginate($args['first'], ['*'], 'page', $args['page']);

        $orders = $paginated->getCollection()->map(function ($order) {
            return [
                'order_id'     => $order->order_id,
                'payment_mode' => $order->payment_mode,
                'order_total'  => core()->convertPrice($order->order_total),
                'created_at'   => core()->formatDate($order->created_at, 'd F, Y h:i A'),
            ];
        })->toArray();

        return [
            'today_sale' => [
                'opening_amount'     => core()->convertPrice($drawer->opening_amount ?? 0),
                'cash_payment_sale'  => core()->convertPrice($order->cash_payment_sale ?? 0),
                'other_payment_sale' => core()->convertPrice($order->other_payment_sale ?? 0),
            ],

            'data' => $orders,

            'paginatorInfo' => [
                'count'       => $paginated->count(),
                'currentPage' => $paginated->currentPage(),
                'lastPage'    => $paginated->lastPage(),
                'total'       => $paginated->total(),
            ],
        ];
    }

    /**
     * Get the sale history of the cashier.
     */
    public function getSaleHistory($rootValue, array $args, GraphQLContext $context): ?array
    {
        $baseQuery = $this->orderRepository->getModel()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(COALESCE(cash_total, 0)) as total_cash'),
                DB::raw('SUM(COALESCE(card_total, 0)) as total_card'),
                DB::raw('(SUM(COALESCE(cash_total, 0)) + SUM(COALESCE(card_total, 0))) as total_sale')
            )
            ->where('user_id', $context->user()->id)
            ->when(! empty($args['date_from']), function ($query) use ($args) {
                return $query->whereDate('created_at', '>=', $args['date_from']);
            })
            ->when(! empty($args['date_to']), function ($query) use ($args) {
                return $query->whereDate('created_at', '<=', $args['date_to']);
            })
            ->groupByRaw('DATE(created_at)');

        $subQuery = DB::query()->fromSub($baseQuery, 'daily_sales');

        $this->applyFilters($subQuery, $args, 'cash_payment', 'total_cash');
        $this->applyFilters($subQuery, $args, 'other_payment', 'total_card');
        $this->applyFilters($subQuery, $args, 'total_sale', 'total_sale');

        $paginated = $subQuery->orderByDesc('date')
            ->paginate($args['first'], ['*'], 'page', $args['page']);

        $data = $paginated->getCollection()->map(function ($sale) {
            return [
                'date'          => core()->formatDate($sale->date, 'd F, Y'),
                'cash_payment'  => core()->convertPrice($sale->total_cash),
                'other_payment' => core()->convertPrice($sale->total_card),
                'total_sale'    => core()->convertPrice($sale->total_sale),
                'drawer_note'   => 'N/A',
            ];
        })->toArray();

        return [
            'data'          => $data,
            'paginatorInfo' => [
                'count'       => $paginated->count(),
                'currentPage' => $paginated->currentPage(),
                'lastPage'    => $paginated->lastPage(),
                'total'       => $paginated->total(),
            ],
        ];
    }

    /**
     * Apply multiple numeric filters to a query
     */
    private function applyFilters(
        $query,
        array $args,
        string $filterKey,
        string $dbColumn,
        string $type = 'numeric'
    ): void {
        if (
            ! empty($args[$filterKey])
            && is_array($args[$filterKey])
        ) {
            foreach ($args[$filterKey] as $filter) {
                if (
                    isset($filter['operator'])
                    && isset($filter['value'])
                ) {
                    $operator = $this->sanitizeOperator($filter['operator']);

                    if ($type === 'time') {
                        $query->whereRaw(
                            "DATE_FORMAT({$dbColumn}, '%H:%i') {$operator} ?",
                            [$filter['value']]
                        );
                    } else {
                        $query->havingRaw(
                            "{$dbColumn} {$operator} ?",
                            [$filter['value']]
                        );
                    }
                }
            }
        }
    }

    /**
     * Sanitize operator to prevent SQL injection
     */
    private function sanitizeOperator($operator): string
    {
        $allowedOperators = ['=', '>', '<', '>=', '<=', '!=', '<>'];

        return in_array($operator, $allowedOperators) ? $operator : '=';
    }

    /**
     * Get today's drawer.
     */
    protected function getTodaysDrawer(object $user): ?object
    {
        return $this->userDrawerRepository->getModel()
            ->whereDate('created_at', today())
            ->whereStatus(1)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Get today's orders.
     */
    protected function getTodaysOrdersDetails(object $user): ?object
    {
        return $this->orderRepository->getModel()
            ->join('orders', 'orders.id', 'pos_order.order_id')
            ->where('user_id', $user->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereDate('orders.created_at', today())
            ->select(
                DB::raw('SUM(cash_total) as cash_payment_sale'),
                DB::raw('SUM(card_total) as other_payment_sale')
            )
            ->first();
    }
}
