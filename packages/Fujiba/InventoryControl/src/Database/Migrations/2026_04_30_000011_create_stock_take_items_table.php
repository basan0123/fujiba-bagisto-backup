<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_take_items')) {
            return;
        }

        Schema::create('stock_take_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedInteger('product_id');
            $table->string('sku', 191)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('matched_by', 16)->nullable(); // 'barcode' | 'sku'
            $table->integer('system_qty');
            $table->integer('counted_qty');
            $table->integer('difference');
            $table->boolean('applied')->default(false);
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('stock_take_sessions')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->unique(['session_id', 'product_id'], 'sti_session_product_unique');
            $table->index('session_id', 'sti_session_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_items');
    }
};
