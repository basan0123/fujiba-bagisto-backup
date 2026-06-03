<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderNoteRequest;
use Fujiba\OrdersAdmin\Services\OrderNoteWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderRepository;

class OrderNoteController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderNoteWriter $noteWriter,
    ) {}

    public function update(UpdateOrderNoteRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        $comment = $this->noteWriter->write($order, $request->validated('customer_note'));

        return response()->json([
            'message' => 'Note saved.',
            'data'    => $comment,
        ]);
    }
}
