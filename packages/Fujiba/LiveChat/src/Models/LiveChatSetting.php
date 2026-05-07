<?php

namespace Fujiba\LiveChat\Models;

use Illuminate\Database\Eloquent\Model;

class LiveChatSetting extends Model
{
    protected $connection = 'mysql';
    protected $table = 'livechat_settings';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'channel_code',
        'chatpion_url_1',
        'chatpion_url_2',
        'chatpion_url_3',
        'chatpion_api_key',
        'chatpion_user_id',
        'fb_page_id',
        'fb_bot_id',
        'livechat_enabled',
        'auto_login_enabled',
        'order_notifications_enabled',
        'customer_chat_plugin_enabled',
        'auto_flow_enabled',
        'auto_flow_postback_id',
        'auto_flow_lookback_minutes',
        'auto_flow_grace_minutes',
        'abandoned_cart_enabled',
        'abandoned_cart_delay_minutes',
        'abandoned_cart_message_en',
        'abandoned_cart_message_ja',
        'abandoned_cart_message_my',
        'active_server_index',
        'last_health_check',
        'server_health',
    ];

    protected $casts = [
        'livechat_enabled'              => 'boolean',
        'auto_login_enabled'            => 'boolean',
        'order_notifications_enabled'   => 'boolean',
        'customer_chat_plugin_enabled'  => 'boolean',
        'auto_flow_enabled'             => 'boolean',
        'auto_flow_lookback_minutes'    => 'integer',
        'auto_flow_grace_minutes'       => 'integer',
        'abandoned_cart_enabled'        => 'boolean',
        'abandoned_cart_delay_minutes'  => 'integer',
        'last_health_check'             => 'datetime',
    ];
}
