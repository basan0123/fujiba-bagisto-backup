<?php

namespace Fujiba\Dashboard\Listeners;

use Carbon\Carbon;
use Fujiba\Dashboard\Repositories\ShippingPerformanceRepository;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Core\Models\Channel;
use Webkul\Theme\ViewRenderEventManager;

class ShippingPerformanceListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        try {
            $repo = app(ShippingPerformanceRepository::class);

            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfDay();

            $channelCode = (string) request()->query('channel', '');
            $mode        = request()->query('mode') === 'payments' ? 'payments' : 'orders';

            $channelName = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
            if ($channelCode !== '') {
                $channel = Channel::where('code', $channelCode)->first();
                if (! $channel) {
                    $channelCode = '';
                } else {
                    $channelName = $channel->name;
                }
            }

            $payload = [
                'channel_name' => $channelName,
                'mode'         => $mode,
                'period'       => [
                    'start' => $start->toDateString(),
                    'end'   => $end->toDateString(),
                ],
                'summary'      => $repo->getSummary($channelCode, $start, $end, $mode),
                'fetched_at'   => $repo->getFetchedAt(),
            ];

            $event->addTemplate('fujiba::admin.dashboard.shipping-performance', [
                'shippingPerformanceInitial' => $payload,
            ]);
        } catch (Throwable $e) {
            Log::warning('Fujiba\\Dashboard ShippingPerformanceListener failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
