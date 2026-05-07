<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->enum('rule_type', [
                'cool_fee',
                'free_threshold',
                'fixed_fee',
                'prefecture_qty',
                'bulk_bonus',
                'product_exclusion',
            ]);
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['channel_id', 'is_active', 'priority'], 'sr_ch_active_priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rules');
    }
};
