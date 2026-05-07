<?php

namespace Fujiba\Dashboard\Http\Controllers\Admin;

use Fujiba\Dashboard\Repositories\CartActivityRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\Channel;

class CartActivityController extends Controller
{
    public function __construct(private readonly CartActivityRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $channelCode = (string) $request->query('channel', '');

        if ($channelCode === '') {
            $isSingleColumn = true;
            $channelName    = trans('fujiba::app.dashboard.inventory_value.all_channels_label');
        } else {
            $channel = Channel::where('code', $channelCode)->first();

            if (! $channel) {
                Log::warning('Fujiba\\Dashboard cart-activity: unknown channel code, falling back to all-channels.', [
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
            'cart_activity'    => $this->repo->latest($channelCode),
            'fetched_at'       => now()->toIso8601String(),
        ]);
    }

    public function show($cart): JsonResponse
    {
        // Accept either an int route param or an auto-resolved Cart model.
        // Laravel's implicit binding picks up {cart} as a Cart instance when
        // the model exists; we just want the id.
        $cartId = is_object($cart) ? (int) $cart->id : (int) $cart;
        $detail = $this->repo->detail($cartId);

        if (! $detail) {
            return response()->json([
                'error' => trans('fujiba::app.dashboard.cart_activity.not_found'),
            ], 404);
        }

        return response()->json([
            'cart' => $detail,
        ]);
    }
}
