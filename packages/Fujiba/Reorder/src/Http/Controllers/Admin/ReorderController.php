<?php

namespace Fujiba\Reorder\Http\Controllers\Admin;

use Fujiba\Reorder\Models\ReorderBatch;
use Fujiba\Reorder\Models\ReorderItem;
use Fujiba\Reorder\Models\ReorderTodo;
use Fujiba\Reorder\Services\BarcodeSvg;
use Fujiba\Reorder\Services\ChannelStockResolver;
use Fujiba\Reorder\Services\PendingQtyAggregator;
use Fujiba\Reorder\Services\ReorderCalculator;
use Fujiba\Reorder\Services\SalesAggregator;
use Fujiba\Reorder\Services\StockUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Repositories\ProductRepository;

class ReorderController
{
    public function __construct(
        private ProductRepository $products,
        private ChannelStockResolver $stockResolver,
        private SalesAggregator $salesAggregator,
        private PendingQtyAggregator $pendingAggregator,
        private ReorderCalculator $calculator,
        private StockUpdateService $stockUpdater,
        private BarcodeSvg $barcode,
    ) {}

    public function index(Request $request)
    {
        $channel     = $this->resolveChannel($request);
        $channelId   = (int) $channel->id;
        $channelCode = $channel->code;

        $attrSourceId = $this->attributeId('Source');

        $sourceOptions = [];
        if ($attrSourceId) {
            $sourceOptions = DB::table('attribute_options')
                ->where('attribute_id', $attrSourceId)
                ->orderBy('sort_order')
                ->orderBy('admin_name')
                ->get(['id', 'admin_name'])
                ->map(fn ($o) => ['id' => (int) $o->id, 'name' => (string) $o->admin_name])
                ->all();
        }

        $channels = [];
        foreach (core()->getAllChannels() as $c) {
            $channels[] = [
                'code' => $c->code,
                'name' => (string) ($c->code),
            ];
        }

        // Pre-selected product IDs from the catalog clipboard hand-off,
        // if any. Read once from the flashed session so a refresh clears them.
        $preselect = (array) session('reorder.preselect', []);

        return view('reorder::admin.index', [
            'channel'         => $channel,
            'channelId'       => $channelId,
            'channels'        => $channels,
            'sourceOptions'   => $sourceOptions,
            'leadTimeDays'    => $this->calculator->leadTimeDays($channelCode),
            'salesWindowDays' => $this->calculator->salesWindowDays($channelCode),
            'preselectIds'    => array_values(array_map('intval', $preselect)),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $channel     = $this->resolveChannel($request);
        $channelId   = (int) $channel->id;
        $channelCode = $channel->code;

        $sortBuyAt = $request->query('sort_buy_at', 'asc') === 'desc' ? 'desc' : 'asc';
        $search    = trim((string) $request->query('q', ''));
        $sourceFilter = trim((string) $request->query('source', ''));

        $window = $this->calculator->salesWindowDays($channelCode);

        $attrSource   = $this->attributeId('Source');
        $attrOrigName = $this->attributeId('original_product_name');
        $attrName     = $this->attributeId('name');
        $attrCost     = $this->attributeId('cost');
        $attrBuyAt    = $this->attributeId('buy_at');
        $attrWaste    = $this->attributeId('waste_count');

        // Aggregate one row per product. pav rows can repeat per locale/channel,
        // so we use MAX() instead of wide GROUP BY to get a single value per
        // product without row explosion.
        $q = DB::table('products as p')
            ->leftJoin('product_attribute_values as pav_source', function ($j) use ($attrSource) {
                $j->on('pav_source.product_id', '=', 'p.id')
                    ->where('pav_source.attribute_id', '=', $attrSource);
            })
            ->leftJoin('product_attribute_values as pav_cost', function ($j) use ($attrCost) {
                $j->on('pav_cost.product_id', '=', 'p.id')
                    ->where('pav_cost.attribute_id', '=', $attrCost);
            })
            ->leftJoin('product_attribute_values as pav_buy_at', function ($j) use ($attrBuyAt) {
                $j->on('pav_buy_at.product_id', '=', 'p.id')
                    ->where('pav_buy_at.attribute_id', '=', $attrBuyAt);
            })
            ->leftJoin('product_attribute_values as pav_name', function ($j) use ($attrName) {
                $j->on('pav_name.product_id', '=', 'p.id')
                    ->where('pav_name.attribute_id', '=', $attrName);
            })
            ->leftJoin('product_attribute_values as pav_orig_name', function ($j) use ($attrOrigName) {
                $j->on('pav_orig_name.product_id', '=', 'p.id')
                    ->where('pav_orig_name.attribute_id', '=', $attrOrigName);
            })
            ->leftJoin('product_attribute_values as pav_waste', function ($j) use ($attrWaste) {
                $j->on('pav_waste.product_id', '=', 'p.id')
                    ->where('pav_waste.attribute_id', '=', $attrWaste);
            })
            ->leftJoin('product_images as pi', function ($j) {
                $j->on('pi.product_id', '=', 'p.id')
                    ->whereRaw('pi.id = (SELECT MIN(id) FROM product_images WHERE product_id = p.id)');
            })
            ->select(
                'p.id',
                'p.sku',
                // Two separate fields — the JS renders `name` on line 1 and
                // `original_name` (if non-empty) on line 2. Earlier versions
                // collapsed both into `name` via COALESCE, which prevented
                // the UI from distinguishing them.
                DB::raw('MAX(pav_name.text_value) as name'),
                DB::raw('NULLIF(MAX(pav_orig_name.text_value), "") as original_name'),
                DB::raw('MAX(pav_source.text_value)  as source'),
                DB::raw('MAX(pav_cost.float_value)   as cost'),
                DB::raw('MAX(pav_buy_at.datetime_value) as buy_at'),
                DB::raw('MAX(pav_waste.integer_value) as waste_count'),
                DB::raw('MAX(pi.path) as image_path')
            )
            ->groupBy('p.id', 'p.sku');

        // Limit to products assigned to the selected channel via the
        // product_channels pivot. Without this, every channel returned the
        // same global product list, so the channel <select> appeared broken.
        $q->whereExists(function ($sub) use ($channelId) {
            $sub->select(DB::raw(1))
                ->from('product_channels')
                ->whereColumn('product_channels.product_id', 'p.id')
                ->where('product_channels.channel_id', $channelId);
        });

        if ($sourceFilter !== '') {
            $q->whereRaw('FIND_IN_SET(?, pav_source.text_value)', [$sourceFilter]);
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('p.sku', 'like', "%{$search}%")
                  ->orWhere('pav_name.text_value', 'like', "%{$search}%")
                  ->orWhere('pav_orig_name.text_value', 'like', "%{$search}%");
            });
        }

        // Order at the outer level so MySQL accepts the alias.
        $q->orderByRaw('buy_at IS NULL, buy_at '.$sortBuyAt);

        $rows = $q->limit(2000)->get();

        $ids        = $rows->pluck('id')->map(fn ($v) => (int) $v)->all();
        $stockMap   = $this->stockResolver->forChannel($channelId, $ids);
        $salesMap   = $this->salesAggregator->forChannel($channelId, $window, $ids);
        $pendingMap = $this->pendingAggregator->forChannel($channelId, $ids);

        $payload = $rows->map(function ($r) use ($stockMap, $salesMap, $pendingMap, $window, $channelCode) {
            $stock      = (int) ($stockMap[$r->id] ?? 0);
            $sold       = (int) ($salesMap[$r->id] ?? 0);
            $pending    = (int) ($pendingMap[$r->id] ?? 0);
            $suggested  = $this->calculator->suggest($sold, $window, $stock, $channelCode);

            return [
                'id'            => (int) $r->id,
                'sku'           => $r->sku,
                'name'          => $r->name ?: $r->sku,
                'original_name' => $r->original_name ?: null,
                'source'        => $r->source,
                'image'         => $r->image_path ? Storage::url($r->image_path) : null,
                'cost'          => (float) ($r->cost ?? 0),
                'buy_at'        => $r->buy_at,
                'waste_count'   => (int) ($r->waste_count ?? 0),
                'stock'         => $stock,
                'sale_count'    => $sold,
                'pending_qty'   => $pending,
                'suggested_qty' => $suggested,
            ];
        });

        return response()->json(['data' => $payload]);
    }

