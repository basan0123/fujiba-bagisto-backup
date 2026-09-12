<?php

namespace Fujiba\AutoPoster\Models;

use Illuminate\Database\Eloquent\Model;

class AutoPosterTemplate extends Model
{
    protected $table = 'autoposter_templates';

    protected $fillable = [
        'channel_id',
        'name',
        'language_mode',
        'body_en',
        'body_ja',
        'body_my',
        'include_emojis',
        'include_hashtags',
        'include_campaign_text',
        'include_new_arrival_text',
        'campaign_text',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'include_emojis'           => 'boolean',
        'include_hashtags'         => 'boolean',
        'include_campaign_text'    => 'boolean',
        'include_new_arrival_text' => 'boolean',
        'is_default'               => 'boolean',
        'is_active'                => 'boolean',
    ];
}
