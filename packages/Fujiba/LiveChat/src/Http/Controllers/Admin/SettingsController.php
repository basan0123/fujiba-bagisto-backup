<?php

namespace Fujiba\LiveChat\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Fujiba\LiveChat\Models\LiveChatSetting;
use Fujiba\LiveChat\Models\MessengerPageMap;
use Fujiba\LiveChat\Services\ChatpionFailoverService;

class SettingsController extends Controller
{
    public function index()
    {
        $tenantId = tenant('id') ?? auth()->user()->tenant_id ?? '';
        $settings = LiveChatSetting::where('tenant_id', $tenantId)->get();
        $channels = core()->getAllChannels();
        return view('fujiba_livechat::admin.settings.tenant', compact('settings', 'channels'));
    }

    public function show($channelId)
    {
        $tenantId = tenant('id') ?? auth()->user()->tenant_id ?? '';
        $setting  = LiveChatSetting::firstOrNew([
            'tenant_id'  => $tenantId,
            'channel_id' => $channelId,
        ]);
        $channel  = core()->getAllChannels()->firstWhere('id', $channelId);
        return view('fujiba_livechat::admin.settings.form', compact('setting', 'channel'));
    }

    public function save(Request $request, $channelId)
    {
        $tenantId = tenant('id') ?? auth()->user()->tenant_id ?? '';
        $validated = $request->validate([
            'chatpion_url_1'                 => 'required|url',
            'chatpion_url_2'                 => 'nullable|url',
            'chatpion_url_3'                 => 'nullable|url',
            'chatpion_api_key'               => 'required|string',
            'chatpion_user_id'               => 'nullable|string',
            'fb_page_id'                     => 'required|string',
            'fb_bot_id'                      => 'required|string',
            'livechat_enabled'               => 'boolean',
            'auto_login_enabled'             => 'boolean',
            'order_notifications_enabled'    => 'boolean',
            'customer_chat_plugin_enabled'   => 'boolean',
            'auto_flow_enabled'              => 'boolean',
            'auto_flow_postback_id'          => 'nullable|string|max:64',
            'auto_flow_lookback_minutes'     => 'nullable|integer|min:5|max:1440',
            'auto_flow_grace_minutes'        => 'nullable|integer|min:0|max:60',
            'abandoned_cart_enabled'         => 'boolean',
            'abandoned_cart_delay_minutes'   => 'nullable|integer|min:5|max:1440',
            'abandoned_cart_message_en'      => 'nullable|string|max:2000',
            'abandoned_cart_message_ja'      => 'nullable|string|max:2000',
            'abandoned_cart_message_my'      => 'nullable|string|max:2000',
        ]);

        // Defensive: if abandoned_cart_enabled, English message is required as fallback
        if (
            ! empty($validated['abandoned_cart_enabled']) &&
            empty($validated['abandoned_cart_message_en'])
        ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'abandoned_cart_message_en is required when abandoned_cart_enabled is on',
            ], 422);
        }

        // Defensive: enforce grace < lookback
        if (
            isset($validated['auto_flow_lookback_minutes'], $validated['auto_flow_grace_minutes']) &&
            (int) $validated['auto_flow_grace_minutes'] >= (int) $validated['auto_flow_lookback_minutes']
        ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'auto_flow_grace_minutes must be less than auto_flow_lookback_minutes',
            ], 422);
        }

        $validated['tenant_id']  = $tenantId;
        $validated['channel_id'] = $channelId;

        $setting = LiveChatSetting::updateOrCreate(
            ['tenant_id' => $tenantId, 'channel_id' => $channelId],
            $validated
        );

        // Also update messenger_page_map
        MessengerPageMap::updateOrCreate(
            ['facebook_page_id' => $validated['fb_page_id']],
            [
                'tenant_id'    => $tenantId,
                'channel_id'   => $channelId,
                'channel_code' => core()->getAllChannels()->firstWhere('id', $channelId)?->code ?? '',
                'tenant_db'    => DB::connection('mysql')->getDatabaseName(),
                'is_active'    => true,
            ]
        );

        return response()->json(['status' => 'saved', 'id' => $setting->id]);
    }

    public function testConnection(Request $request, $channelId)
    {
        $tenantId = tenant('id') ?? auth()->user()->tenant_id ?? '';
        $setting  = LiveChatSetting::where('tenant_id', $tenantId)
            ->where('channel_id', $channelId)->firstOrFail();

        $chatpion = new ChatpionFailoverService($setting);
        $health   = $chatpion->healthCheck();

        return response()->json($health);
    }
}
