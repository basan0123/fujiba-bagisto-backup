<?php

namespace Fujiba\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerChannelToken extends Model
{
    public const PROVIDER_MESSENGER = 'messenger';
    public const PROVIDER_LINE      = 'line';
    public const PROVIDER_WHATSAPP  = 'whatsapp';

    protected $table = 'customer_channel_tokens';

    protected $fillable = [
        'customer_id',
        'provider',
        'page_id',
        'token',
        'meta',
        'is_active',
        'verified_at',
    ];

    protected $casts = [
        'meta'        => 'array',
        'is_active'   => 'bool',
        'verified_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Customer\Models\Customer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProvider($query, string $provider, ?string $pageId = null)
    {
        $query->where('provider', $provider);

        if ($pageId !== null) {
            $query->where('page_id', $pageId);
        }

        return $query;
    }
}
