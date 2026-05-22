<?php

namespace Fujiba\Dashboard\Http\Controllers\Admin;

use Fujiba\Dashboard\Http\Controllers\Admin\Concerns\ResolvesDashboardPeriod;
use Fujiba\Dashboard\Repositories\ShippingPerformanceRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\Channel;

class ShippingPerformanceController extends Controller
{
    use ResolvesDashboardPeriod;

    public function __construct(private readonly ShippingPerformanceRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $channelCode   = (string) $request->query('channel', '');
        $mode          = $request->query('mode') === 'payments' ? 'payments' : 'orders';
        [$start, $end] = $this->resolvePeriod($request);

        $channelName = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
        if ($channelCode !== '') {
            $channel = Channel::where('code', $channelCode)->first();
            if (! $channel) {
                Log::warning('Fujiba\\Dashboard shipping-performance: unknown channel code, falling back to all-channels.', [
                    'channel_code' => $channelCode,
                ]);
                $channelCode = '';
            } else {
                $channelName = $channel->name;
            }
        }

        return response()->json([
            'channel_name' => $channelName,
            'mode'         => $mode,
            'period'       => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ],
            'summary'      => $this->repo->getSummary($channelCode, $start, $end, $mode),
            'fetched_at'   => $this->repo->getFetchedAt(),
        ]);
    }
}
