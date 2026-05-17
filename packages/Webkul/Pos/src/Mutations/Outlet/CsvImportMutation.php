<?php

namespace Webkul\Pos\Mutations\Outlet;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Helpers\RoleGate;

/**
 * Synchronous CSV imports for customers and stock.
 *
 * Design notes:
 *  - csvText comes in as a plain string (the client converts a file
 *    pick to text). No file-upload directive yet.
 *  - We parse with PHP's native str_getcsv() — handles quoted fields
 *    with embedded commas without needing a third-party library.
 *  - Each row gets a per-row try/catch so one bad row doesn't fail
 *    the batch; errors are returned in `errors[]` keyed by row #.
 *  - dryRun=true validates + returns counts without writing rows.
 *  - manager+ gate (matches the rest of the bulk-ish mutations).
 *
 * Customer CSV header:
 *   firstName,lastName,email,phone,city,state,country,postcode,address
 * Required: firstName, lastName, email.
 *
 * Stock CSV header:
 *   sku,quantity
 * Required: sku, quantity (integer ≥ 0). Increments to ABS qty —
 * a recount, not a delta. Stamps an audit row in
 * pos_stock_movement_log via the existing StockMutation API so the
 * StaffActivity screen picks it up automatically.
 */
class CsvImportMutation
{
    /* ----------------- customers ----------------- */
    public function customers($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $parsed = $this->parse($args['csvText'] ?? '');
        $errors = [];
        $ok = 0;
        $skipped = 0;
        $dryRun = (bool) ($args['dryRun'] ?? false);

        foreach ($parsed['rows'] as $idx => $row) {
            $line = $idx + 2; // header is row 1
            try {
                $r = $this->map($parsed['header'], $row);
                $v = Validator::make($r, [
                    'firstName' => 'required|string|max:120',
                    'lastName'  => 'required|string|max:120',
                    'email'     => 'required|email|max:190',
                    'phone'     => 'nullable|string|max:64',
                    'city'      => 'nullable|string|max:120',
                    'state'     => 'nullable|string|max:120',
                    'country'   => 'nullable|string|max:64',
                    'postcode'  => 'nullable|string|max:32',
                    'address'   => 'nullable|string|max:255',
                ]);
                if ($v->fails()) {
                    $errors[] = ['row' => $line, 'message' => $v->errors()->first()];
                    continue;
                }

                if ($dryRun) { $ok++; continue; }

                // Idempotent upsert by email — re-importing the same
                // CSV shouldn't duplicate rows.
                $existing = DB::table('customers')->where('email', $r['email'])->first();
                if ($existing) {
                    DB::table('customers')->where('id', $existing->id)->update([
                        'first_name' => $r['firstName'],
                        'last_name'  => $r['lastName'],
                        'phone'      => $r['phone'] ?? null,
                        'updated_at' => now(),
                    ]);
                    $skipped++; // counted as skipped because no NEW row
                    continue;
                }
                DB::table('customers')->insert([
                    'first_name'    => $r['firstName'],
                    'last_name'     => $r['lastName'],
                    'email'         => $r['email'],
                    'phone'         => $r['phone'] ?? '0000000000',
                    'status'        => 1,
                    'is_verified'   => 1,
                    'is_suspended'  => 0,
                    'customer_group_id' => DB::table('customer_groups')->where('code', 'general')->value('id'),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $ok++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $line, 'message' => $e->getMessage()];
            }
        }

        return [
            'success'      => true,
            'message'      => $dryRun ? 'Dry run complete.' : null,
            'totalRows'    => count($parsed['rows']),
            'successCount' => $ok,
            'failureCount' => count($errors),
            'skippedCount' => $skipped,
            'errors'       => $errors,
        ];
    }

    /* ----------------- stock ----------------- */
    public function stock($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $user = $context->user();
        $outletId = (int) $user->outlet_id;
        $inventorySourceId = (int) ($user->outlet->inventory_source_id ?? 1);

        $parsed = $this->parse($args['csvText'] ?? '');
        $errors = [];
        $ok = 0;
        $skipped = 0;
        $dryRun = (bool) ($args['dryRun'] ?? false);

        foreach ($parsed['rows'] as $idx => $row) {
            $line = $idx + 2;
            try {
                $r = $this->map($parsed['header'], $row);
                $v = Validator::make($r, [
                    'sku'      => 'required|string|max:120',
                    'quantity' => 'required|integer|min:0',
                ]);
                if ($v->fails()) {
                    $errors[] = ['row' => $line, 'message' => $v->errors()->first()];
                    continue;
                }

                $product = DB::table('products')->where('sku', $r['sku'])->first();
                if (! $product) {
                    $errors[] = ['row' => $line, 'message' => "SKU not found: {$r['sku']}"];
                    continue;
                }

                if ($dryRun) { $ok++; continue; }

                $newQty = (int) $r['quantity'];

                $prev = DB::table('product_inventories')
                    ->where('product_id', $product->id)
                    ->where('inventory_source_id', $inventorySourceId)
                    ->first();
                $prevQty = $prev ? (float) $prev->qty : 0.0;

                DB::table('product_inventories')->updateOrInsert(
                    ['product_id' => $product->id, 'inventory_source_id' => $inventorySourceId],
                    ['qty' => $newQty, 'updated_at' => now()],
                );

                // Audit row so this import shows in StockMovements.
                $idem = 'csv-import-' . substr(hash('sha256', $r['sku'] . ':' . now()->timestamp), 0, 32);
                DB::table('pos_stock_movement_log')->insertOrIgnore([
                    'pos_user_id'         => (int) $user->id,
                    'product_id'          => (int) $product->id,
                    'inventory_source_id' => $inventorySourceId,
                    'action'              => 'adjust',
                    'qty_before'          => $prevQty,
                    'qty_delta'           => $newQty - $prevQty,
                    'qty_after'           => $newQty,
                    'reason'              => 'csv_import',
                    'note'                => "CSV import row {$line}",
                    'idempotency_key'     => $idem,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                $ok++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $line, 'message' => $e->getMessage()];
            }
        }

        return [
            'success'      => true,
            'message'      => $dryRun ? 'Dry run complete.' : null,
            'totalRows'    => count($parsed['rows']),
            'successCount' => $ok,
            'failureCount' => count($errors),
            'skippedCount' => $skipped,
            'errors'       => $errors,
        ];
    }

    /* ----------------- helpers ----------------- */

    /** Parse the raw CSV text into header + rows arrays. */
    private function parse(string $csvText): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($csvText));
        if ($text === '') {
            return ['header' => [], 'rows' => []];
        }
        $lines = preg_split('/\n/', $text);
        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn ($h) => trim((string) $h), $header);

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $rows[] = str_getcsv($line);
        }
        return ['header' => $header, 'rows' => $rows];
    }

    /** Map a parsed row's positional values to its header names. */
    private function map(array $header, array $row): array
    {
        $out = [];
        foreach ($header as $i => $name) {
            if ($name === '') continue;
            $val = $row[$i] ?? null;
            $out[$name] = is_string($val) ? trim($val) : $val;
            if ($out[$name] === '') $out[$name] = null;
        }
        return $out;
    }
}
