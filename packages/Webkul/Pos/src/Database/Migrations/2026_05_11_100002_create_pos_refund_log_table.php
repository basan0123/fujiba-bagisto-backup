<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pos_refund_log')) {
            return;
        }

        Schema::create('pos_refund_log', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('pos_user_id')->index();
            $t->unsignedInteger('order_id')->index();
            $t->unsignedInteger('refund_id')->nullable()->index();
            $t->enum('action', ['refund', 'void']);
            $t->enum('target', ['cash', 'credit', 'original'])->default('original');
            $t->decimal('amount', 12, 4)->default(0);
            $t->string('reason', 60)->nullable();
            $t->string('note', 500)->nullable();
            $t->json('items')->nullable()->comment('partial-refund line items snapshot');
            $t->string('idempotency_key', 64)->nullable();
            $t->timestamps();

            $t->unique(['pos_user_id', 'idempotency_key'], 'uq_prl_user_idem');
            $t->index(['action', 'created_at'], 'idx_prl_action_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_refund_log');
    }
};
