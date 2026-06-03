<?php

namespace Webkul\Pos\Services;

use Fujiba\LiveChat\Models\LiveChatSetting;
use Fujiba\LiveChat\Services\ChatpionFailoverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POS ⇄ Facebook Messenger operator panel.
 *
 * POS chat is Messenger-ONLY. This service is a thin POS-facing wrapper around
 * the existing, working Fujiba\LiveChat / Chatpion stack — the same backend the
 * admin LiveChat UI (/admin/fujiba-livechat) uses. It does NOT introduce any new
 * storage; conversations, messages, bot flows and canned responses all live in
 * Chatpion / Facebook. A POS conversation `id` IS the Messenger subscriber PSID
 * (a plain string), so the RN client passes PSIDs straight through.
 *
 * Everything is scoped to the POS agent's channel via its LiveChatSetting
 * (keyed by tenant_id + channel_id). Each Facebook page (Kimono, Myanmar food,
 * …) is a different channel → different LiveChatSetting → different fb_bot_id,
 * so multi-page support is automatic.
 *
 * IMPORTANT: this file lives in the POS package and must never modify the
 * Fujiba\LiveChat package. It only consumes its public services + models.
 */
class PosMessengerService
{
    /** Facebook Graph version used for direct dispatch (matches BotFlowSender). */
    private const GRAPH_VERSION = 'v18.0';

