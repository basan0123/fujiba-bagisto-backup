<?php

namespace Webkul\Pos\Mutations\Outlet;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Helpers\RoleGate;

/**
 * Stock management mutations for the merchant surface.
 *
 *   stockAdjust          cashier+ : delta on a single product+source row
 *   stockTransfer        manager+ : qty out of source A, into source B
 *   (waste lives in WasteMutation; this class doesn't duplicate it)
 *
 * Every successful write also logs to pos_stock_movement_log so we can
 * answer "who changed what when" without sniffing audit_log or
 * indexer-event triggers. Movements are idempotent per
 * (pos_user_id, idempotency_key); replays return the original log row.
 */
class StockMutation
{
    private const ADJUST_REASONS  = ['received', 'damaged', 'recount', 'theft', 'expired', 'other'];

    public function adjust($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'productId'         => 'required|integer',
            'quantityDelta'     => 'required|numeric',
            'inventorySourceId' => 'nullable|integer',
            'reason'            => 'required|string|in:' . implode(',', self::ADJUST_REASONS),
            'note'              => 'nullable|string|max:500',
            'idempotencyKey'    => 'nullable|string|min:16|max:64|regex:/^[A-Za-z0-9_-]+$/',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $user = $context->user();
        $sourceId = $args['inventorySourceId'] ?? $user->outlet->inventory_source_id ?? null;
        if (! $sourceId) {
            throw new CustomException(trans('pos::app.outlet.stock.no-source-stock'));
        }

        if ($replay = $this->idempotencyHit($user->id, $args['idempotencyKey'] ?? null)) {
            return $replay;
        }

        return DB::transaction(function () use ($user, $args, $sourceId) {
            $row = DB::table('product_inventories')
                ->where('product_id', $args['productId'])
                ->where('inventory_source_id', $sourceId)
                ->lockForUpdate()
                ->first();

            $qtyBefore = (float) ($row->qty ?? 0);
            $qtyAfter  = $qtyBefore + (float) $args['quantityDelta'];

            if ($qtyAfter < 0) {
                throw new CustomException(trans('pos::app.outlet.stock.over-deduct', ['qty' => $qtyBefore]));
            }

            if ($row) {
                DB::table('product_inventories')
                    ->where('id', $row->id)
                    ->update(['qty' => $qtyAfter]);
            } else {
                DB::table('product_inventories')->insert([
                    'product_id'          => $args['productId'],
                    'inventory_source_id' => $sourceId,
                    'qty'                 => $qtyAfter,
                    'vendor_id'           => 0,
                ]);
            }

            // Trigger Bagisto's index refresh so the cart sees the new qty.
            $product = \Webkul\Product\Models\Product::find($args['productId']);
            if ($product) {
                Event::dispatch('catalog.product.update.after', $product);
            }

            $logId = $this->log($user->id, [
                'product_id'          => $args['productId'],
                'inventory_source_id' => $sourceId,
                'action'              => 'adjust',
                'qty_before'          => $qtyBefore,
                'qty_delta'           => (float) $args['quantityDelta'],
                'qty_after'           => $qtyAfter,
                'reason'              => $args['reason'],
                'note'                => $args['note'] ?? null,
                'idempotency_key'     => $args['idempotencyKey'] ?? null,
            ]);

            return [
                'success'     => true,
                'message'     => trans('pos::app.outlet.stock.adjusted'),
                'qtyBefore'   => $qtyBefore,
                'qtyAfter'    => $qtyAfter,
                'auditRowId'  => $logId,
            ];
        });
    }

