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
            if (! Schema::hasColumn('stock_take_sessions', 'advanced_mode')) {
                $table->boolean('advanced_mode')->default(false)->after('status');
            }
            if (! Schema::hasColumn('stock_take_sessions', 'baseline_status')) {
                $table->enum('baseline_status', ['none', 'pending', 'ready'])->default('none')->after('advanced_mode');
            }
            if (! Schema::hasColumn('stock_take_sessions', 'baseline_count')) {
                $table->unsignedInteger('baseline_count')->default(0)->after('baseline_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_take_sessions')) {
            return;
        }

        Schema::table('stock_take_sessions', function (Blueprint $table) {
            foreach (['advanced_mode', 'baseline_status', 'baseline_count'] as $col) {
                if (Schema::hasColumn('stock_take_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
