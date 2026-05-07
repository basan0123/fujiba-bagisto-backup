<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductShippingExclusion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'channel_id', 'product_id', 'category_id',
        'exclude_type', 'reason', 'priority', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeByPriority($q)           { return $q->orderBy('priority'); }
    public function scopeForChannel($q, $id)      { return $q->where('channel_id', $id); }
    public function scopeGlobalOrChannel($q, $id) { return $q->where(fn($q) => $q->whereNull('channel_id')->orWhere('channel_id', $id)); }
    public function scopeForProduct($q, $id)      { return $q->where('product_id', $id); }
    public function scopeForCategory($q, $id)     { return $q->where('category_id', $id); }
}
