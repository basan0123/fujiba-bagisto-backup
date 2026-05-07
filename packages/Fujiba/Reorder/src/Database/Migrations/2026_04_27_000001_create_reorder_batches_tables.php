<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reorder_batches')) {
            Schema::create('reorder_batches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('channel_id');
                $table->string('status', 32)->default('pending');
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();

                $table->index(['channel_id', 'status']);
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('reorder_items')) {
            Schema::create('reorder_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reorder_batch_id');
                $table->unsignedBigInteger('product_id');
                $table->string('name_snapshot', 255)->nullable();
                $table->integer('qty')->default(0);
                $table->integer('received_qty')->default(0);
                $table->decimal('cost', 12, 4)->default(0);
                $table->timestamps();

                $table->index('reorder_batch_id');
                $table->index('product_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_items');
        Schema::dropIfExists('reorder_batches');
    }
};
