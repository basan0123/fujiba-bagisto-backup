<?php

namespace Webkul\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankForm extends FormRequest
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
            'status' => $this->status ?? 0,
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
            'name'     => ['required', 'max:50', 'regex:'.self::NAME_REGEX],
            'email'    => ['required', 'email'],
            'phone'    => ['required', 'numeric'],
            'status'   => ['boolean'],
            'address'  => ['required', 'max:250'],
            'agent_id' => ['required', 'exists:pos_users,id'],
        ];
    }
}
