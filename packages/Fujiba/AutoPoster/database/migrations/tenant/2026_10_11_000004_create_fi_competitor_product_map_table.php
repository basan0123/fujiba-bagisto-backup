<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-I — competitor post/image region -> our catalog product.
 * their_code is the SUPPLIER code format: internal only, never public.
 * match_confidence: exact|probable|possible|none (fabric_match thresholds,
 * plus human side-by-side verification flag).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_competitor_product_map')) {
            return;
        }

        Schema::create('fi_competitor_product_map', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('competitor_id')->index();
            $t->unsignedBigInteger('competitor_post_id')->index();
            $t->string('region', 64)->default('full');        // crop / band label
            $t->string('their_code', 64)->nullable();         // INTERNAL ONLY (supplier format)
            $t->string('code_mapping', 191)->nullable();      // e.g. 1750->FV (price map)
            $t->string('code_confidence', 16)->nullable();    // high|low|none
            $t->unsignedInteger('product_id')->nullable()->index();
            $t->string('our_name', 255)->nullable();
            $t->string('our_sku', 191)->nullable();
            $t->boolean('our_enabled')->nullable();
            $t->integer('our_stock_qty')->nullable();
            $t->integer('our_reserved_qty')->nullable();
            $t->decimal('their_price', 10, 2)->nullable();
            $t->string('their_price_note', 191)->nullable();
            $t->decimal('our_price', 10, 2)->nullable();
            $t->string('price_flag', 16)->default('unknown');  // lower|higher|same|unknown (their price vs ours)
            $t->string('match_method', 64)->nullable();       // code|visual_orb|code+visual|visual_human
            $t->string('match_confidence', 16)->default('none'); // exact|probable|possible|none
            $t->decimal('geometry_score', 6, 4)->nullable();
            $t->unsignedInteger('inliers')->nullable();
            $t->decimal('colour_score', 6, 4)->nullable();
            $t->string('matcher_verdict', 32)->nullable();
            $t->unsignedTinyInteger('candidate_rank')->nullable();
            $t->boolean('verified_side_by_side')->default(false);
            $t->text('evidence')->nullable();
            $t->boolean('internal_only')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_competitor_product_map');
    }
};
