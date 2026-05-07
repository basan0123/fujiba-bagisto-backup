<?php

namespace Fujiba\Dashboard\Http\Controllers\Admin;

use Fujiba\Dashboard\Http\Controllers\Admin\Concerns\ResolvesDashboardPeriod;
use Fujiba\Dashboard\Repositories\BusinessOverviewRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\Channel;

class BusinessOverviewController extends Controller
{
    use ResolvesDashboardPeriod;

    private const POS_ACTIVE_TTL = 300;

    public function __construct(private readonly BusinessOverviewRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $channelCode    = (string) $request->query('channel', '');
        $mode           = $request->query('mode') === 'payments' ? 'payments' : 'orders';
        [$start, $end]  = $this->resolvePeriod($request);

        $posActive = Cache::remember(
            'fujiba.dashboard.pos_active',
            self::POS_ACTIVE_TTL,
            fn () => DB::table('pos_order')->exists()
        );

        // Resolve channel for display name + is_single_column flag.
        if ($channelCode === '') {
            $isSingleColumn = true;
            $channelName    = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
        } else {
            $channel = Channel::where('code', $channelCode)->first();

            if (! $channel) {
                Log::warning('Fujiba\\Dashboard business-overview: unknown channel code, falling back to all-channels.', [
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
            'overview'         => $this->repo->getOverview($channelCode, $start, $end, $mode),
            'pos_active'       => $posActive,
            'fetched_at'       => $this->repo->getFetchedAt(),
        ]);
    }
}
