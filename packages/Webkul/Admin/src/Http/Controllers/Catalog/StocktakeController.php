<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Controller;

class StocktakeController extends Controller
{
    public function index()
    {
        return view('admin::catalog.products.take-stock');
    }

    public function quickFix()
    {
        return view('admin::catalog.products.stock-quick-fix');
    }

    public function sessions(Request $request): JsonResponse
    {
        $query = DB::table('pos_stocktake_sessions')
            ->orderByDesc('id');

        if ($from = $request->query('from')) {
            $query->where('start_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('start_date', '<=', $to);
        }

        $limit = min(200, max(1, (int) $request->query('limit', 50)));

        $rows = $query->limit($limit)->get()->map(function ($s) {
            return [
                'id'             => $s->id,
                'outlet_id'      => $s->outlet_id,
                'status'         => $s->status,
                'start_date'     => $s->start_date,
                'completed_date' => $s->completed_date,
                'notes'          => $s->notes,
                'created_at'     => $s->created_at,
                'item_count'     => DB::table('pos_stocktake_items')->where('session_id', $s->id)->count(),
            ];
        });

        return new JsonResponse(['data' => $rows]);
    }

    public function sessionDetail(int $id): JsonResponse
    {
        $session = DB::table('pos_stocktake_sessions')->where('id', $id)->first();
        if (! $session) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        $items = DB::table('pos_stocktake_items as i')
            ->leftJoin('product_flat as pf', function ($j) {
                $j->on('pf.product_id', '=', 'i.product_id')
                    ->where('pf.locale', '=', app()->getLocale());
            })
            ->select('i.*', 'pf.name as product_name')
            ->where('i.session_id', $id)
            ->orderBy('i.id')
            ->get();

        return new JsonResponse([
            'data'  => $session,
            'items' => $items,
        ]);
    }

    public function storeSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'outlet_id'           => 'nullable|integer',
            'start_date'          => 'nullable|date',
            'notes'               => 'nullable|string|max:2000',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|integer',
            'items.*.sku'         => 'nullable|string|max:255',
            'items.*.system_qty'  => 'required|integer|min:0',
            'items.*.counted_qty' => 'required|integer|min:0',
        ]);

        $admin = auth('admin')->user();

        return DB::transaction(function () use ($data, $admin) {
            $sessionId = DB::table('pos_stocktake_sessions')->insertGetId([
                'outlet_id'      => $data['outlet_id'] ?? 0,
                'created_by'     => $admin?->id ?? 0,
                'status'         => 'completed',
                'start_date'     => $data['start_date'] ?? now()->toDateString(),
                'completed_date' => now()->toDateString(),
                'notes'          => $data['notes'] ?? null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($data['items'] as $item) {
                $diff = (int) $item['counted_qty'] - (int) $item['system_qty'];

                DB::table('pos_stocktake_items')->insert([
                    'session_id'  => $sessionId,
                    'product_id'  => $item['product_id'],
                    'sku'         => $item['sku'] ?? null,
                    'system_qty'  => $item['system_qty'],
                    'counted_qty' => $item['counted_qty'],
                    'difference'  => $diff,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                if ($diff !== 0) {
                    DB::table('pos_inventory_adjustments')->insert([
                        'product_id'  => $item['product_id'],
                        'session_id'  => $sessionId,
                        'before_qty'  => $item['system_qty'],
                        'after_qty'   => $item['counted_qty'],
                        'reason'      => 'stocktake',
                        'adjusted_by' => $admin?->id,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            return new JsonResponse([
                'data' => [
                    'id'         => $sessionId,
                    'item_count' => count($data['items']),
                ],
            ], 201);
        });
    }
}
