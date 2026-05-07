<?php

namespace Fujiba\Dashboard\Listeners;

use Carbon\Carbon;
use Fujiba\Dashboard\Repositories\SubscribersRepository;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Core\Models\Channel;
use Webkul\Theme\ViewRenderEventManager;

class SubscribersListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        try {
            $repo = app(SubscribersRepository::class);

            $channelCode = (string) request()->query('channel', '');
            $channelName = trans('fujiba::app.dashboard.inventory_value.all_channels_label');

            if ($channelCode !== '') {
                $channel = Channel::where('code', $channelCode)->first();
                if ($channel) {
                    $channelName = $channel->name;
                } else {
                    $channelCode = '';
                }
            }

            // First-paint period mirrors the BusinessOverviewListener default
            // (month-to-date). The Vue widget re-fetches with the user's
            // saved/selected filters once `reporting-filter-updated` fires.
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfDay();

            $payload = [
                'channel_name' => $channelName,
                'period'       => [
                    'start' => $start->toDateString(),
                    'end'   => $end->toDateString(),
                ],
                'stats'        => $repo->getStats($channelCode, $start, $end),
                'fetched_at'   => now()->toIso8601String(),
            ];

            $event->addTemplate('fujiba::admin.dashboard.subscribers', [
                'subscribersInitial' => $payload,
            ]);
        } catch (Throwable $e) {
            Log::warning('Fujiba\\Dashboard SubscribersListener failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
