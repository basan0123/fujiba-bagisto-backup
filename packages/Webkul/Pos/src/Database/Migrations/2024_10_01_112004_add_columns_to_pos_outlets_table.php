<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pos_outlets', function (Blueprint $table) {
            $table->after('name', function ($table) {
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('website')->nullable();
                $table->string('customer_care_number')->nullable();
                $table->string('gst_number')->nullable();
                $table->integer('low_stock_qty')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_outlets', function (Blueprint $table) {
            $table->dropColumn('email');
            $table->dropColumn('phone');
            $table->dropColumn('website');
            $table->dropColumn('customer_care_number');
            $table->dropColumn('gst_number');
        });
    }
};
