<?php

namespace Webkul\Waste\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Helpers\StockAuditRecorder;
use Webkul\Waste\Models\WasteRecord;

class WasteController extends Controller
{
    // ── Index (list + filters) ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        $channels = core()->getAllChannels();

        $query = WasteRecord::query();

        if ($channelId = $request->input('channel_id')) {
            $query->where('channel_id', $channelId);
        }

        if ($month = $request->input('month')) {
            $query->whereMonth('created_at', $month);
        }

        if ($year = $request->input('year')) {
            $query->whereYear('created_at', $year);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('product_sku', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $records = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        // Summary totals (filtered)
        $totals = (clone $query)->selectRaw('SUM(amount) as total_amount, SUM(quantity) as total_qty')->first();

        return view('waste::admin.waste.index', compact('channels', 'records', 'totals'));
    }

    // ── Store ──────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'   => 'required|integer',
            'product_name' => 'required|string|max:255',
            'product_sku'  => 'nullable|string|max:100',
            'channel_id'   => 'nullable|integer',
            'quantity'     => 'required|numeric|min:0.0001',
            'amount'       => 'nullable|numeric|min:0',
            'reason'       => 'required|string|max:100',
            'notes'        => 'nullable|string|max:1000',
            'waste_date'   => 'nullable|date',
        ]);

        $record = WasteRecord::create([
            'product_id'   => $validated['product_id'],
            'product_name' => $validated['product_name'],
            'product_sku'  => $validated['product_sku'] ?? null,
            'channel_id'   => $validated['channel_id'] ?? null,
            'quantity'     => $validated['quantity'],
            'amount'       => $validated['amount'] ?? 0,
            'reason'       => $validated['reason'],
            'notes'        => $validated['notes'] ?? null,
            'created_by'   => auth()->id(),
            'created_at'   => $validated['waste_date'] ?? now(),
            'updated_at'   => now(),
        ]);

        // ── Bonus: reduce product inventory ────────────────────────────────────
        $this->deductInventory($validated['product_id'], $validated['quantity']);

        Log::info('WasteRecord created', ['id' => $record->id, 'product_id' => $record->product_id]);

        return new JsonResponse([
            'success' => true,
            'message' => __('waste::app.messages.saved'),
            'record'  => $record,
        ]);
    }

    // ── Dashboard (chart data) ─────────────────────────────────────────────────

    public function dashboard(Request $request): JsonResponse
    {
        $channelId = $request->input('channel_id');
        $year      = $request->input('year', date('Y'));

        $base = WasteRecord::query();
        if ($channelId) {
            $base->where('channel_id', $channelId);
        }

        // Monthly chart for selected year
        $monthly = (clone $base)
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total_amount, SUM(quantity) as total_qty')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyData[] = [
                'month'        => $m,
                'label'        => date('M', mktime(0, 0, 0, $m, 1)),
                'total_amount' => (float) ($monthly[$m]->total_amount ?? 0),
                'total_qty'    => (float) ($monthly[$m]->total_qty ?? 0),
            ];
        }

        // Yearly chart (last 5 years)
        $currentYear = (int) date('Y');
        $yearly = (clone $base)
            ->whereBetween(DB::raw('YEAR(created_at)'), [$currentYear - 4, $currentYear])
            ->selectRaw('YEAR(created_at) as yr, SUM(amount) as total_amount, SUM(quantity) as total_qty')
            ->groupBy('yr')
            ->orderBy('yr')
            ->get()
            ->keyBy('yr');

        $yearlyData = [];
        for ($y = $currentYear - 4; $y <= $currentYear; $y++) {
            $yearlyData[] = [
                'year'         => $y,
                'total_amount' => (float) ($yearly[$y]->total_amount ?? 0),
                'total_qty'    => (float) ($yearly[$y]->total_qty ?? 0),
            ];
        }

        // Grand totals
        $totals = (clone $base)
            ->selectRaw('SUM(amount) as total_amount, SUM(quantity) as total_qty, COUNT(*) as total_records')
            ->first();

        return new JsonResponse([
            'monthly' => $monthlyData,
            'yearly'  => $yearlyData,
            'totals'  => [
                'amount'  => (float) ($totals->total_amount ?? 0),
                'qty'     => (float) ($totals->total_qty ?? 0),
                'records' => (int)   ($totals->total_records ?? 0),
            ],
        ]);
    }

    // ── Product Search (name / SKU / barcode) ─────────────────────────────────

    public function searchProducts(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 1) {
            return new JsonResponse(['products' => []]);
        }

        // First try barcode match (pos_product_barcode)
        $barcodeMatch = DB::table('pos_product_barcode')
            ->where('barcode', $q)
            ->orWhere('scan_value', $q)
            ->first(['product_id']);

        // Find candidate product IDs across ALL locales/channels. The previous
        // implementation locked the search to app()->getLocale(), which hid
        // mmfood products (only translated to en + my, not ja) from a Japanese
        // admin and vice-versa. Stock comes from product_inventories which is
        // per-source — sum across sources to mirror the dashboard.
        // Status filter is intentionally omitted: waste records are commonly
        // entered for discontinued/disabled SKUs (expired stock of a product
        // pulled from sale), so status=1 would hide exactly the products the
        // operator needs.
        $idsQuery = DB::table('product_flat as pf')
            ->whereNull('pf.parent_id');

        if ($barcodeMatch) {
            $idsQuery->where('pf.product_id', $barcodeMatch->product_id);
        } else {
            $idsQuery->where(function ($sq) use ($q) {
                $sq->where('pf.name', 'like', "%{$q}%")
                   ->orWhere('pf.sku', 'like', "%{$q}%");
            });
        }

        $productIds = $idsQuery->distinct()->limit(50)->pluck('pf.product_id')->all();

        if (empty($productIds)) {
            return new JsonResponse(['products' => []]);
        }

        // Per-product best display row: prefer current locale, then any other.
        $locale = app()->getLocale();
        $rows   = DB::table('product_flat')
            ->whereIn('product_id', $productIds)
            ->whereNull('parent_id')
            ->get(['product_id', 'name', 'sku', 'price', 'locale', 'channel']);

        $stockByPid = DB::table('product_inventories')
            ->whereIn('product_id', $productIds)
            ->select('product_id', DB::raw('SUM(qty) as stock'))
            ->groupBy('product_id')
            ->pluck('stock', 'product_id')
            ->toArray();

        // Cost (buy price) lives as the `cost` attribute (id 12) in
        // product_attribute_values.float_value — null-locale, null-channel.
        // Waste accounting uses cost, not sell price; fall back to sell price
        // when cost is missing so the form is never blank.
        $costAttrId = DB::table('attributes')->where('code', 'cost')->value('id');
        $costByPid  = $costAttrId
            ? DB::table('product_attribute_values')
                ->whereIn('product_id', $productIds)
                ->where('attribute_id', $costAttrId)
                ->pluck('float_value', 'product_id')
                ->toArray()
            : [];

        $byPid = [];
        foreach ($rows as $r) {
            $cur = $byPid[$r->product_id] ?? null;
            // Pick the row from the active locale if available; else first seen.
            if (! $cur || ($r->locale === $locale && $cur->locale !== $locale)) {
                $byPid[$r->product_id] = $r;
            }
        }

        $products = collect($productIds)
            ->map(fn ($pid) => $byPid[$pid] ?? null)
            ->filter()
            ->map(fn ($r) => (object) [
                'product_id' => $r->product_id,
                'name'       => $r->name,
                'sku'        => $r->sku,
                'price'      => $r->price,
                'cost'       => (float) ($costByPid[$r->product_id] ?? 0),
                'stock'      => (int) ($stockByPid[$r->product_id] ?? 0),
            ])
            ->take(15)
            ->values();

        return new JsonResponse(['products' => $products]);
    }

    // ── Show (for edit modal) ──────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $record = WasteRecord::findOrFail($id);

        return new JsonResponse(['success' => true, 'record' => $record]);
    }

    // ── Update ─────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $record = WasteRecord::findOrFail($id);

        $validated = $request->validate([
            'product_name' => 'sometimes|required|string|max:255',
            'product_sku'  => 'nullable|string|max:100',
            'channel_id'   => 'nullable|integer',
            'quantity'     => 'sometimes|required|numeric|min:0.0001',
            'amount'       => 'nullable|numeric|min:0',
            'reason'       => 'sometimes|required|string|max:100',
            'notes'        => 'nullable|string|max:1000',
            'waste_date'   => 'nullable|date',
        ]);

        $data = array_filter([
            'product_name' => $validated['product_name'] ?? null,
            'product_sku'  => array_key_exists('product_sku', $validated) ? ($validated['product_sku'] ?? null) : $record->product_sku,
            'channel_id'   => array_key_exists('channel_id', $validated) ? ($validated['channel_id'] ?? null) : $record->channel_id,
            'quantity'     => $validated['quantity'] ?? null,
            'amount'       => array_key_exists('amount', $validated) ? ($validated['amount'] ?? 0) : $record->amount,
            'reason'       => $validated['reason'] ?? null,
            'notes'        => array_key_exists('notes', $validated) ? ($validated['notes'] ?? null) : $record->notes,
            'created_at'   => $validated['waste_date'] ?? $record->created_at,
        ], fn ($v) => $v !== null);

        $record->fill($data)->save();

        return new JsonResponse([
            'success' => true,
            'message' => __('waste::app.messages.updated'),
            'record'  => $record->fresh(),
        ]);
    }

    // ── Delete ─────────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        try {
            $record = WasteRecord::findOrFail($id);
            $record->delete();

            return new JsonResponse(['success' => true, 'message' => __('waste::app.messages.deleted')]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Reduce product_inventories qty (spread across sources, highest-qty first).
     */
    private function deductInventory(int $productId, float $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $inventories = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('qty', '>', 0)
            ->orderByDesc('qty')
            ->get();

        $remaining = $qty;

        foreach ($inventories as $inv) {
            if ($remaining <= 0) {
                break;
            }

            $deduct = min($inv->qty, $remaining);
            $newQty = max(0, $inv->qty - $deduct);
            DB::table('product_inventories')
                ->where('id', $inv->id)
                ->update(['qty' => $newQty]);

            StockAuditRecorder::record(
                (int) $productId,
                (int) $inv->inventory_source_id,
                (int) $inv->qty,
                (int) $newQty,
                'admin.waste.deduct'
            );

            $remaining -= $deduct;
        }
    }
}
