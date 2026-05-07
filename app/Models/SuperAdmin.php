<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuperAdmin extends Authenticatable
{
    protected $guard = 'superadmin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'mfa_secret',
        'mfa_enabled_at',
        'ip_allowlist',
        'last_login_at',
        'last_login_ip',
        'is_root',
        'force_password_reset',
    ];

    protected $hidden = ['password', 'remember_token', 'mfa_secret'];

    protected $casts = [
        'ip_allowlist'         => 'array',
        'mfa_enabled_at'       => 'datetime',
        'last_login_at'        => 'datetime',
        'is_root'              => 'boolean',
        'force_password_reset' => 'boolean',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(SuperAdminPermission::class);
    }

    public function hasMfaEnabled(): bool
    {
        return ! empty($this->mfa_secret) && $this->mfa_enabled_at !== null;
    }

    public function can($abilities, $arguments = []): bool
    {
        // Single permission key string check — our RBAC layer.
        if (is_string($abilities)) {
            if ($this->is_root) {
                return true;
            }

            $rec = $this->permissions()->where('permission_key', $abilities)->first();

            if ($rec) {
                return (bool) $rec->value;
            }

            return self::defaultPermissionValue($abilities);
        }

        return parent::can($abilities, $arguments);
    }

    public static function defaultPermissionValue(string $key): bool
    {
        return match ($key) {
            'can_create_tenant' => true,
            default             => false,
        };
    }

    public static function knownPermissionKeys(): array
    {
        return [
            'can_create_tenant',
            'can_delete_tenant',
            'can_impersonate',
            'can_run_raw_sql',
            'can_manage_super_admins',
            'can_modify_billing',
        ];
    }
}
