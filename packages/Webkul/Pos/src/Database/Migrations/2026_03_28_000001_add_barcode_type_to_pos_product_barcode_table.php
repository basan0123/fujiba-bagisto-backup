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
            // Barcode symbology chosen per-product by the admin.
            // Supported values match the barcode() function: code128, code128a, code128b,
            // code39, code25, codabar.  NULL means "use the global config default".
            $table->string('barcode_type', 50)->nullable()->after('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_product_barcode', function (Blueprint $table) {
            $table->dropColumn('barcode_type');
        });
    }
};
