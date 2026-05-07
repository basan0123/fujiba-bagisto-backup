<?php

namespace Fujiba\Dashboard\Listeners;

use Carbon\Carbon;
use Fujiba\Dashboard\Repositories\BusinessOverviewRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Core\Models\Channel;
use Webkul\Theme\ViewRenderEventManager;

class BusinessOverviewListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        try {
            $repo = app(BusinessOverviewRepository::class);

            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfDay();

            $posActive = Cache::remember(
                'fujiba.dashboard.pos_active',
                300,
                fn () => DB::table('pos_order')->exists()
            );

            $channelCode = (string) request()->query('channel', '');
            $mode = request()->query('mode') === 'payments' ? 'payments' : 'orders';

            // Resolve channel for first-paint display (matches controller logic).
            if ($channelCode === '') {
                $payload = [
                    'is_single_column' => true,
                    'channel_name'     => trans('fujiba::app.dashboard.inventory_value.all_channels_label'),
                    'mode'             => $mode,
                    'period'           => [
                        'start' => $start->toDateString(),
                        'end'   => $end->toDateString(),
                    ],
                    'overview'         => $repo->getOverview('', $start, $end, $mode),
                    'pos_active'       => $posActive,
                    'fetched_at'       => $repo->getFetchedAt(),
                ];
            } else {
                $channel = Channel::where('code', $channelCode)->first();
                if ($channel) {
                    $payload = [
                        'is_single_column' => false,
                        'channel_name'     => $channel->name,
                        'mode'             => $mode,
                        'period'           => [
                            'start' => $start->toDateString(),
                            'end'   => $end->toDateString(),
                        ],
                        'overview'         => $repo->getOverview($channelCode, $start, $end, $mode),
                        'pos_active'       => $posActive,
                        'fetched_at'       => $repo->getFetchedAt(),
                    ];
                } else {
                    $payload = [
                        'is_single_column' => true,
                        'channel_name'     => trans('fujiba::app.dashboard.inventory_value.all_channels_label'),
                        'mode'             => $mode,
                        'period'           => [
                            'start' => $start->toDateString(),
                            'end'   => $end->toDateString(),
                        ],
                        'overview'         => $repo->getOverview('', $start, $end, $mode),
                        'pos_active'       => $posActive,
                        'fetched_at'       => $repo->getFetchedAt(),
                    ];
                }
            }

            $event->addTemplate('fujiba::admin.dashboard.business-overview', [
                'businessOverviewInitial' => $payload,
            ]);
        } catch (Throwable $e) {
            Log::warning('Fujiba\\Dashboard BusinessOverviewListener failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
