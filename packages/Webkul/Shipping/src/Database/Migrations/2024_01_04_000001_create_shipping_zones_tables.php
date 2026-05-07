<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Zones: named groups of prefectures sharing the same shipping rates
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->nullable()->index();
            $table->string('zone_name');          // e.g. "関東・中部・東北南部"
            $table->string('zone_code')->nullable(); // optional short code
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['channel_id', 'zone_name']);
        });

        // Which prefectures belong to each zone
        Schema::create('shipping_zone_prefectures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->string('prefecture_code', 10);  // e.g. '13' (Tokyo)
            $table->string('prefecture_name_ja', 50);
            $table->timestamps();

            $table->unique(['zone_id', 'prefecture_code']);
        });

        // Rates per zone per qty range
        Schema::create('shipping_zone_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->unsignedInteger('qty_from');
            $table->unsignedInteger('qty_to');
            $table->decimal('shipping_price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['zone_id', 'qty_from', 'qty_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zone_rates');
        Schema::dropIfExists('shipping_zone_prefectures');
        Schema::dropIfExists('shipping_zones');
    }
};
