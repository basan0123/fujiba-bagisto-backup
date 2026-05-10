<?php

namespace Fujiba\AuthGateway\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Force JSON 422 — gateway is API-only, never redirect to a web form.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'error'   => 'validation_failed',
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors()->toArray(),
        ], 422));
    }

    public function rules(): array
    {
        return [
            'email'      => ['required', 'email:filter', 'max:190'],
            'password'   => [
                'required',
                'string',
                'min:8',
                'max:200',
                // mixed case + at least one digit
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
            ],
            'store_name' => ['required', 'string', 'min:2', 'max:120'],
            'name'       => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Password must include upper, lower, and digit.',
            'password.min'   => 'Password must be at least 8 characters.',
        ];
    }
}
