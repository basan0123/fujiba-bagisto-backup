<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCertificate extends Model
{
    protected $fillable = ['domain_id', 'domain', 'certificate', 'private_key', 'certificate_chain', 'issuer', 'issued_at', 'expires_at', 'auto_renew', 'renewal_attempted_at', 'status'];
    
    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'renewal_attempted_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function scopeExpiringSoon($query)
    {
        return $query->where('expires_at', '<=', now()->addDays(30))->where('status', 'issued');
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return $this->expires_at->diffInDays(now());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at < now();
    }
}
