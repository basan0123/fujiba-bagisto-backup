<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('waste_records')) {
            return;
        }

        Schema::table('waste_records', function (Blueprint $table) {
            if (! Schema::hasColumn('waste_records', 'batch_id')) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('product_sku');
                $table->index('batch_id', 'wr_batch_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('waste_records')) {
            return;
        }

        Schema::table('waste_records', function (Blueprint $table) {
            if (Schema::hasColumn('waste_records', 'batch_id')) {
                $table->dropIndex('wr_batch_idx');
                $table->dropColumn('batch_id');
            }
        });
    }
};
