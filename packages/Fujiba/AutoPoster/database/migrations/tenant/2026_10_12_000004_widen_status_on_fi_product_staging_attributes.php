<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * status was varchar(16), so 'needs_measurement' (17) was stored truncated as
 * 'needs_measuremen'. Widen to 32 and repair the truncated rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fi_product_staging_attributes', function (Blueprint $t) {
            $t->string('status', 32)->default('proposed')->change();
        });

        DB::table('fi_product_staging_attributes')
            ->where('status', 'needs_measuremen')
            ->update(['status' => 'needs_measurement']);
    }

    public function down(): void
    {
        DB::table('fi_product_staging_attributes')
            ->where('status', 'needs_measurement')
            ->update(['status' => 'needs_measuremen']);

        Schema::table('fi_product_staging_attributes', function (Blueprint $t) {
            $t->string('status', 16)->default('proposed')->change();
        });
    }
};
