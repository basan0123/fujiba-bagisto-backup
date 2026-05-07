<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Webkul\Sales\Repositories\OrderRepository;

/**
 * Orders Controller - Custom Views
 * 
 * Handles order list views with status management,
 * notifications, and delivery date selection
 */
class OrdersViewController extends Controller
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get all orders as JSON for custom list view
     * Includes customer info, status, dates, and delivery details
     */
    public function allOrders(): JsonResponse
    {
        $locale = app()->getLocale();
        $search = request('q', '');
        $status = request('status', '');
        $limit = min((int) request('limit', 1000), 2000);
        $offset = max((int) request('offset', 0), 0);

        $query = DB::table('orders')
            ->leftJoin('order_addresses', function($j) {
                $j->on('orders.id', '=', 'order_addresses.order_id')
                  ->where('order_addresses.address_type', 'billing');
            })
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->leftJoin('orders_postage', 'orders.id', '=', 'orders_postage.order_id')
            ->select([
                'orders.id',
                'orders.increment_id as order_number',
                'orders.status',
                'orders.created_at as order_date',
                'orders.updated_at as last_updated',
                'orders.grand_total',
                'orders.currency_code',
                'customers.id as customer_id',
                'customers.email',
                'customers.first_name',
                'customers.last_name',
                'order_addresses.first_name as shipping_first_name',
                'order_addresses.last_name as shipping_last_name',
                'order_addresses.address_line1',
                'order_addresses.country',
                'order_addresses.state',
                'order_addresses.postcode',
                'orders_postage.delivery_date',
                'orders_postage.delivery_time',
            ])
            ->where('orders.status', '!=', 'canceled')
            ->orderBy('orders.created_at', 'desc');

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('orders.increment_id', 'like', "%{$search}%")
                  ->orWhere('customers.email', 'like', "%{$search}%")
                  ->orWhere('customers.first_name', 'like', "%{$search}%")
                  ->orWhere('customers.last_name', 'like', "%{$search}%")
                  ->orWhere('order_addresses.postcode', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status) {
            $query->where('orders.status', $status);
        }

        $total = (clone $query)->count();

        $records = $query->skip($offset)
                         ->take($limit)
                         ->get()
                         ->map(function ($order) {
                             return [
                                 'id' => $order->id,
                                 'order_number' => $order->order_number,
                                 'status' => $order->status,
                                 'customer_name' => trim($order->first_name . ' ' . $order->last_name),
                                 'customer_email' => $order->email,
                                 'customer_id' => $order->customer_id,
                                 'shipping_address' => $order->state,
                                 'total' => $order->grand_total,
                                 'currency' => $order->currency_code,
                                 'order_date' => $order->order_date,
                                 'last_updated' => $order->last_updated,
                                 'delivery_date' => $order->delivery_date,
                                 'delivery_time' => $order->delivery_time,
                                 'postcode' => $order->postcode,
                             ];
                         });

        return new JsonResponse([
            'data' => $records,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }

    /**
     * Update order status with notification
     */
    public function updateStatus(): JsonResponse
    {
        $orderId = (int) request('id');
        $newStatus = request('status');
        $notifyCustomer = request('notify', true);
        $message = request('message', '');

        if (!$orderId || !$newStatus) {
            return new JsonResponse(['message' => 'Missing order ID or status'], 422);
        }

        $order = $this->orderRepository->find($orderId);
        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], 404);
        }

        // Update status
        DB::table('orders')
            ->where('id', $orderId)
            ->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

        // Send notification if requested
        if ($notifyCustomer) {
            $this->notifyCustomer($order, $newStatus, $message);
        }

        return new JsonResponse([
            'message' => 'Order status updated',
            'id' => $orderId,
            'status' => $newStatus,
        ]);
    }

    /**
     * Notify customer of order status change
     */
    protected function notifyCustomer($order, $status, $customMessage = '')
    {
        $customer = $order->customer;
        $statusLabels = [
            'pending' => '注文受付中',
            'processing' => '処理中',
            'completed' => '配送完了',
            'canceled' => 'キャンセル',
            'shipped' => '配送中',
        ];

        $subject = "注文 #{$order->increment_id} - ステータス更新: " . ($statusLabels[$status] ?? $status);
        
        $message = $customMessage ?: "ご注文のステータスが更新されました。\n\n"
            . "注文番号: #{$order->increment_id}\n"
            . "新しいステータス: " . ($statusLabels[$status] ?? $status) . "\n\n"
            . "詳細はマイページからご確認ください。";

        // Send email
        try {
            \Mail::raw($message, function ($mail) use ($customer, $subject) {
                $mail->to($customer->email)
                     ->subject($subject);
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send order status email', ['error' => $e->getMessage()]);
        }

        // Can also integrate with messaging service here
        // Example: Send to Messenger, LINE, WhatsApp, etc.
    }

    /**
     * Get order status options
     */
    public function getStatusOptions(): JsonResponse
    {
        $statuses = [
            'pending' => ['label' => '注文受付中', 'color' => '#FFA500'],
            'processing' => ['label' => '処理中', 'color' => '#2196F3'],
            'shipped' => ['label' => '配送中', 'color' => '#4CAF50'],
            'completed' => ['label' => '配送完了', 'color' => '#8BC34A'],
            'canceled' => ['label' => 'キャンセル', 'color' => '#F44336'],
        ];

        return new JsonResponse(['statuses' => $statuses]);
    }

    /**
     * Get delivery date/time options for order
     */
    public function getDeliveryOptions($orderId): JsonResponse
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], 404);
        }

        // Get prefecture from shipping address
        $prefecture = $order->shipping_address->state ?? '';
        
        $sagawa = new \Webkul\Admin\Helpers\SagawaDelivery();
        $deliveryDates = $sagawa->getSelectableDeliveryDates($prefecture, 5);
        $timeSlots = $sagawa->getTimeSlots();

        return new JsonResponse([
            'delivery_dates' => $deliveryDates,
            'time_slots' => $timeSlots,
            'current_date' => $order->delivery_date ?? null,
            'current_time' => $order->delivery_time ?? null,
        ]);
    }

    /**
     * Update delivery date/time for order
     */
    public function updateDeliverySchedule(): JsonResponse
    {
        $orderId = (int) request('id');
        $deliveryDate = request('delivery_date');
        $deliveryTime = request('delivery_time');

        if (!$orderId || !$deliveryDate) {
            return new JsonResponse(['message' => 'Missing order ID or delivery date'], 422);
        }

        DB::table('orders_postage')
            ->updateOrInsert(
                ['order_id' => $orderId],
                [
                    'delivery_date' => $deliveryDate,
                    'delivery_time' => $deliveryTime,
                    'updated_at' => now(),
                ]
            );

        return new JsonResponse([
            'message' => 'Delivery schedule updated',
            'id' => $orderId,
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
        ]);
    }
}
