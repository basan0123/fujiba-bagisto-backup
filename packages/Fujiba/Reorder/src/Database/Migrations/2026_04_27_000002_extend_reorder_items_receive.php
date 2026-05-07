<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add receiving-flow columns to reorder_items and rename received_qty
 * to to_stock_qty. Each ALTER is guarded so tenants:migrate is re-runnable.
 *
 * Note: doctrine/dbal isn't always present in this project, so we use
 * raw ALTER for the rename instead of $table->renameColumn().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reorder_items')) {
            return;
        }

        Schema::table('reorder_items', function (Blueprint $table) {
            if (! Schema::hasColumn('reorder_items', 'delivered_qty')) {
                $table->integer('delivered_qty')->default(0)->after('qty');
            }
            if (! Schema::hasColumn('reorder_items', 'damage_qty')) {
                $table->integer('damage_qty')->default(0)->after('delivered_qty');
            }
            if (! Schema::hasColumn('reorder_items', 'loss_qty')) {
                $table->integer('loss_qty')->default(0)->after('damage_qty');
            }
            if (! Schema::hasColumn('reorder_items', 'cut_off_qty')) {
                $table->integer('cut_off_qty')->default(0)->after('loss_qty');
            }
        });

        // Rename received_qty -> to_stock_qty if needed.
        if (Schema::hasColumn('reorder_items', 'received_qty')
            && ! Schema::hasColumn('reorder_items', 'to_stock_qty')) {
            \DB::statement('ALTER TABLE reorder_items CHANGE received_qty to_stock_qty INT NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('reorder_items')) {
            return;
        }

        if (Schema::hasColumn('reorder_items', 'to_stock_qty')
            && ! Schema::hasColumn('reorder_items', 'received_qty')) {
            \DB::statement('ALTER TABLE reorder_items CHANGE to_stock_qty received_qty INT NOT NULL DEFAULT 0');
        }

        Schema::table('reorder_items', function (Blueprint $table) {
            foreach (['cut_off_qty', 'loss_qty', 'damage_qty', 'delivered_qty'] as $col) {
                if (Schema::hasColumn('reorder_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
