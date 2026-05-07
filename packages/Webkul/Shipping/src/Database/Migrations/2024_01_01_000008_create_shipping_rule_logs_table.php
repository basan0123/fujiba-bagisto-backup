<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rule_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('cart_id')->index();
            $table->json('rules_applied');
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('cool_fee_total', 10, 2)->default(0);
            $table->boolean('free_shipping_applied')->default(false);
            $table->json('bulk_bonus_items')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
            $table->index(['channel_id', 'cart_id'], 'srl_ch_cart');
            $table->index(['channel_id', 'order_id'], 'srl_ch_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rule_logs');
    }
};
