<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pos_customer_credit_log — audit trail for every credit balance
 * adjustment, plus the source of truth for "current balance" via
 * SUM(delta) per customer.
 *
 * Why a log table instead of a balance column on customers:
 *  - Every change is recorded with who/why/when, no information lost
 *  - Idempotency-key dedup is straightforward (unique on user+key)
 *  - Reads are still fast: a small index on customer_id and a SUM
 *    query, or read the most recent row's balance_after as a hint
 *
 * delta is signed: positive for adds, negative for deducts.
 * balance_after is denormalized so audit reads don't need to recompute.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_customer_credit_log')) {
            Schema::create('pos_customer_credit_log', function (Blueprint $table) {
                $table->increments('id');

                $table->integer('customer_id')->unsigned();
                $table->foreign('customer_id')
                    ->references('id')->on('customers')
                    ->onDelete('cascade');

                $table->integer('pos_user_id')->unsigned();
                $table->foreign('pos_user_id')
                    ->references('id')->on('pos_users')
                    ->onDelete('cascade');

                $table->decimal('delta', 12, 4);
                $table->decimal('balance_after', 12, 4);

                $table->enum('reason', [
                    'manual_add',
                    'manual_deduct',
                    'order_change',
                    'refund_to_credit',
                    'expiry',
                    'correction',
                ]);

                $table->text('note')->nullable();

                $table->string('idempotency_key', 64)->nullable();

                $table->timestamps();

                $table->index('customer_id', 'pos_credit_log_customer_idx');
                $table->unique(
                    ['pos_user_id', 'idempotency_key'],
                    'pos_credit_log_user_idem_unique',
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_customer_credit_log');
    }
};
