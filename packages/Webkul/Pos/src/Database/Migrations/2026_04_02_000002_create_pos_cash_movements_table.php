<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_cash_movements')) {
            return;
        }

        Schema::create('pos_cash_movements', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('outlet_id')->nullable();
            $table->foreign('outlet_id')->references('id')->on('pos_outlets')->onDelete('set null');

            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('pos_users')->onDelete('set null');

            $table->unsignedInteger('drawer_id')->nullable();
            $table->foreign('drawer_id')->references('id')->on('pos_user_drawer')->onDelete('set null');

            // income (入金) | expense (出金)
            $table->string('type', 20)->default('expense');

            // rent, electricity, gas, salary, supplies, misc, sales_other
            $table->string('category', 50)->default('misc');

            $table->string('description', 255)->nullable();
            $table->decimal('amount', 12, 4)->default(0);
            $table->string('currency', 10)->nullable();
            $table->date('movement_date');
            $table->string('reference_number', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_cash_movements');
    }
};