    public function transfer($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $validator = Validator::make($args, [
            'productId'              => 'required|integer',
            'fromInventorySourceId'  => 'required|integer',
            'toInventorySourceId'    => 'required|integer|different:fromInventorySourceId',
            'quantity'               => 'required|numeric|min:0.0001',
            'note'                   => 'nullable|string|max:500',
            'idempotencyKey'         => 'nullable|string|min:16|max:64|regex:/^[A-Za-z0-9_-]+$/',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $user = $context->user();

        if ($replay = $this->idempotencyHit($user->id, $args['idempotencyKey'] ?? null)) {
            return $replay;
        }

        // Confirm both inventory sources exist (avoids silent zero inserts).
        $fromExists = DB::table('inventory_sources')->where('id', $args['fromInventorySourceId'])->exists();
        $toExists   = DB::table('inventory_sources')->where('id', $args['toInventorySourceId'])->exists();
        if (! $fromExists) throw new CustomException(trans('pos::app.outlet.stock.no-source-stock'));
        if (! $toExists)   throw new CustomException(trans('pos::app.outlet.stock.no-dest-source'));

        return DB::transaction(function () use ($user, $args) {
            $qty = (float) $args['quantity'];

            // Deduct from source
            $fromRow = DB::table('product_inventories')
                ->where('product_id', $args['productId'])
                ->where('inventory_source_id', $args['fromInventorySourceId'])
                ->lockForUpdate()
                ->first();

            $fromBefore = (float) ($fromRow->qty ?? 0);
            if ($qty > $fromBefore) {
                throw new CustomException(trans('pos::app.outlet.stock.over-deduct', ['qty' => $fromBefore]));
            }
            $fromAfter = $fromBefore - $qty;

            DB::table('product_inventories')
                ->where('id', $fromRow->id)
                ->update(['qty' => $fromAfter]);

            // Add to destination (create row if absent)
            $toRow = DB::table('product_inventories')
                ->where('product_id', $args['productId'])
                ->where('inventory_source_id', $args['toInventorySourceId'])
                ->lockForUpdate()
                ->first();

            $toBefore = (float) ($toRow->qty ?? 0);
            $toAfter  = $toBefore + $qty;

            if ($toRow) {
                DB::table('product_inventories')
                    ->where('id', $toRow->id)
                    ->update(['qty' => $toAfter]);
            } else {
                DB::table('product_inventories')->insert([
                    'product_id'          => $args['productId'],
                    'inventory_source_id' => $args['toInventorySourceId'],
                    'qty'                 => $toAfter,
                    'vendor_id'           => 0,
                ]);
            }

            $product = \Webkul\Product\Models\Product::find($args['productId']);
            if ($product) {
                Event::dispatch('catalog.product.update.after', $product);
            }

            $outId = $this->log($user->id, [
                'product_id'             => $args['productId'],
                'inventory_source_id'    => $args['fromInventorySourceId'],
                'action'                 => 'transfer_out',
                'qty_before'             => $fromBefore,
                'qty_delta'              => -$qty,
                'qty_after'              => $fromAfter,
                'note'                   => $args['note'] ?? null,
                'to_inventory_source_id' => $args['toInventorySourceId'],
                'idempotency_key'        => $args['idempotencyKey'] ?? null,
            ]);

            $this->log($user->id, [
                'product_id'             => $args['productId'],
                'inventory_source_id'    => $args['toInventorySourceId'],
                'action'                 => 'transfer_in',
                'qty_before'             => $toBefore,
                'qty_delta'              => $qty,
                'qty_after'               => $toAfter,
                'note'                   => $args['note'] ?? null,
                // counterpart pointer back to outId for trace
                'to_inventory_source_id' => $args['fromInventorySourceId'],
                'idempotency_key'        => null,
            ]);

            return [
                'success'           => true,
                'message'           => trans('pos::app.outlet.stock.transferred'),
                'fromQtyAfter'      => $fromAfter,
                'toQtyAfter'        => $toAfter,
                'auditRowId'        => $outId,
            ];
        });
    }

    protected function log(int $userId, array $row): int
    {
        $row['pos_user_id'] = $userId;
        $row['created_at']  = now();
        $row['updated_at']  = now();
        return DB::table('pos_stock_movement_log')->insertGetId($row);
    }

    protected function idempotencyHit(int $userId, ?string $key): ?array
    {
        if (! $key) return null;
        $existing = DB::table('pos_stock_movement_log')
            ->where('pos_user_id', $userId)
            ->where('idempotency_key', $key)
            ->first();
        if (! $existing) return null;
        $msg = $existing->action === 'transfer_out'
            ? trans('pos::app.outlet.stock.transferred')
            : trans('pos::app.outlet.stock.adjusted');
        return [
            'success'     => true,
            'message'     => $msg,
            'qtyBefore'   => (float) $existing->qty_before,
            'qtyAfter'    => (float) $existing->qty_after,
            'auditRowId'  => (int) $existing->id,
        ];
    }

    // ── Global out-of-stock-sale flag ────────────────────────────────────
    // Backed by the standard Bagisto config key
    // catalog.inventory.stock_options.back_orders, which Simple::haveSufficientQuantity()
    // reads to decide whether a zero-stock product is still saleable.
    private const BACK_ORDERS_CODE = 'catalog.inventory.stock_options.back_orders';

    public function getBackOrders($root, array $args, GraphQLContext $context): array
    {
        return [
            'success' => true,
            'enabled' => $this->readBackOrders(),
        ];
    }

    public function setBackOrders($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $enabled = (bool) ($args['enabled'] ?? false);

        // Global (non-channel/locale) setting → channel_code/locale_code null.
        // SystemConfig::getCoreConfig() reads core_config straight from the
        // repository on every call (no cache layer), so this takes effect on
        // the next saleability check immediately — no cache bust needed.
        DB::table('core_config')->updateOrInsert(
            ['code' => self::BACK_ORDERS_CODE, 'channel_code' => null, 'locale_code' => null],
            ['value' => $enabled ? '1' : '0', 'updated_at' => now()],
        );

        return [
            'success' => true,
            'enabled' => $enabled,
            'message' => trans('pos::app.outlet.stock.back-orders-saved'),
        ];
    }

    private function readBackOrders(): bool
    {
        // Prefer the global row; fall back to core() helper (which honors the
        // channel fallback chain) when the row isn't present.
        $row = DB::table('core_config')
            ->where('code', self::BACK_ORDERS_CODE)
            ->whereNull('channel_code')
            ->whereNull('locale_code')
            ->value('value');

        if ($row !== null) {
            return (bool) (int) $row;
        }

        return (bool) core()->getConfigData(self::BACK_ORDERS_CODE);
    }
}
