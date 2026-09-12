<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-channel Auto Poster configuration.
 *
 * Deliberately does NOT store a page id, catalog id or access token: those
 * already live in facebook_catalog_settings (one row per channel, token cast
 * 'encrypted') and duplicating them would create two sources of truth that
 * drift the moment someone rotates a token. This table holds only scheduling
 * and selection policy; the publisher joins to facebook_catalog_settings for
 * credentials.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('autoposter_settings')) {
            Schema::create('autoposter_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('channel_id');

                // Master switch. Default OFF: pre-flight has to pass before an
                // admin can turn this on, so shipping it ON would let a fresh
                // install start posting with an unvalidated token.
                $table->boolean('auto_posting_enabled')->default(false);

                // 5/day is the agreed launch cadence (K, 2026-09-03), reviewed
                // ~quarterly against engagement. Editable in admin — never read
                // a hardcoded cadence from code.
                $table->unsignedTinyInteger('posts_per_day')->default(5);

                // NULL = auto-distribute across posting_window_*; otherwise a
                // JSON array of "HH:MM" strings the admin pinned explicitly.
                $table->json('posting_times')->nullable();

                $table->string('timezone', 64)->default('Asia/Tokyo');
                $table->time('posting_window_start')->default('09:00:00');
                $table->time('posting_window_end')->default('21:30:00');

                // random | new_arrivals | category | product_set | never_posted
                // | least_recently_posted | manual
                $table->string('selection_mode', 40)->default('random');
                $table->json('selection_config')->nullable();
                $table->unsignedTinyInteger('products_per_post')->default(5);

                // Days before a product may appear in another post.
                $table->unsignedSmallInteger('repeat_cooldown_days')->default(30);

                // multi_photo today; carousel_ad reserved for the paid path so
                // adding it later needs no schema change.
                $table->string('default_format', 30)->default('multi_photo');
                $table->string('default_language', 20)->default('en_ja_my');
                $table->unsignedBigInteger('default_template_id')->nullable();

                // Messenger CTA. m.me/<ref> is the only destination we build;
                // messenger_ref is appended as ?ref= for attribution.
                $table->string('messenger_destination', 255)->nullable();
                $table->string('messenger_ref', 100)->nullable();

                $table->boolean('auto_retry_enabled')->default(true);
                $table->unsignedTinyInteger('max_retries')->default(3);

                $table->timestamp('last_preflight_at')->nullable();
                $table->boolean('last_preflight_passed')->default(false);
                $table->json('last_preflight_result')->nullable();

                $table->timestamps();

                $table->unique('channel_id');
            });
        }

        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'autoposter_settings'
              AND CONSTRAINT_NAME = 'autoposter_settings_channel_id_foreign'
        "))->isNotEmpty();

        if (! $fkExists) {
            Schema::table('autoposter_settings', function (Blueprint $table) {
                $table->foreign('channel_id', 'autoposter_settings_channel_id_foreign')
                    ->references('id')->on('channels')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'autoposter_settings'
              AND CONSTRAINT_NAME = 'autoposter_settings_channel_id_foreign'
        "))->isNotEmpty();

        if ($fkExists) {
            Schema::table('autoposter_settings', function (Blueprint $table) {
                $table->dropForeign('autoposter_settings_channel_id_foreign');
            });
        }

        Schema::dropIfExists('autoposter_settings');
    }
};
