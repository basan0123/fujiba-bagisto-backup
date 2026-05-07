<?php

namespace Fujiba\AdminSecurity\Http\Controllers;

use Fujiba\AdminSecurity\Http\Requests\UpdateSecurityRequest;
use Fujiba\AdminSecurity\Models\TenantAdminSecurity;
use Fujiba\AdminSecurity\Models\TenantAdminSecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
{
    public function index()
    {
        $tenantId = $this->currentTenantId();
        $row = $tenantId ? TenantAdminSecurity::where('tenant_id', $tenantId)->first() : null;

        return view('admin-security::settings', [
            'tenantId' => $tenantId,
            'row' => $row,
            'allowedIpsText' => $row && is_array($row->allowed_ips)
                ? implode("\n", $row->allowed_ips)
                : '',
        ]);
    }

    public function test(Request $request)
    {
        $request->validate([
            'secret_path' => ['nullable', 'string', 'min:8', 'max:64', 'regex:/^[A-Za-z0-9\-]+$/'],
            'security_mode' => ['required', \Illuminate\Validation\Rule::in(['none', 'basic_auth', 'ip_allowlist'])],
            'allowed_ips_text' => ['nullable', 'string', 'max:4000'],
        ]);

        $proposed = $this->buildProposed($request);
        $result = $this->simulate($request, $proposed);

        return response()->json($result);
    }

    public function update(UpdateSecurityRequest $request)
    {
        $tenantId = $this->currentTenantId();
        if (! $tenantId) {
            return back()->withErrors(['tenant' => 'Tenant context unavailable.']);
        }

        $existing = TenantAdminSecurity::where('tenant_id', $tenantId)->first();

        $proposed = $this->buildProposed($request, $existing);

        if (! empty($proposed['enabled'])) {
            $sim = $this->simulate($request, $proposed);
            if ($sim['would_block_current_admin']) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'enabled' => 'Cannot enable enforcement: simulated request from your current session would be blocked. Reason: ' . $sim['reason'],
                    ]);
            }
        }

        $before = $existing ? $existing->only([
            'secret_path', 'security_mode', 'basic_auth_user', 'allowed_ips',
            'block_default_admin', 'enabled',
        ]) : null;

        $row = TenantAdminSecurity::updateOrCreate(
            ['tenant_id' => $tenantId],
            array_merge($proposed, [
                'updated_by' => auth()->guard('admin')->user()?->email,
            ])
        );

        TenantAdminSecurityAudit::create([
            'tenant_id' => $tenantId,
            'actor_email' => auth()->guard('admin')->user()?->email,
            'actor_ip' => $request->ip(),
            'action' => $existing ? 'update' : 'create',
            'before_state' => $before,
            'after_state' => $row->only([
                'secret_path', 'security_mode', 'basic_auth_user', 'allowed_ips',
                'block_default_admin', 'enabled',
            ]),
            'note' => $request->input('note'),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Admin security settings saved. Phase 1 is observation-only — enforcement will not take effect until Phase 3.');
    }

    protected function currentTenantId(): ?string
    {
        try {
            $t = function_exists('tenant') ? tenant() : null;
            return $t?->id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildProposed(Request $request, ?TenantAdminSecurity $existing = null): array
    {
        $allowed = [];
        $raw = (string) $request->input('allowed_ips_text', '');
        if (trim($raw) !== '') {
            $allowed = array_values(array_unique(array_filter(array_map(
                'trim',
                preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY)
            ))));
        }

        $passHash = $existing?->basic_auth_pass_hash;
        $newPass = (string) $request->input('basic_auth_pass_new', '');
        if ($newPass !== '') {
            $passHash = Hash::make($newPass);
        }

        return [
            'secret_path' => $request->input('secret_path') ?: null,
            'security_mode' => $request->input('security_mode', 'none'),
            'basic_auth_user' => $request->input('basic_auth_user') ?: null,
            'basic_auth_pass_hash' => $passHash,
            'allowed_ips' => $allowed,
            'block_default_admin' => (bool) $request->input('block_default_admin', false),
            'enabled' => (bool) $request->input('enabled', false),
        ];
    }

    protected function simulate(Request $request, array $proposed): array
    {
        $reasons = [];
        $wouldBlock = false;

        $path = '/' . trim((string) config('admin_security.admin_path_prefix', 'admin'), '/');
        $secret = $proposed['secret_path'] ? trim($proposed['secret_path'], '/') : null;
        $hasSecretMatch = $secret && str_contains($request->path(), $secret);

        if (! empty($proposed['block_default_admin']) && ! $hasSecretMatch) {
            $wouldBlock = true;
            $reasons[] = 'default_admin_blocked';
        }

        if ($proposed['security_mode'] === 'ip_allowlist') {
            $ip = $request->ip();
            $ok = $this->ipMatches($ip, (array) $proposed['allowed_ips']);
            if (! $ok) {
                $wouldBlock = true;
                $reasons[] = 'your_ip_' . $ip . '_not_in_allowlist';
            }
        }

        if ($proposed['security_mode'] === 'basic_auth') {
            $reasons[] = 'basic_auth_will_challenge_browser';
        }

        return [
            'would_block_current_admin' => $wouldBlock,
            'reason' => implode(',', $reasons) ?: 'pass',
            'observed_ip' => $request->ip(),
        ];
    }

    protected function ipMatches(?string $ip, array $allowed): bool
    {
        if (! $ip) return false;
        foreach ($allowed as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') continue;
            if (! str_contains($entry, '/')) {
                if ($ip === $entry) return true;
                continue;
            }
            [$subnet, $bits] = explode('/', $entry, 2);
            $bits = (int) $bits;
            $ipL = ip2long($ip);
            $sL = ip2long($subnet);
            if ($ipL === false || $sL === false) continue;
            $mask = $bits === 0 ? 0 : (-1 << (32 - $bits));
            if (($ipL & $mask) === ($sL & $mask)) return true;
        }
        return false;
    }
}
