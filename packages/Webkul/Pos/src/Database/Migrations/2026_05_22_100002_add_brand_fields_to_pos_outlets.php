<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brand fields the POS BrandUploadScreen edits. Store name already lives on
 * pos_outlets.name and the logo on pos_receipts.logo; this adds the two
 * brand attributes that had no home:
 *
 *   tagline      — short subtitle shown under the store name (e.g.
 *                  「荒川の和装と惣菜」). Nullable.
 *   brand_color  — palette key/hex for the brand accent (e.g. 'teal' or
 *                  '#00B8D9'). Nullable; the app falls back to the theme
 *                  primary when unset.
 *
 * Both guarded so tenants:migrate re-runs cleanly
 * (project memory: feedback_migration_idempotency).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_outlets', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_outlets', 'tagline')) {
                $table->string('tagline', 255)->nullable()->after('name');
            }

            if (! Schema::hasColumn('pos_outlets', 'brand_color')) {
                $table->string('brand_color', 32)->nullable()->after('tagline');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_outlets', function (Blueprint $table) {
            foreach (['tagline', 'brand_color'] as $col) {
                if (Schema::hasColumn('pos_outlets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
