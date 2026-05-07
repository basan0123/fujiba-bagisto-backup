<?php

namespace Webkul\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Address;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;

class OutletForm extends FormRequest
{
    /**
     * Regular expression for validating names.
     *
     * Allows only alphabetic characters (A–Z, a–z) and spaces.
     * Disallows numbers, symbols, and any special characters to
     * prevent invalid or malicious input (e.g., HTML or script tags).
     */
    private const NAME_REGEX = '/^[a-zA-Z\s]+$/u';

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    public function prepareForValidation()
    {
        $this->merge([
            'status'   => $this->status ?? 0,
            'name'     => trim($this->name),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name'                 => ['required', 'regex:'.self::NAME_REGEX],
            'email'                => ['required', 'email'],
            'phone'                => ['required', new PhoneNumber],
            'website'              => ['required', 'url'],
            'customer_care_number' => ['required', new PhoneNumber],
            'gst_number'           => ['required', 'max:15'],
            'status'               => ['boolean'],
            'address'              => ['required', new Address],
            'state'                => ['required'],
            'country'              => ['required', 'exists:countries,code'],
            'city'                 => ['required'],
            'postcode'             => ['required', new PostCode],
            'receipt_id'           => ['required', 'exists:pos_receipts,id'],
            'inventory_source_id'  => ['required', 'unique:pos_outlets,inventory_source_id'],
            'low_stock_qty'        => ['required', 'numeric', 'min:0'],
        ];

        if ($this->method() == 'PUT') {
            $rules['inventory_source_id'] = ['required', 'unique:pos_outlets,inventory_source_id,'.$this->route('id')];
        }

        return $rules;
    }
}
