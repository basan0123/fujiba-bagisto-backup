<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAdminPermission extends Model
{
    protected $table = 'super_admin_permissions';

    protected $fillable = ['super_admin_id', 'permission_key', 'value'];

    protected $casts = ['value' => 'boolean'];

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }
}
