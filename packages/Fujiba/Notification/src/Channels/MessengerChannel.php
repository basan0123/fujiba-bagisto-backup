<?php

namespace Fujiba\Notification\Channels;

use Fujiba\LiveChat\Models\LiveChatSetting;
use Fujiba\Notification\Channels\Contracts\Channel;
use Fujiba\Notification\Models\NotificationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessengerChannel implements Channel
{
    public function key(): string
    {
        return 'messenger';
    }

    public function send(NotificationLog $log, string $subject, string $body, array $context = []): void
    {
        $psid       = $log->recipient;
        $channelId  = $context['channel_id'] ?? null;
        $messageTag = $context['message_tag'] ?? 'POST_PURCHASE_UPDATE';

        if (! $psid) {
            throw new \RuntimeException('Messenger recipient PSID missing');
        }

        $token = $this->resolveAccessToken($channelId, $context['page_id'] ?? null);

        $buttons = $context['buttons'] ?? [];
        $message = $this->buildMessage($body, is_array($buttons) ? $buttons : []);

        $payload = [
            'recipient'      => ['id' => $psid],
            'messaging_type' => 'MESSAGE_TAG',
            'tag'            => $messageTag,
            'message'        => $message,
        ];

        Log::debug('[FujibaNotification] Messenger send', [
            'log_id'      => $log->id,
            'event'       => $log->event,
            'psid'        => $psid,
            'page_id'     => $context['page_id'] ?? null,
            'channel_id'  => $channelId,
            'message_tag' => $messageTag,
            'body_length' => mb_strlen($body),
            'body'        => $body,
            'button_count' => is_array($buttons) ? count($buttons) : 0,
            'payload'     => $payload,
        ]);

        $response = Http::timeout(15)
            ->post("https://graph.facebook.com/v18.0/me/messages?access_token={$token}", $payload);

        Log::debug('[FujibaNotification] Messenger send response', [
            'log_id'   => $log->id,
            'status'   => $response->status(),
            'body'     => mb_substr($response->body(), 0, 500),
        ]);

        if (! $response->successful()) {
            $err = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('Messenger send failed: '.$err);
        }
    }

    /**
     * Plain text when no buttons; Graph button template when buttons exist.
     * Per Facebook docs, button template requires `attachment.payload.buttons`
     * with at most 3 buttons and `text` <= 640 chars.
     *
     * Note: button templates with MESSAGE_TAG=POST_PURCHASE_UPDATE may be
     * silently rejected by Graph for users outside the 24h conversation
     * window. This is intentional — same delivery profile as text-only,
     * with buttons attached when delivery succeeds.
     */
    protected function buildMessage(string $text, array $buttons): array
    {
        if (empty($buttons)) {
            return ['text' => mb_substr($text, 0, 2000)];
        }

        $fbButtons = [];
        foreach (array_slice($buttons, 0, 3) as $btn) {
            $type  = $btn['type']  ?? 'url';
            $label = mb_substr((string) ($btn['text']  ?? ''), 0, 20);
            $value = (string) ($btn['value'] ?? '');

            if ($label === '' || $value === '') {
                continue;
            }

            if ($type === 'url') {
                $fbButtons[] = ['type' => 'web_url', 'url' => $value, 'title' => $label];
            } elseif ($type === 'postback') {
                $fbButtons[] = ['type' => 'postback', 'title' => $label, 'payload' => $value];
            } elseif ($type === 'phone') {
                $fbButtons[] = ['type' => 'phone_number', 'title' => $label, 'payload' => $value];
            }
        }

        if (empty($fbButtons)) {
            return ['text' => mb_substr($text, 0, 2000)];
        }

        return [
            'attachment' => [
                'type'    => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text'          => mb_substr($text, 0, 640),
                    'buttons'       => $fbButtons,
                ],
            ],
        ];
    }

    /**
     * Look up the page access token via the LiveChat setting for this channel.
     * Falls back to scanning by page_id if channel context is missing.
     */
    protected function resolveAccessToken(?int $channelId, ?string $pageId): string
    {
        $fbBotId = null;

        if ($channelId) {
            $setting = LiveChatSetting::where('channel_id', $channelId)->first();
            $fbBotId = $setting?->fb_bot_id;
        }

        $row = DB::connection('chatpion_import')
            ->table('facebook_rx_fb_page_info')
            ->when($fbBotId, fn ($q) => $q->where('id', $fbBotId))
            ->when(! $fbBotId && $pageId, fn ($q) => $q->where('page_id', $pageId))
            ->first(['page_access_token']);

        if (! $row || ! $row->page_access_token) {
            throw new \RuntimeException('No FB page access token available for channel '.$channelId);
        }

        return $row->page_access_token;
    }
}
