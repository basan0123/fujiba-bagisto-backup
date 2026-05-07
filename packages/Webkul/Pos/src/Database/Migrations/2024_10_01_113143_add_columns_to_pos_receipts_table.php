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
        Schema::table('pos_receipts', function (Blueprint $table) {
            $table->after('display_outlet_name', function ($table) {
                $table->string('show_order_barcode')->nullable();
                $table->string('show_print_confirmation')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_receipts', function (Blueprint $table) {
            $table->dropColumn('show_order_barcode');
            $table->dropColumn('show_print_confirmation');
        });
    }
};
