<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_take_items')) {
            return;
        }

        Schema::table('stock_take_items', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_take_items', 'baseline_qty')) {
                $table->integer('baseline_qty')->nullable()->after('system_qty');
            }
            if (! Schema::hasColumn('stock_take_items', 'scanned_by')) {
                $table->unsignedInteger('scanned_by')->nullable()->after('matched_by');
            }
            if (! Schema::hasColumn('stock_take_items', 'scanned_at')) {
                $table->dateTime('scanned_at')->nullable()->after('scanned_by');
            }
            if (! Schema::hasColumn('stock_take_items', 'parent_product_id')) {
                $table->unsignedInteger('parent_product_id')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('stock_take_items', 'variant_label')) {
                $table->string('variant_label', 255)->nullable()->after('name');
            }
        });

        // Backfill baseline_qty for any existing rows (single tenant typically a
        // brand-new install — but be safe). Use system_qty as the starting point
        // since that's the closest historical snapshot we already captured.
        Schema::getConnection()
            ->table('stock_take_items')
            ->whereNull('baseline_qty')
            ->update(['baseline_qty' => Schema::getConnection()->raw('system_qty')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_take_items')) {
            return;
        }

        Schema::table('stock_take_items', function (Blueprint $table) {
            foreach (['baseline_qty', 'scanned_by', 'scanned_at', 'parent_product_id', 'variant_label'] as $col) {
                if (Schema::hasColumn('stock_take_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
