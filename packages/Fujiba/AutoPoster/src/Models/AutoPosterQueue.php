<?php

namespace Fujiba\AutoPoster\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutoPosterQueue extends Model
{
    protected $table = 'autoposter_queue';

    protected $fillable = [
        'channel_id',
        'status',
        'format',
        'caption',
        'language_mode',
        'template_id',
        'caption_meta',
        'messenger_destination',
        'messenger_ref',
        'scheduled_at',
        'published_at',
        'facebook_post_id',
        'facebook_permalink',
        'last_api_response',
        'last_error',
        'last_error_at',
        'attempts',
        'next_retry_at',
        'lock_token',
        'locked_at',
        'origin',
        'created_by',
        'idempotency_key',
    ];

    protected $casts = [
        'caption_meta'      => 'array',
        'last_api_response' => 'array',
        'scheduled_at'      => 'datetime',
        'published_at'      => 'datetime',
        'last_error_at'     => 'datetime',
        'next_retry_at'     => 'datetime',
        'locked_at'         => 'datetime',
        'attempts'          => 'integer',
    ];

    public const STATUS_DRAFT      = 'draft';
    public const STATUS_SCHEDULED  = 'scheduled';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PUBLISHED  = 'published';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_CANCELLED  = 'cancelled';

    public const ORIGIN_AUTO   = 'auto';
    public const ORIGIN_MANUAL = 'manual';
    public const ORIGIN_BULK   = 'bulk';

    public function products(): HasMany
    {
        return $this->hasMany(AutoPosterPostProduct::class, 'queue_id')->orderBy('sort_order');
    }

    /**
     * A post that already carries a facebook_post_id must never be sent again,
     * whatever the status column says — see the queue migration's third guard.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED || ! empty($this->facebook_post_id);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED], true);
    }
}
