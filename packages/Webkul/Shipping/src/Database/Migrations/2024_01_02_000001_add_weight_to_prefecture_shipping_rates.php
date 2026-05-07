<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prefecture_shipping_rates', function (Blueprint $table) {
            $table->enum('rate_type', ['qty', 'weight'])->default('qty')->after('prefecture_code');
            $table->decimal('weight_from', 8, 3)->nullable()->after('qty_to');
            $table->decimal('weight_to', 8, 3)->nullable()->after('weight_from');
        });

        Schema::create('weight_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->string('prefecture_code', 10)->nullable();
            $table->decimal('weight_from', 8, 3);
            $table->decimal('weight_to', 8, 3);
            $table->decimal('shipping_price', 10, 2);
            $table->string('label')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['channel_id', 'prefecture_code', 'is_active'], 'wsr_ch_pref_active');
        });
    }

    public function down(): void
    {
        Schema::table('prefecture_shipping_rates', function (Blueprint $table) {
            $table->dropColumn(['rate_type', 'weight_from', 'weight_to']);
        });
        Schema::dropIfExists('weight_shipping_rates');
    }
};
