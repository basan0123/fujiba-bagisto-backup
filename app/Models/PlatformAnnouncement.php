<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PlatformAnnouncement extends Model
{
    protected $table = 'platform_announcements';

    protected $fillable = [
        'message',
        'severity',
        'show_to_tenant_admins',
        'show_to_super_admins',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $casts = [
        'show_to_tenant_admins' => 'boolean',
        'show_to_super_admins'  => 'boolean',
        'starts_at'             => 'datetime',
        'ends_at'               => 'datetime',
    ];

    public function scopeActive(Builder $q): Builder
    {
        $now = now();

        return $q->where(function ($q1) use ($now) {
                $q1->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })->where(function ($q2) use ($now) {
                $q2->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }
}
