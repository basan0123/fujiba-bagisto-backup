<?php

namespace Modules\FujibaLiveChat\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifyOrderUpdate
{
    /**
     * Handle the sales.order.update-status.after event.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     */
    public function handle($order): void
    {
        try {
            // Look up Messenger subscriber linked to this customer
            $map = \DB::table('messenger_page_channel_map')
                ->where('customer_id', $order->customer_id)
                ->first();

            if (! $map) {
                return;
            }

            // Load per-channel settings (with a short cache)
            $settings = \Cache::remember("livechat_settings_{$order->channel_id}", 300, function () use ($order) {
                return \DB::table('fujiba_livechat_settings')
                    ->where('channel_id', $order->channel_id)
                    ->first();
            });

            if (! $settings || ! $settings->enabled) {
                return;
            }

            $baseUrl = rtrim($settings->chatpion_url, '/');

            Http::timeout(8)->post("{$baseUrl}/api/send_bagisto_notification", [
                'api_key'       => $settings->api_key,
                'subscriber_id' => $map->subscriber_id,
                'order_id'      => $order->increment_id,
                'status'        => $order->status,
                'tracking_no'   => $order->shipments->first()?->track_number ?? '',
                'btn_url'       => route('shop.customers.account.orders.view', $order->id),
            ]);
        } catch (\Exception $e) {
            Log::warning('FujibaLiveChat: order notification failed — '.$e->getMessage());
        }
    }
}
