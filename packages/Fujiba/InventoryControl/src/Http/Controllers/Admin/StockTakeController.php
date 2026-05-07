<?php

namespace Fujiba\InventoryControl\Http\Controllers\Admin;

use Fujiba\InventoryControl\Jobs\SnapshotBaselineJob;
use Fujiba\InventoryControl\Models\InventoryBatch;
use Fujiba\InventoryControl\Models\StockTakeBatch;
use Fujiba\InventoryControl\Models\StockTakeItem;
use Fujiba\InventoryControl\Models\StockTakeSession;
use Fujiba\InventoryControl\Support\BarcodeNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Core\Models\Channel;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Product\Helpers\StockAuditRecorder;
use Webkul\Product\ProductImage;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Waste\Models\WasteRecord;

class StockTakeController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductImage $productImage,
    ) {}

    public function index()
    {
        $sessions = StockTakeSession::query()
            ->with('inventorySource')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('inventory-control::admin.stock-take.index', compact('sessions'));
    }

    public function create()
    {
        $inventorySources = InventorySource::where('status', 1)->orderBy('priority')->get();
        $channels = Channel::orderBy('id')->get();

        return view('inventory-control::admin.stock-take.create', compact('inventorySources', 'channels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:150',
            'inventory_source_id' => 'required|integer|exists:inventory_sources,id',
            'channel_id'          => 'required|integer|exists:channels,id',
            'notes'               => 'nullable|string|max:1000',
            'advanced_mode'       => 'nullable|boolean',
        ]);

        $adminId = optional(auth()->guard('admin')->user())->id;
        $advanced = (bool) ($data['advanced_mode'] ?? false);

        $session = StockTakeSession::create([
            'name'                => $data['name'],
            'inventory_source_id' => $data['inventory_source_id'],
            'channel_id'          => $data['channel_id'],
            'status'              => StockTakeSession::STATUS_OPEN,
            'advanced_mode'       => $advanced,
            'baseline_status'     => $advanced ? StockTakeSession::BASELINE_PENDING : StockTakeSession::BASELINE_NONE,
            'created_by'          => $adminId,
            'notes'               => $data['notes'] ?? null,
        ]);

        if ($advanced) {
            // Dispatched to Redis. QueueTenancyBootstrapper carries tenant
            // context into the worker so the snapshot writes to the correct DB.
            SnapshotBaselineJob::dispatch($session->id);
        }

        return redirect()->route('admin.inventory_control.stock_take.show', $session->id);
    }

    /**
     * Lightweight poll endpoint — the show page hits this every 2s while
     * baseline_status is 'pending'. Returns just enough for the UI to flip
     * the badge and reveal the scan input.
     */
    public function baselineStatus(int $id): JsonResponse
    {
        $row = DB::table('stock_take_sessions')
            ->where('id', $id)
            ->first(['baseline_status', 'baseline_count', 'advanced_mode']);

        if (! $row) {
            return new JsonResponse(['success' => false], 404);
        }

        return new JsonResponse([
            'success'         => true,
            'advanced_mode'   => (bool) $row->advanced_mode,
            'baseline_status' => $row->baseline_status,
            'baseline_count'  => (int) $row->baseline_count,
        ]);
    }

    public function show(int $id)
    {
        $session = StockTakeSession::with('inventorySource')->findOrFail($id);

        $items = $this->itemsWithScanner($id);

        $summary = $this->summarize($id);

        $batches = $this->batchRowsForSession($id);

        return view('inventory-control::admin.stock-take.show', compact('session', 'items', 'summary', 'batches'));
    }

    /**
     * Scan endpoint — match a product by barcode (any product), then SKU
     * (parent or child). Returns the system qty so the UI can show it before
     * the operator types the counted qty, plus parent/variant context for
     * configurable products.
     *
     * Performance budget: <100ms per scan. We avoid Eloquent here in the hot
     * path and use a small set of indexed queries against the schema columns.
     */
    public function scan(Request $request, int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        if (! $session->isOpen()) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.session_locked'),
            ], 422);
        }

        $code = trim((string) $request->input('code', ''));

        if ($code === '') {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.empty_code'),
            ], 422);
        }

        $code = BarcodeNormalizer::normalize($code) ?: $code;

        $resolved = $this->resolveProductByCode($code);

        if (! $resolved) {
            return new JsonResponse([
                'success' => false,
                'code'    => $code,
                'message' => __('inventory-control::app.stock_take.errors.not_found', ['code' => $code]),
            ], 404);
        }

        $payload = $this->buildProductPayload(
            $session,
            $resolved['product_id'],
            $resolved['matched_by'],
            $resolved['inventory_batch_id'] ?? null,
        );

        return new JsonResponse(['success' => true, 'product' => $payload]);
    }

    /**
     * Persist a per-batch counted line. The associated stock_take_items row is
     * upserted so the batch totals roll up to the per-product summary used
     * everywhere else (counts, deltas, CSV export). counted_qty on the item
     * equals the SUM of counted_qty across its batches.
     */
    public function saveBatch(Request $request, int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        if (! $session->isOpen()) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.session_locked'),
            ], 422);
        }

        $data = $request->validate([
            'inventory_batch_id' => 'required|integer|exists:inventory_batches,id',
            'counted_qty'        => 'required|integer|min:0',
        ]);

        $batch = DB::table('inventory_batches')
            ->where('id', $data['inventory_batch_id'])
            ->where('inventory_source_id', $session->inventory_source_id)
            ->first(['id', 'product_id', 'batch_number', 'expiry_date', 'quantity_remaining']);

        if (! $batch) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.batch_not_found'),
            ], 404);
        }

        $adminId = optional(auth()->guard('admin')->user())->id;

        $result = DB::transaction(function () use ($session, $batch, $data, $adminId) {
            // Item row — created on first batch save for this product.
            $info = $this->productDisplayInfo((int) $batch->product_id);
            $liveQty = $this->liveQty((int) $batch->product_id, (int) $session->inventory_source_id);

            $item = StockTakeItem::firstOrNew([
                'session_id' => $session->id,
                'product_id' => $batch->product_id,
            ]);
            if (! $item->exists) {
                $item->parent_product_id = $info['parent_product_id'];
                $item->sku               = $info['sku'];
                $item->name              = $info['name'];
                $item->variant_label     = $info['variant_label'];
                $item->matched_by        = 'batch';
                $item->system_qty        = $liveQty;
                $item->baseline_qty      = $liveQty; // rollup baseline of all current batches; useful for non-expiry display
                $item->counted_qty       = 0;
                $item->difference        = 0;
                $item->save();
            }

            // Batch row — upsert. baseline_qty here is the per-batch frozen
            // value (set at session create OR on first scan).
            $stb = StockTakeBatch::firstOrNew([
                'session_id'         => $session->id,
                'inventory_batch_id' => $batch->id,
            ]);
            if (! $stb->exists) {
                $stb->item_id      = $item->id;
                $stb->product_id   = $batch->product_id;
                $stb->batch_number = $batch->batch_number;
                $stb->expiry_date  = $batch->expiry_date;
                // Snapshot per-batch baseline if not already snapshotted at session create.
                $stb->baseline_qty = (int) $batch->quantity_remaining;
            }
            // Always backfill item_id (advanced-mode rows have it null at create time).
            if (! $stb->item_id) {
                $stb->item_id = $item->id;
            }

            $stb->counted_qty = (int) $data['counted_qty'];
            $stb->difference  = $stb->counted_qty - (int) $stb->baseline_qty;
            $stb->scanned_by  = $adminId;
            $stb->scanned_at  = now();
            $stb->save();

            // Recompute the rollup on the item from all its batch rows.
            $this->rollupItemFromBatches($item);

            return ['item' => $item->fresh(), 'batch' => $stb->fresh()];
        });

        return new JsonResponse([
            'success' => true,
            'item'    => $this->itemPayload($result['item']),
            'batch'   => $this->batchPayload($result['batch']),
            'summary' => $this->summarize($session->id),
        ]);
    }

    public function deleteBatch(int $id, int $batchRowId): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        if (! $session->isOpen()) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.session_locked'),
            ], 422);
        }

        $stb = StockTakeBatch::where('session_id', $id)->where('id', $batchRowId)->first();

        if (! $stb) {
            return new JsonResponse(['success' => true, 'summary' => $this->summarize($id)]);
        }

        $itemId = $stb->item_id;

        $stb->delete();

        // If item still exists, refresh its rollup; if it has no batches left
        // and no manual count, drop the item entirely.
        if ($itemId) {
            $item = StockTakeItem::find($itemId);
            if ($item) {
                $remaining = StockTakeBatch::where('item_id', $itemId)->count();
                if ($remaining === 0) {
                    $item->delete();
                } else {
                    $this->rollupItemFromBatches($item);
                }
            }
        }

        return new JsonResponse([
            'success' => true,
            'summary' => $this->summarize($id),
        ]);
    }

    /**
     * Persist a counted line. Captures baseline_qty on first save (frozen for
     * the rest of the session — this is the value Apply will diff against).
     * Records scanned_by + scanned_at for multi-user tracking.
     */
    public function saveItem(Request $request, int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        if (! $session->isOpen()) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.session_locked'),
            ], 422);
        }

        $data = $request->validate([
            'product_id'  => 'required|integer|exists:products,id',
            'counted_qty' => 'required|integer|min:0',
            'matched_by'  => 'nullable|string|max:16',
            'notes'       => 'nullable|string|max:500',
        ]);

        $info = $this->productDisplayInfo((int) $data['product_id']);

        $systemQty = $this->liveQty((int) $data['product_id'], (int) $session->inventory_source_id);

        $existing = StockTakeItem::where('session_id', $session->id)
            ->where('product_id', $data['product_id'])
            ->first();

        // baseline_qty is frozen at first save. Subsequent saves keep it.
        $baselineQty = $existing?->baseline_qty ?? $systemQty;

        $countedQty = (int) $data['counted_qty'];
        $difference = $countedQty - $baselineQty;

        $adminId = optional(auth()->guard('admin')->user())->id;

        // Notes: only overwrite when the request explicitly carries the field,
        // so a count-only save doesn't clobber a previously saved note.
        $update = [
            'parent_product_id' => $info['parent_product_id'],
            'sku'               => $info['sku'],
            'name'              => $info['name'],
            'variant_label'     => $info['variant_label'],
            'matched_by'        => $data['matched_by'] ?? null,
            'scanned_by'        => $adminId,
            'scanned_at'        => now(),
            'system_qty'        => $systemQty,
            'baseline_qty'      => $baselineQty,
            'counted_qty'       => $countedQty,
            'difference'        => $difference,
        ];
        if ($request->has('notes')) {
            $update['notes'] = $data['notes'] !== '' ? $data['notes'] : null;
        }

        $item = StockTakeItem::updateOrCreate(
            [
                'session_id' => $session->id,
                'product_id' => $data['product_id'],
            ],
            $update
        );

        return new JsonResponse([
            'success' => true,
            'item'    => $this->itemPayload($item),
            'summary' => $this->summarize($session->id),
        ]);
    }

    /**
     * Quick-scan increment — atomically bump counted_qty by 1 (or by `delta`)
     * without a separate manual save step. Same baseline_qty rules as
     * saveItem.
     */
    public function quickIncrement(Request $request, int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        if (! $session->isOpen()) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.session_locked'),
            ], 422);
        }

        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'matched_by' => 'nullable|string|max:16',
            'delta'      => 'nullable|integer|min:1|max:1000',
        ]);

        $delta = (int) ($data['delta'] ?? 1);

        $info = $this->productDisplayInfo((int) $data['product_id']);

        $systemQty = $this->liveQty((int) $data['product_id'], (int) $session->inventory_source_id);

        $adminId = optional(auth()->guard('admin')->user())->id;

        $item = DB::transaction(function () use ($session, $data, $info, $systemQty, $delta, $adminId) {
            $existing = StockTakeItem::where('session_id', $session->id)
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->first();

            $baselineQty = $existing?->baseline_qty ?? $systemQty;
            $countedQty  = ($existing?->counted_qty ?? 0) + $delta;
            $difference  = $countedQty - $baselineQty;

            return StockTakeItem::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'product_id' => $data['product_id'],
                ],
                [
                    'parent_product_id' => $info['parent_product_id'],
                    'sku'               => $info['sku'],
                    'name'              => $info['name'],
                    'variant_label'     => $info['variant_label'],
                    'matched_by'        => $data['matched_by'] ?? null,
                    'scanned_by'        => $adminId,
                    'scanned_at'        => now(),
                    'system_qty'        => $systemQty,
                    'baseline_qty'      => $baselineQty,
                    'counted_qty'       => $countedQty,
                    'difference'        => $difference,
                ]
            );
        });

        return new JsonResponse([
            'success' => true,
            'item'    => $this->itemPayload($item),
            'summary' => $this->summarize($session->id),
        ]);
    }

    /**
     * Link an unrecognized barcode to an existing product so future scans
     * resolve it. Writes to pos_product_barcode (the table the existing scan
     * resolver already reads). After linking, the caller can re-issue the
     * scan request and get a hit.
     */
    public function linkBarcode(Request $request, int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        if (! $session->isOpen()) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.session_locked'),
            ], 422);
        }

        if (! DB::getSchemaBuilder()->hasTable('pos_product_barcode')) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.barcode_table_missing'),
            ], 422);
        }

        $data = $request->validate([
            'barcode'    => 'required|string|max:500',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $data['barcode'] = BarcodeNormalizer::normalize($data['barcode']);

        $existing = DB::table('pos_product_barcode')
            ->where('barcode', $data['barcode'])
            ->orWhere('scan_value', $data['barcode'])
            ->first();

        if ($existing) {
            DB::table('pos_product_barcode')
                ->where('id', $existing->id)
                ->update([
                    'product_id' => $data['product_id'],
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('pos_product_barcode')->insert([
                'barcode'    => $data['barcode'],
                'scan_value' => $data['barcode'],
                'scan_type'  => 'gtin',
                'product_id' => $data['product_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $payload = $this->buildProductPayload($session, (int) $data['product_id'], 'barcode');

        return new JsonResponse(['success' => true, 'product' => $payload]);
    }

    /**
     * Search products by name or SKU for the link-barcode modal. Constrained
     * to status=1 and current locale; capped to 15 rows to keep the dropdown
     * snappy.
     */
    public function searchProducts(Request $request, int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return new JsonResponse(['products' => []]);
        }

        // Restrict to products that exist at this session's inventory source —
        // matches productsForSession(). Do NOT filter on product_flat.status:
        // mmfood-only products are status=0 in product_flat but are still
        // valid stock-take targets.
        $searchQuery = DB::table('product_flat as pf')
            ->join('product_inventories as pi', 'pi.product_id', '=', 'pf.product_id')
            ->join('products as p', 'p.id', '=', 'pf.product_id')
            ->where('pi.inventory_source_id', $session->inventory_source_id)
            ->where('p.type', '!=', 'configurable')
            ->where(function ($w) use ($q) {
                $w->where('pf.name', 'like', "%{$q}%")
                  ->orWhere('pf.sku', 'like', "%{$q}%");
            });

        if ($session->channel_id) {
            $searchQuery->whereIn('pi.product_id', $this->productIdsForChannel((int) $session->channel_id));
        }

        $rows = $searchQuery
            ->orderByRaw('pf.locale = ? DESC', [app()->getLocale()])
            ->limit(100)
            ->get(['pf.product_id', 'pf.parent_id', 'pf.name', 'pf.sku']);

        // Dedupe by product_id, prefer current-locale row.
        $byPid = [];
        foreach ($rows as $r) {
            if (! isset($byPid[$r->product_id])) {
                $byPid[$r->product_id] = $r;
            }
        }

        $products = collect(array_values($byPid))->take(15)->map(fn ($r) => [
            'product_id' => (int) $r->product_id,
            'name'       => (string) ($r->name ?? ''),
            'sku'        => (string) ($r->sku ?? ''),
            'is_variant' => $r->parent_id !== null,
        ]);

        return new JsonResponse(['products' => $products]);
    }

    public function deleteItem(int $id, int $itemId): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        if (! $session->isOpen()) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.session_locked'),
            ], 422);
        }

        StockTakeItem::where('session_id', $id)->where('id', $itemId)->delete();

        return new JsonResponse([
            'success' => true,
            'summary' => $this->summarize($id),
        ]);
    }

    public function summary(int $id): JsonResponse
    {
        StockTakeSession::findOrFail($id);

        return new JsonResponse(['summary' => $this->summarize($id)]);
    }

    /**
     * Bulk assign or remove a channel on the given product IDs.
     * Action-driven: 'assign' inserts (ignoring duplicates), 'remove' deletes.
     * Variants of configurables follow their parent — we walk parent_id so a
     * scoped child becomes scoped at the parent (which is what product_channels
     * stores anyway).
     */
    public function bulkChannel(Request $request, int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        $data = $request->validate([
            'action'       => 'required|in:assign,remove',
            'channel_id'   => 'required|integer|exists:channels,id',
            'product_ids'  => 'required|array|min:1|max:1000',
            'product_ids.*'=> 'integer|exists:products,id',
        ]);

        $channelId = (int) $data['channel_id'];
        $action    = $data['action'];
        $pids      = array_map('intval', $data['product_ids']);

        // Resolve to canonical row ids in product_channels — variants store
        // under their parent. Map each pid to its parent (if any).
        $parentIdByPid = DB::table('products')
            ->whereIn('id', $pids)
            ->pluck('parent_id', 'id');

        $targetIds = [];
        foreach ($pids as $pid) {
            $parent = $parentIdByPid[$pid] ?? null;
            $targetIds[] = $parent ? (int) $parent : $pid;
        }
        $targetIds = array_values(array_unique($targetIds));

        $changed = 0;

        DB::transaction(function () use ($action, $channelId, $targetIds, &$changed) {
            if ($action === 'assign') {
                // Insert ignoring duplicates — INSERT ... ON DUPLICATE KEY UPDATE
                $now = now();
                $rows = array_map(fn ($pid) => [
                    'product_id' => $pid,
                    'channel_id' => $channelId,
                ], $targetIds);
                // Use insertOrIgnore to skip the (product_id, channel_id) duplicates.
                $changed = DB::table('product_channels')->insertOrIgnore($rows);
            } else {
                $changed = DB::table('product_channels')
                    ->where('channel_id', $channelId)
                    ->whereIn('product_id', $targetIds)
                    ->delete();
            }
        });

        // Invalidate the per-channel pid cache used by productsForSession so
        // session-scoped filtering reflects the change immediately.
        $this->channelPidCache = [];

        return new JsonResponse([
            'success' => true,
            'action'  => $action,
            'changed' => (int) $changed,
            'message' => ($action === 'assign' ? 'Assigned to' : 'Removed from')
                . ' channel for ' . count($targetIds) . ' product(s); '
                . $changed . ' row(s) ' . ($action === 'assign' ? 'inserted' : 'deleted'),
        ]);
    }

    /**
     * Full product list for the session's warehouse, joined with any existing
     * stock_take_items row so the inline-edit table can show counted_qty
     * pre-filled. Drives the default-visible scrollable table on the show page.
     */
    public function productsForSession(int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        $locale = app()->getLocale();

        // Products with a product_inventories row at this source — these are
        // the ones that "belong" at this warehouse. Children of configurables
        // qualify, parents don't (parents have no inventory rows).
        $query = DB::table('product_inventories as pi')
            ->join('products as p', 'p.id', '=', 'pi.product_id')
            ->leftJoin('product_flat as pf', function ($j) use ($locale) {
                $j->on('pf.product_id', '=', 'pi.product_id')
                  ->where('pf.locale', '=', $locale);
            })
            ->leftJoin('product_flat as pf_any', 'pf_any.product_id', '=', 'pi.product_id')
            ->leftJoin('stock_take_items as sti', function ($j) use ($id) {
                $j->on('sti.product_id', '=', 'pi.product_id')
                  ->where('sti.session_id', '=', $id);
            })
            ->where('pi.inventory_source_id', $session->inventory_source_id)
            ->where('p.type', '!=', 'configurable');

        // Channel filter — only products mapped to this session's channel
        // (directly OR via parent for variants) qualify. NULL channel_id means
        // legacy session created before the column existed; show all.
        if ($session->channel_id) {
            $allowedPids = $this->productIdsForChannel((int) $session->channel_id);
            $query->whereIn('pi.product_id', $allowedPids);
        }

        $rows = $query->groupBy('pi.product_id')
            ->orderByRaw('COALESCE(pf.name, pf_any.name) ASC')
            ->get([
                'pi.product_id',
                DB::raw('COALESCE(pf.name, MAX(pf_any.name)) as name'),
                DB::raw('COALESCE(pf.sku, MAX(pf_any.sku)) as sku'),
                DB::raw('COALESCE(pf.parent_id, MAX(pf_any.parent_id)) as parent_id'),
                DB::raw('SUM(pi.qty) as system_qty'),
                DB::raw('MAX(sti.id) as item_id'),
                DB::raw('MAX(sti.counted_qty) as counted_qty'),
                DB::raw('MAX(sti.baseline_qty) as baseline_qty'),
                DB::raw('MAX(sti.difference) as `difference`'),
                DB::raw('MAX(sti.notes) as notes'),
            ]);

        $pids = $rows->pluck('product_id')->map(fn ($v) => (int) $v)->all();

        // Channel membership — children inherit from parent in product_channels
        // (parents are the row in product_channels for configurables). Build a
        // map covering both the product itself AND its parent so variants get
        // their parent's channels.
        $parentMap = [];
        foreach ($rows as $r) {
            $parentMap[(int) $r->product_id] = $r->parent_id ? (int) $r->parent_id : null;
        }
        $allPids = array_unique(array_merge($pids, array_filter(array_values($parentMap))));
        $channelsByPid = [];
        if (! empty($allPids)) {
            $chRows = DB::table('product_channels as pc')
                ->join('channels as c', 'c.id', '=', 'pc.channel_id')
                ->whereIn('pc.product_id', $allPids)
                ->get(['pc.product_id', 'c.code']);
            foreach ($chRows as $cr) {
                $channelsByPid[(int) $cr->product_id][] = (string) $cr->code;
            }
        }

        // Barcode presence — pos_product_barcode is canonical (memory note).
        $barcodePids = [];
        if (! empty($pids)) {
            $bRows = DB::table('pos_product_barcode')
                ->whereIn('product_id', $pids)
                ->whereNotNull('barcode')
                ->where('barcode', '!=', '')
                ->pluck('product_id');
            foreach ($bRows as $pid) {
                $barcodePids[(int) $pid] = true;
            }
        }

        $products = $rows->map(function ($r) use ($channelsByPid, $barcodePids, $parentMap) {
            $variantLabel = null;
            if ($r->parent_id) {
                $variantLabel = $this->variantAttributeLabel((int) $r->product_id, (int) $r->parent_id);
            }
            $pid = (int) $r->product_id;
            $photoPid = $r->parent_id ? (int) $r->parent_id : $pid;

            $codes = $channelsByPid[$pid] ?? [];
            if ($r->parent_id && empty($codes)) {
                $codes = $channelsByPid[(int) $r->parent_id] ?? [];
            }
            $codes = array_values(array_unique($codes));

            $sysQty = (int) $r->system_qty;
            $countedForLeft = $r->counted_qty === null ? 0 : (int) $r->counted_qty;

            return [
                'product_id'    => $pid,
                'name'          => (string) ($r->name ?? ''),
                'sku'           => (string) ($r->sku ?? ''),
                'variant_label' => $variantLabel,
                'system_qty'    => $sysQty,
                'item_id'       => $r->item_id ? (int) $r->item_id : null,
                'counted_qty'   => $r->counted_qty === null ? null : (int) $r->counted_qty,
                'baseline_qty'  => $r->baseline_qty === null ? null : (int) $r->baseline_qty,
                'difference'    => $r->difference === null ? null : (int) $r->difference,
                'qty_left'      => max(0, $sysQty - $countedForLeft),
                'notes'         => $r->notes !== null ? (string) $r->notes : null,
                'photo_url'     => $this->productPhotoUrl($photoPid),
                'channel_codes' => $codes,
                'has_barcode'   => isset($barcodePids[$pid]),
            ];
        })->values();

        $channels = DB::table('channels as c')
            ->leftJoin('channel_translations as ct', function ($j) use ($locale) {
                $j->on('ct.channel_id', '=', 'c.id')->where('ct.locale', '=', $locale);
            })
            ->leftJoin('channel_translations as ct_any', 'ct_any.channel_id', '=', 'c.id')
            ->groupBy('c.id', 'c.code')
            ->orderBy('c.id')
            ->get([
                'c.id',
                'c.code',
                DB::raw('COALESCE(MAX(ct.name), MAX(ct_any.name), c.code) as name'),
            ])
            ->map(fn ($c) => [
                'id'   => (int) $c->id,
                'code' => (string) $c->code,
                'name' => (string) $c->name,
            ])->values();

        return new JsonResponse([
            'success'  => true,
            'products' => $products,
            'channels' => $channels,
        ]);
    }

    /**
     * Apply all counted differences to product_inventories using baseline_qty
     * as the "before" value (NOT the current live qty). This means an admin
     * editing inventory through another channel during the session won't have
     * their changes silently overwritten — but if they did edit the same
     * product, the baseline mismatch shows up as drift in the audit trail.
     *
     * Audit: still recorded via StockAuditRecorder with the LIVE before-qty
     * (so the audit reflects the actual write delta), with baseline written
     * to the remark for traceability.
     */
    public function apply(int $id)
    {
        $session = StockTakeSession::findOrFail($id);

        if (! $session->isOpen()) {
            return back()->with('error', __('inventory-control::app.stock_take.errors.already_applied'));
        }

        $items = StockTakeItem::where('session_id', $id)->where('applied', false)->get();

        $appliedCount = 0;

        DB::transaction(function () use ($session, $items, &$appliedCount) {
            foreach ($items as $item) {
                $batchRows = StockTakeBatch::where('session_id', $session->id)
                    ->where('item_id', $item->id)
                    ->where('applied', false)
                    ->whereNotNull('counted_qty')
                    ->get();

                if ($batchRows->isNotEmpty()) {
                    // ── Expiry-tracked path ─────────────────────────────────
                    // 1) Write inventory_batches.quantity_remaining per batch
                    //    + per-batch audit row.
                    foreach ($batchRows as $stb) {
                        $live = (int) (DB::table('inventory_batches')
                            ->where('id', $stb->inventory_batch_id)
                            ->value('quantity_remaining') ?? 0);

                        $after = (int) $stb->counted_qty;

                        $update = [
                            'quantity_remaining' => $after,
                            'updated_at'         => now(),
                        ];
                        // Auto-flip status: depleted on 0, active on >0.
                        if ($after === 0) {
                            $update['status'] = InventoryBatch::STATUS_DEPLETED;
                        } elseif ($after > 0) {
                            $update['status'] = InventoryBatch::STATUS_ACTIVE;
                        }

                        DB::table('inventory_batches')
                            ->where('id', $stb->inventory_batch_id)
                            ->update($update);

                        if ($live !== $after) {
                            $this->recordBatchAudit(
                                (int) $stb->product_id,
                                (int) $session->inventory_source_id,
                                (int) $stb->inventory_batch_id,
                                $live,
                                $after,
                                'admin.stock_take.apply.batch'
                            );
                            $appliedCount++;
                        }

                        $stb->update(['applied' => true]);
                    }

                    // 2) Recompute the product_inventories aggregate from all
                    //    active batches (NOT the operator's count — this avoids
                    //    drift if some batches at this source weren't scanned).
                    $aggLive = (int) DB::table('product_inventories')
                        ->where('product_id', $item->product_id)
                        ->where('inventory_source_id', $session->inventory_source_id)
                        ->value('qty') ?? 0;

                    $aggAfter = (int) DB::table('inventory_batches')
                        ->where('product_id', $item->product_id)
                        ->where('inventory_source_id', $session->inventory_source_id)
                        ->where('status', InventoryBatch::STATUS_ACTIVE)
                        ->sum('quantity_remaining');

                    $this->writeProductInventory($item, $session, $aggAfter);

                    if ($aggLive !== $aggAfter) {
                        // Aggregate audit row — no batch_id, captures the
                        // rolled-up movement so existing reports still work.
                        StockAuditRecorder::record(
                            (int) $item->product_id,
                            (int) $session->inventory_source_id,
                            $aggLive,
                            $aggAfter,
                            'admin.stock_take.apply.aggregate'
                        );
                    }
                } else {
                    // ── Non-expiry path (unchanged behavior) ────────────────
                    $live  = (int) (DB::table('product_inventories')
                        ->where('product_id', $item->product_id)
                        ->where('inventory_source_id', $session->inventory_source_id)
                        ->value('qty') ?? 0);

                    $after = (int) $item->counted_qty;

                    $this->writeProductInventory($item, $session, $after);

                    if ($live !== $after) {
                        StockAuditRecorder::record(
                            (int) $item->product_id,
                            (int) $session->inventory_source_id,
                            $live,
                            $after,
                            'admin.stock_take.apply'
                        );
                        $appliedCount++;
                    }
                }

                $item->update(['applied' => true]);
            }

            $session->update([
                'status'     => StockTakeSession::STATUS_APPLIED,
                'applied_by' => optional(auth()->guard('admin')->user())->id,
                'applied_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.inventory_control.stock_take.show', $session->id)
            ->with('success', __('inventory-control::app.stock_take.applied', ['count' => $appliedCount]));
    }

    public function cancel(int $id)
    {
        $session = StockTakeSession::findOrFail($id);

        if ($session->isOpen()) {
            $session->update(['status' => StockTakeSession::STATUS_CANCELLED]);
        }

        return redirect()->route('admin.inventory_control.stock_take.index');
    }

    /**
     * CSV export. Streamed so a session with thousands of items doesn't
     * pin the whole result set in memory.
     */
    public function export(int $id): StreamedResponse
    {
        $session = StockTakeSession::with('inventorySource')->findOrFail($id);

        $filename = sprintf('stock-take-%d-%s.csv', $session->id, $session->created_at->format('Ymd-His'));

        return new StreamedResponse(function () use ($session) {
            $out = fopen('php://output', 'w');

            // Excel-friendly UTF-8 BOM.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'session_id', 'session_name', 'warehouse',
                'product_id', 'parent_product_id', 'sku', 'name', 'variant',
                'matched_by', 'scanned_by_id', 'scanned_by_name', 'scanned_at',
                'system_qty', 'baseline_qty', 'counted_qty', 'difference', 'applied',
            ]);

            StockTakeItem::query()
                ->leftJoin('admins', 'admins.id', '=', 'stock_take_items.scanned_by')
                ->where('stock_take_items.session_id', $session->id)
                ->orderBy('stock_take_items.id')
                ->select([
                    'stock_take_items.*',
                    'admins.name as scanned_by_name',
                ])
                ->chunk(500, function ($rows) use ($out, $session) {
                    foreach ($rows as $r) {
                        fputcsv($out, [
                            $session->id,
                            $session->name,
                            $session->inventorySource->name ?? '',
                            $r->product_id,
                            $r->parent_product_id,
                            $r->sku,
                            $r->name,
                            $r->variant_label,
                            $r->matched_by,
                            $r->scanned_by,
                            $r->scanned_by_name,
                            $r->scanned_at,
                            $r->system_qty,
                            $r->baseline_qty,
                            $r->counted_qty,
                            $r->difference,
                            $r->applied ? '1' : '0',
                        ]);
                    }
                });

            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Resolve a scan code. Order:
     *   1. inventory_batches.batch_number  → product + preselected batch
     *   2. pos_product_barcode             → product
     *   3. product_flat.sku                → product (parent or child)
     */
    private function resolveProductByCode(string $code): ?array
    {
        // 1. Batch barcode (preferred for expiry-tracked stock).
        $batch = DB::table('inventory_batches')
            ->where('batch_number', $code)
            ->where('status', InventoryBatch::STATUS_ACTIVE)
            ->orderBy('id')
            ->first(['id', 'product_id']);

        if ($batch) {
            return [
                'product_id'         => (int) $batch->product_id,
                'matched_by'         => 'batch',
                'inventory_batch_id' => (int) $batch->id,
            ];
        }

        if (Schema::hasTable('pos_product_barcode')) {
            $hit = DB::table('pos_product_barcode')
                ->where(function ($w) use ($code) {
                    $w->where('barcode', $code)->orWhere('scan_value', $code);
                })
                ->value('product_id');

            if ($hit) {
                return ['product_id' => (int) $hit, 'matched_by' => 'barcode'];
            }
        }

        // SKU match — accept BOTH parent and child SKUs. Phase 2.4 explicitly
        // requires variant SKU matching, so we drop the parent_id IS NULL
        // filter the original implementation used.
        $hit = DB::table('product_flat')
            ->where('sku', $code)
            ->orderByRaw('parent_id IS NULL DESC') // prefer parent on tie
            ->value('product_id');

        if ($hit) {
            return ['product_id' => (int) $hit, 'matched_by' => 'sku'];
        }

        return null;
    }

    /**
     * Build the JSON payload returned to the scan UI. When the matched product
     * has expiry tracking enabled, the payload includes a batch list (keyed by
     * inventory_batch_id) with system + counted qty per batch. The optional
     * $hintBatchId is preselected — set when the user scanned a batch barcode.
     */
    private function buildProductPayload(StockTakeSession $session, int $productId, string $matchedBy, ?int $hintBatchId = null): array
    {
        $info = $this->productDisplayInfo($productId);

        $systemQty = $this->liveQty($productId, (int) $session->inventory_source_id);

        $existing = StockTakeItem::where('session_id', $session->id)
            ->where('product_id', $productId)
            ->first();

        $hasExpiry = (bool) DB::table('products')->where('id', $productId)->value('has_expiry_date');

        return [
            'product_id'        => $productId,
            'parent_product_id' => $info['parent_product_id'],
            'name'              => $info['name'],
            'sku'               => $info['sku'],
            'variant_label'     => $info['variant_label'],
            'matched_by'        => $matchedBy,
            'system_qty'        => $systemQty,
            'has_expiry_date'   => $hasExpiry,
            'hint_batch_id'     => $hintBatchId,
            'photo_url'         => $this->productPhotoUrl($info['parent_product_id'] ?: $productId),
            'batches'           => $hasExpiry
                ? $this->batchesForProduct($session, $productId)
                : [],
            'existing'          => $existing ? [
                'id'           => $existing->id,
                'counted_qty'  => (int) $existing->counted_qty,
                'baseline_qty' => (int) $existing->baseline_qty,
                'difference'   => (int) $existing->difference,
            ] : null,
        ];
    }

    /**
     * Active batches for the product at the session source, joined to any
     * existing stock_take_batches row so the UI can show counted qty per batch.
     * Ordered by FIFO (expiry asc, created asc) so the operator sees the
     * oldest batch first.
     */
    private function batchesForProduct(StockTakeSession $session, int $productId): array
    {
        $today = Carbon::today();
        $soon  = (clone $today)->addDays(7);

        $rows = DB::table('inventory_batches as ib')
            ->leftJoin('stock_take_batches as stb', function ($j) use ($session) {
                $j->on('stb.inventory_batch_id', '=', 'ib.id')
                  ->where('stb.session_id', '=', $session->id);
            })
            ->where('ib.product_id', $productId)
            ->where('ib.inventory_source_id', $session->inventory_source_id)
            ->where('ib.status', InventoryBatch::STATUS_ACTIVE)
            ->orderByRaw('ib.expiry_date IS NULL, ib.expiry_date ASC, ib.id ASC')
            ->get([
                'ib.id', 'ib.batch_number', 'ib.expiry_date', 'ib.quantity_remaining',
                'stb.id as stb_id', 'stb.baseline_qty as stb_baseline', 'stb.counted_qty as stb_counted', 'stb.difference as stb_diff',
            ]);

        $out = [];
        foreach ($rows as $r) {
            $expiry = $r->expiry_date ? Carbon::parse($r->expiry_date) : null;
            $status = 'normal';
            if ($expiry) {
                if ($expiry->lt($today))      $status = 'expired';
                elseif ($expiry->lte($soon))  $status = 'expiring';
            }

            $out[] = [
                'inventory_batch_id' => (int) $r->id,
                'batch_number'       => (string) ($r->batch_number ?? ''),
                'expiry_date'        => $r->expiry_date,
                'expiry_status'      => $status,
                'system_qty'         => (int) $r->quantity_remaining,
                'baseline_qty'       => $r->stb_baseline === null ? null : (int) $r->stb_baseline,
                'counted_qty'        => $r->stb_counted === null ? null : (int) $r->stb_counted,
                'difference'         => $r->stb_diff === null ? null : (int) $r->stb_diff,
                'stb_id'             => $r->stb_id ? (int) $r->stb_id : null,
            ];
        }

        return $out;
    }

    /**
     * Display strings for a product. For variants, we resolve the parent's
     * name and the variant attribute values (e.g. "Color: Red, Size: M") so
     * the UI can show "Parent Name — Color: Red, Size: M".
     */
    private function productDisplayInfo(int $productId): array
    {
        $row = DB::table('product_flat')
            ->where('product_id', $productId)
            ->orderByRaw('locale = ? DESC', [app()->getLocale()])
            ->first(['product_id', 'parent_id', 'name', 'sku', 'type']);

        $parentId = $row->parent_id ?? null;
        $sku      = $row->sku ?? '';
        $name     = $row->name ?? '';
        $variant  = null;

        if ($parentId) {
            $parent = DB::table('product_flat')
                ->where('product_id', $parentId)
                ->orderByRaw('locale = ? DESC', [app()->getLocale()])
                ->first(['name']);

            $name = $parent->name ?? $name;
            $variant = $this->variantAttributeLabel((int) $productId, (int) $parentId);
        }

        return [
            'product_id'        => $productId,
            'parent_product_id' => $parentId ? (int) $parentId : null,
            'sku'               => (string) $sku,
            'name'              => (string) $name,
            'variant_label'     => $variant,
        ];
    }

    private function productPhotoUrl(int $productId): ?string
    {
        $product = $this->productRepository->find($productId);
        if (! $product) {
            return null;
        }
        $images = $this->productImage->getProductBaseImage($product);
        return $images['small_image_url'] ?? $images['medium_image_url'] ?? $images['large_image_url'] ?? null;
    }

    /**
     * "Color: Red, Size: M" — built from product_super_attributes (configures
     * which attrs the variant uses) and product_attribute_values (the actual
     * value on the child). Returns null for non-configurable products.
     */
    private function variantAttributeLabel(int $childId, int $parentId): ?string
    {
        $superAttrs = DB::table('product_super_attributes as psa')
            ->join('attributes as a', 'a.id', '=', 'psa.attribute_id')
            ->where('psa.product_id', $parentId)
            ->get(['a.id', 'a.code', 'a.admin_name', 'a.type']);

        if ($superAttrs->isEmpty()) {
            return null;
        }

        $parts = [];
        $locale = app()->getLocale();

        foreach ($superAttrs as $attr) {
            $val = DB::table('product_attribute_values')
                ->where('product_id', $childId)
                ->where('attribute_id', $attr->id)
                ->first(['integer_value', 'text_value', 'json_value']);

            if (! $val) {
                continue;
            }

            $display = null;

            // Select / multiselect → resolve option label via attribute_options.
            if (! is_null($val->integer_value)) {
                $opt = DB::table('attribute_options as ao')
                    ->leftJoin('attribute_option_translations as aot', function ($j) use ($locale) {
                        $j->on('aot.attribute_option_id', '=', 'ao.id')
                          ->where('aot.locale', '=', $locale);
                    })
                    ->where('ao.id', $val->integer_value)
                    ->first(['ao.admin_name', 'aot.label']);

                $display = $opt->label ?? $opt->admin_name ?? null;
            }

            if ($display === null && ! is_null($val->text_value)) {
                $display = (string) $val->text_value;
            }

            if ($display === null) {
                continue;
            }

            $parts[] = ($attr->admin_name ?: $attr->code) . ': ' . $display;
        }

        return $parts ? implode(', ', $parts) : null;
    }

    /**
     * IDs of every product visible in a given channel: rows in product_channels
     * for this channel directly, plus any product whose parent_id is in that
     * set (variants inherit their configurable parent's channel mapping).
     * Cached per request so productsForSession + searchProducts don't re-query.
     */
    private array $channelPidCache = [];

    private function productIdsForChannel(int $channelId): array
    {
        if (isset($this->channelPidCache[$channelId])) {
            return $this->channelPidCache[$channelId];
        }

        $direct = DB::table('product_channels')
            ->where('channel_id', $channelId)
            ->pluck('product_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        if (empty($direct)) {
            return $this->channelPidCache[$channelId] = [];
        }

        $variants = DB::table('products')
            ->whereIn('parent_id', $direct)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        return $this->channelPidCache[$channelId] = array_values(array_unique(array_merge($direct, $variants)));
    }

    private function liveQty(int $productId, int $sourceId): int
    {
        $v = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $sourceId)
            ->value('qty');

        return $v === null ? 0 : (int) $v;
    }

    /**
     * Upsert product_inventories row to a target qty. Used by apply for both
     * expiry (recomputed-from-batches) and non-expiry (counted_qty) paths.
     */
    private function writeProductInventory(StockTakeItem $item, StockTakeSession $session, int $after): void
    {
        $row = DB::table('product_inventories')
            ->where('product_id', $item->product_id)
            ->where('inventory_source_id', $session->inventory_source_id)
            ->first();

        if ($row) {
            DB::table('product_inventories')
                ->where('id', $row->id)
                ->update(['qty' => $after, 'updated_at' => now()]);
        } else {
            DB::table('product_inventories')->insert([
                'product_id'          => $item->product_id,
                'inventory_source_id' => $session->inventory_source_id,
                'qty'                 => $after,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }

    /**
     * Insert a per-batch audit row. StockAuditRecorder doesn't expose batch_id
     * yet, so we write directly. Mirrors StockAuditRecorder::record's contract:
     * skip when delta == 0, capture admin_id from the admin guard, set source.
     */
    private function recordBatchAudit(
        int $productId,
        int $inventorySourceId,
        int $batchId,
        int $before,
        int $after,
        string $sourceAction
    ): void {
        if (! Schema::hasTable('product_stock_audits')) {
            return;
        }
        if ($before === $after) {
            return;
        }

        $adminId = null;
        try {
            if ($u = auth()->guard('admin')->user()) {
                $adminId = $u->id;
            }
        } catch (\Throwable $e) {
            // Leave null in CLI/queue context.
        }

        DB::table('product_stock_audits')->insert([
            'product_id'          => $productId,
            'inventory_source_id' => $inventorySourceId,
            'batch_id'            => $batchId,
            'qty_before'          => $before,
            'qty_after'           => $after,
            'delta'               => $after - $before,
            'admin_id'            => $adminId,
            'source_action'       => $sourceAction,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    private function itemPayload(StockTakeItem $item): array
    {
        $scannedBy = $item->scanned_by
            ? DB::table('admins')->where('id', $item->scanned_by)->value('name')
            : null;

        return [
            'id'                => $item->id,
            'product_id'        => $item->product_id,
            'parent_product_id' => $item->parent_product_id,
            'name'              => $item->name,
            'sku'               => $item->sku,
            'variant_label'     => $item->variant_label,
            'system_qty'        => $item->system_qty,
            'baseline_qty'      => $item->baseline_qty,
            'counted_qty'       => $item->counted_qty,
            'difference'        => $item->difference,
            'qty_left'          => max(0, (int) $item->system_qty - (int) $item->counted_qty),
            'notes'             => $item->notes,
            'scanned_by'        => $item->scanned_by,
            'scanned_by_name'   => $scannedBy,
            'scanned_at'        => optional($item->scanned_at)->format('Y-m-d H:i'),
        ];
    }

    private function batchPayload(StockTakeBatch $stb): array
    {
        $today = Carbon::today();
        $soon  = (clone $today)->addDays(7);

        $expiry = $stb->expiry_date ? Carbon::parse($stb->expiry_date) : null;
        $status = 'normal';
        if ($expiry) {
            if ($expiry->lt($today))      $status = 'expired';
            elseif ($expiry->lte($soon))  $status = 'expiring';
        }

        return [
            'id'                 => $stb->id,
            'item_id'            => $stb->item_id,
            'inventory_batch_id' => $stb->inventory_batch_id,
            'product_id'         => $stb->product_id,
            'batch_number'       => $stb->batch_number,
            'expiry_date'        => optional($stb->expiry_date)->format('Y-m-d'),
            'expiry_status'      => $status,
            'baseline_qty'       => $stb->baseline_qty,
            'counted_qty'        => $stb->counted_qty,
            'difference'         => $stb->difference,
        ];
    }

    /**
     * Refresh stock_take_items rollup from its batch rows. Item.counted_qty =
     * SUM(batches.counted_qty). For non-expiry items there are no batches and
     * the existing per-item save flow stays intact.
     */
    private function rollupItemFromBatches(StockTakeItem $item): void
    {
        $agg = DB::table('stock_take_batches')
            ->where('item_id', $item->id)
            ->selectRaw('
                COALESCE(SUM(counted_qty), 0) as counted,
                COALESCE(SUM(baseline_qty), 0) as baseline,
                COUNT(counted_qty) as scanned_batches
            ')
            ->first();

        // No batches scanned yet — leave the item alone (it might be a manual
        // non-batch row from the simple flow).
        if ((int) $agg->scanned_batches === 0) {
            return;
        }

        $counted  = (int) $agg->counted;
        $baseline = (int) $agg->baseline;

        $item->update([
            'counted_qty'  => $counted,
            'baseline_qty' => $baseline,
            'difference'   => $counted - $baseline,
        ]);
    }

    /**
     * All batch rows for a session, joined to admins for display.
     */
    private function batchRowsForSession(int $sessionId): array
    {
        $rows = DB::table('stock_take_batches as stb')
            ->leftJoin('admins as a', 'a.id', '=', 'stb.scanned_by')
            ->leftJoin('product_flat as pf', function ($j) {
                $j->on('pf.product_id', '=', 'stb.product_id')->whereNull('pf.parent_id');
            })
            ->where('stb.session_id', $sessionId)
            ->select([
                'stb.*',
                'a.name as scanned_by_name',
                'pf.name as product_name',
                'pf.sku as product_sku',
            ])
            ->orderByRaw('stb.expiry_date IS NULL, stb.expiry_date ASC, stb.id ASC')
            ->get();

        return $rows->map(function ($r) {
            $today = Carbon::today();
            $soon  = (clone $today)->addDays(7);
            $expiry = $r->expiry_date ? Carbon::parse($r->expiry_date) : null;
            $status = 'normal';
            if ($expiry) {
                if ($expiry->lt($today))      $status = 'expired';
                elseif ($expiry->lte($soon))  $status = 'expiring';
            }
            return [
                'id'                 => (int) $r->id,
                'item_id'            => $r->item_id ? (int) $r->item_id : null,
                'inventory_batch_id' => (int) $r->inventory_batch_id,
                'product_id'         => (int) $r->product_id,
                'product_name'       => $r->product_name,
                'product_sku'        => $r->product_sku,
                'batch_number'       => $r->batch_number,
                'expiry_date'        => $r->expiry_date,
                'expiry_status'      => $status,
                'baseline_qty'       => $r->baseline_qty === null ? null : (int) $r->baseline_qty,
                'counted_qty'        => $r->counted_qty === null ? null : (int) $r->counted_qty,
                'difference'         => $r->difference === null ? null : (int) $r->difference,
                'scanned_by_name'    => $r->scanned_by_name,
            ];
        })->all();
    }

    private function itemsWithScanner(int $sessionId)
    {
        return StockTakeItem::query()
            ->leftJoin('admins', 'admins.id', '=', 'stock_take_items.scanned_by')
            ->where('stock_take_items.session_id', $sessionId)
            ->orderByDesc('stock_take_items.updated_at')
            ->select([
                'stock_take_items.*',
                'admins.name as scanned_by_name',
            ])
            ->get();
    }

    /**
     * Single aggregate query — keeps the summary cheap to recompute after
     * every save (the UI refreshes it inline).
     */
    private function summarize(int $sessionId): array
    {
        $row = DB::table('stock_take_items')
            ->where('session_id', $sessionId)
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN difference > 0 THEN 1 ELSE 0 END) as increased_count,
                SUM(CASE WHEN difference < 0 THEN 1 ELSE 0 END) as decreased_count,
                SUM(CASE WHEN difference = 0 THEN 1 ELSE 0 END) as unchanged_count,
                COALESCE(SUM(difference), 0) as total_delta
            ')
            ->first();

        // Unscanned batches = baseline rows that never got a counted_qty.
        // Only meaningful in advanced mode where baselines are pre-snapshotted.
        $unscanned = (int) DB::table('stock_take_batches')
            ->where('session_id', $sessionId)
            ->whereNull('counted_qty')
            ->count();

        return [
            'total_count'     => (int) ($row->total_count ?? 0),
            'increased_count' => (int) ($row->increased_count ?? 0),
            'decreased_count' => (int) ($row->decreased_count ?? 0),
            'unchanged_count' => (int) ($row->unchanged_count ?? 0),
            'total_delta'     => (int) ($row->total_delta ?? 0),
            'unscanned_batches' => $unscanned,
        ];
    }

    /**
     * Detailed list of unscanned batches for the pre-apply warning. Capped at
     * 20 to keep the modal usable; the count in summary() shows the true total.
     */
    public function unscannedBatches(int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        $rows = DB::table('stock_take_batches as stb')
            ->leftJoin('product_flat as pf', function ($j) {
                $j->on('pf.product_id', '=', 'stb.product_id')->whereNull('pf.parent_id');
            })
            ->where('stb.session_id', $id)
            ->whereNull('stb.counted_qty')
            ->orderByRaw('stb.expiry_date IS NULL, stb.expiry_date ASC')
            ->limit(20)
            ->get([
                'stb.id', 'stb.batch_number', 'stb.expiry_date', 'stb.baseline_qty',
                'stb.product_id', 'pf.name as product_name', 'pf.sku as product_sku',
            ]);

        $total = (int) DB::table('stock_take_batches')
            ->where('session_id', $id)
            ->whereNull('counted_qty')
            ->count();

        return new JsonResponse([
            'success' => true,
            'total'   => $total,
            'shown'   => $rows->count(),
            'rows'    => $rows->map(fn ($r) => [
                'id'           => (int) $r->id,
                'batch_number' => $r->batch_number,
                'expiry_date'  => $r->expiry_date,
                'baseline_qty' => (int) $r->baseline_qty,
                'product_id'   => (int) $r->product_id,
                'product_name' => $r->product_name,
                'product_sku'  => $r->product_sku,
            ])->all(),
        ]);
    }

    // ── Create batch (from picker "+ New Batch" button) ────────────────────

    /**
     * Create a new inventory_batch from inside the stock-take picker, then
     * upsert a stock_take_batches row with counted_qty pre-filled. quantity
     * comes from the operator's count, not a separate receiving flow — this
     * is the "I just found a carton with no record" case.
     */
    public function createBatch(Request $request, int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        if (! $session->isOpen()) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.session_locked'),
            ], 422);
        }

        $data = $request->validate([
            'product_id'   => 'required|integer|exists:products,id',
            'batch_number' => 'required|string|max:100',
            'expiry_date'  => 'required|date',
            'counted_qty'  => 'required|integer|min:0',
        ]);

        // Reject if a batch with the same number already exists for this
        // product/source — operator should scan it instead.
        $duplicate = DB::table('inventory_batches')
            ->where('product_id', $data['product_id'])
            ->where('inventory_source_id', $session->inventory_source_id)
            ->where('batch_number', $data['batch_number'])
            ->exists();

        if ($duplicate) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.batch_exists'),
            ], 422);
        }

        $adminId = optional(auth()->guard('admin')->user())->id;
        $countedQty = (int) $data['counted_qty'];

        $result = DB::transaction(function () use ($session, $data, $countedQty, $adminId) {
            $now = now();

            // Create the new batch with quantity_received = counted_qty.
            // Apply will later reconcile product_inventories from the SUM
            // across active batches, so this is consistent.
            $batchId = DB::table('inventory_batches')->insertGetId([
                'product_id'          => $data['product_id'],
                'inventory_source_id' => $session->inventory_source_id,
                'channel_id'          => null,
                'batch_number'        => $data['batch_number'],
                'quantity_received'   => $countedQty,
                'quantity_remaining'  => $countedQty,
                'cost_price'          => null,
                'manufacture_date'    => null,
                'expiry_date'         => $data['expiry_date'],
                'status'              => InventoryBatch::STATUS_ACTIVE,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);

            // Item upsert.
            $info    = $this->productDisplayInfo((int) $data['product_id']);
            $liveQty = $this->liveQty((int) $data['product_id'], (int) $session->inventory_source_id);

            $item = StockTakeItem::firstOrNew([
                'session_id' => $session->id,
                'product_id' => $data['product_id'],
            ]);
            if (! $item->exists) {
                $item->parent_product_id = $info['parent_product_id'];
                $item->sku               = $info['sku'];
                $item->name              = $info['name'];
                $item->variant_label     = $info['variant_label'];
                $item->matched_by        = 'batch_create';
                $item->system_qty        = $liveQty;
                $item->baseline_qty      = $liveQty;
                $item->counted_qty       = 0;
                $item->difference        = 0;
                $item->save();
            }

            // Stock-take batch row. baseline_qty = 0 (this batch didn't exist
            // before the session) so the full counted_qty registers as a
            // positive delta.
            $stb = StockTakeBatch::create([
                'session_id'         => $session->id,
                'item_id'            => $item->id,
                'inventory_batch_id' => $batchId,
                'product_id'         => $data['product_id'],
                'batch_number'       => $data['batch_number'],
                'expiry_date'        => $data['expiry_date'],
                'baseline_qty'       => 0,
                'counted_qty'        => $countedQty,
                'difference'         => $countedQty,
                'scanned_by'         => $adminId,
                'scanned_at'         => $now,
                'applied'            => false,
            ]);

            $this->rollupItemFromBatches($item);

            return ['item' => $item->fresh(), 'batch' => $stb->fresh()];
        });

        return new JsonResponse([
            'success' => true,
            'item'    => $this->itemPayload($result['item']),
            'batch'   => $this->batchPayload($result['batch']),
            'summary' => $this->summarize($session->id),
        ]);
    }

    // ── Mark expired batches as waste ─────────────────────────────────────

    /**
     * One-click cleanup: every active batch at this session's source whose
     * expiry_date is in the past becomes a WasteRecord, the batch flips to
     * status='expired' with quantity_remaining=0, product_inventories is
     * decremented, and a stock audit row is written.
     *
     * Only allowed on an OPEN session (session lock is the safety net for
     * accidental double-clicks).
     */
    public function markExpiredAsWaste(int $id): JsonResponse
    {
        $session = StockTakeSession::findOrFail($id);

        if (! $session->isOpen()) {
            return new JsonResponse([
                'success' => false,
                'message' => __('inventory-control::app.stock_take.errors.session_locked'),
            ], 422);
        }

        $today = Carbon::today();
        $adminId = optional(auth()->guard('admin')->user())->id;

        $expired = DB::table('inventory_batches as ib')
            ->leftJoin('product_flat as pf', function ($j) {
                $j->on('pf.product_id', '=', 'ib.product_id')->whereNull('pf.parent_id');
            })
            ->where('ib.inventory_source_id', $session->inventory_source_id)
            ->where('ib.status', InventoryBatch::STATUS_ACTIVE)
            ->where('ib.quantity_remaining', '>', 0)
            ->whereNotNull('ib.expiry_date')
            ->where('ib.expiry_date', '<', $today)
            ->orderBy('ib.id')
            ->get([
                'ib.id', 'ib.product_id', 'ib.batch_number', 'ib.expiry_date',
                'ib.quantity_remaining', 'ib.cost_price',
                'pf.name as product_name', 'pf.sku as product_sku',
            ]);

        if ($expired->isEmpty()) {
            return new JsonResponse([
                'success' => true,
                'count'   => 0,
                'qty'     => 0,
            ]);
        }

        $defaultChannel = (int) (DB::table('channels')->orderBy('id')->value('id') ?? 0) ?: null;

        $stats = DB::transaction(function () use ($expired, $session, $adminId, $defaultChannel) {
            $count = 0;
            $totalQty = 0;

            foreach ($expired as $b) {
                $qty = (int) $b->quantity_remaining;
                $cost = $b->cost_price ? (float) $b->cost_price : 0.0;
                $amount = $cost * $qty;

                // 1) Waste record (with batch_id for traceability).
                WasteRecord::create([
                    'product_id'   => $b->product_id,
                    'batch_id'     => $b->id,
                    'product_name' => $b->product_name ?: ('Product #' . $b->product_id),
                    'product_sku'  => $b->product_sku,
                    'channel_id'   => $defaultChannel,
                    'quantity'     => $qty,
                    'amount'       => $amount,
                    'reason'       => 'Expired',
                    'notes'        => 'Auto-marked from stock-take #' . $session->id,
                    'created_by'   => $adminId,
                ]);

                // 2) Zero out + flip batch status.
                DB::table('inventory_batches')
                    ->where('id', $b->id)
                    ->update([
                        'quantity_remaining' => 0,
                        'status'             => InventoryBatch::STATUS_EXPIRED,
                        'updated_at'         => now(),
                    ]);

                // 3) Deduct from product_inventories aggregate.
                $invRow = DB::table('product_inventories')
                    ->where('product_id', $b->product_id)
                    ->where('inventory_source_id', $session->inventory_source_id)
                    ->first();

                if ($invRow) {
                    $before = (int) $invRow->qty;
                    $after = max(0, $before - $qty);
                    DB::table('product_inventories')
                        ->where('id', $invRow->id)
                        ->update(['qty' => $after, 'updated_at' => now()]);

                    if ($before !== $after) {
                        $this->recordBatchAudit(
                            (int) $b->product_id,
                            (int) $session->inventory_source_id,
                            (int) $b->id,
                            $before,
                            $after,
                            'admin.stock_take.waste.expired'
                        );
                    }
                }

                $count++;
                $totalQty += $qty;
            }

            return ['count' => $count, 'qty' => $totalQty];
        });

        return new JsonResponse([
            'success' => true,
            'count'   => $stats['count'],
            'qty'     => $stats['qty'],
        ]);
    }
}
