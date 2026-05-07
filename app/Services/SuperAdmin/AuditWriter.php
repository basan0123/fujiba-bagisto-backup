<?php
namespace App\Services\SuperAdmin;

use App\Models\SuperAdmin;
use App\Models\SuperAdminAuditLog;
use Illuminate\Support\Facades\Request;

class AuditWriter
{
    public function log(
        ?SuperAdmin $sa,
        string $action,
        ?string $targetTenantId = null,
        ?array $before = null,
        ?array $after = null,
    ): SuperAdminAuditLog {
        $req = app('request');

        return SuperAdminAuditLog::create([
            'super_admin_id'  => $sa?->id,
            'actor_email'     => $sa?->email,
            'action'          => $action,
            'target_tenant_id'=> $targetTenantId,
            'before'          => $before,
            'after'           => $after,
            'ip'              => $req?->ip(),
            'user_agent'      => substr((string) $req?->userAgent(), 0, 512),
            'created_at'      => now(),
        ]);
    }
}
