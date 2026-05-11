<?php

namespace Fujiba\OrdersAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('sales.orders.edit');
    }

    public function rules(): array
    {
        return [
            // `cash` is the admin-only walk-in shop method (no COD surcharge —
            // that belongs only to Sagawa's cashondelivery).
            'method' => 'required|string|in:cashondelivery,moneytransfer,cash',
        ];
    }
}
