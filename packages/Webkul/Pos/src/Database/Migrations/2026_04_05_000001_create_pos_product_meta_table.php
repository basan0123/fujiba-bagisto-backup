<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_product_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id')->unique();
            $table->decimal('buy_price', 15, 4)->nullable()->comment('Purchase cost in original currency');
            $table->string('currency', 10)->nullable()->default('JPY')->comment('JPY or MMK');
            $table->decimal('exchange_rate', 15, 4)->nullable()->default(1)->comment('1 JPY = X MMK');
            $table->decimal('weight_kg', 8, 3)->nullable()->comment('Weight for cargo cost calc');
            $table->unsignedInteger('sold_count')->nullable()->default(0);
            $table->text('cost_notes')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_product_meta');
    }
};
