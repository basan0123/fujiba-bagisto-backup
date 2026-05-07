<?php

namespace Fujiba\Dashboard\Http\Controllers\Admin;

use Fujiba\Dashboard\Repositories\InventoryValueRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\Channel;

class InventoryValueController extends Controller
{
    public function __construct(private readonly InventoryValueRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $channelCode = (string) $request->query('channel', '');

        // All-channels mode: single-column response, scope = tenant-wide.
        if ($channelCode === '') {
            return response()->json([
                'is_single_column' => true,
                'channel_name'     => trans('fujiba::app.dashboard.inventory_value.all_channels_label'),
                'current'          => $this->repo->getAllCompanyTotals(),
                'missing'          => $this->repo->getMissingCostCountAllChannels(),
                'fetched_at'       => $this->repo->getFetchedAt(),
            ]);
        }

        // Per-channel mode: two-column response (left = channel, right = all-company).
        $channel = Channel::where('code', $channelCode)->first();

        if (! $channel) {
            // Unknown channel code — fall through to all-channels with a logged warning.
            Log::warning('Fujiba\\Dashboard inventory-value: unknown channel code, falling back to all-channels.', [
                'channel_code' => $channelCode,
            ]);

            return response()->json([
                'is_single_column' => true,
                'channel_name'     => trans('fujiba::app.dashboard.inventory_value.all_channels_label'),
                'current'          => $this->repo->getAllCompanyTotals(),
                'missing'          => $this->repo->getMissingCostCountAllChannels(),
                'fetched_at'       => $this->repo->getFetchedAt(),
            ]);
        }

        return response()->json([
            'is_single_column' => false,
            'channel_name'     => $channel->name,
            'current'          => $this->repo->getChannelTotals((int) $channel->id),
            'all'              => $this->repo->getAllCompanyTotals(),
            'missing'          => $this->repo->getMissingCostCount((int) $channel->id),
            'fetched_at'       => $this->repo->getFetchedAt(),
        ]);
    }
}
