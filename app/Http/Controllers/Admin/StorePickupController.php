<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Repositories\OrderRepository;

class StorePickupController extends Controller
{
    public function __construct(private OrderRepository $orderRepository) {}

    /**
     * Mark order as store pickup. Direct DB updates so model events
     * don't fire — keeps the customer email suppressed per spec.
     */
    public function markAsPickup(Request $request, int $orderId)
    {
        $data = $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer',
        ]);

        $order = $this->orderRepository->findOrFail($orderId);

        DB::transaction(function () use ($order, $data) {
            DB::table('orders')->where('id', $order->id)->update([
                'shipping_title'        => 'Store Pickup',
                'shipping_method'       => 'free_free',
                'shipping_amount'       => 0,
                'base_shipping_amount'  => 0,
                'status'                => 'completed',
                'updated_at'            => now(),
            ]);

            $methodTitle = $data['payment_method'] === 'cash' ? 'Cash' : 'Bank Transfer';

            DB::table('order_payment')->updateOrInsert(
                ['order_id' => $order->id],
                [
                    'method'       => $data['payment_method'],
                    'method_title' => $methodTitle,
                    'updated_at'   => now(),
                    'created_at'   => now(),
                ]
            );

            DB::table('order_transactions')->insert([
                'transaction_id' => 'PICKUP-' . $order->id . '-' . time(),
                'status'         => 'paid',
                'type'           => $data['payment_method'],
                'amount'         => $order->grand_total,
                'payment_method' => $data['payment_method'],
                'order_id'       => $order->id,
                'invoice_id'     => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });

        return redirect()
            ->route('admin.sales.orders.view', $order->id)
            ->with('success', 'Order marked as Store Pickup.');
    }
}
