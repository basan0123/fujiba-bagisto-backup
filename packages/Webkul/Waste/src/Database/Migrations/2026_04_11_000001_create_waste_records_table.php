<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_records', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('product_name', 255);
            $table->string('product_sku', 100)->nullable();

            $table->unsignedInteger('channel_id')->nullable()->index();

            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('amount', 12, 4)->default(0);   // total waste value

            $table->string('reason', 100)->default('Expired');
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable(); // admin user id

            $table->timestamps();                                 // created_at = waste date
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_records');
    }
};
