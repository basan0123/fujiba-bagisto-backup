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

    /**
     * Single order (POS sale OR invoice-builder order) by its sales order id,
     * scoped to the cashier's outlet. Powers the OrderDetail screen — replaces
     * the old "fetch a wide page and find-by-id on the client" approach that
     * 404'd whenever the order fell outside the fetched page.
     *
     * Unlike the list, this does NOT filter by payment method: the detail view
     * is reachable from both the POS sale history and (for reprint/領収書) any
     * order belonging to the outlet, so we resolve by id + outlet only.
     */
    public function getOrder($root, array $args, GraphQLContext $context)
    {
        $orderId = (int) ($args['id'] ?? 0);
        if (! $orderId) {
            return null;
        }

        return $this->orderRepository->query()
            ->where('pos_order.outlet_id', $context->user()->outlet_id)
            ->where('pos_order.order_id', $orderId)
            ->first();
    }
}
