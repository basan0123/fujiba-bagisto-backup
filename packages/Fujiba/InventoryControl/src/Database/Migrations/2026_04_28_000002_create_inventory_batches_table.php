<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_batches')) {
            return;
        }

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('channel_id')->nullable();
            $table->unsignedInteger('inventory_source_id')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->integer('quantity_received');
            $table->integer('quantity_remaining');
            $table->decimal('cost_price', 12, 4)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['active', 'depleted', 'expired', 'removed'])->default('active');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->onDelete('set null');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('set null');

            $table->index(['product_id', 'expiry_date']);
            $table->index(['expiry_date', 'status']);
            $table->index(['status', 'quantity_remaining']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
