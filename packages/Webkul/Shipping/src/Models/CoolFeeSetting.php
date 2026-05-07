<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoolFeeSetting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'channel_id', 'cool_box_fee', 'frozen_box_fee',
        'cool_enabled', 'frozen_enabled', 'fee_per_item',
        'max_fee_cap', 'priority', 'is_active',
    ];

    protected $casts = [
        'cool_box_fee'   => 'float',
        'frozen_box_fee' => 'float',
        'max_fee_cap'    => 'float',
        'cool_enabled'   => 'boolean',
        'frozen_enabled' => 'boolean',
        'fee_per_item'   => 'boolean',
        'is_active'      => 'boolean',
    ];

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeByPriority($q)           { return $q->orderBy('priority'); }
    public function scopeForChannel($q, $id)      { return $q->where('channel_id', $id); }
    public function scopeGlobalOrChannel($q, $id) { return $q->where(fn($q) => $q->whereNull('channel_id')->orWhere('channel_id', $id)); }
}
