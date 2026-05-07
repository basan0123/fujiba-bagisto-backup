<?php

namespace Webkul\Pos\Http\Requests;

use App\Rules\RelaxedImage;
use Illuminate\Foundation\Http\FormRequest;

class UserForm extends FormRequest
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
            'firstname' => trim($this->firstname),
            'lastname'  => trim($this->lastname),
            'status'    => $this->status ?? 0,
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
            'outlet_id' => ['required', 'exists:pos_outlets,id'],
            'username'  => ['required', 'alpha_dash', 'unique:pos_users,username'],
            'email'     => ['email', 'unique:pos_users,email'],
            'password'  => ['required', 'confirmed', 'min:6'],
            'firstname' => ['required', 'regex:'.self::NAME_REGEX],
            'lastname'  => ['required', 'regex:'.self::NAME_REGEX],
            'status'    => ['boolean'],
            'image.*'   => ['nullable', new RelaxedImage],
        ];

        if ($this->method() == 'PUT') {
            $rules['username'] = ['required', 'alpha_dash', 'unique:pos_users,username,'.$this->route('id')];
            $rules['email'] = ['email', 'unique:pos_users,email,'.$this->route('id')];
            $rules['password'] = ['confirmed', 'min:6'];
        }

        return $rules;
    }
}
