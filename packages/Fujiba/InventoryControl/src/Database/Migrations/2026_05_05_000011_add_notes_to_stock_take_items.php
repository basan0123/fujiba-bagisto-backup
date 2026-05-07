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
            if (! Schema::hasColumn('stock_take_items', 'notes')) {
                $table->string('notes', 500)->nullable()->after('difference');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_take_items')) {
            return;
        }

        Schema::table('stock_take_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_take_items', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
