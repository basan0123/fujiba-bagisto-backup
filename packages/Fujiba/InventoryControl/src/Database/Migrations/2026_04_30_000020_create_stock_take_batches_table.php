<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_take_batches')) {
            return;
        }

        Schema::create('stock_take_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('item_id')->nullable();           // FK to stock_take_items (set when product first scanned)
            $table->unsignedBigInteger('inventory_batch_id');            // FK to inventory_batches
            $table->unsignedInteger('product_id');
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('baseline_qty')->nullable();                 // frozen at session create OR first scan
            $table->integer('counted_qty')->nullable();
            $table->integer('difference')->nullable();
            $table->unsignedInteger('scanned_by')->nullable();
            $table->dateTime('scanned_at')->nullable();
            $table->boolean('applied')->default(false);
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('stock_take_sessions')->onDelete('cascade');
            $table->foreign('inventory_batch_id')->references('id')->on('inventory_batches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->unique(['session_id', 'inventory_batch_id'], 'stb_session_batch_unique');
            $table->index(['session_id', 'product_id'], 'stb_session_product_idx');
            $table->index('item_id', 'stb_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_batches');
    }
};
