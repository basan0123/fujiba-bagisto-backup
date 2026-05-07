<?php

namespace Fujiba\LiveChat\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Fujiba\LiveChat\Models\LiveChatSetting;
use Fujiba\LiveChat\Services\ChatpionFailoverService;

class OrderStatusNotifier
{
    public function handle($event): void
    {
        try {
            $order = $event->order ?? $event;

            // Get customer's messenger subscriber_id
            $customer = DB::table('customers')
                ->where('id', $order->customer_id)
                ->select('messenger_subscriber_id', 'messenger_page_id')
                ->first();

            if (!$customer || !$customer->messenger_subscriber_id) {
                return;
            }

            // Get setting for this channel
            $tenantId = $order->tenant_id ?? config('app.tenant_id', '');
            $setting  = LiveChatSetting::where('tenant_id', $tenantId)
                ->where('channel_id', $order->channel_id)
                ->first();

            if (!$setting || !$setting->order_notifications_enabled) {
                return;
            }

            $chatpion = new ChatpionFailoverService($setting);

            // Build order URL
            $orderUrl = route('shop.customers.account.orders.view', ['id' => $order->id]);

            $chatpion->post('api/send_bagisto_notification', [
                'api_key'       => $setting->chatpion_api_key,
                'subscriber_id' => $customer->messenger_subscriber_id,
                'order_id'      => $order->increment_id,
                'status'        => $order->status,
                'tracking_no'   => $order->shipments->first()?->track_number ?? '',
                'btn_url'       => $orderUrl,
            ]);

        } catch (\Exception $e) {
            Log::error("OrderStatusNotifier failed: " . $e->getMessage());
        }
    }
}
