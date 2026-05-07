<?php

namespace Fujiba\OrdersAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('sales.orders.edit');
    }

    public function rules(): array
    {
        return [
            'tracking_number' => 'required|string|max:100',
        ];
    }
}
