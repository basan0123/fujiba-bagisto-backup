<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_bonus_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedInteger('buy_qty');
            $table->unsignedInteger('free_qty');
            $table->unsignedInteger('max_free_per_order')->nullable();
            $table->boolean('stackable')->default(true);
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['channel_id', 'product_id', 'is_active', 'priority'], 'bbr_ch_prod_active');
            $table->index(['channel_id', 'product_id', 'buy_qty'], 'bbr_ch_prod_qty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_bonus_rules');
    }
};
