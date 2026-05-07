<?php
namespace App\Http\Middleware;

use App\Services\SuperAdmin\AuditWriter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces, for every authenticated super-admin request:
 *  - IP allowlist (if configured on the user)
 *  - 30 min idle timeout
 *  - 8 hr hard cap on a single super-admin session
 */
class SuperAdminSessionGuard
{
    public const IDLE_SECONDS = 30 * 60;
    public const HARD_CAP_SECONDS = 8 * 3600;

    public function __construct(private AuditWriter $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $sa = Auth::guard('superadmin')->user();
        if (! $sa) {
            return $next($request);
        }

        // IP allowlist
        $list = $sa->ip_allowlist;
        if (! empty($list) && is_array($list) && ! in_array($request->ip(), $list, true)) {
            $this->audit->log($sa, 'session.ip_blocked', null, null, ['ip' => $request->ip()]);
            return $this->forceLogout($request, 'Your IP is no longer permitted for this account.');
        }

        $now = now()->timestamp;
        $started = (int) $request->session()->get('superadmin.session_started_at', 0);
        $lastSeen = (int) $request->session()->get('superadmin.last_seen_at', 0);

        if ($started && ($now - $started) > self::HARD_CAP_SECONDS) {
            $this->audit->log($sa, 'session.hard_cap_expired', null, null, null);
            return $this->forceLogout($request, 'Session expired (8 hour cap). Please log in again.');
        }

        if ($lastSeen && ($now - $lastSeen) > self::IDLE_SECONDS) {
            $this->audit->log($sa, 'session.idle_expired', null, null, null);
            return $this->forceLogout($request, 'Session expired due to inactivity. Please log in again.');
        }

        $request->session()->put('superadmin.last_seen_at', $now);

        return $next($request);
    }

    private function forceLogout(Request $request, string $message): Response
    {
        Auth::guard('superadmin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('superadmin.login')->withErrors(['email' => $message]);
    }
}
