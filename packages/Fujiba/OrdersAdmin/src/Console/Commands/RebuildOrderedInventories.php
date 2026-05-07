<?php

namespace Fujiba\OrdersAdmin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Rebuild product_ordered_inventories from the actual unshipped portion
 * of OPEN order_items.
 *
 * The advanced-orders "shipped" dropdown used to flip fujiba_label_printed=1
 * without creating a shipment, so qty_shipped never advanced and stock stayed
 * reserved on those orders forever. This command fixes the historical data
 * by recomputing the reserved counter from scratch and replacing the table.
 *
 * It does NOT touch product_inventories — those are already correct because
 * stock physically left the warehouse when the labels were printed.
 */
class RebuildOrderedInventories extends Command
{
    protected $signature = 'fujiba:rebuild-ordered-inventories
                            {--apply : write changes (default is dry-run)}
                            {--limit=20 : how many diff rows to show in the report}';

    protected $description = 'Recompute product_ordered_inventories from open order_items; clears stale reservations from label-printed orders.';

    public function handle(): int
    {
        $dbName = DB::connection()->getDatabaseName();

        if (Str::startsWith((string) $dbName, ['bagisto_central', 'central_'])) {
            $this->error("Refusing to run on central DB ({$dbName}). Use: php artisan tenants:run fujiba:rebuild-ordered-inventories");
            return self::FAILURE;
        }

        foreach (['orders', 'order_items', 'channels', 'product_ordered_inventories'] as $t) {
            if (! Schema::hasTable($t)) {
                $this->warn("Table {$t} missing on {$dbName} — skipping.");
                return self::SUCCESS;
            }
        }

        $apply = (bool) $this->option('apply');
        $this->info("Tenant DB: {$dbName} ".($apply ? '(WRITE MODE)' : '(dry-run)'));

        // Open = order is in an actively-reserving status AND label not yet
        // printed. `completed`/`closed`/`canceled` orders are done, regardless
        // of whether order_items.qty_shipped was ever incremented (which it
        // wasn't on the legacy label-printed-without-shipment flow). The
        // label_printed flag is the admin's "I shipped this physically"
        // signal — anything with label_printed=1 should not be holding
        // reservations either.
        $hasLabel = Schema::hasColumn('orders', 'fujiba_label_printed');

        $itemsQuery = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereNull('oi.parent_id')
            ->whereIn('o.status', ['pending', 'pending_payment', 'processing', 'fraud'])
            ->whereRaw('(oi.qty_ordered - COALESCE(oi.qty_shipped,0) - COALESCE(oi.qty_canceled,0)) > 0')
            ->selectRaw('oi.product_id, o.channel_id, SUM(oi.qty_ordered - COALESCE(oi.qty_shipped,0) - COALESCE(oi.qty_canceled,0)) as qty')
            ->groupBy('oi.product_id', 'o.channel_id');

        if ($hasLabel) {
            $itemsQuery->where(function ($q) {
                $q->whereNull('o.fujiba_label_printed')
                    ->orWhere('o.fujiba_label_printed', 0);
            });
        }

        $expected = $itemsQuery->get()
            ->mapWithKeys(fn ($r) => [$r->product_id.':'.$r->channel_id => (int) $r->qty]);

        $current = DB::table('product_ordered_inventories')
            ->get(['id', 'product_id', 'channel_id', 'qty'])
            ->mapWithKeys(fn ($r) => [$r->product_id.':'.$r->channel_id => $r]);

        $keys = collect()->merge($expected->keys())->merge($current->keys())->unique();

        $toInsert = [];
        $toUpdate = [];
        $toDelete = [];

        $reservedBefore = (int) DB::table('product_ordered_inventories')->sum('qty');
        $reservedAfter  = 0;

        foreach ($keys as $key) {
            [$productId, $channelId] = array_map('intval', explode(':', $key));
            $want = (int) ($expected[$key] ?? 0);
            $have = $current[$key] ?? null;
            $reservedAfter += $want;

            if ($want === 0) {
                if ($have) {
                    $toDelete[] = $have->id;
                }
                continue;
            }

            if (! $have) {
                $toInsert[] = ['product_id' => $productId, 'channel_id' => $channelId, 'qty' => $want];
                continue;
            }

            if ((int) $have->qty !== $want) {
                $toUpdate[] = ['id' => $have->id, 'qty' => $want, 'before' => (int) $have->qty];
            }
        }

        $this->table(['metric', 'value'], [
            ['rows in product_ordered_inventories now', $current->count()],
            ['reserved qty total now',                  $reservedBefore],
            ['rows that should exist (post-rebuild)',   $expected->reject(fn ($v) => $v === 0)->count()],
            ['reserved qty total after rebuild',        $reservedAfter],
            ['delta',                                   $reservedAfter - $reservedBefore],
            ['rows to insert',                          count($toInsert)],
            ['rows to update',                          count($toUpdate)],
            ['rows to delete',                          count($toDelete)],
        ]);

        $limit = (int) $this->option('limit');
        if (count($toUpdate) && $limit > 0) {
            $this->line('');
            $this->line("Top {$limit} updates (product_id | channel_id | before → after):");
            foreach (array_slice($toUpdate, 0, $limit) as $u) {
                $row = $current->first(fn ($r) => $r->id === $u['id']);
                $this->line(sprintf('  #%d | ch:%d | %d → %d', $row->product_id, $row->channel_id, $u['before'], $u['qty']));
            }
        }

        if (! $apply) {
            $this->line('');
            $this->info('Dry-run only. Re-run with --apply to write.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($toInsert, $toUpdate, $toDelete) {
            if ($toDelete) {
                DB::table('product_ordered_inventories')->whereIn('id', $toDelete)->delete();
            }
            foreach ($toUpdate as $u) {
                DB::table('product_ordered_inventories')->where('id', $u['id'])->update(['qty' => $u['qty']]);
            }
            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table('product_ordered_inventories')->insert($chunk);
            }
        });

        $this->info('product_ordered_inventories rebuilt.');
        return self::SUCCESS;
    }
}
