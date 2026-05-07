<?php

namespace Fujiba\OrdersAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderPaymentConfirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('sales.orders.edit');
    }

    public function rules(): array
    {
        return [
            'confirmed' => 'required|boolean',
            'note'      => 'nullable|string|max:5000',
        ];
    }
}
