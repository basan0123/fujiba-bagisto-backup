<?php

namespace Fujiba\Dashboard\Http\Controllers\Admin;

use Fujiba\Dashboard\Http\Controllers\Admin\Concerns\ResolvesDashboardPeriod;
use Fujiba\Dashboard\Repositories\SubscribersRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Core\Models\Channel;

class SubscribersController extends Controller
{
    use ResolvesDashboardPeriod;

    public function __construct(private readonly SubscribersRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $channelCode   = (string) $request->query('channel', '');
        [$start, $end] = $this->resolvePeriod($request);

        if ($channelCode === '') {
            $channelName = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
        } else {
            $channel = Channel::where('code', $channelCode)->first();
            if (! $channel) {
                $channelCode = '';
                $channelName = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
            } else {
                $channelName = $channel->name;
            }
        }

        return response()->json([
            'channel_name' => $channelName,
            'period'       => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ],
            'stats'        => $this->repo->getStats($channelCode, $start, $end),
            'fetched_at'   => now()->toIso8601String(),
        ]);
    }
}
