<?php

namespace Fujiba\OrdersAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('sales.orders.edit');
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:delivery,pickup,cod,bank_transfer',
        ];
    }
}
