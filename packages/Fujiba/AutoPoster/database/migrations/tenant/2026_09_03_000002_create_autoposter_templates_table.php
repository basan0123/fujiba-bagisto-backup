<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable caption templates (EN / JA / MY and mixes).
 *
 * body_* hold the operator-authored scaffold with {{placeholders}}; the caption
 * generator fills them, and the AI generator (when an ANTHROPIC_API_KEY is
 * present) uses them as the tone/structure prompt rather than free-styling.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('autoposter_templates')) {
            Schema::create('autoposter_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('channel_id');
                $table->string('name', 120);

                // en | ja | my | en_ja | en_my | en_ja_my | custom
                $table->string('language_mode', 20)->default('en_ja_my');

                $table->text('body_en')->nullable();
                $table->text('body_ja')->nullable();
                $table->text('body_my')->nullable();

                $table->boolean('include_emojis')->default(true);
                $table->boolean('include_hashtags')->default(true);
                $table->boolean('include_campaign_text')->default(false);
                $table->boolean('include_new_arrival_text')->default(false);
                $table->text('campaign_text')->nullable();

                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['channel_id', 'is_active'], 'autoposter_tpl_channel_active_idx');
            });
        }

        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'autoposter_templates'
              AND CONSTRAINT_NAME = 'autoposter_templates_channel_id_foreign'
        "))->isNotEmpty();

        if (! $fkExists) {
            Schema::table('autoposter_templates', function (Blueprint $table) {
                $table->foreign('channel_id', 'autoposter_templates_channel_id_foreign')
                    ->references('id')->on('channels')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'autoposter_templates'
              AND CONSTRAINT_NAME = 'autoposter_templates_channel_id_foreign'
        "))->isNotEmpty();

        if ($fkExists) {
            Schema::table('autoposter_templates', function (Blueprint $table) {
                $table->dropForeign('autoposter_templates_channel_id_foreign');
            });
        }

        Schema::dropIfExists('autoposter_templates');
    }
};
