<?php

namespace Fujiba\OrdersAdmin\Services;

use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderCommentRepository;

/**
 * Records an internal admin note on an order. Shared by the admin REST
 * controller and the POS GraphQL mutation.
 *
 * The order_comments.additional `is_admin_note` marker lets the UI filter
 * admin notes from customer-facing comments without inspecting the text.
 */
class OrderNoteWriter
{
    public function __construct(
        protected OrderCommentRepository $orderCommentRepository,
    ) {}

    /** @return mixed the created comment row */
    public function write(Order $order, string $note)
    {
        return $this->orderCommentRepository->create([
            'order_id'          => $order->id,
            'comment'           => $note,
            'customer_notified' => 0,
            'additional'        => ['is_admin_note' => true],
        ]);
    }
}
