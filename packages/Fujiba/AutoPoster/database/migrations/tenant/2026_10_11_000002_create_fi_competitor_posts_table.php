<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-I — one row per competitor post / screenshot.
 * Engagement numbers are NOT exposed by TikTok oEmbed: they stay NULL with
 * metrics_status='not_exposed' and are never estimated.
 * supplier_code_on_image is the supplier's own code format and is INTERNAL ONLY.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_competitor_posts')) {
            return;
        }

        Schema::create('fi_competitor_posts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('competitor_id')->index();
            $t->string('platform', 32);
            $t->string('external_id', 64);                    // tiktok video id | ig screenshot ref
            $t->string('url', 512)->nullable();
            $t->string('source', 32);                         // oembed|owner_screenshot
            $t->string('discovery_keyword', 191)->nullable();
            $t->string('http_status', 16)->nullable();        // final oEmbed status
            $t->json('fetch_attempts')->nullable();
            $t->string('fetch_status', 32)->default('ok');    // ok|failed|not_applicable
            $t->string('fail_reason', 255)->nullable();
            $t->text('caption')->nullable();                  // personal mentions/contacts redacted
            $t->json('hashtags')->nullable();
            $t->string('author', 191)->nullable();
            $t->timestamp('posted_at')->nullable();
            $t->string('posted_at_method', 32)->nullable();   // id_timestamp
            $t->string('thumbnail_path', 512)->nullable();    // relative to storage/app/intel/competitors
            $t->text('on_screen_text')->nullable();
            $t->string('price_shown', 128)->nullable();       // verbatim, e.g. '¥1500 / ¥1600'
            $t->decimal('price_min', 10, 2)->nullable();
            $t->decimal('price_max', 10, 2)->nullable();
            $t->string('currency', 8)->nullable();
            $t->string('format_type', 64)->nullable()->index();
            $t->json('secondary_formats')->nullable();
            $t->string('presentation', 191)->nullable();      // worn as lungi / dress form / folded ...
            $t->string('background', 255)->nullable();
            $t->json('props')->nullable();
            $t->string('content_kind', 32)->nullable();       // product_photo|inspiration|shop_video|profile
            $t->boolean('ai_generated_suspected')->default(false);
            $t->string('supplier_code_on_image', 64)->nullable(); // INTERNAL ONLY
            $t->text('fabric_description')->nullable();       // our own words, never their text
            $t->unsignedBigInteger('views')->nullable();
            $t->unsignedBigInteger('likes')->nullable();
            $t->unsignedBigInteger('comments')->nullable();
            $t->unsignedBigInteger('shares')->nullable();
            $t->string('metrics_status', 32)->default('not_exposed');
            $t->text('observations')->nullable();
            $t->boolean('internal_only')->default(true);
            $t->json('raw')->nullable();
            $t->timestamps();

            $t->unique(['platform', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_competitor_posts');
    }
};
