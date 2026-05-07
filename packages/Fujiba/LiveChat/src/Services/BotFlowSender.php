<?php

namespace Fujiba\LiveChat\Services;

use Fujiba\LiveChat\Models\LiveChatSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotFlowSender
{
    /**
     * Send a Chatpion bot-flow template to a single Messenger subscriber.
     *
     * Reads the flow template + page access token from the local chatpion_import
     * snapshot, parses the template into Facebook Graph API-compatible messages,
     * and sends each step directly via Graph (Chatpion has no per-subscriber
     * flow-trigger endpoint). Each successful step is logged back into Chatpion
     * via livechat_log_message so the admin's livechat history stays in sync.
     *
     * Return shape is byte-identical to the response previously emitted by
     * LiveChatController::sendBotFlow:
     *   ['status' => 'success', 'message' => 'Bot flow sent (N step(s))']
     *   ['status' => 'error',   'message' => 'Bot flow partially sent (N/M steps)', 'errors' => [...]]
     *   ['status' => 'error',   'message' => '<reason>']
     *
     * @param  LiveChatSetting  $setting        per-channel Chatpion config
     * @param  string           $subscriberId   Messenger PSID (from_user_id)
     * @param  string           $postbackId     Chatpion bot postback_id (the flow ID)
     * @param  string|null      $agentName      Logged into Chatpion as the sender; defaults
     *                                          to the authed admin's name when null, else 'Admin'.
     */
    public function send(
        LiveChatSetting $setting,
        string $subscriberId,
        string $postbackId,
        ?string $agentName = null
    ): array {
        // 1. Load the bot flow template from local chatpion_import DB
        $bot = DB::connection('chatpion_import')
            ->table('messenger_bot_postback')
            ->where('page_id', $setting->fb_bot_id)
            ->where('postback_id', $postbackId)
            ->first(['template_jsoncode', 'bot_name']);

        if (! $bot || ! $bot->template_jsoncode) {
            Log::warning('SendBotFlow: bot flow not found', [
                'postbackId' => $postbackId,
                'page_id'    => $setting->fb_bot_id,
            ]);
            return ['status' => 'error', 'message' => 'Bot flow not found for this page'];
        }

        // 2. Get the page access token from local chatpion_import DB
        $pageInfo = DB::connection('chatpion_import')
            ->table('facebook_rx_fb_page_info')
            ->where('id', $setting->fb_bot_id)
            ->first(['page_access_token', 'page_id as fb_page_id']);

        if (! $pageInfo || ! $pageInfo->page_access_token) {
            return ['status' => 'error', 'message' => 'Page access token not found'];
        }

        $token = $pageInfo->page_access_token;

        // 3. Replace subscriber ID placeholders, then parse template
        $jsonStr  = str_replace('#SUBSCRIBER_ID_REPLACE#', $subscriberId, $bot->template_jsoncode);
        $template = json_decode($jsonStr, true);
        $messages = $this->extractBotFlowMessages($template ?? []);

        if (empty($messages)) {
            return ['status' => 'error', 'message' => 'No sendable messages in bot flow'];
        }

        // 4. Send each message step directly via Facebook Graph API
        $errors    = [];
        $sent      = 0;
        $fbPageId  = $pageInfo->fb_page_id ?? 'me';   // e.g. "103196179075087"

        $resolvedAgentName = $agentName
            ?? (auth()->user()->name ?? 'Admin');

        $chatpion = new ChatpionFailoverService($setting);
        foreach ($messages as $fbMsg) {
            $payload = [
                'recipient'      => ['id' => $subscriberId],
                'messaging_type' => 'MESSAGE_TAG',
                'tag'            => 'HUMAN_AGENT',
                'message'        => $fbMsg,
            ];

            $res = Http::timeout(15)
                ->post("https://graph.facebook.com/v18.0/me/messages?access_token={$token}", $payload);

            if ($res->successful()) {
                $sent++;
                $fbMid = $res->json()['message_id'] ?? '';
                $cleanText = $fbMsg['text'] ?? ($fbMsg['attachment']['payload']['text'] ?? '');
                $chatpion->apiPost('livechat_log_message', [
                    'subscriber_id'    => $subscriberId,
                    'whatsapp_bot_id'  => $setting->fb_bot_id,
                    'message_content'  => json_encode(['recipient'=>['id'=>$subscriberId],'message'=>$fbMsg]),
                    'message'          => $cleanText,
                    'message_type'     => isset($fbMsg['attachment']) ? ($fbMsg['attachment']['type'] ?: 'template') : 'text',
                    'direction'        => 'out',
                    'sender'           => 'bot',
                    'agent_name'       => $resolvedAgentName,
                    'fb_message_id'    => $fbMid,
                ]);
            } else {
                $errors[] = ['step' => $sent + 1, 'error' => $res->json()];
                Log::error('SendBotFlow FB API error', ['step' => $sent + 1, 'response' => $res->json(), 'payload' => $payload]);
            }
        }

        Log::info('SendBotFlow Direct FB', [
            'page_id'       => $setting->fb_bot_id,
            'subscriber_id' => $subscriberId,
            'postback_id'   => $postbackId,
            'bot_name'      => $bot->bot_name,
            'steps_total'   => count($messages),
            'steps_sent'    => $sent,
            'errors'        => $errors,
        ]);

        if (! empty($errors)) {
            return [
                'status'  => 'error',
                'message' => "Bot flow partially sent ({$sent}/" . count($messages) . " steps)",
                'errors'  => $errors,
            ];
        }

        return [
            'status'  => 'success',
            'message' => "Bot flow sent ({$sent} step" . ($sent !== 1 ? 's' : '') . ')',
        ];
    }

    /**
     * Parse Chatpion template_jsoncode and return an array of Facebook-ready
     * message objects (each ready to put in the "message" key of the Graph API call).
     *
     * Chatpion stores templates in two formats:
     *   Format A (visual flow): {"out":[{"reply":{"webhook_response":[...]}}]}
     *   Format B (legacy):      {"1":{...}, "2":{...}, ...}
     *
     * Each step's "message" object contains Chatpion meta-keys that must be stripped.
     */
    protected function extractBotFlowMessages(array $template): array
    {
        $messages   = [];
        $stripKeys  = ['typing_on_settings', 'delay_in_reply', 'text_reply_unique_id', 'template_type'];

        if (isset($template['out'])) {
            // Format A — visual flow builder
            foreach ($template['out'] as $out) {
                $responses = $out['reply']['webhook_response'] ?? [];
                foreach ($responses as $resp) {
                    $msg = $this->buildFbMessagePayload($resp['message'] ?? [], $stripKeys);
                    if ($msg !== null) {
                        $messages[] = $msg;
                    }
                }
            }
        } else {
            // Format B — legacy numbered steps {"1":{...}, "2":{...}}
            foreach ($template as $key => $step) {
                if (! is_numeric($key)) {
                    continue;
                }
                $msg = $this->buildFbMessagePayload($step['message'] ?? [], $stripKeys);
                if ($msg !== null) {
                    $messages[] = $msg;
                }
            }
        }

        return $messages;
    }

    /**
     * Strip Chatpion-specific meta keys from a raw message object,
     * returning only the Facebook Graph API-compatible fields.
     * Returns null if nothing useful remains.
     */
    protected function buildFbMessagePayload(array $raw, array $stripKeys): ?array
    {
        $msg = array_diff_key($raw, array_flip($stripKeys));

        // Must have at least one of: text, attachment, quick_replies
        if (empty($msg) || (! isset($msg['text']) && ! isset($msg['attachment']) && ! isset($msg['quick_replies']))) {
            return null;
        }

        return $msg;
    }
}
