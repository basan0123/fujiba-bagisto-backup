<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (Schema::hasColumn('products', 'has_expiry_date')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_expiry_date')->default(false)->after('sku');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'has_expiry_date')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('has_expiry_date');
        });
    }
};
