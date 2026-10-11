<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-I — content formats observed across competitors.
 * Ranked by observed frequency only; engagement is 'unmeasured' because
 * oEmbed does not expose views/likes/comments.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_competitor_formats')) {
            return;
        }

        Schema::create('fi_competitor_formats', function (Blueprint $t) {
            $t->id();
            $t->string('code', 64)->unique();
            $t->string('label', 191);
            $t->string('family', 64)->nullable();             // worn|dress_form|folded|bolt|hanger|closeup|reply|dress
            $t->text('description')->nullable();
            $t->unsignedInteger('frequency_primary')->default(0);
            $t->unsignedInteger('frequency_any')->default(0);   // primary + secondary appearances
            $t->unsignedInteger('frequency_rank')->nullable();
            $t->json('competitors_using')->nullable();
            $t->json('example_post_ids')->nullable();
            $t->string('engagement_status', 32)->default('unmeasured');
            $t->boolean('we_can_produce')->nullable();
            $t->text('requirements')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_competitor_formats');
    }
};
