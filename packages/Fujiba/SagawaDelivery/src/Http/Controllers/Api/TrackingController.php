<?php

namespace Fujiba\SagawaDelivery\Http\Controllers\Api;

use Fujiba\SagawaDelivery\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TrackingController extends Controller
{
    /**
     * GET /api/v1/sagawa/tracking?order_ids=1,2,3
     *
     * Batch-resolve shipment + tracking info for the customer's order list.
     * One DB hit, no live Sagawa polling here — the queue/jobs already keep
     * Shipment.status fresh. Returned shape is keyed by order_id so the PWA
     * can render a horizontal timeline per card without a per-order request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $customer = auth()->guard('api')->user() ?? auth('customer')->user();
        if (! $customer) {
            return response()->json(['data' => []]);
        }

        $ids = collect(explode(',', (string) $request->query('order_ids', '')))
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->take(100)
            ->values()
            ->all();

        if (empty($ids)) {
            return response()->json(['data' => []]);
        }

        // Restrict to orders that belong to this customer.
        $ownedIds = \DB::table('orders')
            ->whereIn('id', $ids)
            ->where('customer_id', $customer->id)
            ->pluck('id')
            ->all();

        if (empty($ownedIds)) {
            return response()->json(['data' => []]);
        }

        $shipments = Shipment::whereIn('order_id', $ownedIds)
            ->orderBy('id', 'desc')
            ->get()
            ->keyBy('order_id');

        $data = [];
        foreach ($ownedIds as $oid) {
            /** @var Shipment|null $s */
            $s = $shipments->get($oid);

            if (! $s) {
                $data[(string) $oid] = null;
                continue;
            }

            $data[(string) $oid] = [
                'tracking_number' => $s->tracking_number,
                'status'          => $s->status,
                'status_step'     => $this->statusToStep($s->status),
                'delivery_date'   => $s->delivery_date?->toDateString(),
                'delivery_time'   => $s->delivery_time,
                'shipped_at'      => $s->shipped_at?->toIso8601String(),
                'tracking_url'    => $s->tracking_number
                    ? 'https://k2k.sagawa-exp.co.jp/p/sagawa/web/okurijoinput.jsp?okurijoNo=' . $s->tracking_number
                    : null,
            ];
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Translate a sagawa shipment status into one of 4 timeline steps so the
     * PWA can highlight a horizontal dot strip without knowing every internal
     * sub-status.
     *
     *   0 = pending verification
     *   1 = preparing waybill
     *   2 = shipped (in transit)
     *   3 = delivered (no Sagawa-API hook yet — stays at "shipped" once issued)
     */
    private function statusToStep(?string $status): int
    {
        return match ($status) {
            Shipment::STATUS_PENDING_VERIFICATION,
            Shipment::STATUS_ADDRESS_REJECTED  => 0,
            Shipment::STATUS_ADDRESS_VERIFIED,
            Shipment::STATUS_WAYBILL_PENDING,
            Shipment::STATUS_REISSUE_REQUESTED => 1,
            Shipment::STATUS_WAYBILL_ISSUED,
            Shipment::STATUS_WAYBILL_FAILED    => 2,
            default                            => 0,
        };
    }
}
