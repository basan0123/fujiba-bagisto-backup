<?php

namespace Fujiba\Dashboard\Listeners;

use Carbon\Carbon;
use Fujiba\Dashboard\Repositories\PaymentMethodsRepository;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Core\Models\Channel;
use Webkul\Theme\ViewRenderEventManager;

class PaymentMethodsListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        try {
            $repo        = app(PaymentMethodsRepository::class);
            $channelCode = (string) request()->query('channel', '');
            $mode        = request()->query('mode') === 'payments' ? 'payments' : 'orders';

            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfDay();

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
                'mode'             => $mode,
                'period'           => [
                    'start' => $start->toDateString(),
                    'end'   => $end->toDateString(),
                ],
                'payment_methods'  => $repo->summary($channelCode, $start, $end, $mode),
                'fetched_at'       => now()->toIso8601String(),
            ];

            $event->addTemplate('fujiba::admin.dashboard.payment-pie', [
                'paymentMethodsInitial' => $payload,
            ]);
        } catch (Throwable $e) {
            Log::warning('Fujiba\\Dashboard PaymentMethodsListener failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
