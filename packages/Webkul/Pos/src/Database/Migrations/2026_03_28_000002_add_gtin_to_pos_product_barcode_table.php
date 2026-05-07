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
        Schema::table('pos_product_barcode', function (Blueprint $table) {
            // The real-world barcode number entered by the admin: EAN-13, UPC-A, GTIN-14, etc.
            // This is what scanners read.  The existing `barcode` column stores the
            // generated image filename (prefix + id/sku); `gtin` stores the scan value.
            $table->string('gtin', 100)->nullable()->after('barcode_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_product_barcode', function (Blueprint $table) {
            $table->dropColumn('gtin');
        });
    }
};
