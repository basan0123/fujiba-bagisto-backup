<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_shipping_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->decimal('fee_amount', 10, 2)->default(600);
            $table->string('label')->default('Standard Shipping');
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['channel_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_shipping_fees');
    }
};
