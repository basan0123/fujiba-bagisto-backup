<?php

namespace Fujiba\OrdersAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Sales\Models\Order;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('sales.orders.edit');
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'in:' . implode(',', [
                    Order::STATUS_PENDING,
                    Order::STATUS_PENDING_PAYMENT,
                    Order::STATUS_PROCESSING,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_CANCELED,
                    Order::STATUS_CLOSED,
                    Order::STATUS_FRAUD,
                ]),
            ],
            'customer_message' => 'nullable|string|max:5000',
        ];
    }
}
