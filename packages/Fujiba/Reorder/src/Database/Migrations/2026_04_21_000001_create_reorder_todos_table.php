<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reorder_todos')) {
            Schema::create('reorder_todos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('channel_id');
                $table->integer('stock')->default(0);
                $table->integer('sale_count')->default(0);
                $table->integer('suggested_qty')->default(0);
                $table->integer('wish_qty')->default(0);
                $table->integer('arrived_qty')->default(0);
                $table->decimal('cost', 12, 4)->default(0);
                $table->string('status', 32)->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['channel_id', 'status']);
                $table->index('product_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_todos');
    }
};
