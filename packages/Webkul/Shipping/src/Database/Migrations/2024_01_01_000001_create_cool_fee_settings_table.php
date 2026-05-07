<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cool_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->decimal('cool_box_fee', 10, 2)->default(350);
            $table->decimal('frozen_box_fee', 10, 2)->default(500);
            $table->boolean('cool_enabled')->default(true);
            $table->boolean('frozen_enabled')->default(true);
            $table->boolean('fee_per_item')->default(false);
            $table->decimal('max_fee_cap', 10, 2)->nullable();
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['channel_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cool_fee_settings');
    }
};
