<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderNoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderCommentRepository;
use Webkul\Sales\Repositories\OrderRepository;

class OrderNoteController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderCommentRepository $orderCommentRepository,
    ) {}

    public function update(UpdateOrderNoteRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        // The order_comments.additional JSON column was added by this package's
        // migration. The marker lets the Vue side filter admin notes from
        // customer-facing comments without inspecting the comment text.
        $comment = $this->orderCommentRepository->create([
            'order_id'          => $order->id,
            'comment'           => $request->validated('customer_note'),
            'customer_notified' => 0,
            'additional'        => ['is_admin_note' => true],
        ]);

        return response()->json([
            'message' => 'Note saved.',
            'data'    => $comment,
        ]);
    }
}
