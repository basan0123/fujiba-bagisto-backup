<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-I — competitor research (internal only).
 * One row per competitor account. Contact details (phone numbers, personal
 * names) are never stored — only whether a contact route was shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_competitors')) {
            return;
        }

        Schema::create('fi_competitors', function (Blueprint $t) {
            $t->id();
            $t->string('platform', 32);                       // tiktok|instagram
            $t->string('handle', 191);
            $t->string('display_name', 255)->nullable();
            $t->string('profile_url', 512)->nullable();
            $t->string('discovery_keyword', 191)->nullable();  // e.g. 'japanese lungi' | 'owner_reference'
            $t->string('data_source', 64)->nullable();         // oembed|owner_screenshot
            $t->string('language', 64)->nullable();
            $t->string('language_method', 64)->nullable();     // inferred_from_caption
            $t->string('location_tag', 191)->nullable();       // only if in caption/hashtags/on-screen text
            $t->string('location_evidence', 255)->nullable();
            $t->string('target_audience', 64)->nullable();     // nepali|myanmar|other|unknown
            $t->string('target_audience_evidence', 512)->nullable();
            $t->string('selling_channel', 255)->nullable();    // dm|shop|link|social ... (from caption text only)
            $t->boolean('contact_shown')->default(false);      // never the contact itself
            $t->string('profile_oembed_status', 16)->nullable();
            $t->string('profile_embed_type', 32)->nullable();
            $t->string('profile_oembed_title', 255)->nullable();
            $t->boolean('posts_enumerated')->default(false);
            $t->string('posts_enumerated_reason', 128)->nullable();
            $t->unsignedInteger('posts_collected')->default(0);
            $t->unsignedInteger('posts_readable')->default(0);
            $t->unsignedInteger('posts_failed')->default(0);
            $t->boolean('sells_same_supplier_fabric')->nullable();
            $t->string('same_supplier_evidence', 512)->nullable();
            $t->text('notes')->nullable();
            $t->boolean('internal_only')->default(true);
            $t->json('raw')->nullable();
            $t->timestamps();

            $t->unique(['platform', 'handle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_competitors');
    }
};
