<?php

namespace Fujiba\AutoPoster\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokAccount extends Model
{
    protected $table = 'autoposter_tiktok_accounts';

    protected $fillable = [
        'channel_id', 'open_id', 'display_name', 'avatar_url',
        'access_token', 'refresh_token', 'scope',
        'access_expires_at', 'refresh_expires_at',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $casts = [
        'access_token'       => 'encrypted',
        'refresh_token'      => 'encrypted',
        'access_expires_at'  => 'datetime',
        'refresh_expires_at' => 'datetime',
    ];
}
