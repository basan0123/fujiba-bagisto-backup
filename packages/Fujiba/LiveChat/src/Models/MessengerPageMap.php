<?php

namespace Fujiba\LiveChat\Models;

use Illuminate\Database\Eloquent\Model;

class MessengerPageMap extends Model
{
    protected $connection = 'mysql';
    protected $table = 'messenger_page_map';

    protected $fillable = [
        'tenant_id',
        'facebook_page_id',
        'channel_id',
        'channel_code',
        'tenant_db',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
