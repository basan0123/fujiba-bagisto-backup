<?php

namespace Fujiba\AutoPoster\Models;

use Illuminate\Database\Eloquent\Model;

class AutoPosterProductHistory extends Model
{
    protected $table = 'autoposter_product_history';

    protected $fillable = [
        'product_id',
        'channel_id',
        'last_posted_at',
        'post_count',
        'facebook_post_id',
        'status',
        'is_excluded',
    ];

    protected $casts = [
        'last_posted_at' => 'datetime',
        'post_count'     => 'integer',
        'is_excluded'    => 'boolean',
    ];
}
