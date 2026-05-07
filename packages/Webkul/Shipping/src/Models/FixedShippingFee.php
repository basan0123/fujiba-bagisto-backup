<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedShippingFee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'channel_id', 'fee_amount', 'label', 'priority', 'is_active',
    ];

    protected $casts = [
        'fee_amount' => 'float',
        'is_active'  => 'boolean',
    ];

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeByPriority($q)           { return $q->orderBy('priority'); }
    public function scopeForChannel($q, $id)      { return $q->where('channel_id', $id); }
    public function scopeGlobalOrChannel($q, $id) { return $q->where(fn($q) => $q->whereNull('channel_id')->orWhere('channel_id', $id)); }
}
