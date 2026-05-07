<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkBonusRule extends Model
{
    use SoftDeletes;

    public const DISCOUNT_FREE_ITEMS    = 'free_items';
    public const DISCOUNT_PERCENT       = 'percent_off_order';
    public const DISCOUNT_FIXED_PER_TIER = 'fixed_per_tier';

    protected $fillable = [
        'channel_id', 'product_id', 'category_id',
        'buy_qty', 'free_qty',
        'discount_type', 'percent_off', 'fixed_amount', 'min_product_price',
        'max_free_per_order',
        'stackable', 'replaces_lower_tiers', 'block_coupons',
        'priority', 'is_active',
        'starts_at', 'ends_at',
    ];

    protected $casts = [
        'buy_qty'              => 'integer',
        'free_qty'             => 'integer',
        'percent_off'          => 'decimal:2',
        'fixed_amount'         => 'decimal:4',
        'min_product_price'    => 'decimal:4',
        'max_free_per_order'   => 'integer',
        'stackable'            => 'boolean',
        'replaces_lower_tiers' => 'boolean',
        'block_coupons'        => 'boolean',
        'is_active'            => 'boolean',
        'starts_at'            => 'datetime',
        'ends_at'              => 'datetime',
    ];

    protected $attributes = [
        'discount_type'        => self::DISCOUNT_FREE_ITEMS,
        'stackable'            => true,
        'replaces_lower_tiers' => false,
        'block_coupons'        => false,
    ];

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeByPriority($q)           { return $q->orderBy('priority'); }
    public function scopeForChannel($q, $id)      { return $q->where('channel_id', $id); }
    public function scopeGlobalOrChannel($q, $id) { return $q->where(fn($q) => $q->whereNull('channel_id')->orWhere('channel_id', $id)); }
    public function scopeValid($q)                {
        return $q->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                 ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
