<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_take_sessions')) {
            return;
        }

        Schema::create('stock_take_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 150);
            $table->unsignedInteger('inventory_source_id');
            $table->enum('status', ['open', 'applied', 'cancelled'])->default('open');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('applied_by')->nullable();
            $table->dateTime('applied_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->onDelete('restrict');
            $table->index(['status', 'created_at'], 'sts_status_created_idx');
            $table->index('inventory_source_id', 'sts_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_sessions');
    }
};
