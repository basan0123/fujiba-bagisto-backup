<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\SslGenerateJob;
use App\Models\Tenant;
use App\Services\SuperAdmin\AuditWriter;
use App\Services\SuperAdmin\SslService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SslController extends Controller
{
    public function __construct(
        private AuditWriter $audit,
        private SslService $ssl,
    ) {}

    public function generate(Tenant $tenant)
    {
        $domain = $tenant->domains()->first()?->domain;
        if (! $domain) {
            return back()->withErrors(['ssl' => 'Tenant has no domain to issue against.']);
        }

        $email = $tenant->owner_email ?? $tenant->email ?? 'admin@snssale.com';

        if (! $this->ssl->sudoersConfigured()) {
            $tenant->update(['ssl_status' => SslService::STATUS_FAILED, 'ssl_last_checked_at' => now()]);
            $this->audit->log(auth('superadmin')->user(), 'ssl.unconfigured', $tenant->id, null, ['domain' => $domain]);
            return back()->withErrors(['ssl' => 'SSL automation not configured. Ask K to add the sudoers entry for www-data.']);
        }

        SslGenerateJob::dispatch($tenant->id, $domain, $email, 'generate');

        $this->audit->log(auth('superadmin')->user(), 'ssl.generate_dispatched', $tenant->id, null, ['domain' => $domain]);

        return back()->with('success', "SSL generate dispatched for {$domain}. Refresh in ~30s for status.");
    }

    public function renew(Tenant $tenant)
    {
        $domain = $tenant->domains()->first()?->domain;
        if (! $domain) {
            return back()->withErrors(['ssl' => 'Tenant has no domain.']);
        }

        if (! $this->ssl->sudoersConfigured()) {
            return back()->withErrors(['ssl' => 'SSL automation not configured. Ask K to add the sudoers entry for www-data.']);
        }

        SslGenerateJob::dispatch($tenant->id, $domain, $tenant->owner_email ?? 'admin@snssale.com', 'renew');

        $this->audit->log(auth('superadmin')->user(), 'ssl.renew_dispatched', $tenant->id, null, ['domain' => $domain]);

        return back()->with('success', "SSL renew dispatched for {$domain}.");
    }

    public function updateDomain(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'new_domain' => 'required|string|max:255|regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i',
        ]);

        $oldDomainRow = $tenant->domains()->first();
        $oldDomain = $oldDomainRow?->domain;

        if (DB::table('domains')->where('domain', $data['new_domain'])->where('tenant_id', '!=', $tenant->id)->exists()) {
            return back()->withErrors(['new_domain' => 'Domain already in use by another tenant.']);
        }

        if ($oldDomainRow) {
            $oldDomainRow->update(['domain' => $data['new_domain']]);
        } else {
            $tenant->domains()->create(['domain' => $data['new_domain']]);
        }

        $tenant->update([
            'ssl_status'     => SslService::STATUS_NONE,
            'ssl_expires_at' => null,
        ]);

        $this->audit->log(auth('superadmin')->user(), 'tenant.domain_updated', $tenant->id, ['domain' => $oldDomain], ['domain' => $data['new_domain']]);

        if ($this->ssl->sudoersConfigured()) {
            SslGenerateJob::dispatch($tenant->id, $data['new_domain'], $tenant->owner_email ?? 'admin@snssale.com', 'generate');
        }

        return back()->with('success', "Domain updated to {$data['new_domain']}." . ($this->ssl->sudoersConfigured() ? ' SSL generate dispatched.' : ' SSL automation not configured.'));
    }
}
