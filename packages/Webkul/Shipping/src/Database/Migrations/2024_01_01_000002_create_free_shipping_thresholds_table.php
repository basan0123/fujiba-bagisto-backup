<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('free_shipping_thresholds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->decimal('threshold_amount', 10, 2);
            $table->string('label');
            $table->boolean('apply_to_cool')->default(false);
            $table->boolean('apply_to_frozen')->default(false);
            $table->json('excluded_categories')->nullable();
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['channel_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('free_shipping_thresholds');
    }
};
