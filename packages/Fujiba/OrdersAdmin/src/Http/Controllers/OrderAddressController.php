<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Http\Requests\UpdateOrderAddressRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Repositories\OrderRepository;

class OrderAddressController extends Controller
{
    public function __construct(protected OrderRepository $orderRepository) {}

    public function update(UpdateOrderAddressRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        $address = $order->addresses()
            ->where('address_type', OrderAddress::ADDRESS_TYPE_SHIPPING)
            ->first();

        if (! $address) {
            return response()->json(['message' => 'Order has no shipping address.'], 422);
        }

        // The `addresses` table stores name as first_name/last_name. Split on
        // the LAST whitespace so a 3-word name keeps the surname intact and
        // middle words ride with the first name (e.g. "Moe Su Hlaing" →
        // first="Moe Su", last="Hlaing"). Mirrors the canonical split in
        // LiveChatController::linkCustomer().
        $name = trim((string) $request->validated('name'));
        $name = preg_replace('/\s+/u', ' ', $name);
        $cut = mb_strrpos($name, ' ');
        if ($cut !== false) {
            $firstName = mb_substr($name, 0, $cut);
            $lastName  = mb_substr($name, $cut + 1);
        } else {
            $firstName = $name;
            $lastName  = '';
        }

        // Combine address1 + address2 into the single `address` text column
        // separated by newline (the same convention Bagisto uses internally).
        $addressLines = trim($request->validated('address1', ''));
        if ($extra = trim((string) $request->input('address2', ''))) {
            $addressLines .= "\n" . $extra;
        }

        $address->update([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'address'    => $addressLines,
            'city'       => $request->validated('city'),
            'state'      => $request->validated('state', ''),
            'postcode'   => $request->validated('postcode'),
            'country'    => $request->validated('country'),
            'phone'      => $request->input('phone', $address->phone),
        ]);

        return response()->json([
            'message' => 'Address updated.',
            'data'    => $address->fresh(),
        ]);
    }
}
