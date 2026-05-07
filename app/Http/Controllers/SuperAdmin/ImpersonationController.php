<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\TenantImpersonationStarted;
use App\Models\SuperAdminImpersonationLog;
use App\Models\Tenant;
use App\Services\SuperAdmin\AuditWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ImpersonationController extends Controller
{
    public function __construct(private AuditWriter $audit) {}

    public function start(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'reason' => 'required|string|min:8|max:500',
        ]);

        $sa = Auth::guard('superadmin')->user();
        if (! $sa) {
            return redirect()->route('superadmin.login');
        }

        $domain = $tenant->domains()->first()?->domain;
        if (! $domain) {
            return back()->withErrors(['impersonate' => 'Tenant has no domain to impersonate into.']);
        }

        // Resolve target admin: prefer tenant.email, else first active admin.
        $tenantDb = $tenant->database()->getName();
        $targetAdmin = DB::selectOne(
            "SELECT id, email FROM `{$tenantDb}`.admins WHERE status = 1 ORDER BY id ASC LIMIT 1"
        );
        if (! $targetAdmin) {
            return back()->withErrors(['impersonate' => 'No active admin found in tenant DB to impersonate.']);
        }

        // Persist impersonation log row first so we have an id to revoke later.
        $log = SuperAdminImpersonationLog::create([
            'super_admin_id'     => $sa->id,
            'tenant_id'          => $tenant->id,
            'target_admin_email' => $targetAdmin->email,
            'reason'             => $data['reason'],
            'ip'                 => $request->ip(),
            'user_agent'         => substr((string) $request->userAgent(), 0, 512),
            'started_at'         => now(),
        ]);

        $this->audit->log($sa, 'impersonation.started', $tenant->id, null, [
            'log_id' => $log->id,
            'target' => $targetAdmin->email,
            'reason' => $data['reason'],
        ]);

        // Email tenant owner asynchronously (best-effort; don't block on failure).
        $ownerEmail = $tenant->owner_email ?? $tenant->email;
        if ($ownerEmail) {
            try {
                Mail::to($ownerEmail)->send(new TenantImpersonationStarted(
                    tenantId:        $tenant->id,
                    domain:          $domain,
                    superAdminEmail: $sa->email,
                    reason:          $data['reason'],
                    startedAt:       $log->started_at,
                ));
            } catch (\Throwable $e) {
                Log::warning('[Impersonation] mail failed', ['err' => $e->getMessage()]);
            }
        }

        // Generate signed one-time URL targeting the tenant's accept endpoint.
        // The signature carries: log id, target admin id, expiry. We pass it
        // via a 5-minute signed URL so we don't need a new DB token table.
        $acceptUrl = URL::temporarySignedRoute(
            'admin.impersonate.accept',
            now()->addMinutes(5),
            [
                'log'   => $log->id,
                'admin' => $targetAdmin->id,
                'sa'    => $sa->id,
            ],
            absolute: false,
        );

        // Switch the host: signed route was generated against the central
        // app URL, but the accept endpoint must be hit on the tenant domain
        // so its tenancy middleware initializes correctly.
        $parsed = parse_url($acceptUrl);
        $finalUrl = 'http://' . $domain . ($parsed['path'] ?? '') . '?' . ($parsed['query'] ?? '');

        return redirect()->away($finalUrl);
    }

    public function stop(Request $request)
    {
        $sa = Auth::guard('superadmin')->user();
        if (! $sa) {
            return redirect()->route('superadmin.login');
        }

        $log = SuperAdminImpersonationLog::where('super_admin_id', $sa->id)
            ->whereNull('ended_at')
            ->latest('id')
            ->first();
        if ($log) {
            $log->update(['ended_at' => now()]);
            $this->audit->log($sa, 'impersonation.stopped', $log->tenant_id, null, ['log_id' => $log->id]);
        }

        return redirect()->route('superadmin.dashboard')->with('success', 'Impersonation ended.');
    }
}
