<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store-profile settings the POS app's StoreProfileScreen edits but had
 * no home for: business hours and the tax display mode. Name / address /
 * phone / T-number (gst_number) already live on pos_outlets.
 *
 * Both columns are guarded so tenants:migrate re-runs cleanly
 * (project memory: feedback_migration_idempotency).
 *
 *   business_hours — JSON map keyed by weekday label, e.g.
 *     {"月": {"open": "10:00", "close": "20:00", "closed": false}, ...}
 *     Nullable; null means "not configured" and the app shows defaults.
 *
 *   tax_mode — 'inclusive' (内税) or 'exclusive' (外税). Default inclusive,
 *     which is the Japanese-retail norm and matches the screen's default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_outlets', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_outlets', 'business_hours')) {
                $table->json('business_hours')->nullable()->after('postcode');
            }

            if (! Schema::hasColumn('pos_outlets', 'tax_mode')) {
                $table->string('tax_mode', 20)->default('inclusive')->after('business_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_outlets', function (Blueprint $table) {
            foreach (['business_hours', 'tax_mode'] as $col) {
                if (Schema::hasColumn('pos_outlets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
