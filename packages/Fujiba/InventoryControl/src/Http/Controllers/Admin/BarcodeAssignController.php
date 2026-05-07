<?php

namespace Fujiba\InventoryControl\Http\Controllers\Admin;

use Fujiba\InventoryControl\Support\BarcodeNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\Product\ProductImage;
use Webkul\Product\Repositories\ProductRepository;

class BarcodeAssignController
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductImage $productImage,
    ) {
    }

    public function index()
    {
        $this->assertTenantConnection();

        $channels = DB::table('channels')
            ->orderBy('id')
            ->get(['code', 'id'])
            ->map(fn ($c) => ['code' => (string) $c->code])
            ->values()
            ->all();

        return view('inventory-control::admin.barcode-assign.index', [
            'canReassign' => (bool) bouncer()->hasPermission('catalog.products.barcode_reassign'),
            'sessionId'   => $this->ensureSessionId(),
            'channels'    => $channels,
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->assertTenantConnection();
        $this->assertBarcodeTable();

        $data = $request->validate([
            'barcode' => 'required|string|max:500',
        ]);

        $data['barcode'] = BarcodeNormalizer::normalize($data['barcode']);

        $row = DB::table('pos_product_barcode')
            ->where(function ($w) use ($data) {
                $w->where('barcode', $data['barcode'])
                    ->orWhere('scan_value', $data['barcode']);
            })
            ->first(['product_id', 'barcode']);

        if (! $row || ! $row->product_id) {
            return new JsonResponse(['found' => false, 'product' => null]);
        }

        return new JsonResponse([
            'found'   => true,
            'product' => $this->productPayload((int) $row->product_id, (string) $row->barcode),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->assertTenantConnection();

        $data = $request->validate([
            'q'                => 'nullable|string|max:120',
            'limit'            => 'nullable|integer|min:1|max:200',
            'offset'           => 'nullable|integer|min:0',
            'include_inactive' => 'nullable|boolean',
            'channel'          => 'nullable|string|max:64',
        ]);

        $limit           = (int) ($data['limit'] ?? 50);
        $offset          = (int) ($data['offset'] ?? 0);
        $q               = trim((string) ($data['q'] ?? ''));
        $includeInactive = (bool) ($data['include_inactive'] ?? false);
        $channel         = trim((string) ($data['channel'] ?? ''));
        $locale          = app()->getLocale();

        $base = DB::table('product_flat as pf')
            ->whereNull('pf.parent_id') // configurable / simple parent rows only; skip variants
            ->where('pf.locale', $locale);

        if ($channel !== '') {
            $base->where('pf.channel', $channel);
        }

        if (! $includeInactive) {
            $base->where('pf.status', 1);
        }

        if ($q !== '') {
            $needle = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $base->where(function ($w) use ($needle) {
                $w->where('pf.name', 'like', $needle)
                    ->orWhere('pf.sku', 'like', $needle);
            });
        }

        $total = (clone $base)->count('pf.product_id');

        // Page over distinct product_ids first, then attach a single barcode per
        // product. Without this, a future product with multiple barcode rows would
        // multiply pf rows via the leftJoin and silently push other products off
        // the page (total comes from the unjoined base, page comes from the joined
        // query — they'd disagree).
        $pageIds = (clone $base)
            ->orderByRaw("(pf.name IS NULL OR pf.name = '') ASC")
            ->orderBy('pf.name')
            ->orderBy('pf.sku')
            ->offset($offset)
            ->limit($limit)
            ->pluck('pf.product_id')
            ->all();

        $rows = empty($pageIds)
            ? collect()
            : (function () use ($pageIds, $locale, $channel) {
                $q = DB::table('product_flat as pf')
                    ->leftJoin('pos_product_barcode as ppb', 'ppb.product_id', '=', 'pf.product_id')
                    ->where('pf.locale', $locale)
                    ->whereIn('pf.product_id', $pageIds);
                if ($channel !== '') {
                    $q->where('pf.channel', $channel);
                }
                return $q
                    ->orderByRaw("(pf.name IS NULL OR pf.name = '') ASC")
                    ->orderBy('pf.name')
                    ->orderBy('pf.sku')
                    ->get([
                        'pf.product_id',
                        'pf.name',
                        'pf.sku',
                        'ppb.barcode',
                    ])
                    ->unique('product_id')
                    ->values();
            })();

        $results = $rows->map(function ($r) {
            $name = (string) ($r->name ?? '');
            if ($name === '') {
                $name = '(' . ($r->sku ?? 'no name') . ')';
            }
            return $this->productPayload(
                (int) $r->product_id,
                $r->barcode !== null ? (string) $r->barcode : null,
                ['name' => $name, 'sku' => (string) ($r->sku ?? '')],
            );
        });

        return new JsonResponse([
            'results'  => $results,
            'total'    => $total,
            'offset'   => $offset,
            'limit'    => $limit,
            'has_more' => ($offset + $rows->count()) < $total,
        ]);
    }

    public function assign(Request $request): JsonResponse
    {
        $this->assertTenantConnection();
        $this->assertBarcodeTable();

        $data = $request->validate([
            'product_id'     => 'required|integer|exists:products,id',
            'barcode'        => 'required|string|max:500',
            'force_reassign' => 'nullable|boolean',
        ]);

        $data['barcode'] = BarcodeNormalizer::normalize($data['barcode']);

        if ($data['barcode'] === '') {
            return new JsonResponse([
                'success' => false,
                'error'   => 'barcode_empty_after_normalize',
                'message' => __('inventory-control::app.barcode_assign.errors.barcode_empty_after_normalize'),
            ], 422);
        }

        $force = (bool) ($data['force_reassign'] ?? false);

        if ($force && ! bouncer()->hasPermission('catalog.products.barcode_reassign')) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'forbidden_reassign',
                'message' => __('inventory-control::app.barcode_assign.errors.forbidden_reassign'),
            ], 403);
        }

        $existingForBarcode = DB::table('pos_product_barcode')
            ->where(function ($w) use ($data) {
                $w->where('barcode', $data['barcode'])
                    ->orWhere('scan_value', $data['barcode']);
            })
            ->first();

        $existingForProduct = DB::table('pos_product_barcode')
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existingForBarcode && (int) $existingForBarcode->product_id !== (int) $data['product_id'] && ! $force) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'barcode_in_use',
                'message' => __('inventory-control::app.barcode_assign.errors.barcode_in_use'),
                'holder'  => $this->productPayload((int) $existingForBarcode->product_id, (string) $existingForBarcode->barcode),
            ], 409);
        }

        if ($existingForProduct && (string) $existingForProduct->barcode !== $data['barcode'] && ! $force) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'product_has_barcode',
                'message' => __('inventory-control::app.barcode_assign.errors.product_has_barcode'),
                'current' => $this->productPayload((int) $data['product_id'], (string) $existingForProduct->barcode),
            ], 409);
        }

        $sessionId = $this->ensureSessionId();
        $logIds    = [];

        DB::beginTransaction();

        try {
            // If the barcode currently belongs to another product, detach it from that product
            // and log an unassign for the old product.
            if ($existingForBarcode && (int) $existingForBarcode->product_id !== (int) $data['product_id']) {
                $oldProductId = (int) $existingForBarcode->product_id;

                DB::table('pos_product_barcode')
                    ->where('id', $existingForBarcode->id)
                    ->delete();

                $logIds[] = $this->writeLog([
                    'product_id'  => $oldProductId,
                    'barcode_old' => (string) $existingForBarcode->barcode,
                    'barcode_new' => null,
                    'action'      => 'unassign',
                    'session_id'  => $sessionId,
                ]);
            }

            $productHasRow = DB::table('pos_product_barcode')
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->first();

            $oldBarcode = $productHasRow ? (string) $productHasRow->barcode : null;
            $action     = $productHasRow && $oldBarcode !== null && $oldBarcode !== '' ? 'reassign' : 'assign';

            if ($productHasRow) {
                DB::table('pos_product_barcode')
                    ->where('id', $productHasRow->id)
                    ->update([
                        'barcode'    => $data['barcode'],
                        'scan_value' => $data['barcode'],
                        'scan_type'  => 'gtin',
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

            $logIds[] = $this->writeLog([
                'product_id'  => (int) $data['product_id'],
                'barcode_old' => $oldBarcode,
                'barcode_new' => $data['barcode'],
                'action'      => $action,
                'session_id'  => $sessionId,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return new JsonResponse([
            'success' => true,
            'product' => $this->productPayload((int) $data['product_id'], $data['barcode']),
            'log_ids' => $logIds,
        ]);
    }

    public function undo(Request $request): JsonResponse
    {
        $this->assertTenantConnection();
        $this->assertBarcodeTable();

        $data = $request->validate([
            'log_id' => 'required|integer',
        ]);

        $sessionId = $this->ensureSessionId();
        $userId    = auth('admin')->id();

        $log = DB::table('barcode_assignments_log')
            ->where('id', $data['log_id'])
            ->where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->where('reverted', false)
            ->first();

        if (! $log) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'log_not_found',
                'message' => __('inventory-control::app.barcode_assign.errors.undo_not_found'),
            ], 404);
        }

        DB::beginTransaction();

        try {
            if ($log->action === 'assign') {
                // Forward: created or set barcode_new on product_id. Reverse: clear it (if still matches).
                DB::table('pos_product_barcode')
                    ->where('product_id', $log->product_id)
                    ->where('barcode', $log->barcode_new)
                    ->delete();
            } elseif ($log->action === 'reassign') {
                // Forward: changed product's barcode from old → new. Reverse: restore old.
                DB::table('pos_product_barcode')
                    ->where('product_id', $log->product_id)
                    ->where('barcode', $log->barcode_new)
                    ->update([
                        'barcode'    => $log->barcode_old,
                        'scan_value' => $log->barcode_old,
                        'updated_at' => now(),
                    ]);
            } elseif ($log->action === 'unassign') {
                // Forward: deleted row holding barcode_old for product_id. Reverse: re-insert.
                DB::table('pos_product_barcode')->insert([
                    'product_id' => $log->product_id,
                    'barcode'    => $log->barcode_old,
                    'scan_value' => $log->barcode_old,
                    'scan_type'  => 'gtin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('barcode_assignments_log')
                ->where('id', $log->id)
                ->update(['reverted' => true, 'updated_at' => now()]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Fail loudly if we're on the central connection. The spec calls this out as
     * the previously-hit bug — better a 500 here than silent writes to bagisto_central.
     */
    private function assertTenantConnection(): void
    {
        $default = config('database.default');

        if ($default === 'mysql_central' || $default === 'central') {
            abort(500, 'TENANT_CONNECTION_REQUIRED: barcode-assign refuses to run on central connection.');
        }

        $dbName = DB::connection()->getDatabaseName();

        if (Str::startsWith((string) $dbName, ['bagisto_central', 'central_'])) {
            abort(500, 'TENANT_CONNECTION_REQUIRED: connected to central DB ('.$dbName.').');
        }
    }

    private function assertBarcodeTable(): void
    {
        if (! Schema::hasTable('pos_product_barcode')) {
            abort(500, 'BARCODE_TABLE_MISSING: pos_product_barcode does not exist on tenant connection.');
        }
    }

    private function ensureSessionId(): string
    {
        $sid = session('barcode_assign.session_id');

        if (! $sid) {
            $sid = (string) Str::uuid();
            session(['barcode_assign.session_id' => $sid]);
        }

        return $sid;
    }

    private function writeLog(array $row): int
    {
        return (int) DB::table('barcode_assignments_log')->insertGetId(array_merge([
            'user_id'    => auth('admin')->id(),
            'user_type'  => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ], $row));
    }

    /**
     * Build the product payload returned to the client. Pulls name+sku from product_flat
     * (locale-aware), and the small image URL via Bagisto's ProductImage helper.
     *
     * $hint lets callers pass already-fetched name/sku to avoid an extra query.
     */
    private function productPayload(int $productId, ?string $barcode, ?array $hint = null): array
    {
        if ($hint === null) {
            $row = DB::table('product_flat')
                ->where('product_id', $productId)
                ->orderByRaw('locale = ? DESC', [app()->getLocale()])
                ->first(['name', 'sku']);

            $name = $row->name ?? '';
            $sku  = $row->sku ?? '';
        } else {
            $name = (string) ($hint['name'] ?? '');
            $sku  = (string) ($hint['sku'] ?? '');
        }

        $product   = $this->productRepository->find($productId);
        $photoUrl  = null;

        if ($product) {
            $images = $this->productImage->getProductBaseImage($product);
            $photoUrl = $images['small_image_url'] ?? null;
        }

        return [
            'id'        => $productId,
            'name'      => $name,
            'sku'       => $sku,
            'barcode'   => $barcode,
            'photo_url' => $photoUrl,
        ];
    }
}
