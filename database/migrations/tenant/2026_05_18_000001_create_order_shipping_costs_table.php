<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_shipping_costs')) {
            return;
        }

        Schema::create('order_shipping_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('tracking_number', 64)->nullable();
            $table->decimal('carrier_cost', 12, 4)->default(0);
            $table->string('carrier_code', 32)->nullable();
            $table->string('billing_month', 7)->nullable();
            $table->enum('source', ['csv_import', 'manual'])->default('manual');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique('order_id');
            $table->index(['billing_month', 'carrier_code']);
            $table->index('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipping_costs');
    }
};
