<?php

namespace Fujiba\OrdersAdmin\Services;

use Fujiba\OrdersAdmin\Exceptions\OrderEditException;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;

/**
 * Updates an order's SHIPPING address. Shared by the admin REST controller
 * and the POS GraphQL mutation so the name-split + address-join rules stay
 * identical across both.
 */
class OrderAddressUpdater
{
    /**
     * @param  array{name:string,address1:string,address2?:?string,city:string,state?:?string,postcode:string,country:string,phone?:?string}  $data
     * @return OrderAddress  the fresh address row
     *
     * @throws OrderEditException when the order has no shipping address
     */
    public function update(Order $order, array $data): OrderAddress
    {
        $address = $order->addresses()
            ->where('address_type', OrderAddress::ADDRESS_TYPE_SHIPPING)
            ->first();

        if (! $address) {
            throw new OrderEditException('Order has no shipping address.');
        }

        // Split name on the LAST whitespace so a 3-word name keeps the surname
        // intact and middle words ride with the first name (e.g.
        // "Moe Su Hlaing" → first="Moe Su", last="Hlaing"). Mirrors the
        // canonical split in LiveChatController::linkCustomer().
        $name = preg_replace('/\s+/u', ' ', trim((string) ($data['name'] ?? '')));
        $cut  = mb_strrpos($name, ' ');
        if ($cut !== false) {
            $firstName = mb_substr($name, 0, $cut);
            $lastName  = mb_substr($name, $cut + 1);
        } else {
            $firstName = $name;
            $lastName  = '';
        }

        // Combine address1 + address2 into the single `address` text column
        // separated by newline (the convention Bagisto uses internally).
        $addressLines = trim((string) ($data['address1'] ?? ''));
        if ($extra = trim((string) ($data['address2'] ?? ''))) {
            $addressLines .= "\n".$extra;
        }

        $address->update([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'address'    => $addressLines,
            'city'       => $data['city'] ?? $address->city,
            'state'      => $data['state'] ?? '',
            'postcode'   => $data['postcode'] ?? $address->postcode,
            'country'    => $data['country'] ?? $address->country,
            'phone'      => $data['phone'] ?? $address->phone,
        ]);

        return $address->fresh();
    }
}
