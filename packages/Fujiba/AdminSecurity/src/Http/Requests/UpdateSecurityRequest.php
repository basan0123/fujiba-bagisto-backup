<?php

namespace Fujiba\AdminSecurity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSecurityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    public function rules(): array
    {
        return [
            'secret_path' => ['nullable', 'string', 'min:8', 'max:64', 'regex:/^[A-Za-z0-9\-]+$/'],
            'security_mode' => ['required', Rule::in(['none', 'basic_auth', 'ip_allowlist'])],
            'basic_auth_user' => ['nullable', 'string', 'max:128', 'required_if:security_mode,basic_auth'],
            'basic_auth_pass_new' => ['nullable', 'string', 'min:8', 'max:128'],
            'allowed_ips_text' => ['nullable', 'string', 'max:4000'],
            'block_default_admin' => ['sometimes', 'boolean'],
            'enabled' => ['sometimes', 'boolean'],
            'confirm_enable' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'secret_path.regex' => 'Secret path must be alphanumeric with dashes only.',
            'secret_path.min' => 'Secret path must be at least 8 characters.',
            'basic_auth_user.required_if' => 'Basic auth username is required when basic auth mode is selected.',
        ];
    }

    public function parsedAllowedIps(): array
    {
        $raw = (string) $this->input('allowed_ips_text', '');
        if (trim($raw) === '') {
            return [];
        }
        $items = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_map('trim', $items)));
    }
}
