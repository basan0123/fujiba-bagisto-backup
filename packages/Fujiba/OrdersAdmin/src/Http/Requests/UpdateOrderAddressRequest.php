<?php

namespace Fujiba\OrdersAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('sales.orders.edit');
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'address1' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city'     => 'required|string|max:100',
            'state'    => 'nullable|string|max:100',
            'postcode' => 'required|string|max:20',
            'country'  => 'required|string|size:2',
            'phone'    => 'nullable|string|max:30',
        ];
    }
}
