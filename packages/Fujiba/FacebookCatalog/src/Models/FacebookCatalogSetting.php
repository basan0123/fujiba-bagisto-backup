<?php

namespace Fujiba\FacebookCatalog\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookCatalogSetting extends Model
{
    protected $table = 'facebook_catalog_settings';

    protected $fillable = [
        'channel_id',
        'catalog_id',
        'business_id',
        'page_id',
        'pixel_id',
        'access_token',
        'default_currency',
        'default_locale',
        'is_active',
        'auto_sync_price_stock',
        'last_synced_at',
        'last_sync_error',
    ];

    /**
     * access_token uses an encrypted cast — random IV per write means the
     * ciphertext changes on every save, so do NOT use access_token in WHERE.
     * Query against channel_id / is_active only.
     */
    protected $casts = [
        'access_token'          => 'encrypted',
        'is_active'             => 'boolean',
        'auto_sync_price_stock' => 'boolean',
        'last_synced_at'        => 'datetime',
    ];
}
