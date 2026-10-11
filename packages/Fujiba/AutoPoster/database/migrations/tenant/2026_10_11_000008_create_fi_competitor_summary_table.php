<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-I — cross-competitor summary (mirrors storage/app/intel/competitors/summary.json).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_competitor_summary')) {
            return;
        }

        Schema::create('fi_competitor_summary', function (Blueprint $t) {
            $t->id();
            $t->string('section', 64);                        // price_range|formats|same_supplier|gaps|coverage
            $t->string('item_key', 191);
            $t->json('value')->nullable();
            $t->text('evidence')->nullable();
            $t->boolean('internal_only')->default(true);
            $t->timestamps();

            $t->unique(['section', 'item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_competitor_summary');
    }
};
