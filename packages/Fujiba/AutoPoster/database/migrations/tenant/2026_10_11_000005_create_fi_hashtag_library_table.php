<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-I — hashtags seen in competitor captions (research input only;
 * competitor brand tags are flagged so they are never reused).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_hashtag_library')) {
            return;
        }

        Schema::create('fi_hashtag_library', function (Blueprint $t) {
            $t->id();
            $t->string('tag', 191)->unique();                 // without '#'
            $t->string('language', 32)->nullable();           // ne|ja|en|ne-Latn|mixed
            $t->string('language_method', 64)->nullable();
            $t->string('tag_type', 32)->nullable();           // product|generic|platform|location|competitor_brand|campaign
            $t->string('source', 64)->default('competitor');
            $t->json('source_competitors')->nullable();
            $t->unsignedInteger('use_count')->default(0);
            $t->json('example_post_ids')->nullable();
            $t->boolean('reusable_by_us')->default(false);
            $t->text('notes')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_hashtag_library');
    }
};
