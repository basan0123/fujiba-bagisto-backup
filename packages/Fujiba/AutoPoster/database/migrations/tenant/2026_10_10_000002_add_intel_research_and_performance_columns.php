<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promote fields WS-C (research) and WS-E (insights) were packing into JSON
 * into real columns. Additive, nullable/defaulted; fi_performance is empty so
 * its unique key can be swapped to (platform, external_post_id, date, source),
 * which — unlike the old (creative_id, date, source) — dedupes rows whose
 * creative_id is NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fi_research_sources', function (Blueprint $table) {
            $table->smallInteger('http_status')->nullable()->after('publisher');
            $table->string('cache_path', 500)->nullable()->after('http_status');
            $table->boolean('robots_ok')->nullable()->after('cache_path');
            $table->timestamp('fetched_at')->nullable()->after('robots_ok');
            $table->boolean('internal_only')->default(false)->after('fetched_at');
        });

        Schema::table('fi_facts', function (Blueprint $table) {
            $table->text('claim')->nullable()->after('value');
            $table->text('evidence_excerpt')->nullable()->after('claim');
            $table->enum('match_type', ['exact', 'similar', 'generic'])->nullable()->after('evidence_excerpt');
            $table->boolean('internal_only')->default(false)->after('match_type');
        });

        Schema::table('fi_performance', function (Blueprint $table) {
            $table->string('platform', 16)->nullable()->after('creative_id');
            $table->string('external_post_id', 191)->nullable()->after('platform');
            $table->unsignedBigInteger('autoposter_queue_id')->nullable()->after('external_post_id');
            $table->unsignedBigInteger('comments')->default(0)->after('engagement');
            $table->unsignedBigInteger('shares')->default(0)->after('comments');
            $table->unsignedBigInteger('saves')->default(0)->after('shares');
            $table->unsignedBigInteger('reactions')->default(0)->after('saves');
            $table->timestamp('fetched_at')->nullable()->after('source');

            $table->index('external_post_id', 'fi_perf_external_post_idx');
            $table->dropUnique('fi_perf_creative_date_uq');
            $table->unique(['platform', 'external_post_id', 'date', 'source'], 'fi_perf_post_date_uq');
        });
    }

    public function down(): void
    {
        Schema::table('fi_performance', function (Blueprint $table) {
            $table->dropUnique('fi_perf_post_date_uq');
            $table->unique(['creative_id', 'date', 'source'], 'fi_perf_creative_date_uq');
            $table->dropIndex('fi_perf_external_post_idx');
            $table->dropColumn([
                'platform', 'external_post_id', 'autoposter_queue_id',
                'comments', 'shares', 'saves', 'reactions', 'fetched_at',
            ]);
        });

        Schema::table('fi_facts', function (Blueprint $table) {
            $table->dropColumn(['claim', 'evidence_excerpt', 'match_type', 'internal_only']);
        });

        Schema::table('fi_research_sources', function (Blueprint $table) {
            $table->dropColumn(['http_status', 'cache_path', 'robots_ok', 'fetched_at', 'internal_only']);
        });
    }
};
