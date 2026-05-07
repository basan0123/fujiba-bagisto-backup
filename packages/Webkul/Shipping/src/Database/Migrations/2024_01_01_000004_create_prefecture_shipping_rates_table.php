<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prefecture_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->string('prefecture_code', 10);
            $table->string('prefecture_name_ja');
            $table->string('prefecture_name_en');
            $table->unsignedInteger('qty_from');
            $table->unsignedInteger('qty_to');
            $table->decimal('shipping_price', 10, 2);
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['channel_id', 'prefecture_code', 'is_active'], 'psr_channel_code_active');
            $table->index(['channel_id', 'prefecture_code', 'qty_from', 'qty_to'], 'psr_channel_code_qty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prefecture_shipping_rates');
    }
};
