<?php

namespace Webkul\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptForm extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    public function prepareForValidation()
    {
        $this->merge([
            'status'                  => $this->status ?? 0,
            'display_outlet_name'     => $this->display_outlet_name ?? 0,
            'show_order_barcode'      => $this->show_order_barcode ?? 0,
            'show_print_confirmation' => $this->show_print_confirmation ?? 0,
            'display_date'            => $this->display_date ?? 0,
            'display_order_id'        => $this->display_order_id ?? 0,
            'display_customer_name'   => $this->display_customer_name ?? 0,
            'display_sub_total'       => $this->display_sub_total ?? 0,
            'display_tax'             => $this->display_tax ?? 0,
            'display_credit_amount'   => $this->display_credit_amount ?? 0,
            'display_change_amount'   => $this->display_change_amount ?? 0,
            'display_cashier_name'    => $this->display_cashier_name ?? 0,
            'display_outlet_address'  => $this->display_outlet_address ?? 0,
            'display_discount'        => $this->display_discount ?? 0,
            'display_logo'            => $this->display_logo ?? 0,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title'                   => ['required', 'string', 'max:255'],
            'status'                  => ['required', 'boolean'],
            'display_outlet_name'     => ['required', 'boolean'],
            'show_order_barcode'      => ['required', 'boolean'],
            'show_print_confirmation' => ['required', 'boolean'],
            'display_date'            => ['required', 'boolean'],
            'display_order_id'        => ['required', 'boolean'],
            'order_id_label'          => ['required', 'string', 'max:255'],
            'display_customer_name'   => ['required', 'boolean'],
            'display_sub_total'       => ['required', 'boolean'],
            'sub_total_label'         => ['required', 'string', 'max:255'],
            'display_tax'             => ['required', 'boolean'],
            'tax_label'               => ['required', 'string', 'max:255'],
            'display_credit_amount'   => ['required', 'boolean'],
            'credit_amount_label'     => ['required', 'string', 'max:255'],
            'display_change_amount'   => ['required', 'boolean'],
            'credit_change_label'     => ['required', 'string', 'max:255'],
            'display_cashier_name'    => ['required', 'boolean'],
            'cashier_label'           => ['required', 'string', 'max:255'],
            'display_outlet_address'  => ['required', 'boolean'],
            'grand_total_label'       => ['required', 'string', 'max:255'],
            'display_discount'        => ['required', 'boolean'],
            'discount_label'          => ['required', 'string', 'max:255'],
            'display_logo'            => ['required', 'boolean'],
            'logo_width'              => ['nullable', 'numeric'],
            'logo_height'             => ['nullable', 'numeric'],
            'logo_alt'                => ['nullable', 'string', 'max:255'],
            'header_content'          => ['nullable', 'string'],
            'footer_content'          => ['nullable', 'string'],
            'logo.*'                  => ['nullable', 'image'],
        ];
    }
}
