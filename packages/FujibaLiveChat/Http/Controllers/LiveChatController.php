<?php
namespace Modules\FujibaLiveChat\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Modules\FujibaLiveChat\Services\ChatpionService;

class LiveChatController extends Controller
{
    protected ChatpionService $chatpion;

    public function __construct(ChatpionService $chatpion)
    {
        $this->chatpion = $chatpion;
    }

    public function index()
    {
        $channels = core()->getAllChannels();
        return view('fujiba_livechat::admin.livechat', compact('channels'));
    }

    // Proxy: get conversation list
    public function getConversationList(Request $request)
    {
        $data = $this->chatpion->post('livechat/get_conversation_list', [
            'whatsapp_bot_id' => $request->bot_id,
            'message_type'    => $request->message_type ?? 'all',
            'start'           => $request->start ?? 0,
            'media_type'      => $request->media_type ?? 'fb',
        ]);
        return response()->json($data);
    }

    // Proxy: get single conversation
    public function getConversation(Request $request)
    {
        $data = $this->chatpion->post('livechat/get_conversation_single', [
            'thread_id'       => $request->thread_id,
            'whatsapp_bot_id' => $request->bot_id,
            'from_user_id'    => $request->from_user_id,
            'last_message_id' => $request->last_message_id ?? '',
        ]);
        return response()->json($data);
    }

    // Proxy: send reply
    public function sendReply(Request $request)
    {
        $data = $this->chatpion->post('livechat/reply_to_conversation', [
            'whatsapp_bot_id' => $request->bot_id,
            'reply_message'   => $request->message,
            'from_user_id'    => $request->from_user_id,
            'thread_id'       => $request->thread_id,
            'media_type'      => $request->media_type ?? 'fb',
            'message_tag'     => $request->message_tag ?? 'HUMAN_AGENT',
        ]);
        return response()->json($data);
    }

    // Proxy: send bot flow (postback)
    public function sendBotFlow(Request $request)
    {
        $data = $this->chatpion->post('livechat/send_postback_reply', [
            'api_key'         => $this->chatpion->getApiKey(),
            'whatsapp_bot_id' => $request->bot_id,
            'subscriber_id'   => $request->subscriber_id,
            'postback_id'     => $request->postback_id,
        ]);
        return response()->json($data);
    }

    // Proxy: get bot flows list
    public function getBotFlows(Request $request)
    {
        $data = $this->chatpion->get("livechat/get_postback_list_json/{$request->bot_id}/fb");
        return response()->json($data);
    }

    // Proxy: subscriber info
    public function getSubscriberInfo(Request $request)
    {
        $apiKey = $this->chatpion->getApiKey();
        $data = $this->chatpion->get('api/subscriber_information', [
            'api_key'       => $apiKey,
            'subscriber_id' => $request->subscriber_id,
        ]);
        return response()->json($data);
    }

    // Fujiba: send order notification via Fujiba API
    public function sendOrderNotification(Request $request)
    {
        $apiKey = $this->chatpion->getApiKey();
        $res = Http::post(config('fujiba_livechat.chatpion_url').'/api/send_bagisto_notification', [
            'api_key'       => $apiKey,
            'subscriber_id' => $request->subscriber_id,
            'order_id'      => $request->order_id,
            'status'        => $request->status,
            'tracking_no'   => $request->tracking_no ?? '',
            'btn_url'       => $request->btn_url ?? '',
        ]);
        return response()->json($res->json());
    }

    // Settings CRUD for channel -> chatpion mapping
    public function getSettings($channelId)
    {
        $settings = \DB::table('fujiba_livechat_settings')
            ->where('channel_id', $channelId)->first();
        return response()->json($settings);
    }

    public function saveSettings(Request $request, $channelId)
    {
        $data = $request->validate([
            'chatpion_url'   => 'required|url',
            'chatpion_url_2' => 'nullable|url',
            'chatpion_url_3' => 'nullable|url',
            'api_key'        => 'required|string',
            'fb_page_id'     => 'required|string',
            'bot_id'         => 'required|string',
            'enabled'        => 'boolean',
        ]);
        $data['channel_id'] = $channelId;
        \DB::table('fujiba_livechat_settings')
            ->updateOrInsert(['channel_id' => $channelId], $data);
        Cache::forget("livechat_settings_{$channelId}");
        return response()->json(['status' => 'ok']);
    }
}