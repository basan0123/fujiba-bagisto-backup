<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sf_crawled_products', function (Blueprint $table) {
            $table->string('maker', 120)->nullable()->after('series');   // as the shop states it (e.g. 但馬屋 メーカー欄)
        });
    }

    public function down(): void
    {
        Schema::table('sf_crawled_products', fn (Blueprint $table) => $table->dropColumn('maker'));
    }
};
