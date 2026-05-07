<?php

namespace Webkul\Pos\Queries\Order;

use App\Http\Controllers\Controller;
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
     * Get orders.
     */
    public function getOrders($query, $input, GraphQLContext $context)
    {
        return $this->orderRepository->query()
            ->where('pos_order.outlet_id', $context->user()->outlet_id)
            ->orderBy('pos_order.created_at');
    }
}
