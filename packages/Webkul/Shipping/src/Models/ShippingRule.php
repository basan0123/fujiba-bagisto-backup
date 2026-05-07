<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'channel_id', 'rule_type', 'name', 'description',
        'conditions', 'actions', 'priority', 'is_active',
        'starts_at', 'ends_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions'    => 'array',
        'is_active'  => 'boolean',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
    ];

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeByPriority($q)           { return $q->orderBy('priority'); }
    public function scopeForChannel($q, $id)      { return $q->where('channel_id', $id); }
    public function scopeGlobalOrChannel($q, $id) { return $q->where(fn($q) => $q->whereNull('channel_id')->orWhere('channel_id', $id)); }
    public function scopeOfType($q, string $type) { return $q->where('rule_type', $type); }
}
