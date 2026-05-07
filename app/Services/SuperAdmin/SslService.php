<?php
namespace App\Services\SuperAdmin;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around certbot + nginx.
 *
 * Real shell-out happens via `sudo` against the certbot/nginx binaries listed
 * in the project's sudoers entry:
 *
 *   www-data ALL=(root) NOPASSWD: /usr/bin/certbot, /usr/sbin/nginx -s reload, /usr/sbin/nginx -t
 *
 * If the sudoers entry is missing, every method returns a structured error
 * instead of crashing the queue. Tenants stay HTTP until SSL is wired.
 *
 * NOTE: this code path is the ONLY place in the app that should ever
 * invoke certbot. Memory says nginx is K's domain — Laravel reads/writes
 * the ssl_status column and can request a cert, but never edits vhosts
 * directly.
 */
class SslService
{
    public const STATUS_NONE     = 'none';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_EXPIRING = 'expiring';
    public const STATUS_FAILED   = 'failed';

    public function generate(Tenant $tenant, string $domain, string $email): array
    {
        if (! $this->sudoersConfigured()) {
            return $this->markFailed($tenant, $domain, 'SSL automation not configured. Ask K to add the sudoers entry for www-data.');
        }

        $cmd = sprintf(
            'sudo /usr/bin/certbot --nginx --non-interactive --agree-tos -m %s -d %s 2>&1',
            escapeshellarg($email),
            escapeshellarg($domain),
        );
        [$exit, $output] = $this->run($cmd);
        if ($exit !== 0) {
            return $this->markFailed($tenant, $domain, "certbot exit={$exit}: " . $this->trimOutput($output));
        }

        // Reload nginx — certbot --nginx already reloaded but explicit doesn't hurt.
        $this->run('sudo /usr/sbin/nginx -s reload 2>&1');

        $expires = $this->readCertExpiry($domain);

        $this->updateStatus($tenant, $domain, self::STATUS_ACTIVE, $expires, 'issued');

        return [
            'ok'        => true,
            'expires'   => $expires?->toDateTimeString(),
            'message'   => 'SSL issued and nginx reloaded.',
        ];
    }

    public function renew(Tenant $tenant, string $domain): array
    {
        if (! $this->sudoersConfigured()) {
            return $this->markFailed($tenant, $domain, 'SSL automation not configured. Ask K to add the sudoers entry for www-data.');
        }

        $cmd = sprintf(
            'sudo /usr/bin/certbot renew --cert-name %s --non-interactive 2>&1',
            escapeshellarg($domain),
        );
        [$exit, $output] = $this->run($cmd);
        if ($exit !== 0) {
            return $this->markFailed($tenant, $domain, "certbot renew exit={$exit}: " . $this->trimOutput($output));
        }

        $this->run('sudo /usr/sbin/nginx -s reload 2>&1');

        $expires = $this->readCertExpiry($domain);
        $this->updateStatus($tenant, $domain, self::STATUS_ACTIVE, $expires, 'renewed');

        return [
            'ok'      => true,
            'expires' => $expires?->toDateTimeString(),
            'message' => 'SSL renewed and nginx reloaded.',
        ];
    }

    /**
     * Recompute ssl_status from the cert file expiry. Used by the daily
     * scheduler so the dashboard stays honest without re-running certbot.
     */
    public function refreshStatus(Tenant $tenant): void
    {
        $domain = $tenant->domains()->first()?->domain;
        if (! $domain) {
            return;
        }
        $expires = $this->readCertExpiry($domain);
        if (! $expires) {
            $tenant->update([
                'ssl_status'           => self::STATUS_NONE,
                'ssl_last_checked_at'  => now(),
            ]);
            return;
        }

        $status = $expires->lt(now()->addDays(30)) ? self::STATUS_EXPIRING : self::STATUS_ACTIVE;
        $tenant->update([
            'ssl_status'          => $status,
            'ssl_expires_at'      => $expires,
            'ssl_last_checked_at' => now(),
        ]);
    }

    public function sudoersConfigured(): bool
    {
        // Cheap probe: -n means non-interactive; sudo will refuse rather than
        // prompt if the entry isn't whitelisted. We don't actually run nginx
        // -t for real here — we only need to know whether sudo is allowed.
        [$exit, ] = $this->run('sudo -n /usr/sbin/nginx -t 2>&1', timeoutSeconds: 5);
        return $exit === 0;
    }

    public function readCertExpiry(string $domain): ?\Carbon\Carbon
    {
        $path = "/etc/letsencrypt/live/{$domain}/fullchain.pem";
        if (! is_readable($path)) {
            return null;
        }
        $pem = @file_get_contents($path);
        if (! $pem) {
            return null;
        }
        $info = @openssl_x509_parse($pem);
        if (! $info || empty($info['validTo_time_t'])) {
            return null;
        }
        return \Carbon\Carbon::createFromTimestamp($info['validTo_time_t']);
    }

    private function updateStatus(Tenant $tenant, string $domain, string $status, ?\Carbon\Carbon $expires, string $action): void
    {
        $tenant->update([
            'ssl_status'          => $status,
            'ssl_expires_at'      => $expires,
            'ssl_last_checked_at' => now(),
        ]);

        $domainRow = DB::table('domains')->where('domain', $domain)->first();
        if ($domainRow) {
            DB::table('ssl_certificates')->updateOrInsert(
                ['domain_id' => $domainRow->id],
                [
                    'domain'       => $domain,
                    'certificate'  => '(managed by certbot)',
                    'private_key'  => '(managed by certbot)',
                    'expires_at'   => $expires,
                    'status'       => $status === self::STATUS_ACTIVE ? 'issued' : 'pending',
                    'updated_at'   => now(),
                    'created_at'   => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }
    }

    private function markFailed(Tenant $tenant, string $domain, string $message): array
    {
        $tenant->update([
            'ssl_status'          => self::STATUS_FAILED,
            'ssl_last_checked_at' => now(),
        ]);
        Log::warning('[SslService] failed', ['domain' => $domain, 'message' => $message]);
        return ['ok' => false, 'message' => $message];
    }

    private function run(string $cmd, int $timeoutSeconds = 600): array
    {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = @proc_open($cmd, $descriptors, $pipes);
        if (! is_resource($proc)) {
            return [127, 'failed to spawn'];
        }
        $start = time();
        $output = '';
        while (true) {
            $status = proc_get_status($proc);
            $output .= stream_get_contents($pipes[1]) ?: '';
            $output .= stream_get_contents($pipes[2]) ?: '';
            if (! $status['running']) {
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($proc);
                return [$exit, $output];
            }
            if ((time() - $start) > $timeoutSeconds) {
                proc_terminate($proc, 9);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);
                return [124, $output . "\n[timeout after {$timeoutSeconds}s]"];
            }
            usleep(100_000);
        }
    }

    private function trimOutput(string $s): string
    {
        $s = trim($s);
        return strlen($s) > 800 ? substr($s, 0, 800) . '…' : $s;
    }
}
