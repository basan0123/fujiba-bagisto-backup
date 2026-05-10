<?php

namespace Fujiba\AuthGateway\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
            'email'    => ['required', 'email:filter', 'max:190'],
            'password' => ['required', 'string', 'min:1', 'max:200'],
        ];
    }
}
