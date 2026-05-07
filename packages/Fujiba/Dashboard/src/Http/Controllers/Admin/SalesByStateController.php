<?php

namespace Fujiba\Dashboard\Http\Controllers\Admin;

use Fujiba\Dashboard\Http\Controllers\Admin\Concerns\ResolvesDashboardPeriod;
use Fujiba\Dashboard\Repositories\OrdersByRegionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\Channel;

class SalesByStateController extends Controller
{
    use ResolvesDashboardPeriod;

    public function __construct(private readonly OrdersByRegionRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $channelCode   = (string) $request->query('channel', '');
        $mode          = $request->query('mode') === 'payments' ? 'payments' : 'orders';
        [$start, $end] = $this->resolvePeriod($request);

        if ($channelCode === '') {
            $channelName = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
        } else {
            $channel = Channel::where('code', $channelCode)->first();
            if (! $channel) {
                Log::warning('Fujiba\\Dashboard sales-by-state: unknown channel code, falling back to all-channels.', [
                    'channel_code' => $channelCode,
                ]);
                $channelCode = '';
                $channelName = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
            } else {
                $channelName = $channel->name;
            }
        }

        return response()->json([
            'channel_name'   => $channelName,
            'mode'           => $mode,
            'period'         => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ],
            'sales_by_state' => $this->repo->salesByState($channelCode, $start, $end, $mode),
            'fetched_at'     => now()->toIso8601String(),
        ]);
    }
}
