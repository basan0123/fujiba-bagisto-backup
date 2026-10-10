<?php

namespace Fujiba\AutoPoster\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokPost extends Model
{
    protected $table = 'autoposter_tiktok_posts';

    protected $fillable = [
        'channel_id', 'queue_id', 'mode', 'media_type', 'privacy_level', 'caption',
        'publish_id', 'status', 'error', 'last_response', 'created_by',
    ];

    protected $casts = ['last_response' => 'array'];
}
