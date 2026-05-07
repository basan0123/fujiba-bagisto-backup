<?php

namespace Fujiba\Dashboard\Http\Controllers\Admin;

use Fujiba\Dashboard\Http\Controllers\Admin\Concerns\ResolvesDashboardPeriod;
use Fujiba\Dashboard\Repositories\PaymentMethodsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\Channel;

class PaymentMethodsController extends Controller
{
    use ResolvesDashboardPeriod;

    public function __construct(private readonly PaymentMethodsRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $channelCode    = (string) $request->query('channel', '');
        $mode           = $request->query('mode') === 'payments' ? 'payments' : 'orders';
        [$start, $end]  = $this->resolvePeriod($request);

        if ($channelCode === '') {
            $isSingleColumn = true;
            $channelName    = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
        } else {
            $channel = Channel::where('code', $channelCode)->first();
            if (! $channel) {
                Log::warning('Fujiba\\Dashboard payment-methods: unknown channel code, falling back to all-channels.', [
                    'channel_code' => $channelCode,
                ]);
                $channelCode    = '';
                $isSingleColumn = true;
                $channelName    = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
            } else {
                $isSingleColumn = false;
                $channelName    = $channel->name;
            }
        }

        return response()->json([
            'is_single_column' => $isSingleColumn,
            'channel_name'     => $channelName,
            'mode'             => $mode,
            'period'           => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ],
            'payment_methods'  => $this->repo->summary($channelCode, $start, $end, $mode),
            'fetched_at'       => now()->toIso8601String(),
        ]);
    }
}
