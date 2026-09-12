<?php

namespace Fujiba\AutoPoster\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoPosterPostProduct extends Model
{
    protected $table = 'autoposter_post_products';

    protected $fillable = [
        'queue_id',
        'product_id',
        'sort_order',
        'image_url',
        'product_url',
        'override_image_url',
        'fb_media_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(AutoPosterQueue::class, 'queue_id');
    }

    /**
     * The photo actually sent to Facebook: an admin override wins over the
     * snapshot taken when the post was built.
     */
    public function effectiveImageUrl(): ?string
    {
        return $this->override_image_url ?: $this->image_url;
    }
}
