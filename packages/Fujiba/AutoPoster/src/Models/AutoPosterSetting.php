<?php

namespace Fujiba\AutoPoster\Models;

use Illuminate\Database\Eloquent\Model;

class AutoPosterSetting extends Model
{
    protected $table = 'autoposter_settings';

    protected $fillable = [
        'channel_id',
        'auto_posting_enabled',
        'posts_per_day',
        'posting_times',
        'timezone',
        'posting_window_start',
        'posting_window_end',
        'selection_mode',
        'selection_config',
        'products_per_post',
        'repeat_cooldown_days',
        'default_format',
        'default_language',
        'default_template_id',
        'messenger_destination',
        'messenger_ref',
        'auto_retry_enabled',
        'max_retries',
        'last_preflight_at',
        'last_preflight_passed',
        'last_preflight_result',
    ];

    protected $casts = [
        'auto_posting_enabled'  => 'boolean',
        'auto_retry_enabled'    => 'boolean',
        'last_preflight_passed' => 'boolean',
        'posts_per_day'         => 'integer',
        'products_per_post'     => 'integer',
        'repeat_cooldown_days'  => 'integer',
        'max_retries'           => 'integer',
        'posting_times'         => 'array',
        'selection_config'      => 'array',
        'last_preflight_result' => 'array',
        'last_preflight_at'     => 'datetime',
    ];

    public const MODE_RANDOM                = 'random';
    public const MODE_NEW_ARRIVALS          = 'new_arrivals';
    public const MODE_CATEGORY              = 'category';
    public const MODE_PRODUCT_SET           = 'product_set';
    public const MODE_NEVER_POSTED          = 'never_posted';
    public const MODE_LEAST_RECENTLY_POSTED = 'least_recently_posted';
    public const MODE_MANUAL                = 'manual';

    public const FORMAT_MULTI_PHOTO  = 'multi_photo';
    public const FORMAT_SINGLE_IMAGE = 'single_image';
    public const FORMAT_CAROUSEL_AD  = 'carousel_ad'; // reserved — paid path, not built

    /**
     * Defaults for a channel that has never been configured. Mirrors the column
     * defaults so the admin UI can render a sensible form before the first save.
     */
    public static function defaultsFor(int $channelId): self
    {
        return new self([
            'channel_id'            => $channelId,
            'auto_posting_enabled'  => false,
            'posts_per_day'         => 5,
            'posting_times'         => null,
            'timezone'              => 'Asia/Tokyo',
            'posting_window_start'  => '09:00:00',
            'posting_window_end'    => '21:30:00',
            'selection_mode'        => self::MODE_RANDOM,
            'products_per_post'     => 5,
            'repeat_cooldown_days'  => 30,
            'default_format'        => self::FORMAT_MULTI_PHOTO,
            'default_language'      => 'en_ja_my',
            'auto_retry_enabled'    => true,
            'max_retries'           => 3,
        ]);
    }
}
