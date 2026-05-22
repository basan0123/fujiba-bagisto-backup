<?php

namespace Fujiba\LiveChat\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Fujiba\LiveChat\Services\BotFlowSender;
use Fujiba\LiveChat\Services\ChatpionFailoverService;
use Fujiba\LiveChat\Models\LiveChatSetting;

class LiveChatController extends Controller
{
    /**
     * Get setting by channel (STRICT)
     */
    protected function getSetting(Request $request): LiveChatSetting
    {
        $tenantId  = tenant('id') ?? auth()->user()->tenant_id ?? null;
       $channelId = $request->input('channel_id');

\Log::info('CHANNEL DEBUG FINAL', [
    'input' => $request->input('channel_id'),
    'all'   => $request->all(),
    'query' => $request->query(),
]);

if (!$channelId) {
    $channelId = $request->query('channel_id');
}

        // 🔴 FORCE channel_id (important fix)
        if (! $channelId) {
            abort(400, 'channel_id is required');
        }

        $query = LiveChatSetting::where('channel_id', (int) $channelId);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $setting = $query->firstOrFail();

        // ✅ Debug log
        Log::info('LiveChat Setting Loaded', [
            'tenant_id'  => $tenantId,
            'channel_id' => $channelId,
            'fb_bot_id'  => $setting->fb_bot_id,
        ]);

        return $setting;
    }

    public function index()
    {
        $tenantId = tenant('id') ?? auth()->user()->tenant_id ?? null;

        $query = LiveChatSetting::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $settings = $query->get();

        return view('fujiba_livechat::admin.livechat.index', compact('settings'));
    }

    /**
     * Get conversations
     */
    public function conversations(Request $request)
    {
        $setting  = $this->getSetting($request);
        $chatpion = new ChatpionFailoverService($setting);

        $data = $chatpion->apiPost('livechat_conversations', [
            'whatsapp_bot_id' => $setting->fb_bot_id,
            'message_type'    => $request->input('message_type', 'all'),
            'start'           => $request->input('start', 0),
            'media_type'      => $request->input('media_type', 'fb'),
            'search'          => (string) $request->input('search', ''),
        ]);

        if (isset($data['data']) && is_array($data['data'])) {
            usort($data['data'], fn($a, $b) =>
                strcmp($b['last_message_time'] ?? '', $a['last_message_time'] ?? '')
            );

            $limit = (int) $request->input('limit', 30);
            $data['data'] = array_slice($data['data'], 0, $limit);
        }

        return response()->json($data);
    }

