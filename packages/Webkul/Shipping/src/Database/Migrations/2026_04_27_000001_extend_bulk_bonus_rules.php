<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend bulk_bonus_rules so admins can express:
 *   - tiered "buy N → free M" rules (existing)
 *   - tiered "buy N → X% off entire order" rules (new)
 *   - per-product price floor (only items at/above this price qualify)
 *   - "this tier replaces lower tiers" flag (no stacking when set)
 *   - "block manual coupons while active" flag
 *
 * Each tenant's bulk_bonus_rules table needs these columns. Idempotent so
 * re-running on a tenant that already has them is safe (works around the
 * stancl/tenancy migrate flow that re-runs on first request).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bulk_bonus_rules')) {
            return;
        }

        Schema::table('bulk_bonus_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_bonus_rules', 'discount_type')) {
                $table->string('discount_type', 32)
                    ->default('free_items')
                    ->after('free_qty');
            }
            if (! Schema::hasColumn('bulk_bonus_rules', 'percent_off')) {
                $table->decimal('percent_off', 5, 2)
                    ->nullable()
                    ->after('discount_type');
            }
            if (! Schema::hasColumn('bulk_bonus_rules', 'min_product_price')) {
                $table->decimal('min_product_price', 12, 4)
                    ->nullable()
                    ->after('percent_off');
            }
            if (! Schema::hasColumn('bulk_bonus_rules', 'replaces_lower_tiers')) {
                $table->boolean('replaces_lower_tiers')
                    ->default(false)
                    ->after('stackable');
            }
            if (! Schema::hasColumn('bulk_bonus_rules', 'block_coupons')) {
                $table->boolean('block_coupons')
                    ->default(false)
                    ->after('replaces_lower_tiers');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bulk_bonus_rules')) {
            return;
        }

        Schema::table('bulk_bonus_rules', function (Blueprint $table) {
            foreach (['discount_type', 'percent_off', 'min_product_price', 'replaces_lower_tiers', 'block_coupons'] as $col) {
                if (Schema::hasColumn('bulk_bonus_rules', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
