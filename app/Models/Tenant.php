<?php
namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'plan',
            'plan_id',
            'owner_email',
            'status',
            'ssl_status',
            'ssl_expires_at',
            'ssl_last_checked_at',
            'suspended_at',
            'deleted_at',
            'created_at',
            'updated_at',
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
