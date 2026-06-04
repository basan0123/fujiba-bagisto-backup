<?php
namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Physical (non-virtual) columns on the `tenants` table.
     *
     * The central `tenants` table here only has the Stancl defaults — `id`,
     * `data` (JSON), `created_at`, `updated_at`. The SaaS columns this model
     * once listed (name, email, plan, status, ssl status, suspended_at, …)
     * were never added as physical columns on this database: the
     * `extend_tenants_for_saas` migration is still Pending and it only adds
     * plan_id, owner_email and the ssl columns — never name/email/plan/status.
     * All 8 existing tenants therefore store
     * name/plan/status INSIDE the `data` JSON blob (Stancl VirtualColumn).
     *
     * Listing those as custom columns made Stancl try to INSERT them as real
     * columns → "Unknown column 'name'", which is what broke `central_create`
     * during signup. Returning only the genuinely-physical columns makes new
     * tenants persist name/plan/status into `data`, exactly like the existing
     * tenants, with zero schema change. Attribute reads ($tenant->status,
     * $tenant->suspended_at, …) keep working — VirtualColumn resolves them from
     * `data`. See GATEWAY-PROVISIONING-FIX-REPORT.md.
     *
     * NOTE: any Eloquent where() on status or the ssl columns against the
     * tenants table is a separate pre-existing bug (those are virtual, not SQL-
     * filterable). It is documented for human review; not introduced here.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
        ];
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isSoftDeleted(): bool
    {
        return $this->deleted_at !== null;
    }
}
