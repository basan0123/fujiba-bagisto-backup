<?php

namespace Fujiba\SagawaDelivery\Http\Controllers;

use Fujiba\SagawaDelivery\Services\DeliveryDateCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DeliverySlotController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'prefecture' => 'nullable|string|max:32',
            'channel_id' => 'nullable|integer',
        ]);

        $prefecture = (string) $request->input('prefecture', '');
        $channelId  = (int) ($request->input('channel_id') ?: optional(core()->getCurrentChannel())->id ?: 0);

        $calc = $channelId > 0
            ? DeliveryDateCalculator::forChannel($channelId)
            : DeliveryDateCalculator::fromConfig();

        $dates = $prefecture !== ''
            ? $calc->availableDates($prefecture)
            : [];

        $timeSlotsMap = (array) config('sagawa.time_slots', []);

        return response()->json([
            'prefecture' => $prefecture,
            'dates'      => $dates,
            'time_slots' => $timeSlotsMap,
            'slots'      => collect($timeSlotsMap)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'earliest'   => $dates[0] ?? null,
        ]);
    }
}
