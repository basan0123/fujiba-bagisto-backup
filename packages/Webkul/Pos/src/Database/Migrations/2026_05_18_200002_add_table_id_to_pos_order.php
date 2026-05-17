<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a nullable pos_table_id column to pos_order so a dine-in
 * order can reference the table it was placed against. Without an
 * FK constraint — table deletion shouldn't cascade-nuke historical
 * orders, and dropping a constraint later is harder than not adding
 * it.
 *
 * Idempotent: skips if the column already exists. Index added for
 * the "all orders at table N" lookup.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('pos_order')) {
            return;
        }
        if (Schema::hasColumn('pos_order', 'pos_table_id')) {
            return;
        }
        Schema::table('pos_order', function (Blueprint $t) {
            $t->unsignedBigInteger('pos_table_id')->nullable()->after('outlet_id');
            $t->index('pos_table_id', 'idx_pos_order_table');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pos_order')) {
            return;
        }
        if (! Schema::hasColumn('pos_order', 'pos_table_id')) {
            return;
        }
        Schema::table('pos_order', function (Blueprint $t) {
            $t->dropIndex('idx_pos_order_table');
            $t->dropColumn('pos_table_id');
        });
    }
};
