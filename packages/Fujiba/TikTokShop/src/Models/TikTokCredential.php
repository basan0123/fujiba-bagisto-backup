<?php

namespace Fujiba\TikTokShop\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokCredential extends Model
{
    protected $table = 'tiktok_credentials';

    protected $fillable = [
        'channel_id',
        'tiktok_app_key',
        'tiktok_app_secret',
        'redirect_uri',
        'tiktok_shop_id',
        'tiktok_shop_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'sync_products',
        'sync_orders',
        'sync_stock',
        'last_synced_at',
    ];

    /**
     * Encrypted casts use a random IV per write. Same plaintext encrypts to
     * different ciphertext each save. NEVER use these columns in WHERE clauses
     * (they will never match). Read after first()/get(); query against
     * non-encrypted columns (channel_id, is_active, token_expires_at).
     */
    protected $casts = [
        'tiktok_app_secret' => 'encrypted',
        'access_token'      => 'encrypted',
        'refresh_token'     => 'encrypted',
        'is_active'         => 'boolean',
        'sync_products'     => 'boolean',
        'sync_orders'       => 'boolean',
        'sync_stock'        => 'boolean',
        'token_expires_at'  => 'datetime',
        'last_synced_at'    => 'datetime',
    ];
}
