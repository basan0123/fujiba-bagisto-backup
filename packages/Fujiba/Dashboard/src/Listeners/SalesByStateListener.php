<?php

namespace Fujiba\Dashboard\Listeners;

use Carbon\Carbon;
use Fujiba\Dashboard\Repositories\OrdersByRegionRepository;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Core\Models\Channel;
use Webkul\Theme\ViewRenderEventManager;

class SalesByStateListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        try {
            $repo        = app(OrdersByRegionRepository::class);
            $channelCode = (string) request()->query('channel', '');
            $mode        = request()->query('mode') === 'payments' ? 'payments' : 'orders';

            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfDay();

            if ($channelCode === '') {
                $channelName = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
            } else {
                $channel = Channel::where('code', $channelCode)->first();
                if ($channel) {
                    $channelName = $channel->name;
                } else {
                    $channelName = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
                    $channelCode = '';
                }
            }

            $payload = [
                'channel_name'   => $channelName,
                'mode'           => $mode,
                'period'         => [
                    'start' => $start->toDateString(),
                    'end'   => $end->toDateString(),
                ],
                'sales_by_state' => $repo->salesByState($channelCode, $start, $end, $mode),
                'fetched_at'     => now()->toIso8601String(),
            ];

            $event->addTemplate('fujiba::admin.dashboard.sales-by-state', [
                'salesByStateInitial' => $payload,
            ]);
        } catch (Throwable $e) {
            Log::warning('Fujiba\\Dashboard SalesByStateListener failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
