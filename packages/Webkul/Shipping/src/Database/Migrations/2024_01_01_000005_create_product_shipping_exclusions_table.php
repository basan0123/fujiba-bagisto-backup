<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_shipping_exclusions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->enum('exclude_type', ['free_threshold', 'cool_fee', 'all'])->default('free_threshold');
            $table->string('reason')->nullable();
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['channel_id', 'product_id', 'is_active'], 'pse_ch_prod_active');
            $table->index(['channel_id', 'category_id', 'is_active'], 'pse_ch_cat_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_shipping_exclusions');
    }
};