    /**
     * Resolve the LiveChatSetting (Messenger config) for a channel.
     * Returns null when the channel has no Messenger page wired — a normal state
     * for outlets without Facebook. Tenant scoping mirrors the admin controller.
     */
    public function settingFor(?int $channelId): ?LiveChatSetting
    {
        if ($channelId === null) {
            return null;
        }

        $query = LiveChatSetting::query()->where('channel_id', $channelId);

        $tenantId = $this->tenantId();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    /** Whether Messenger is configured for the channel. */
    public function isConfigured(?int $channelId): bool
    {
        return $this->settingFor($channelId) !== null;
    }

    private function tenantId(): ?string
    {
        try {
            return function_exists('tenant') ? (tenant('id') ?? null) : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /* ===================================================================== */
    /* Inbox + thread (read)                                                  */
    /* ===================================================================== */

    /**
     * Conversation inbox, newest activity first. Mirrors
     * LiveChatController::conversations. `$search` matches Chatpion's `search`
     * param (subscriber name / id / email).
     *
     * Returns rows in the POS ChatConversation shape. Empty array when no
     * Messenger is wired or the upstream is unreachable (degrade gracefully).
     */
    public function conversations(?int $channelId, string $search = '', int $limit = 50): array
    {
        $setting = $this->settingFor($channelId);
        if (! $setting) {
            return [];
        }

        try {
            $chatpion = new ChatpionFailoverService($setting);
            $resp = $chatpion->apiPost('livechat_conversations', [
                'whatsapp_bot_id' => $setting->fb_bot_id,
                'message_type'    => 'all',
                'start'           => 0,
                'media_type'      => 'fb',
                'search'          => $search,
            ]);
        } catch (Throwable $e) {
            Log::warning('PosMessenger.conversations failed', ['channel' => $channelId, 'error' => $e->getMessage()]);

            return [];
        }

        $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $psid = (string) ($row['subscriber_id'] ?? $row['from_user_id'] ?? '');
            if ($psid === '') {
                continue;
            }
            $out[] = $this->mapConversation($row, $psid, $channelId);
        }

        usort($out, fn ($a, $b) => strcmp((string) $b['last_message_at'], (string) $a['last_message_at']));

        return array_slice($out, 0, max(1, $limit));
    }

    /** A single conversation snapshot by PSID (channel-scoped). */
    public function conversation(?int $channelId, string $psid): ?array
    {
        foreach ($this->conversations($channelId, $psid) as $row) {
            if (($row['id'] ?? null) === $psid) {
                return $row;
            }
        }

        // The search may not surface an idle thread; synthesize a minimal row so
        // the thread view can still open (messages are fetched separately).
        $setting = $this->settingFor($channelId);
        if (! $setting) {
            return null;
        }

        return $this->mapConversation([], $psid, $channelId);
    }

    /** Shape a Chatpion conversation row into the POS contract. */
    private function mapConversation(array $row, string $psid, ?int $channelId): array
    {
        $name = trim(((string) ($row['first_name'] ?? '')).' '.((string) ($row['last_name'] ?? '')));
        if ($name === '') {
            $name = (string) ($row['full_name'] ?? $row['from_user'] ?? '');
        }

        return [
            'id'                 => $psid,
            'channel_id'         => $channelId,
            'visitor_name'       => $name !== '' ? $name : null,
            'visitor_email'      => $row['email'] ?? null,
            'visitor_phone'      => $row['phone_number'] ?? null,
            'profile_pic'        => $row['profile_pic'] ?? null,
            'last_message'       => isset($row['last_message']) ? (string) $row['last_message'] : null,
            'last_message_at'    => $row['last_message_time'] ?? null,
            'agent_unread_count' => (int) ($row['unread_count'] ?? 0),
            'created_at'         => $row['last_message_time'] ?? null,
            'chatpion_subscriber_id' => $psid,
        ];
    }

    /**
     * Full message history for one Messenger thread, oldest-first, via the
     * Facebook Graph API. This is the exact approach the admin uses
     * (LiveChatController::conversation), kept self-contained here.
     *
     * Returns rows in the POS ChatMessage shape. `$sinceCount` is a simple
     * "skip the first N" cursor — the RN client re-fetches the window and only
     * appends rows beyond what it already has.
     */
    public function messages(?int $channelId, string $psid, int $sinceCount = 0): array
    {
        $setting = $this->settingFor($channelId);
        if (! $setting || $psid === '') {
            return [];
        }

        $pageInfo = $this->pageInfo($setting);
        if (! $pageInfo) {
            return [];
        }

        [$token, $pageId] = $pageInfo;

        try {
            $convRes = Http::timeout(15)->get("https://graph.facebook.com/".self::GRAPH_VERSION."/{$pageId}/conversations", [
                'user_id'      => $psid,
                'fields'       => 'id',
                'access_token' => $token,
            ]);
            $fbThreadId = $convRes->json()['data'][0]['id'] ?? null;
            if (! $fbThreadId) {
                return [];
            }

            $msgRes = Http::timeout(15)->get("https://graph.facebook.com/".self::GRAPH_VERSION."/{$fbThreadId}/messages", [
                'fields'       => 'message,from,to,created_time,sticker,attachments{name,mime_type,size,image_data,file_url,video_data,generic_template}',
                'limit'        => 50,
                'access_token' => $token,
            ]);
            $raw = $msgRes->json()['data'] ?? [];
        } catch (Throwable $e) {
            Log::warning('PosMessenger.messages graph fetch failed', ['psid' => $psid, 'error' => $e->getMessage()]);

            return [];
        }

        $out = [];
        $idx = 0;
        foreach (array_reverse($raw) as $m) {
            $idx++;
            if ($idx <= $sinceCount) {
                continue;
            }
            $out[] = $this->mapMessage($m, $idx, $psid, $pageId);
        }

        return $out;
    }

    /** Shape a Graph message into the POS ChatMessage contract. */
    private function mapMessage(array $m, int $idx, string $psid, string $pageId): array
    {
        $fromId  = (string) ($m['from']['id'] ?? '');
        $isAgent = ($fromId === (string) $pageId);

        $text     = (string) ($m['message'] ?? '');
        $mediaUrl = null;
        $msgType  = 'text';

        foreach (($m['attachments']['data'] ?? []) as $att) {
            if (! empty($att['generic_template'])) {
                $msgType = 'template';

                continue;
            }
            if ($mediaUrl === null) {
                $mime     = (string) ($att['mime_type'] ?? '');
                $mediaUrl = $att['image_data']['url'] ?? $att['video_data']['url'] ?? $att['file_url'] ?? null;
                if (str_starts_with($mime, 'image/')) {
                    $msgType = 'image';
                } elseif (str_starts_with($mime, 'video/')) {
                    $msgType = 'video';
                } elseif (str_starts_with($mime, 'audio/')) {
                    $msgType = 'audio';
                } else {
                    $msgType = $mime !== '' ? 'file' : 'attachment';
                }
                if ($text === '' && ! empty($att['name'])) {
                    $text = (string) $att['name'];
                }
            }
        }

        return [
            'id'           => $idx,
            'psid'         => $psid,
            'sender_type'  => $isAgent ? 'agent' : 'visitor',
            'body'         => $text,
            'message_type' => $msgType,
            'media_url'    => $mediaUrl,
            'agent_name'   => $m['from']['name'] ?? null,
            'created_at'   => isset($m['created_time']) ? date('Y-m-d H:i:s', strtotime($m['created_time'])) : null,
        ];
    }

    /* ===================================================================== */
    /* Send actions (write)                                                   */
    /* ===================================================================== */

    /**
     * Send a plain-text human-agent reply through Chatpion (livechat_reply),
     * exactly like the admin sendReply. Returns Chatpion's verbatim response.
     */
    public function sendReply(?int $channelId, string $psid, string $body): array
    {
        $setting = $this->settingFor($channelId);
        if (! $setting) {
            return ['status' => 'error', 'message' => 'Messenger is not configured for this channel.'];
        }
        if ($psid === '' || trim($body) === '') {
            return ['status' => 'error', 'message' => 'Empty message.'];
        }

        try {
            $chatpion = new ChatpionFailoverService($setting);

            return $chatpion->apiPost('livechat_reply', [
                'whatsapp_bot_id' => $setting->fb_bot_id,
                'message'         => $body,
                'subscriber_id'   => $psid,
                'thread_id'       => null,
                'message_tag'     => 'HUMAN_AGENT',
            ]);
        } catch (Throwable $e) {
            Log::warning('PosMessenger.sendReply failed', ['psid' => $psid, 'error' => $e->getMessage()]);

            return ['status' => 'error', 'message' => 'Send failed: '.$e->getMessage()];
        }
    }

    /**
     * Canned responses for the channel's page. Mirrors the admin
     * cannedResponses (upstream-first, local chatpion_import fallback).
     * Returns [['id','title','message'], ...].
     */
    public function cannedResponses(?int $channelId): array
    {
        $setting = $this->settingFor($channelId);
        if (! $setting) {
            return [];
        }

        try {
            $chatpion = new ChatpionFailoverService($setting);
            $upstream = $chatpion->apiPost('livechat_canned_responses', [
                'whatsapp_bot_id' => $setting->fb_bot_id,
            ]);
            if (($upstream['status'] ?? null) === 'success' && is_array($upstream['data'] ?? null)) {
                return array_values(array_map(fn ($r) => [
                    'id'      => (string) ($r['id'] ?? ''),
                    'title'   => (string) ($r['title'] ?? $r['name'] ?? ''),
                    'message' => (string) ($r['message'] ?? ''),
                ], $upstream['data']));
            }
        } catch (Throwable $e) {
            Log::warning('PosMessenger.cannedResponses upstream failed', ['error' => $e->getMessage()]);
        }

        try {
            return DB::connection('chatpion_import')
                ->table('canned_response')
                ->where('page_id', $setting->fb_bot_id)
                ->orderBy('name')
                ->get(['id', 'name as title', 'message'])
                ->map(fn ($r) => ['id' => (string) $r->id, 'title' => (string) $r->title, 'message' => (string) $r->message])
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Sendable Chatpion bot flows for the channel's page (postback_id + name),
     * read from the local chatpion_import snapshot scoped to fb_bot_id — the
     * same source the admin falls back to. System postbacks are filtered out.
     */
    public function botFlows(?int $channelId): array
    {
        $setting = $this->settingFor($channelId);
        if (! $setting) {
            return [];
        }

        try {
            return DB::connection('chatpion_import')
                ->table('messenger_bot_postback')
                ->where('page_id', $setting->fb_bot_id)
                ->whereNotNull('postback_id')
                ->where('postback_id', '!=', '')
                ->whereNotNull('bot_name')
                ->whereNotIn('postback_id', self::SYSTEM_POSTBACKS)
                ->orderBy('bot_name')
                ->get(['postback_id', 'bot_name'])
                ->map(fn ($r) => ['postbackId' => (string) $r->postback_id, 'botName' => (string) $r->bot_name])
                ->all();
        } catch (Throwable $e) {
            Log::warning('PosMessenger.botFlows failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /** System postback_ids that should never appear in the agent flow picker. */
    private const SYSTEM_POSTBACKS = [
        'STORY_MENTION', 'STORY_PRIVATE_REPLY', 'MESSAGE_UNSEND_PRIVATE_REPLY',
        'UNSUBSCRIBE_QUICK_BOXER', 'RESUBSCRIBE_QUICK_BOXER',
        'QUICK_REPLY_EMAIL_REPLY_BOT', 'QUICK_REPLY_PHONE_REPLY_BOT',
        'QUICK_REPLY_LOCATION_REPLY_BOT', 'QUICK_REPLY_BIRTHDAY_REPLY_BOT',
        'YES_START_CHAT_WITH_HUMAN', 'YES_START_CHAT_WITH_BOT',
    ];

    /* ----- Direct Graph dispatch (for buttons / attachments / PDFs) ------- */

    /**
     * Send a button template (offer / "Pay Now" / "Order Ready") to a Messenger
     * user. Buttons are `[['type'=>'web_url'|'postback', 'title'=>..., 'url'|'payload'=>...], ...]`.
     * Uses the HUMAN_AGENT message tag like the rest of the agent surface.
     */
    public function sendButtons(?int $channelId, string $psid, string $text, array $buttons): array
    {
        $payload = [
            'attachment' => [
                'type'    => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text'          => mb_substr($text !== '' ? $text : ' ', 0, 640),
                    'buttons'       => $this->normalizeButtons($buttons),
                ],
            ],
        ];

        return $this->graphSend($channelId, $psid, $payload);
    }

    /** Send a quick-reply prompt (chips). $replies = [['title'=>, 'payload'=>], ...]. */
    public function sendQuickReplies(?int $channelId, string $psid, string $text, array $replies): array
    {
        $payload = [
            'text'          => $text !== '' ? $text : ' ',
            'quick_replies' => array_map(fn ($r) => [
                'content_type' => 'text',
                'title'        => mb_substr((string) ($r['title'] ?? ''), 0, 20),
                'payload'      => (string) ($r['payload'] ?? $r['title'] ?? ''),
            ], array_slice($replies, 0, 13)),
        ];

        return $this->graphSend($channelId, $psid, $payload);
    }

    /**
     * Send a file attachment (PDF receipt / 請求書 / 納品書) by public URL.
     * The PDF must be reachable by Facebook's servers (a signed public URL).
     * `$type` is one of image|video|audio|file (PDF → file).
     */
    public function sendAttachmentUrl(?int $channelId, string $psid, string $url, string $type = 'file'): array
    {
        $payload = [
            'attachment' => [
                'type'    => in_array($type, ['image', 'video', 'audio', 'file'], true) ? $type : 'file',
                'payload' => [
                    'url'         => $url,
                    'is_reusable' => false,
                ],
            ],
        ];

        return $this->graphSend($channelId, $psid, $payload);
    }

    /**
     * Low-level Graph send for one message object. Adds the recipient + the
     * HUMAN_AGENT tag and posts to /me/messages with the page token, exactly the
     * way BotFlowSender dispatches its steps. Returns a {status, message} shape
     * consistent with the Chatpion endpoints.
     */
    private function graphSend(?int $channelId, string $psid, array $message): array
    {
        $setting = $this->settingFor($channelId);
        if (! $setting) {
            return ['status' => 'error', 'message' => 'Messenger is not configured for this channel.'];
        }
        if ($psid === '') {
            return ['status' => 'error', 'message' => 'Missing recipient.'];
        }

        $pageInfo = $this->pageInfo($setting);
        if (! $pageInfo) {
            return ['status' => 'error', 'message' => 'Page access token not found.'];
        }
        [$token] = $pageInfo;

        try {
            $res = Http::timeout(20)->post(
                "https://graph.facebook.com/".self::GRAPH_VERSION."/me/messages?access_token={$token}",
                [
                    'recipient'      => ['id' => $psid],
                    'messaging_type' => 'MESSAGE_TAG',
                    'tag'            => 'HUMAN_AGENT',
                    'message'        => $message,
                ]
            );

            $json = $res->json() ?? [];
            if ($res->successful() && empty($json['error'])) {
                return ['status' => 'success', 'message' => 'Sent', 'fb' => $json];
            }

            return ['status' => 'error', 'message' => $json['error']['message'] ?? 'Facebook rejected the message', 'fb' => $json];
        } catch (Throwable $e) {
            Log::warning('PosMessenger.graphSend failed', ['psid' => $psid, 'error' => $e->getMessage()]);

            return ['status' => 'error', 'message' => 'Send failed: '.$e->getMessage()];
        }
    }

    private function normalizeButtons(array $buttons): array
    {
        $out = [];
        foreach (array_slice($buttons, 0, 3) as $b) {
            $title = mb_substr((string) ($b['title'] ?? ''), 0, 20);
            if ($title === '') {
                continue;
            }
            if (! empty($b['url'])) {
                $out[] = ['type' => 'web_url', 'title' => $title, 'url' => (string) $b['url']];
            } else {
                $out[] = ['type' => 'postback', 'title' => $title, 'payload' => (string) ($b['payload'] ?? $title)];
            }
        }

        return $out ?: [['type' => 'postback', 'title' => 'OK', 'payload' => 'OK']];
    }

    /**
     * Look up [page_access_token, page_id] from the chatpion_import mirror for a
     * setting's fb_bot_id. Returns null when missing. Same source the admin uses.
     */
    private function pageInfo(LiveChatSetting $setting): ?array
    {
        try {
            $row = DB::connection('chatpion_import')
                ->table('facebook_rx_fb_page_info')
                ->where('id', $setting->fb_bot_id)
                ->first(['page_id', 'page_access_token']);
        } catch (Throwable $e) {
            Log::warning('PosMessenger.pageInfo lookup failed', ['fb_bot_id' => $setting->fb_bot_id, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $row || empty($row->page_access_token) || empty($row->page_id)) {
            return null;
        }

        return [(string) $row->page_access_token, (string) $row->page_id];
    }
}
