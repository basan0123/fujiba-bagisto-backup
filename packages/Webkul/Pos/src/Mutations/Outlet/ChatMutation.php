<?php

namespace Webkul\Pos\Mutations\Outlet;

use App\Http\Controllers\Controller;
use Fujiba\LiveChat\Services\BotFlowSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Services\PosMessengerService;

/**
 * POS Messenger writes. All channel-scoped to the agent's outlet → Facebook
 * page. Every action delegates to PosMessengerService / the existing
 * Fujiba\LiveChat BotFlowSender — there is no local chat storage.
 *
 * Conversation ids ARE Messenger PSIDs. Each resolver returns a
 * {success, message} ChatActionResult so the RN client has one uniform shape.
 */
class ChatMutation extends Controller
{
    public function __construct(protected PosMessengerService $messenger = new PosMessengerService) {}

    private function channelId(GraphQLContext $context): ?int
    {
        $cid = $context->user()->outlet?->channel_id;

        return $cid !== null ? (int) $cid : null;
    }

    private function result(array $resp): array
    {
        $ok = ($resp['status'] ?? 'error') === 'success';

        return [
            'success' => $ok,
            'message' => $resp['message'] ?? ($ok ? 'Sent' : 'Failed'),
        ];
    }

    /** Agent posts a plain-text reply. */
    public function send($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'conversationId' => 'required|string',
            'body'           => 'required|string|max:5000',
        ]);
        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        return $this->result(
            $this->messenger->sendReply($this->channelId($context), (string) $args['conversationId'], trim($args['body']))
        );
    }

    /**
     * Fire a Chatpion bot flow to the conversation's Messenger subscriber.
     * Delegates to the same BotFlowSender the admin uses, so behaviour is
     * identical. The PSID is the conversation id directly.
     *
     * NOTE: Lighthouse field resolvers receive exactly (root, args, context,
     * ResolveInfo) — the 4th arg is ALWAYS ResolveInfo, so we must resolve
     * BotFlowSender from the container rather than type-hinting it as a param
     * (method injection only works on controllers, not GraphQL resolvers).
     */
    public function sendChatpionBotFlow($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'conversationId' => 'required|string',
            'postbackId'     => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $setting = $this->messenger->settingFor($this->channelId($context));
        if (! $setting) {
            return ['success' => false, 'message' => 'Messenger is not configured for this channel.'];
        }

        $psid      = (string) $args['conversationId'];
        $agentName = (string) ($context->user()->name ?? 'POS');

        $sender = app(BotFlowSender::class);
        $result = $sender->send($setting, $psid, (string) $args['postbackId'], $agentName);

        return $this->result($result);
    }

    /** Send a button template (offer / Pay Now / Order Ready). */
    public function sendButtons($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'conversationId' => 'required|string',
            'text'           => 'required|string|max:640',
            'buttons'        => 'required|string',
        ]);
        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $buttons = json_decode($args['buttons'], true);
        if (! is_array($buttons)) {
            throw new CustomException('buttons must be a JSON array.');
        }

        return $this->result(
            $this->messenger->sendButtons($this->channelId($context), (string) $args['conversationId'], (string) $args['text'], $buttons)
        );
    }

    /** Send a file attachment by public URL (PDF receipt / 請求書 / 納品書 / image). */
    public function sendAttachment($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'conversationId' => 'required|string',
            'url'            => 'required|url',
            'type'           => 'nullable|in:file,image,video,audio',
        ]);
        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        return $this->result(
            $this->messenger->sendAttachmentUrl(
                $this->channelId($context),
                (string) $args['conversationId'],
                (string) $args['url'],
                (string) ($args['type'] ?? 'file')
            )
        );
    }

    /**
     * Send a concise order summary as text, plus an optional Pay-Now button
     * when a payUrl is given. The order is looked up channel-scoped so an agent
     * can't summarise another store's order into a Messenger thread.
     */
    public function sendOrderSummary($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'conversationId' => 'required|string',
            'orderId'        => 'required|integer',
            'payUrl'         => 'nullable|url',
        ]);
        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $channelId = $this->channelId($context);
        $psid      = (string) $args['conversationId'];

        $order = DB::table('orders')
            ->where('id', (int) $args['orderId'])
            ->when($channelId !== null, fn ($q) => $q->where('channel_id', $channelId))
            ->first(['id', 'increment_id', 'grand_total', 'order_currency_code', 'status']);

        if (! $order) {
            return ['success' => false, 'message' => 'Order not found for this channel.'];
        }

        $lines = DB::table('order_items')
            ->where('order_id', $order->id)
            ->limit(20)
            ->get(['name', 'qty_ordered']);

        $currency = (string) ($order->order_currency_code ?? 'JPY');
        $body = "🧾 ご注文 #{$order->increment_id}\n";
        foreach ($lines as $li) {
            $qty = (int) ($li->qty_ordered ?? 1);
            $body .= "・{$li->name} × {$qty}\n";
        }
        $body .= "合計: {$currency} ".number_format((float) $order->grand_total);

        if (! empty($args['payUrl'])) {
            return $this->result(
                $this->messenger->sendButtons($channelId, $psid, $body, [
                    ['title' => 'お支払い / Pay Now', 'url' => (string) $args['payUrl']],
                ])
            );
        }

        return $this->result($this->messenger->sendReply($channelId, $psid, $body));
    }
}
