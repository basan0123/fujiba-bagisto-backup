<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_take_sessions')) {
            return;
        }

        Schema::table('stock_take_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_take_sessions', 'channel_id')) {
                $table->unsignedInteger('channel_id')->nullable()->after('inventory_source_id');
                $table->index('channel_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_take_sessions')) {
            return;
        }

        Schema::table('stock_take_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('stock_take_sessions', 'channel_id')) {
                $table->dropColumn('channel_id');
            }
        });
    }
};
