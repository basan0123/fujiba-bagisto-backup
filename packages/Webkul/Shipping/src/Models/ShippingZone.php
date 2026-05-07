<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'channel_id', 'zone_name', 'zone_code', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function prefectures(): HasMany
    {
        return $this->hasMany(ShippingZonePrefecture::class, 'zone_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingZoneRate::class, 'zone_id')->orderBy('qty_from');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeGlobalOrChannel($q, $channelId)
    {
        return $q->where(fn($q) => $q->whereNull('channel_id')->orWhere('channel_id', $channelId));
    }

    public function scopeForChannel($q, $channelId)
    {
        return $q->where('channel_id', $channelId);
    }
}