    /**
     * Receive a clipboard hand-off from the catalog list view.
     * Stores selected product IDs in the session so the index page
     * can pre-load them without exposing IDs in the URL.
     */
    public function fromClipboard(Request $request)
    {
        $data = $request->validate([
            'product_ids'   => 'required|array|min:1',
            'product_ids.*' => 'integer|min:1',
        ]);

        session()->flash('reorder.preselect', array_values(array_unique(array_map('intval', $data['product_ids']))));

        return redirect()->route('admin.inventory.reorder.index');
    }

    /**
     * Create a new reorder batch from clipboard items.
     * Items: { product_id, qty, cost?, name? }
     * status='pending' = saved & ready to start receiving.
     * status='draft'   = quick-saved, still being edited.
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'note'   => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,pending',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.qty'          => 'required|integer|min:1',
            'items.*.cost'         => 'nullable|numeric|min:0',
            'items.*.name'         => 'nullable|string|max:255',
        ]);

        $channel = $this->resolveChannel($request);
        $userId  = optional(auth('admin')->user())->id;
        $status  = $data['status'] ?? ReorderBatch::STATUS_PENDING;

        $batch = DB::transaction(function () use ($data, $channel, $userId, $status) {
            $batch = ReorderBatch::create([
                'channel_id' => (int) $channel->id,
                'status'     => $status,
                'note'       => $data['note'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                ReorderItem::create([
                    'reorder_batch_id' => $batch->id,
                    'product_id'       => (int) $item['product_id'],
                    'name_snapshot'    => $item['name'] ?? null,
                    'qty'              => (int) $item['qty'],
                    'cost'             => (float) ($item['cost'] ?? 0),
                ]);
            }

            return $batch;
        });

        return response()->json([
            'ok'    => true,
            'batch' => $batch->load('items'),
        ]);
    }

    /**
     * Update an existing draft/pending batch — replace the items with the
     * provided set. We delete-and-reinsert because per-item qty changes are
     * what users do most often, and tracking individual-row diffs (add /
     * remove / update) here doesn't earn anything once status='received'
     * locks the batch.
     */
    public function updateBatch(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'note'   => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,pending',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.qty'          => 'required|integer|min:1',
            'items.*.cost'         => 'nullable|numeric|min:0',
            'items.*.name'         => 'nullable|string|max:255',
        ]);

        $batch = ReorderBatch::findOrFail($id);

        if (in_array($batch->status, [ReorderBatch::STATUS_RECEIVING, ReorderBatch::STATUS_RECEIVED], true)) {
            return response()->json(['ok' => false, 'message' => 'Cannot edit a batch that is being received or already received'], 422);
        }

        DB::transaction(function () use ($batch, $data) {
            if (array_key_exists('note', $data))   $batch->note = $data['note'];
            if (! empty($data['status']))          $batch->status = $data['status'];
            $batch->save();

            ReorderItem::where('reorder_batch_id', $batch->id)->delete();

            foreach ($data['items'] as $item) {
                ReorderItem::create([
                    'reorder_batch_id' => $batch->id,
                    'product_id'       => (int) $item['product_id'],
                    'name_snapshot'    => $item['name'] ?? null,
                    'qty'              => (int) $item['qty'],
                    'cost'             => (float) ($item['cost'] ?? 0),
                ]);
            }
        });

        return response()->json([
            'ok'    => true,
            'batch' => $batch->fresh()->load('items'),
        ]);
    }

    /** Full-screen edit page for a draft/pending batch. */
    public function editBatch(int $id)
    {
        $batch = ReorderBatch::with('items.product')->findOrFail($id);

        $itemPayload = $batch->items->map(function (ReorderItem $it) use ($batch) {
            $stock = $this->stockResolver->forChannel((int) $batch->channel_id, [(int) $it->product_id]);
            $img   = optional($it->product?->images?->first())->path;

            return [
                'id'             => $it->id,
                'product_id'     => $it->product_id,
                'name'           => $it->name_snapshot ?: optional($it->product)->name,
                'sku'            => optional($it->product)->sku,
                'image'          => $img ? Storage::url($img) : null,
                'qty'            => (int) $it->qty,
                'cost'           => (float) $it->cost,
                'current_stock'  => (int) ($stock[$it->product_id] ?? 0),
            ];
        });

        return view('reorder::admin.batch-edit', [
            'batch' => $batch,
            'items' => $itemPayload,
            'csrf'  => csrf_token(),
        ]);
    }

    /**
     * Start receiving: lock the batch into 'receiving' status so concurrent
     * users can't double-receive. Idempotent — already-receiving stays.
     */
    public function startReceive(int $id): JsonResponse
    {
        $batch = ReorderBatch::findOrFail($id);

        if ($batch->status === ReorderBatch::STATUS_RECEIVED) {
            return response()->json(['ok' => false, 'message' => 'Batch already received'], 422);
        }

        if ($batch->status !== ReorderBatch::STATUS_RECEIVING) {
            $batch->status = ReorderBatch::STATUS_RECEIVING;
            $batch->save();
        }

        return response()->json(['ok' => true, 'batch' => $batch->load('items')]);
    }

    /** Full-screen receive page. Shows batch items + older received history. */
    public function receiveScreen(int $id)
    {
        $batch = ReorderBatch::with('items.product')->findOrFail($id);

        $productIds = $batch->items->pluck('product_id')->map(fn ($v) => (int) $v)->all();
        $stockMap   = $this->stockResolver->forChannel((int) $batch->channel_id, $productIds);

        $itemPayload = $batch->items->map(function (ReorderItem $it) use ($stockMap) {
            $img = optional($it->product?->images?->first())->path;

            return [
                'id'             => $it->id,
                'product_id'     => $it->product_id,
                'name'           => $it->name_snapshot ?: optional($it->product)->name,
                'sku'            => optional($it->product)->sku,
                'image'          => $img ? Storage::url($img) : null,
                'qty'            => (int) $it->qty,
                'delivered_qty'  => (int) $it->delivered_qty,
                'damage_qty'     => (int) $it->damage_qty,
                'loss_qty'       => (int) $it->loss_qty,
                'cut_off_qty'    => (int) $it->cut_off_qty,
                'to_stock_qty'   => (int) $it->to_stock_qty,
                'current_stock'  => (int) ($stockMap[$it->product_id] ?? 0),
            ];
        });

        $history = ReorderBatch::where('status', ReorderBatch::STATUS_RECEIVED)
            ->where('id', '!=', $batch->id)
            ->orderByDesc('received_at')
            ->limit(20)
            ->withCount('items')
            ->get(['id', 'note', 'received_at', 'created_at']);

        return view('reorder::admin.batch-receive', [
            'batch'   => $batch,
            'items'   => $itemPayload,
            'history' => $history,
            'csrf'    => csrf_token(),
        ]);
    }

    /**
     * Save the receive draft without applying stock. Lets the user pause
     * mid-count and come back. Each item gets its 4 qty fields and an
     * optional explicit to_stock_qty override (defaults to delivered −
     * damage − loss − cut_off, clamped ≥ 0).
     */
    public function saveReceiveDraft(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.id'             => 'required|integer',
            'items.*.delivered_qty'  => 'nullable|integer|min:0',
            'items.*.damage_qty'     => 'nullable|integer|min:0',
            'items.*.loss_qty'       => 'nullable|integer|min:0',
            'items.*.cut_off_qty'    => 'nullable|integer|min:0',
            'items.*.to_stock_qty'   => 'nullable|integer|min:0',
        ]);

        $batch = ReorderBatch::with('items')->findOrFail($id);

        if ($batch->status === ReorderBatch::STATUS_RECEIVED) {
            return response()->json(['ok' => false, 'message' => 'Batch already received'], 422);
        }

        DB::transaction(function () use ($batch, $data) {
            $byId = $batch->items->keyBy('id');

            foreach ($data['items'] as $row) {
                $item = $byId[$row['id']] ?? null;
                if (! $item) continue;

                $item->delivered_qty = (int) ($row['delivered_qty'] ?? 0);
                $item->damage_qty    = (int) ($row['damage_qty']    ?? 0);
                $item->loss_qty      = (int) ($row['loss_qty']      ?? 0);
                $item->cut_off_qty   = (int) ($row['cut_off_qty']   ?? 0);
                // Explicit override wins if present, else recompute from the four.
                $item->to_stock_qty  = array_key_exists('to_stock_qty', $row) && $row['to_stock_qty'] !== null
                    ? (int) $row['to_stock_qty']
                    : $item->defaultToStockQty();
                $item->save();
            }

            if ($batch->status !== ReorderBatch::STATUS_RECEIVING) {
                $batch->status = ReorderBatch::STATUS_RECEIVING;
                $batch->save();
            }
        });

        return response()->json(['ok' => true, 'batch' => $batch->fresh()->load('items')]);
    }

    /**
     * Confirm: apply to_stock_qty into product_inventories and close the batch.
     * Idempotent guard against re-confirming.
     */
    public function confirmReceive(Request $request, int $id): JsonResponse
    {
        // Allow last-second per-item edits in the same payload as save+confirm.
        $data = $request->validate([
            'items'                  => 'nullable|array',
            'items.*.id'             => 'required_with:items|integer',
            'items.*.delivered_qty'  => 'nullable|integer|min:0',
            'items.*.damage_qty'     => 'nullable|integer|min:0',
            'items.*.loss_qty'       => 'nullable|integer|min:0',
            'items.*.cut_off_qty'    => 'nullable|integer|min:0',
            'items.*.to_stock_qty'   => 'nullable|integer|min:0',
        ]);

        $batch = ReorderBatch::with('items')->findOrFail($id);

        if ($batch->status === ReorderBatch::STATUS_RECEIVED) {
            return response()->json(['ok' => false, 'message' => 'Batch already received'], 422);
        }

        $sourceIds = $this->stockResolver->sourceIdsForChannel((int) $batch->channel_id);
        if (empty($sourceIds)) {
            return response()->json(['ok' => false, 'message' => 'No active inventory source for channel'], 422);
        }
        $sourceId = $sourceIds[0];

        DB::transaction(function () use ($batch, $data, $sourceId) {
            $byId = $batch->items->keyBy('id');

            // Apply any inline edits from the confirm payload first.
            foreach (($data['items'] ?? []) as $row) {
                $item = $byId[$row['id']] ?? null;
                if (! $item) continue;
                if (array_key_exists('delivered_qty', $row)) $item->delivered_qty = (int) $row['delivered_qty'];
                if (array_key_exists('damage_qty',    $row)) $item->damage_qty    = (int) $row['damage_qty'];
                if (array_key_exists('loss_qty',      $row)) $item->loss_qty      = (int) $row['loss_qty'];
                if (array_key_exists('cut_off_qty',   $row)) $item->cut_off_qty   = (int) $row['cut_off_qty'];
                $item->to_stock_qty = array_key_exists('to_stock_qty', $row) && $row['to_stock_qty'] !== null
                    ? (int) $row['to_stock_qty']
                    : $item->defaultToStockQty();
                $item->save();
            }

            // Then apply stock — read fresh items in case nothing came in payload.
            foreach ($batch->items()->get() as $item) {
                $toStock = (int) $item->to_stock_qty;
                if ($toStock <= 0) continue;

                $row = DB::table('product_inventories')
                    ->where('product_id', $item->product_id)
                    ->where('inventory_source_id', $sourceId)
                    ->lockForUpdate()
                    ->first();

                if ($row) {
                    DB::table('product_inventories')
                        ->where('id', $row->id)
                        ->update(['qty' => (int) $row->qty + $toStock]);
                } else {
                    DB::table('product_inventories')->insert([
                        'product_id'          => $item->product_id,
                        'inventory_source_id' => $sourceId,
                        'qty'                 => $toStock,
                    ]);
                }
            }

            $batch->status      = ReorderBatch::STATUS_RECEIVED;
            $batch->received_at = now();
            $batch->save();
        });

        return response()->json(['ok' => true, 'batch' => $batch->fresh()->load('items')]);
    }

    /** Backward-compat alias used by the existing reorder index "Receive" button. */
    public function receiveBatch(Request $request, int $id): JsonResponse
    {
        return $this->confirmReceive($request, $id);
    }

    public function listBatches(Request $request): JsonResponse
    {
        $batches = ReorderBatch::withCount('items')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get([
                'id', 'channel_id', 'status', 'note', 'created_by', 'received_at', 'created_at',
            ]);

        return response()->json(['data' => $batches]);
    }

    public function showBatch(int $id): JsonResponse
    {
        $batch = ReorderBatch::with('items')->findOrFail($id);

        return response()->json(['data' => $batch]);
    }

    /** Full-screen history page. */
    public function historyIndex(Request $request)
    {
        $status = $request->query('status', 'all');

        $q = ReorderBatch::withCount('items')->orderByDesc('created_at');
        if (in_array($status, [ReorderBatch::STATUS_DRAFT, ReorderBatch::STATUS_PENDING, ReorderBatch::STATUS_RECEIVING, ReorderBatch::STATUS_RECEIVED], true)) {
            $q->where('status', $status);
        }
        $batches = $q->limit(500)->get();

        return view('reorder::admin.history', [
            'batches' => $batches,
            'status'  => $status,
        ]);
    }

    /**
     * @deprecated 2026-04-27 — use storeBatch() (POST /batches). Writes
     * legacy reorder_todos rows. Route 'admin.inventory.reorder.store'
     * scheduled for removal after 2026-05-11.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|integer|exists:products,id',
            'items.*.wish_qty'      => 'required|integer|min:1',
            'items.*.cost'          => 'nullable|numeric|min:0',
            'items.*.stock'         => 'nullable|integer',
            'items.*.sale_count'    => 'nullable|integer',
            'items.*.suggested_qty' => 'nullable|integer',
        ]);

        $channel   = $this->resolveChannel($request);
        $channelId = (int) $channel->id;
        $userId    = optional(auth('admin')->user())->id;

        $created = [];

        DB::transaction(function () use ($data, $channelId, $userId, &$created) {
            foreach ($data['items'] as $item) {
                $created[] = ReorderTodo::create([
                    'product_id'    => $item['product_id'],
                    'channel_id'    => $channelId,
                    'stock'         => $item['stock']         ?? 0,
                    'sale_count'    => $item['sale_count']    ?? 0,
                    'suggested_qty' => $item['suggested_qty'] ?? 0,
                    'wish_qty'      => $item['wish_qty'],
                    'cost'          => $item['cost']          ?? 0,
                    'status'        => ReorderTodo::STATUS_DRAFT,
                    'created_by'    => $userId,
                ])->id;
            }
        });

        return response()->json(['ok' => true, 'ids' => $created]);
    }

    /**
     * @deprecated 2026-04-27 — use confirmReceive() (POST /batches/{id}/confirm-receive).
     * Operates on legacy reorder_todos. Scheduled for removal after 2026-05-11.
     */
    public function arrive(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'arrived_qty' => 'required|integer|min:1',
        ]);

        $todo = ReorderTodo::query()->findOrFail($id);

        $todo = $this->stockUpdater->markArrived($todo, (int) $data['arrived_qty']);

        return response()->json(['ok' => true, 'todo' => $todo]);
    }

    /**
     * @deprecated 2026-04-27 — labels rendered against legacy reorder_todos.
     * The new batch flow does not yet have a labels equivalent. Keep the
     * route reachable until the new flow grows label support, then remove.
     */
    public function printLabels(Request $request)
    {
        $ids = (array) $request->query('ids', []);

        $todos = ReorderTodo::query()
            ->with('product')
            ->whereIn('id', $ids)
            ->get();

        $labels  = [];
        $missing = [];

        foreach ($todos as $todo) {
            $product = $todo->product;
            $barcode = $product->sku ?? '';

            if ($barcode === '') {
                $missing[] = $todo->product_id;
                continue;
            }

            $labels[] = [
                'todo_id'    => $todo->id,
                'product_id' => $todo->product_id,
                'sku'        => $barcode,
                'qty'        => (int) $todo->wish_qty,
                'svg'        => $this->barcode->encode($barcode),
            ];
        }

        return view('reorder::admin.labels', [
            'labels'  => $labels,
            'missing' => $missing,
        ]);
    }

    private function resolveChannel(Request $request)
    {
        $code = $request->query('channel');

        if ($code) {
            $channel = core()->getAllChannels()->firstWhere('code', $code);

            if ($channel) {
                return $channel;
            }
        }

        return core()->getCurrentChannel();
    }

    private function attributeId(string $code): int
    {
        static $cache = [];

        if (! isset($cache[$code])) {
            $cache[$code] = (int) DB::table('attributes')->where('code', $code)->value('id');
        }

        return $cache[$code];
    }
}
