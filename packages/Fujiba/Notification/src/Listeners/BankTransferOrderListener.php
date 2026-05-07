<?php

namespace Fujiba\Notification\Listeners;

use Fujiba\LiveChat\Models\LiveChatSetting;
use Fujiba\LiveChat\Services\BotFlowSender;
use Fujiba\Notification\Models\CustomerChannelToken;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;

/**
 * Auto-send the ChatPion "payment" bot flow to the customer when an order
 * is placed with payment_method=moneytransfer (送金 / bank transfer).
 *
 * Replaces the manual workflow of opening /admin/fujiba-livechat, finding
 * the customer's thread, and clicking ▶ on the "payment" flow item. Same
 * end result — same flow, same images, same buttons, same LiveChat-thread
 * visibility — but fires automatically at order-save time.
 *
 * Architecture:
 *   - Listens on checkout.order.save.after, filters payment_method=moneytransfer
 *   - Looks up the customer's Messenger PSID from customer_channel_tokens
 *     (populated by the backfill command + new MessengerLogin signups)
 *   - Resolves the per-channel ChatPion postback_id from the PAYMENT_FLOWS map
 *   - Delegates to Fujiba\LiveChat\Services\BotFlowSender::send() — the same
 *     service that powers the manual ▶ button in the LiveChat admin UI
 *
 * BotFlowSender reads the flow template from chatpion_import.messenger_bot_postback,
 * parses it into Graph-API-compatible message steps, sends each via Facebook,
 * and logs each step back into ChatPion via api/livechat_log_message so the
 * messages appear in the LiveChat conversation thread.
 *
 * Why a separate listener (not branching inside OrderEventListener):
 *   - Smaller blast radius — adding/removing this feature touches one file.
 *   - Bypasses the notification_templates pipeline entirely (this isn't a
 *     templated text message, it's a flow replay).
 *
 * Failure mode: notification failures must not break order save. All errors
 * caught and logged; never thrown.
 */
class BankTransferOrderListener
{
    public const PAYMENT_METHOD = 'moneytransfer';

    /**
     * Bagisto channel_id => ChatPion postback_id (the "payment" flow per channel)
     *
     * Resolved from the LiveChat admin UI on 2026-05-06:
     *   kimono LiveChat → ▶ payment → data-pb=GLaemLrwKQZNvbV (page_id=71)
     *   mmfood LiveChat → ▶ payment → data-pb=6852b9d3dc603   (page_id=69)
     *
     * Hardcoded so we don't depend on chatpion_import (stale snapshot) at
     * dispatch time. If a channel is added or its payment flow renamed,
     * update here. Loud-fail on unknown channel.
     */
    private const PAYMENT_FLOWS = [
        1 => 'GLaemLrwKQZNvbV',  // kimono
        2 => '6852b9d3dc603',    // mmfood
    ];

    public function __construct(
        protected BotFlowSender $botFlowSender,
    ) {}

    public function handle(mixed $payload): void
    {
        // Honor the master Fujiba-notification kill-switch (same one used by
        // OrderEventListener). One toggle disables both paths.
        $enabled = core()->getConfigData('emails.general.fujiba_notification.enabled');
        if ($enabled !== null && ! (int) $enabled) {
            return;
        }

        try {
            if (! $payload instanceof Order) {
                return;
            }

            $order = $payload;

            // 1. Filter to bank-transfer orders only.
            if ($order->payment?->method !== self::PAYMENT_METHOD) {
                return;
            }

            // 2. Skip POS orders — bank transfer is online-only and POS counter
            //    sales don't go through Messenger anyway. Mirrors the POS-suppression
            //    logic in OrderEventListener::isPosOrder for consistency.
            if ($this->isPosOrder($order)) {
                return;
            }

            $customerId = $order->customer_id;
            if (! $customerId) {
                Log::info('[BankTransfer] Skipped — guest order has no customer_id', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            // 3. Resolve the customer's Messenger token. customer_channel_tokens
            //    is the canonical PSID source (per project decision Q1).
            $token = CustomerChannelToken::query()
                ->active()
                ->where('customer_id', $customerId)
                ->where('provider', CustomerChannelToken::PROVIDER_MESSENGER)
                ->orderByDesc('verified_at')
                ->first();

            if (! $token) {
                Log::warning('[BankTransfer] No active messenger token — manual send fallback', [
                    'order_id'    => $order->id,
                    'customer_id' => $customerId,
                ]);
                return;
            }

            // 4. Resolve channel + postback_id.
            $channelId = (int) $order->channel_id;
            $postbackId = self::PAYMENT_FLOWS[$channelId] ?? null;

            if ($postbackId === null) {
                Log::error('[BankTransfer] No payment flow configured for channel', [
                    'order_id'   => $order->id,
                    'channel_id' => $channelId,
                ]);
                return;
            }

            // 5. Resolve LiveChat setting (BotFlowSender needs this for fb_bot_id
            //    + ChatPion API key + server URL).
            $tenantId = config('app.tenant_id') ?? tenant('id');
            $setting  = LiveChatSetting::where('tenant_id', $tenantId)
                ->where('channel_id', $channelId)
                ->first();

            if (! $setting) {
                Log::error('[BankTransfer] No LiveChat setting for channel', [
                    'order_id'   => $order->id,
                    'tenant_id'  => $tenantId,
                    'channel_id' => $channelId,
                ]);
                return;
            }

            // 6. Fire the flow. BotFlowSender handles parsing, multi-step Graph
            //    sends, error collection, and ChatPion-side log_message echo.
            $result = $this->botFlowSender->send(
                $setting,
                $token->token,       // PSID
                $postbackId,
                'Bagisto (auto)',    // agent_name shown in ChatPion log
            );

            Log::info('[BankTransfer] Flow dispatched', [
                'order_id'    => $order->id,
                'customer_id' => $customerId,
                'channel_id'  => $channelId,
                'psid'        => $token->token,
                'postback_id' => $postbackId,
                'result'      => $result,
            ]);
        } catch (\Throwable $e) {
            // Never break order save.
            Log::error('[BankTransfer] Listener failed', [
                'order_id' => isset($order) ? $order?->id : null,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Detect POS orders so counter sales don't trigger a Messenger flow.
     * Same three-detector pattern as OrderEventListener::isPosOrder.
     */
    protected function isPosOrder(Order $order): bool
    {
        try {
            if (\Illuminate\Support\Facades\Auth::guard('pos')->check()) {
                return true;
            }
        } catch (\Throwable $e) {
            // pos guard not registered in CLI/queue contexts — fall through.
        }

        $request = request();
        if ($request && method_exists($request, 'is') && $request->is('pos/*', 'pos')) {
            return true;
        }

        return \Illuminate\Support\Facades\DB::table('pos_order')
            ->where('order_id', $order->id)
            ->exists();
    }
}
