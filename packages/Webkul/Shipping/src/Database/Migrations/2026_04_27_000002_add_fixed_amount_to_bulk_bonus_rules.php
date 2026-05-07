<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `fixed_amount` to bulk_bonus_rules so admins can express
 * tier-based flat-yen discounts: every full multiple of `buy_qty` taken
 * yields a discount of `fixed_amount`, independent of unit price.
 *
 * Used together with discount_type='fixed_per_tier' (no schema change for
 * discount_type itself — it's already a free-form string column).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bulk_bonus_rules')) {
            return;
        }

        Schema::table('bulk_bonus_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_bonus_rules', 'fixed_amount')) {
                $table->decimal('fixed_amount', 12, 4)
                    ->nullable()
                    ->after('percent_off');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bulk_bonus_rules')) {
            return;
        }

        Schema::table('bulk_bonus_rules', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_bonus_rules', 'fixed_amount')) {
                $table->dropColumn('fixed_amount');
            }
        });
    }
};
