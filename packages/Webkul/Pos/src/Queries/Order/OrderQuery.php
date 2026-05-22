<?php

namespace Webkul\Pos\Queries\Order;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Repositories\OrderRepository;

class OrderQuery extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected OrderRepository $orderRepository) {}

    /**
     * Get orders — the POS sale history. Returns only real POS sales
     * (pos_cash / pos_card / pos_split). Invoice-builder orders (payment
     * method = pos_invoice) are EXCLUDED — they live in the invoice list,
     * not the sale history, so the two never mix.
     */
    public function getOrders($query, $input, GraphQLContext $context)
    {
        return $this->orderRepository->query()
            ->where('pos_order.outlet_id', $context->user()->outlet_id)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('order_payment')
                  ->whereColumn('order_payment.order_id', 'pos_order.order_id')
                  ->whereIn('order_payment.method', ['pos_cash', 'pos_card', 'pos_split']);
            })
            ->orderBy('pos_order.created_at');
    }
}
