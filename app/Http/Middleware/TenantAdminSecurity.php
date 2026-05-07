<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantAdminSecurity
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $this->observe($request);
        } catch (\Throwable $e) {
            Log::channel(config('admin_security.log_channel', 'admin_security'))
                ->warning('admin_security observer failure', [
                    'host' => $request->getHost(),
                    'path' => $request->path(),
                    'error' => $e->getMessage(),
                ]);
        }

        return $next($request);
    }

    protected function observe(Request $request): void
    {
        $path = '/' . ltrim($request->path(), '/');
        $prefix = '/' . trim((string) config('admin_security.admin_path_prefix', 'admin'), '/');

        if ($path !== $prefix && ! str_starts_with($path, $prefix . '/')) {
            return;
        }

        if ($this->isWhitelisted($request)) {
            $this->log($request, [
                'decision' => 'whitelist',
                'would_block' => false,
                'reason' => 'static_whitelist',
            ]);
            return;
        }

        $tenant = $this->resolveTenant($request->getHost());

        if (! $tenant) {
            $this->log($request, [
                'decision' => 'no_tenant',
                'would_block' => false,
                'reason' => 'central_or_unknown_host',
            ]);
            return;
        }

        $row = $this->loadSecurity($tenant['id']);

        if (! $row) {
            $this->log($request, [
                'decision' => 'no_config',
                'would_block' => false,
                'reason' => 'tenant_has_no_security_row',
                'tenant_id' => $tenant['id'],
            ]);
            return;
        }

        $eval = $this->evaluate($request, $row);
        $eval['decision'] = 'observed';
        $eval['enabled'] = (bool) $row->enabled;
        $eval['mode'] = $row->security_mode;
        $eval['tenant_id'] = $tenant['id'];
        $this->log($request, $eval);
    }

    protected function isWhitelisted(Request $request): bool
    {
        $patterns = (array) config('admin_security.always_whitelist', []);
        $emergency = (string) config('admin_security.emergency_path', '');

        if ($emergency !== '') {
            $patterns[] = $emergency;
            $patterns[] = $emergency . '/*';
        }

        foreach ($patterns as $p) {
            if ($p !== '' && $request->is($p)) {
                return true;
            }
        }

        return false;
    }

    protected function resolveTenant(string $host): ?array
    {
        $centralDomains = (array) config('tenancy.central_domains', []);
        if (in_array($host, $centralDomains, true)) {
            return null;
        }

        static $cache = [];
        if (array_key_exists($host, $cache)) {
            return $cache[$host];
        }

        $row = DB::connection(config('tenancy.database.central_connection', 'mysql'))
            ->table('domains')
            ->where('domain', $host)
            ->first();

        $cache[$host] = $row ? ['id' => $row->tenant_id] : null;
        return $cache[$host];
    }

    protected function loadSecurity(string $tenantId): ?object
    {
        return DB::connection(config('tenancy.database.central_connection', 'mysql'))
            ->table('tenant_admin_security')
            ->where('tenant_id', $tenantId)
            ->first();
    }

    protected function evaluate(Request $request, object $row): array
    {
        $reasons = [];
        $wouldBlock = false;

        $path = '/' . ltrim($request->path(), '/');
        $prefix = '/' . trim((string) config('admin_security.admin_path_prefix', 'admin'), '/');
        $isDefaultAdmin = ($path === $prefix || str_starts_with($path, $prefix . '/'));

        $secret = $row->secret_path ? trim($row->secret_path, '/') : null;
        $hasSecretMatch = $secret && str_starts_with(ltrim($path, '/'), $secret);

        if ($row->block_default_admin && $isDefaultAdmin && ! $hasSecretMatch) {
            $wouldBlock = true;
            $reasons[] = 'default_admin_blocked';
        }

        if ($secret && ! $hasSecretMatch && ! $row->block_default_admin) {
            $reasons[] = 'secret_path_not_matched_but_not_enforced';
        }

        switch ($row->security_mode) {
            case 'basic_auth':
                $sent = $request->getUser();
                if (! $sent || $sent !== $row->basic_auth_user) {
                    $wouldBlock = true;
                    $reasons[] = 'basic_auth_user_mismatch';
                } elseif ($row->basic_auth_pass_hash
                    && ! password_verify((string) $request->getPassword(), (string) $row->basic_auth_pass_hash)) {
                    $wouldBlock = true;
                    $reasons[] = 'basic_auth_password_mismatch';
                }
                break;

            case 'ip_allowlist':
                $allowed = json_decode((string) $row->allowed_ips, true) ?: [];
                if (! $this->ipMatches($request->ip(), $allowed)) {
                    $wouldBlock = true;
                    $reasons[] = 'ip_not_allowed';
                }
                break;

            case 'none':
            default:
                break;
        }

        return [
            'would_block' => $wouldBlock,
            'reason' => implode(',', $reasons) ?: 'pass',
        ];
    }

    protected function ipMatches(?string $ip, array $allowed): bool
    {
        if (! $ip) {
            return false;
        }
        foreach ($allowed as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') {
                continue;
            }
            if (! str_contains($entry, '/')) {
                if ($ip === $entry) {
                    return true;
                }
                continue;
            }
            [$subnet, $bits] = explode('/', $entry, 2);
            $bits = (int) $bits;
            $ipL = ip2long($ip);
            $subnetL = ip2long($subnet);
            if ($ipL === false || $subnetL === false) {
                continue;
            }
            $mask = $bits === 0 ? 0 : (-1 << (32 - $bits));
            if (($ipL & $mask) === ($subnetL & $mask)) {
                return true;
            }
        }
        return false;
    }

    protected function log(Request $request, array $extra): void
    {
        Log::channel(config('admin_security.log_channel', 'admin_security'))
            ->info('admin_security_observe', array_merge([
                'host' => $request->getHost(),
                'path' => '/' . ltrim($request->path(), '/'),
                'ip' => $request->ip(),
                'method' => $request->getMethod(),
            ], $extra));
    }
}
