<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_stock_audits')) {
            return;
        }

        Schema::table('product_stock_audits', function (Blueprint $table) {
            if (! Schema::hasColumn('product_stock_audits', 'batch_id')) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('inventory_source_id');
                $table->index('batch_id', 'psa_batch_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_stock_audits')) {
            return;
        }

        Schema::table('product_stock_audits', function (Blueprint $table) {
            if (Schema::hasColumn('product_stock_audits', 'batch_id')) {
                $table->dropIndex('psa_batch_idx');
                $table->dropColumn('batch_id');
            }
        });
    }
};
