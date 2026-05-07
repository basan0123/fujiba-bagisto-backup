<?php

namespace Fujiba\FacebookCatalog\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookCatalogProductSync extends Model
{
    protected $table = 'facebook_catalog_product_sync';

    protected $fillable = [
        'product_id',
        'channel_id',
        'approved',
        'approved_by',
        'approved_at',
        'sync_status',
        'fb_retailer_id',
        'sync_error',
        'last_payload_hash',
        'last_synced_at',
    ];

    protected $casts = [
        'approved'        => 'boolean',
        'approved_at'     => 'datetime',
        'last_synced_at'  => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED  = 'queued';
    public const STATUS_SYNCED  = 'synced';
    public const STATUS_FAILED  = 'failed';
}
