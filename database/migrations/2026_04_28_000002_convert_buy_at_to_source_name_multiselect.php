<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repurpose attribute id 30 (originally `buy_at` / price) as `source_name`
 * (multiselect of supplier/source names). The old buy_at column never had
 * production data — values added were test only — so dropping any leftover
 * product_attribute_values for it is safe.
 *
 * Idempotent: re-running checks current state before mutating.
 */
return new class extends Migration
{
    public function up(): void
    {
        $attr = DB::table('attributes')->where('id', 30)->first()
             ?? DB::table('attributes')->where('code', 'buy_at')->first()
             ?? DB::table('attributes')->where('code', 'source_name')->first();

        if (! $attr) {
            return; // Nothing to convert.
        }

        if ($attr->code !== 'source_name' || $attr->type !== 'multiselect') {
            DB::table('attributes')->where('id', $attr->id)->update([
                'code'       => 'source_name',
                'admin_name' => 'Source Name',
                'type'       => 'multiselect',
                'updated_at' => now(),
            ]);
        }

        // Drop any pre-existing values written under the old type — the
        // float_value column is meaningless for a multiselect.
        DB::table('product_attribute_values')
            ->where('attribute_id', $attr->id)
            ->whereNotNull('float_value')
            ->delete();
    }

    public function down(): void
    {
        $attr = DB::table('attributes')->where('code', 'source_name')->first();
        if (! $attr) return;

        DB::table('attributes')->where('id', $attr->id)->update([
            'code'       => 'buy_at',
            'admin_name' => 'Buy At',
            'type'       => 'price',
            'updated_at' => now(),
        ]);
    }
};