    /**
     * Get single conversation messages
     */
    public function conversation(Request $request)
    {
        $setting      = $this->getSetting($request);
        $subscriberId = $request->from_user_id ?? $request->subscriber_id;

        // Fetch full message history from Facebook Graph API
        $pageInfo = \DB::connection('chatpion_import')
            ->table('facebook_rx_fb_page_info')
            ->where('id', $setting->fb_bot_id)
            ->first(['page_id', 'page_access_token']);

        if (! $pageInfo || ! $pageInfo->page_access_token) {
            return response()->json(['status' => 'error', 'message' => 'Page access token not found', 'data' => []]);
        }

        $token  = $pageInfo->page_access_token;
        $pageId = $pageInfo->page_id;

        // Step 1: get the FB conversation thread for this user
        $convRes = Http::timeout(15)->get("https://graph.facebook.com/v18.0/{$pageId}/conversations", [
            'user_id'      => $subscriberId,
            'fields'       => 'id',
            'access_token' => $token,
        ]);

        $fbThreadId = $convRes->json()['data'][0]['id'] ?? null;
        if (! $fbThreadId) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        // Step 2: fetch messages from that thread (newest first by default)
        $msgRes = Http::timeout(15)->get("https://graph.facebook.com/v18.0/{$fbThreadId}/messages", [
            'fields'       => 'message,from,to,created_time,sticker,attachments{name,mime_type,size,image_data,file_url,video_data,generic_template,quick_reply}',
            'limit'        => 50,
            'access_token' => $token,
        ]);

        $fbMsgs = $msgRes->json()['data'] ?? [];

        $messages = [];
        foreach (array_reverse($fbMsgs) as $m) {
            $fromId    = $m['from']['id'] ?? '';
            $direction = ($fromId === $pageId) ? 'out' : 'in';
            $sender    = ($fromId === $pageId) ? 'bot'  : 'user';

            $text         = $m['message'] ?? '';
            $mediaUrl     = null;
            $msgType      = 'text';
            $carouselCards = [];

            $attachments = $m['attachments']['data'] ?? [];
            foreach ($attachments as $att) {
                if (! empty($att['generic_template'])) {
                    $gt = $att['generic_template'];
                    $carouselCards[] = [
                        'title'     => $gt['title'] ?? '',
                        'subtitle'  => $gt['subtitle'] ?? '',
                        'image_url' => $gt['media_url'] ?? ($gt['image_url'] ?? ''),
                        'buttons'   => array_map(fn($b) => [
                            'title' => $b['title'] ?? '',
                            'url'   => $b['url'] ?? '',
                            'type'  => $b['type'] ?? 'web_url',
                        ], $gt['cta'] ?? $gt['buttons'] ?? []),
                    ];
                } else if ($mediaUrl === null) {
                    $mime    = $att['mime_type'] ?? '';
                    $msgType = $mime ?: 'attachment';
                    $mediaUrl = $att['image_data']['url']
                        ?? $att['video_data']['url']
                        ?? $att['file_url']
                        ?? null;
                    if (str_starts_with($mime, 'image/'))      $msgType = 'image';
                    else if (str_starts_with($mime, 'video/')) $msgType = 'video';
                    else if (str_starts_with($mime, 'audio/')) $msgType = 'audio';
                    if (! $text && ! empty($att['name'])) $text = $att['name'];
                }
            }

            // Build message_content as a carousel JSON the frontend already understands
            $messageContent = null;
            if (! empty($carouselCards)) {
                $msgType = 'template';
                $messageContent = json_encode([
                    'message' => [
                        'attachment' => [
                            'type'    => 'template',
                            'payload' => [
                                'template_type' => 'generic',
                                'elements'      => $carouselCards,
                            ],
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $messages[] = [
                'id'                => $m['id'],
                'thread_id'         => $fbThreadId,
                'subscriber_id'     => $subscriberId,
                'message'           => $text,
                'message_content'   => $messageContent,
                'message_type'      => $msgType,
                'media_url'         => $mediaUrl,
                'direction'         => $direction,
                'sender'            => $sender,
                'agent_name'        => $m['from']['name'] ?? null,
                'created_at'        => isset($m['created_time']) ? date('Y-m-d H:i:s', strtotime($m['created_time'])) : null,
                'conversation_time' => isset($m['created_time']) ? date('Y-m-d H:i:s', strtotime($m['created_time'])) : null,
            ];
        }

        return response()->json(['status' => 'success', 'data' => $messages]);
    }

    public function cannedResponses(Request $request)
    {
        $setting = $this->getSetting($request);

        // Prefer live upstream Chatpion API — local chatpion_import is stale
        // because the hourly sync is disabled.
        $chatpion = new ChatpionFailoverService($setting);
        $upstream = $chatpion->apiPost('livechat_canned_responses', [
            'whatsapp_bot_id' => $setting->fb_bot_id,
        ]);

        if (is_array($upstream) && ($upstream['status'] ?? null) === 'success') {
            $rows = is_array($upstream['data'] ?? null) ? $upstream['data'] : [];

            return response()->json(array_values($rows));
        }

        // Upstream unreachable — fall back to local chatpion_import (stale).
        $canned = \DB::connection('chatpion_import')
            ->table('canned_response')
            ->where('page_id', $setting->fb_bot_id)
            ->orderBy('name')
            ->get(['id', 'name as title', 'message']);

        return response()->json($canned->values()->toArray());
    }

    /**
     * 🔥 FIXED SEND REPLY
     */
    public function sendReply(Request $request)
    {
        $request->validate([
            'from_user_id' => 'required|string',
            'thread_id'    => 'nullable|string',
            'message'      => 'required|string|max:2000',
        ]);

        $setting  = $this->getSetting($request);
        $chatpion = new ChatpionFailoverService($setting);

        // ✅ FIX: use subscriber_id instead of from_user_id
        $data = $chatpion->apiPost('livechat_reply', [
            'whatsapp_bot_id' => $setting->fb_bot_id,
            'message'         => $request->message,
            'subscriber_id'   => $request->from_user_id,
            'thread_id'       => $request->thread_id,
            'message_tag'     => $request->input('message_tag', 'HUMAN_AGENT'),
        ]);

        // ✅ Debug response
        Log::info('Chatpion Send Reply', [
            'request' => $request->all(),
            'response' => $data,
        ]);

        return response()->json($data);
    }

    public function sendBotFlow(Request $request, BotFlowSender $sender)
    {
        $request->validate([
            'subscriber_id' => 'required|string',
            'postback_id'   => 'required|string',
        ]);

        $setting = $this->getSetting($request);

        return response()->json(
            $sender->send($setting, $request->subscriber_id, $request->postback_id)
        );
    }

    // System postback_ids to exclude from livechat flow picker
    protected array $systemPostbacks = [
        'STORY_MENTION', 'STORY_PRIVATE_REPLY', 'MESSAGE_UNSEND_PRIVATE_REPLY',
        'UNSUBSCRIBE_QUICK_BOXER', 'RESUBSCRIBE_QUICK_BOXER',
        'QUICK_REPLY_EMAIL_REPLY_BOT', 'QUICK_REPLY_PHONE_REPLY_BOT',
        'QUICK_REPLY_LOCATION_REPLY_BOT', 'QUICK_REPLY_BIRTHDAY_REPLY_BOT',
        'YES_START_CHAT_WITH_HUMAN', 'YES_START_CHAT_WITH_BOT',
    ];

    public function botFlows(Request $request)
    {
        $setting = $this->getSetting($request);

        // Prefer live upstream Chatpion API — local chatpion_import is stale
        // because the hourly sync is disabled (phantom-reservation incident).
        // Daily admin edits to flows on fujiba.shop only show up here when we
        // query the upstream directly.
        $chatpion = new ChatpionFailoverService($setting);
        $upstream = $chatpion->apiGet('livechat_bot_flows', [
            'whatsapp_bot_id' => $setting->fb_bot_id,
        ]);

        // Upstream returns either a flat array of flow rows, or {status:error,...}.
        // When the upstream patch lands, rows include page_id and we can filter
        // upstream-side. Until then we filter locally using the (postback_id,
        // bot_name) → page_id mapping from chatpion_import.messenger_bot_postback.
        if (is_array($upstream) && (! isset($upstream['status']) || $upstream['status'] !== 'error')) {
            $rows = array_values(array_filter($upstream, fn ($r) => is_array($r) && ! empty($r['postback_id'])));

            // Path A — upstream is patched and tells us the page_id directly.
            if (! empty($rows) && array_key_exists('page_id', $rows[0])) {
                $rows = array_values(array_filter(
                    $rows,
                    fn ($r) => (string) ($r['page_id'] ?? '') === (string) $setting->fb_bot_id
                ));
            } else {
                // Path B — upstream returns flows across all pages. Disambiguate
                // by (postback_id, bot_name) against the local mapping. Same
                // postback_id can be cloned across pages with different names,
                // so we key on the pair. Rows we can't map are dropped to avoid
                // leaking flows from another channel into this picker.
                $pageId = (string) $setting->fb_bot_id;

                $localPairsForPage = \DB::connection('chatpion_import')
                    ->table('messenger_bot_postback')
                    ->where('page_id', $pageId)
                    ->pluck('bot_name', 'postback_id')
                    ->all();

                $localPostbackPageCount = \DB::connection('chatpion_import')
                    ->table('messenger_bot_postback')
                    ->select('postback_id')
                    ->selectRaw('COUNT(DISTINCT page_id) AS pages')
                    ->groupBy('postback_id')
                    ->pluck('pages', 'postback_id')
                    ->all();

                $rows = array_values(array_filter($rows, function ($r) use ($pageId, $localPairsForPage, $localPostbackPageCount) {
                    $pb   = $r['postback_id'] ?? '';
                    $name = (string) ($r['bot_name'] ?? '');

                    // Direct (postback_id, bot_name) match on this page.
                    if (isset($localPairsForPage[$pb]) && $localPairsForPage[$pb] === $name) {
                        return true;
                    }

                    // Postback_id exists ONLY on this page locally — safe to keep
                    // even when bot_name has been renamed upstream.
                    if (isset($localPairsForPage[$pb])
                        && ($localPostbackPageCount[$pb] ?? 0) <= 1) {
                        return true;
                    }

                    // Unknown to local cache — drop. Keeps the picker scoped to
                    // this channel rather than leaking sibling-channel flows.
                    return false;
                }));
            }

            $rows = array_values(array_filter(
                $rows,
                fn ($r) => ! in_array($r['postback_id'], $this->systemPostbacks, true)
            ));

            usort($rows, fn ($a, $b) => strcasecmp(
                (string) ($a['bot_name'] ?? ''),
                (string) ($b['bot_name'] ?? '')
            ));

            return response()->json($rows);
        }

        // Upstream unreachable — fall back to local chatpion_import (stale).
        $flows = \DB::connection('chatpion_import')
            ->table('messenger_bot')
            ->where('page_id', $setting->fb_bot_id)
            ->where('status', '1')
            ->whereNotNull('postback_id')
            ->where('postback_id', '!=', '')
            ->whereNotIn('postback_id', $this->systemPostbacks)
            ->orderBy('bot_name')
            ->get(['postback_id', 'bot_name as template_name', 'bot_name']);

        return response()->json($flows->values()->toArray());
    }

    public function markAs(Request $request)
    {
        $request->validate([
            'from_user_id' => 'required|string',
            'action'       => 'required|in:read,unread,archived,unarchived',
        ]);

        $setting  = $this->getSetting($request);
        $chatpion = new ChatpionFailoverService($setting);

        $data = $chatpion->apiPost('livechat_mark_as', [
            'from_user_id' => $request->from_user_id,
            'action'       => $request->action,
        ]);

        return response()->json($data);
    }

    public function subscriberInfo(Request $request)
    {
        $request->validate([
            'subscriber_id' => 'required|string'
        ]);

        $setting  = $this->getSetting($request);
        $chatpion = new ChatpionFailoverService($setting);
        $subscriberId = $request->subscriber_id;

        $data = $chatpion->apiGet('subscriber_information', [
            'subscriber_id' => $subscriberId,
        ]);

        // Tie this Messenger subscriber to a Bagisto customer record (per-channel),
        // syncing photo / name / phone / email so /admin/customers and the chat
        // sidebar see the same person. Then surface recent orders. The admin
        // panel runs on the central domain, so we initialize tenancy by
        // tenant_id from the LiveChatSetting before touching the tenant DB.
        $info = $data['subscriber_info'] ?? null;
        $linked = null;
        $orders = [];
        $tenantId = $setting->tenant_id;
        $wasInitialized = function_exists('tenant') && tenant() !== null;

        if ($info && $tenantId) {
            try {
                if (! $wasInitialized) {
                    \Stancl\Tenancy\Facades\Tenancy::initialize($tenantId);
                }
                $linked = $this->linkCustomer($info, $subscriberId, (int) $setting->channel_id);
                if ($linked) {
                    $orders = \DB::table('orders')
                        ->where('customer_id', $linked->id)
                        ->orderByDesc('id')
                        ->limit(10)
                        ->get(['id', 'increment_id', 'status', 'grand_total', 'order_currency_code', 'created_at'])
                        ->toArray();
                }
            } catch (\Throwable $e) {
                \Log::warning('LiveChat linkCustomer failed: ' . $e->getMessage());
            } finally {
                if (! $wasInitialized) {
                    \Stancl\Tenancy\Facades\Tenancy::end();
                }
            }
        }

        return response()->json(array_merge((array) $data, [
            'bagisto_customer' => $linked ? [
                'id'         => $linked->id,
                'first_name' => $linked->first_name,
                'last_name'  => $linked->last_name,
                'email'      => $linked->email,
                'phone'      => $linked->phone,
                'image'      => $linked->image,
            ] : null,
            'bagisto_orders' => $orders,
        ]));
    }

    /**
     * Find-or-create a Bagisto customer for this Messenger subscriber on the
     * given channel. Idempotent: matching `chatpion_subscribe_id` + channel_id
     * is the strongest key; otherwise we match by email-on-channel and fall
     * back to creating a placeholder synthetic-email account. Photo / phone /
     * name / image are filled in only when the existing field is empty so we
     * never overwrite manual edits an admin made via /admin/customers.
     */
    protected function linkCustomer(array $info, string $subscriberId, int $channelId)
    {
        // Facebook's first/last split drops middle words ("Moe Su Hlaing" arrives
        // as first="Moe" last="Hlaing"), but Chatpion keeps the complete name in
        // full_name. Prefer full_name when it carries more than first+last; split
        // on the LAST space so the surname stays intact and middles ride along
        // with the first name.
        $rawFirst  = trim($info['first_name'] ?? '');
        $rawLast   = trim($info['last_name']  ?? '');
        $rawFull   = trim($info['full_name']  ?? '');
        $joined    = trim($rawFirst . ' ' . $rawLast);
        if ($rawFull !== '' && $rawFull !== '0' && mb_strlen($rawFull) > mb_strlen($joined)) {
            $cut = mb_strrpos($rawFull, ' ');
            if ($cut !== false) {
                $firstName = mb_substr($rawFull, 0, $cut);
                $lastName  = mb_substr($rawFull, $cut + 1);
            } else {
                $firstName = $rawFull;
                $lastName  = '';
            }
        } else {
            $firstName = $rawFirst !== '' ? $rawFirst : 'Messenger';
            $lastName  = $rawLast  !== '' ? $rawLast  : 'User';
        }
        $phone     = $info['phone_number'] ?? null;
        $image     = $info['profile_pic']   ?? null;
        $realEmail = ! empty($info['email']) ? $info['email'] : null;

        $synthetic = "msg_{$subscriberId}_ch{$channelId}@messenger.local";
        $email     = $realEmail ?: $synthetic;

        $customer = \Webkul\Customer\Models\Customer::where('chatpion_subscribe_id', $subscriberId)
            ->where('channel_id', $channelId)
            ->first();

        if (! $customer && $realEmail) {
            $customer = \Webkul\Customer\Models\Customer::where('email', $realEmail)
                ->where('channel_id', $channelId)
                ->first();
        }

        if (! $customer) {
            $customer = \Webkul\Customer\Models\Customer::create([
                'first_name'            => $firstName ?: 'Messenger',
                'last_name'             => $lastName  ?: 'User',
                'email'                 => $email,
                'phone'                 => $phone,
                'image'                 => $image,
                'chatpion_subscribe_id' => $subscriberId,
                'password'              => bcrypt(\Illuminate\Support\Str::random(32)),
                'status'                => 1,
                'is_verified'           => 1,
                'customer_group_id'     => 2,
                'channel_id'            => $channelId,
            ]);
            return $customer;
        }

        // Backfill blanks only — never overwrite admin-curated data.
        // Exception: when Chatpion now gives us a strictly longer full_name
        // (e.g. previous link captured "Moe Hlaing" but Chatpion later filled
        // full_name to "Moe Su Hlaing"), upgrade to the fuller version.
        $dirty = false;
        $existingJoined = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        $newJoined      = trim($firstName . ' ' . $lastName);
        $upgradeName    = $rawFull !== ''
            && mb_strlen($newJoined) > mb_strlen($existingJoined)
            && stripos($newJoined, $existingJoined) !== false;
        if (! $customer->chatpion_subscribe_id)        { $customer->chatpion_subscribe_id = $subscriberId; $dirty = true; }
        if ($firstName && ($this->looksPlaceholder($customer->first_name) || $upgradeName)) { $customer->first_name = $firstName; $dirty = true; }
        if ($lastName  && ($this->looksPlaceholder($customer->last_name)  || $upgradeName)) { $customer->last_name  = $lastName;  $dirty = true; }
        if ($phone     && empty($customer->phone))     { $customer->phone = $phone; $dirty = true; }
        if ($image     && empty($customer->image))     { $customer->image = $image; $dirty = true; }
        if ($realEmail && str_contains((string) $customer->email, '@messenger.local')) {
            $customer->email = $realEmail; $dirty = true;
        }
        if ($dirty) $customer->save();

        return $customer;
    }

    protected function looksPlaceholder(?string $v): bool
    {
        return $v === null || $v === '' || $v === '-' || $v === '0' || $v === 'Customer' || $v === 'Messenger';
    }

    public function uploadFile(Request $request)
    {
        $setting  = $this->getSetting($request);
        $chatpion = new ChatpionFailoverService($setting);

        $base = $chatpion->getActiveServerUrl();

        $res = Http::attach(
            'media_file',
            file_get_contents($request->file('file')),
            $request->file('file')->getClientOriginalName()
        )->asMultipart()->post("{$base}/api/livechat_upload_file", [
            ['name' => 'api_key', 'contents' => $chatpion->getApiKey()],
        ]);

        return response()->json($res->json());
    }

    public function serverHealth(Request $request)
    {
        $setting  = $this->getSetting($request);
        $chatpion = new ChatpionFailoverService($setting);

        return response()->json($chatpion->healthCheck());
    }
}