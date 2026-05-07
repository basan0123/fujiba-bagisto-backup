<?php

namespace App\Http\Controllers\Api\V1\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Pos\Models\InventoryAdjustment;
use Webkul\Pos\Models\StocktakeItem;
use Webkul\Pos\Models\StocktakeSession;

class StocktakeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agent    = auth('pos')->user();
        $outletId = $agent->outlet_id;

        if (! $outletId) {
            return response()->json(['error' => 'no_outlet'], 422);
        }

        $query = StocktakeSession::where('outlet_id', $outletId)
            ->orderByDesc('id');

        if ($from = $request->query('from')) {
            $query->where('start_date', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->where('start_date', '<=', $to);
        }

        $limit    = min(200, max(1, (int) $request->query('limit', 50)));
        $sessions = $query->limit($limit)->get()->map(fn ($s) => $this->serializeSession($s));

        return response()->json(['data' => $sessions]);
    }

    public function show(int $id): JsonResponse
    {
        $agent   = auth('pos')->user();
        $session = StocktakeSession::where('outlet_id', $agent->outlet_id)
            ->with('items')
            ->find($id);

        if (! $session) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json([
            'data'  => $this->serializeSession($session),
            'items' => $session->items->map(fn ($it) => [
                'id'          => $it->id,
                'product_id'  => $it->product_id,
                'sku'         => $it->sku,
                'system_qty'  => (int) $it->system_qty,
                'counted_qty' => $it->counted_qty === null ? null : (int) $it->counted_qty,
                'difference'  => $it->difference === null ? null : (int) $it->difference,
            ])->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date'             => 'nullable|date',
            'notes'                  => 'nullable|string|max:2000',
            'status'                 => 'nullable|in:in_progress,completed',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|integer',
            'items.*.sku'            => 'nullable|string|max:255',
            'items.*.system_qty'     => 'required|integer|min:0',
            'items.*.counted_qty'    => 'required|integer|min:0',
        ]);

        $agent    = auth('pos')->user();
        $outletId = $agent->outlet_id;

        if (! $outletId) {
            return response()->json(['error' => 'no_outlet'], 422);
        }

        $status = $data['status'] ?? 'completed';

        return DB::transaction(function () use ($data, $agent, $outletId, $status) {
            $session = StocktakeSession::create([
                'outlet_id'      => $outletId,
                'created_by'     => $agent->id,
                'status'         => $status,
                'start_date'     => $data['start_date'] ?? now()->toDateString(),
                'completed_date' => $status === 'completed' ? now()->toDateString() : null,
                'notes'          => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $diff = (int) $item['counted_qty'] - (int) $item['system_qty'];

                StocktakeItem::create([
                    'session_id'  => $session->id,
                    'product_id'  => $item['product_id'],
                    'sku'         => $item['sku'] ?? null,
                    'system_qty'  => $item['system_qty'],
                    'counted_qty' => $item['counted_qty'],
                    'difference'  => $diff,
                ]);

                if ($diff !== 0) {
                    InventoryAdjustment::create([
                        'product_id'  => $item['product_id'],
                        'session_id'  => $session->id,
                        'before_qty'  => $item['system_qty'],
                        'after_qty'   => $item['counted_qty'],
                        'reason'      => 'stocktake',
                        'adjusted_by' => $agent->id,
                    ]);
                }
            }

            return response()->json([
                'data' => $this->serializeSession($session->fresh()),
            ], 201);
        });
    }

    private function serializeSession(StocktakeSession $session): array
    {
        return [
            'id'             => $session->id,
            'status'         => $session->status,
            'start_date'     => optional($session->start_date)->toDateString(),
            'completed_date' => optional($session->completed_date)->toDateString(),
            'notes'          => $session->notes,
            'created_at'     => optional($session->created_at)->toIso8601String(),
            'item_count'     => method_exists($session, 'items')
                ? (int) DB::table('pos_stocktake_items')->where('session_id', $session->id)->count()
                : 0,
        ];
    }
}
