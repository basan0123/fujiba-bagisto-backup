<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-J — sold-out products that competitors are visibly selling (restock
 * shortlist). Staging only: computed from fi_competitor_product_map
 * eye-verified rows + read-only Bagisto stock/sales; never written back.
 *
 * Bagisto keeps no stock timestamps (product_inventories has no
 * created_at/updated_at), so last_stock_change_at / last_restock_at come from
 * Fujiba's product_stock_audits log (starts 2026-04-30) and are proxies.
 * Competitor handles make the rows internal_only.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_restock_candidates')) {
            return;
        }

        Schema::create('fi_restock_candidates', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('product_id')->unique();
            $t->unsignedInteger('rank')->nullable()->index();
            $t->string('sku', 191)->nullable();
            $t->string('public_name', 255)->nullable();
            $t->string('channel', 32)->default('default');
            $t->boolean('enabled')->default(false);
            $t->integer('stock_qty')->default(0);
            $t->integer('reserved_qty')->default(0);
            $t->integer('available_qty')->default(0);
            $t->string('sold_out_reason', 32);                    // disabled|no_stock|disabled+no_stock
            $t->string('competitors', 512)->nullable();            // comma list of handles
            $t->unsignedSmallInteger('distinct_competitors')->default(0);
            $t->unsignedInteger('times_seen')->default(0);          // distinct competitor posts
            $t->string('best_confidence', 16)->nullable();          // exact|probable|possible
            $t->decimal('their_price_min', 10, 2)->nullable();      // only where a price was shown
            $t->decimal('their_price_max', 10, 2)->nullable();
            $t->string('their_price_note', 255)->nullable();
            $t->decimal('our_price', 10, 2)->nullable();
            $t->timestamp('last_sold_at')->nullable();               // excl. canceled orders
            $t->integer('sold_qty_all_time')->default(0);
            $t->integer('sold_qty_12m')->default(0);
            $t->integer('sold_qty_12m_completed')->default(0);      // completed/closed orders only
            $t->timestamp('last_stock_change_at')->nullable();       // proxy: product_stock_audits any delta
            $t->timestamp('last_restock_at')->nullable();            // proxy: product_stock_audits delta > 0
            $t->string('stock_date_source', 191)->nullable();
            $t->decimal('evidence_score', 8, 3)->default(0);
            $t->json('evidence')->nullable();                        // map row ids, posts, regions, methods
            $t->string('status', 16)->default('proposed');
            $t->boolean('internal_only')->default(true);
            $t->timestamp('computed_at')->nullable();
            $t->timestamps();

            $t->index(['evidence_score', 'sold_qty_12m'], 'fi_restock_score_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_restock_candidates');
    }
};
