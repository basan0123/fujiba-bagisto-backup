<?php

namespace Fujiba\TikTokShop\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokOrderMap extends Model
{
    protected $table = 'tiktok_order_map';

    protected $fillable = [
        'tiktok_order_id',
        'bagisto_order_id',
        'channel_id',
        'import_status',
        'import_error',
    ];
}
