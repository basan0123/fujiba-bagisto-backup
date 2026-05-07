<?php

namespace Webkul\Pos\Listeners;

use Illuminate\Support\Facades\DB;

/**
 * Ensures every catalog product has a row in pos_product_barcode so the POS
 * scan resolver (`barcode = ? OR scan_value = ?`) can find it the moment the
 * product exists.
 *
 * Triggered by `catalog.product.create.after` and `catalog.product.update.after`.
 *
 * Lightweight by design — DB upsert only. The PNG image is generated lazily
 * on first print/view via ProductBarcodeRepository::getBarcode(). Doing PNG
 * I/O on every product save would slow down imports/syncs noticeably.
 */
class AutoRegisterProductBarcode
{
    public function handle($product): void
    {
        if (! $product || empty($product->id)) {
            return;
        }

        // Variants resolve through their parent — we only register the parent.
        if (! empty($product->parent_id)) {
            return;
        }

        try {
            $sku = trim((string) ($product->sku ?? ''));
            if ($sku === '') {
                return;
            }

            $generateWith = (string) (core()->getConfigData('pos.settings.barcode.generate_with') ?: 'sku');

            // Pick a scan value the resolver can look up. We follow the global
            // config so manual edits via the Pos > Barcode admin page still win.
            $scanValue = match ($generateWith) {
                'id'   => (string) $product->id,
                'gtin' => $sku, // no GTIN known yet — fall back to SKU
                default => $sku,
            };

            $now = now();
            $existing = DB::table('pos_product_barcode')
                ->where('product_id', $product->id)
                ->first();

            if (! $existing) {
                DB::table('pos_product_barcode')->insert([
                    'product_id' => $product->id,
                    'barcode'    => $scanValue,
                    'scan_value' => $scanValue,
                    'scan_type'  => $generateWith === 'id' ? 'product_id' : 'sku',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                return;
            }

            // Row exists — only fill blanks. Never overwrite manually-set
            // GTIN, scan_type, or barcode that the user assigned via
            // /admin/catalog/barcode-assign.
            $patch = [];
            if (empty($existing->barcode))    { $patch['barcode']    = $scanValue; }
            if (empty($existing->scan_value)) { $patch['scan_value'] = $scanValue; }
            if (empty($existing->scan_type))  { $patch['scan_type']  = $generateWith === 'id' ? 'product_id' : 'sku'; }

            if ($patch) {
                $patch['updated_at'] = $now;
                DB::table('pos_product_barcode')
                    ->where('product_id', $product->id)
                    ->update($patch);
            }
        } catch (\Throwable $e) {
            // Never break a product save because of a barcode bookkeeping hiccup.
            report($e);
        }
    }
}
