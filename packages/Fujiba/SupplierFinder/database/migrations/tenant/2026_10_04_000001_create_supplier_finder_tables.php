<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Makers / wholesalers (問屋) — the "who to call" directory.
        Schema::create('sf_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('kind', 20)->default('maker');       // maker | wholesaler | retailer
            $table->string('region', 30)->nullable();           // Osaka / Kyoto / Tokyo / Wakayama / Shiga
            $table->string('address')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('url')->nullable();
            $table->string('b2b_route')->nullable();            // direct / Superdelivery / showroom
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Shops we crawl. adapter = class basename in Fujiba\SupplierFinder\Adapters.
        Schema::create('sf_source_shops', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('base_url');
            $table->string('adapter', 80)->nullable();          // null = no adapter yet
            $table->json('start_urls')->nullable();
            $table->json('strip_prefixes')->nullable();         // e.g. ["F1-"], ["sz","as","un"]
            $table->string('region', 30)->nullable();
            $table->string('price_type', 12)->default('retail'); // retail | wholesale
            $table->foreignId('supplier_id')->nullable()->constrained('sf_suppliers')->nullOnDelete();
            $table->boolean('enabled')->default(false);
            $table->timestamp('last_crawled_at')->nullable();
            $table->string('last_status', 40)->nullable();      // ok | blocked:robots | blocked:429 | error
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        // Admin-editable decode rules. pattern is a PHP regex run against the
        // normalised code; named group (?<base>…) becomes the grouping key.
        Schema::create('sf_code_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('pattern');
            $table->string('family', 60);                       // e.g. naka, yk, coco26
            $table->string('series')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('sf_suppliers')->nullOnDelete();
            $table->decimal('confidence', 3, 2)->default(0.80);
            $table->unsignedSmallInteger('priority')->default(100); // lower runs first
            $table->boolean('enabled')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // One physical fabric, possibly sold by several shops.
        Schema::create('sf_source_fabrics', function (Blueprint $table) {
            $table->id();
            $table->string('base_code', 80)->unique();          // family:base, e.g. naka:1750-67
            $table->string('family', 60)->nullable();
            $table->foreignId('code_rule_id')->nullable()->constrained('sf_code_rules')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('sf_suppliers')->nullOnDelete();
            $table->string('decode_method', 12)->default('none'); // rule | cross_shop | manual | none
            $table->decimal('confidence', 3, 2)->default(0);
            $table->string('series')->nullable();
            $table->string('title')->nullable();
            $table->string('material', 60)->nullable();
            $table->unsignedSmallInteger('width_cm')->nullable();
            $table->unsignedInteger('lowest_price_per_m')->nullable();
            $table->string('lowest_price_type', 12)->nullable(); // retail | wholesale | estimate
            $table->unsignedSmallInteger('shop_count')->default(0);
            $table->string('match_type', 10)->nullable();        // exact | similar | none
            $table->decimal('match_score', 4, 3)->nullable();
            $table->unsignedInteger('matched_product_id')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('draft_product_id')->nullable();
            $table->timestamps();
            $table->index(['match_type', 'lowest_price_per_m']);
        });

        Schema::create('sf_crawled_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('sf_source_shops')->cascadeOnDelete();
            $table->string('external_id', 80);
            $table->string('url', 500);
            $table->string('title');
            $table->json('codes')->nullable();
            $table->string('series')->nullable();
            $table->string('material', 60)->nullable();
            $table->unsignedSmallInteger('width_cm')->nullable();
            $table->json('colorways')->nullable();              // [{code,label,stock}]
            $table->unsignedInteger('price')->nullable();       // as listed, tax incl.
            $table->string('price_unit', 10)->nullable();       // 10cm | 50cm | 1m | tan
            $table->unsignedInteger('price_per_m')->nullable();
            $table->string('price_type', 12)->default('retail');
            $table->string('min_order')->nullable();
            $table->integer('stock')->nullable();
            $table->json('image_urls')->nullable();             // source only — never rendered
            $table->string('image_path')->nullable();           // private local copy for matching
            $table->string('phash', 16)->nullable();
            $table->json('features')->nullable();               // ImageFeatures: palette (LAB), edge, gold, sat
            $table->string('raw_path')->nullable();             // raw HTML/JSON snapshot
            $table->char('content_hash', 40)->nullable();
            $table->foreignId('source_fabric_id')->nullable()->constrained('sf_source_fabrics')->nullOnDelete();
            $table->timestamp('crawled_at')->nullable();
            $table->timestamp('gone_at')->nullable();           // disappeared from shop
            $table->timestamps();
            $table->unique(['shop_id', 'external_id']);
        });

        Schema::create('sf_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawled_product_id')->constrained('sf_crawled_products')->cascadeOnDelete();
            $table->unsignedInteger('product_id');               // our Bagisto product
            $table->string('match_type', 10);                    // exact | similar
            $table->decimal('score', 4, 3);
            $table->decimal('code_score', 4, 3)->default(0);
            $table->decimal('image_score', 4, 3)->default(0);
            $table->decimal('color_score', 4, 3)->default(0);
            $table->decimal('text_score', 4, 3)->default(0);
            $table->decimal('material_score', 4, 3)->default(0);
            $table->json('details')->nullable();
            $table->timestamps();
            $table->unique(['crawled_product_id', 'product_id']);
            $table->index(['match_type', 'score']);
        });

        // Run log + per-run diff (new / price change / out of stock).
        Schema::create('sf_crawl_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('sf_source_shops')->nullOnDelete();
            $table->string('status', 20)->default('running');
            $table->unsignedInteger('requests')->default(0);
            $table->json('counts')->nullable();
            $table->json('diff')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['sf_crawl_runs', 'sf_matches', 'sf_crawled_products', 'sf_source_fabrics', 'sf_code_rules', 'sf_source_shops', 'sf_suppliers'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
