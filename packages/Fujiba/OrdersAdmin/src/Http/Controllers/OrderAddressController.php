<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Exceptions\OrderEditException;
use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderAddressRequest;
use Fujiba\OrdersAdmin\Services\OrderAddressUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderRepository;

class OrderAddressController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderAddressUpdater $addressUpdater,
    ) {}

    public function update(UpdateOrderAddressRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        try {
            $address = $this->addressUpdater->update($order, [
                'name'     => $request->validated('name'),
                'address1' => $request->validated('address1', ''),
                'address2' => $request->input('address2'),
                'city'     => $request->validated('city'),
                'state'    => $request->validated('state', ''),
                'postcode' => $request->validated('postcode'),
                'country'  => $request->validated('country'),
                'phone'    => $request->input('phone'),
            ]);
        } catch (OrderEditException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Address updated.',
            'data'    => $address,
        ]);
    }
}
