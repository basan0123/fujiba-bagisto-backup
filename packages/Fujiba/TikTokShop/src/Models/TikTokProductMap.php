<?php

namespace Fujiba\TikTokShop\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokProductMap extends Model
{
    protected $table = 'tiktok_product_map';

    protected $fillable = [
        'bagisto_product_id',
        'tiktok_product_id',
        'channel_id',
        'sync_status',
        'sync_error',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];
}
