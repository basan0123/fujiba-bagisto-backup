<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-product barcode encoding type and pre-computed scan value.
 *
 * scan_type  — which data the barcode encodes:
 *              'gtin'       → real EAN-13/UPC-A entered by admin (stored in `gtin`)
 *              'sku'        → products.sku (auto-filled)
 *              'product_id' → products.id  (auto-filled)
 *              NULL         → fall back to tenant-global "generate_with" config
 *
 * scan_value — the pre-computed string a scanner will read.
 *              Stored so the POS can resolve any scan without knowing the type.
 *              Auto-filled by ProductBarcodeRepository::generateBarcode().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_product_barcode', function (Blueprint $table) {
            $table->enum('scan_type', ['gtin', 'sku', 'product_id'])
                  ->nullable()
                  ->default(null)
                  ->after('gtin')
                  ->comment('Which data the barcode encodes per product. NULL = use global config.');

            $table->string('scan_value', 100)
                  ->nullable()
                  ->after('scan_type')
                  ->comment('Pre-computed scannable value (GTIN, SKU, or product ID string).');
        });
    }

    public function down(): void
    {
        Schema::table('pos_product_barcode', function (Blueprint $table) {
            $table->dropColumn(['scan_type', 'scan_value']);
        });
    }
};
