<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pos_stock_movement_log')) {
            return;
        }

        Schema::create('pos_stock_movement_log', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('pos_user_id')->index();
            $t->unsignedInteger('product_id')->index();
            $t->unsignedInteger('inventory_source_id')->index();
            $t->enum('action', ['adjust', 'waste', 'transfer_out', 'transfer_in']);
            $t->decimal('qty_before', 14, 4)->default(0);
            $t->decimal('qty_delta',  14, 4);
            $t->decimal('qty_after',  14, 4)->default(0);
            $t->string('reason', 60)->nullable();
            $t->string('note',   500)->nullable();
            $t->unsignedInteger('to_inventory_source_id')->nullable()
                ->comment('present on transfer_out → counterpart row');
            $t->string('idempotency_key', 64)->nullable();
            $t->timestamps();

            $t->unique(['pos_user_id', 'idempotency_key'], 'uq_psm_user_idem');
            $t->index(['action', 'created_at'], 'idx_psm_action_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_stock_movement_log');
    }
};
