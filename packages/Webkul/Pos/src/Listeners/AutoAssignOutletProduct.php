<?php

namespace Webkul\Pos\Listeners;

use Illuminate\Support\Facades\DB;

/**
 * Links every newly created catalog product to the outlet so it shows up in
 * the POS home/catalog (getOutletProducts joins through pos_outlet_product —
 * a product with no row there is invisible to the POS even though it appears
 * on the storefront).
 *
 * Triggered by `catalog.product.create.after`.
 *
 * SINGLE-OUTLET TENANTS ONLY. Multi-outlet tenants (e.g. a Kimono store + an
 * MMFOOD store under one tenant) deliberately curate which products each
 * outlet sells; auto-linking a new product to every outlet would mix the
 * catalogs (food appearing in the kimono store). When the tenant has more
 * than one outlet we do nothing and leave the assignment to the admin / POS
 * "add product" flow (ProductMutation::ensureOutletAssignment), which links
 * to the cashier's own outlet.
 *
 * Why a listener and not the existing ProductMutation path: products created
 * through the regular Bagisto admin or a bulk import never go through the POS
 * mutation, so they were never linked. That gap left single-outlet POS
 * catalogs showing only the handful of products that happened to be created
 * via POS (the "iPad home only shows some products" bug, 2026-05-24).
 *
 * Lightweight by design — DB insert only, idempotent via existence check.
 */
class AutoAssignOutletProduct
{
    public function handle($product): void
    {
        if (! $product || empty($product->id)) {
            return;
        }

        // Variants are resolved through their parent — only the parent gets a
        // pos_outlet_product row (HomeQuery filters on products.parent_id IS NULL).
        if (! empty($product->parent_id)) {
            return;
        }

        try {
            $outletIds = DB::table('pos_outlets')->pluck('id');

            // Curated multi-outlet tenant — leave assignment to admin/POS.
            if ($outletIds->count() !== 1) {
                return;
            }

            $outletId = (int) $outletIds->first();

            $exists = DB::table('pos_outlet_product')
                ->where('product_id', $product->id)
                ->where('outlet_id', $outletId)
                ->exists();

            if ($exists) {
                return;
            }

            $now = now();
            DB::table('pos_outlet_product')->insert([
                'outlet_id'  => $outletId,
                'product_id' => $product->id,
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            // Never break a product save because of POS bookkeeping.
            report($e);
        }
    }
}
