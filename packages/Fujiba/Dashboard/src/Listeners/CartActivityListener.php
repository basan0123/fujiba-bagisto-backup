<?php

namespace Fujiba\Dashboard\Listeners;

use Fujiba\Dashboard\Repositories\CartActivityRepository;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Core\Models\Channel;
use Webkul\Theme\ViewRenderEventManager;

class CartActivityListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        try {
            $repo        = app(CartActivityRepository::class);
            $channelCode = (string) request()->query('channel', '');

            if ($channelCode === '') {
                $isSingleColumn = true;
                $channelName    = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
            } else {
                $channel = Channel::where('code', $channelCode)->first();
                if ($channel) {
                    $isSingleColumn = false;
                    $channelName    = $channel->name;
                } else {
                    $isSingleColumn = true;
                    $channelName    = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
                    $channelCode    = '';
                }
            }

            $payload = [
                'is_single_column' => $isSingleColumn,
                'channel_name'     => $channelName,
                'cart_activity'    => $repo->latest($channelCode),
                'fetched_at'       => now()->toIso8601String(),
            ];

            $event->addTemplate('fujiba::admin.dashboard.cart-activity', [
                'cartActivityInitial' => $payload,
            ]);
        } catch (Throwable $e) {
            Log::warning('Fujiba\\Dashboard CartActivityListener failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
