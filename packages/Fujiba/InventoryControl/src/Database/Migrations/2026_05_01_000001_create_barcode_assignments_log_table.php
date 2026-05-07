<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('barcode_assignments_log')) {
            return;
        }

        Schema::create('barcode_assignments_log', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('product_id')->nullable();
            $table->string('barcode_old', 500)->nullable();
            $table->string('barcode_new', 500)->nullable();
            $table->string('action', 16);

            $table->unsignedInteger('user_id')->nullable();
            $table->string('user_type', 16)->nullable();

            $table->string('session_id', 64)->nullable();
            $table->boolean('reverted')->default(false);

            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');

            $table->index(['user_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_assignments_log');
    }
};
