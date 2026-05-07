<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_stock_audits')) {
            Schema::create('product_stock_audits', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('inventory_source_id');
                $table->integer('qty_before');
                $table->integer('qty_after');
                $table->integer('delta');
                $table->unsignedInteger('admin_id')->nullable();
                $table->string('source_action', 64)->nullable();
                $table->text('remark')->nullable();
                $table->unsignedInteger('remark_updated_by')->nullable();
                $table->dateTime('remark_updated_at')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'created_at'], 'psa_product_created_idx');
                $table->index('inventory_source_id', 'psa_source_idx');
                $table->index('admin_id', 'psa_admin_idx');
                $table->index('created_at', 'psa_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_audits');
    }
};
