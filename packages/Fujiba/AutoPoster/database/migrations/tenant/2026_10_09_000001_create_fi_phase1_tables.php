<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fujiba Intelligence Agent — Phase 1 group 1 (H, M, N + explainability).
 *
 * Additive only: new tables, no drops, renames or data rewrites. Nothing here
 * touches the live v1 tables (autoposter_queue and friends).
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Product intelligence -------------------------------------------
        if (! Schema::hasTable('fi_product_profiles')) {
            Schema::create('fi_product_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('product_id');
                $table->string('sku', 191)->nullable();
                $table->string('channel', 64)->default('default');

                $table->text('title_snapshot')->nullable();
                $table->text('desc_snapshot')->nullable();
                $table->decimal('price', 12, 4)->nullable();
                $table->integer('stock')->nullable();
                $table->decimal('margin', 8, 4)->nullable();

                // Cache keys — unchanged pair means reuse the analysis, 0 AI calls.
                $table->string('image_hash', 128)->nullable();
                $table->string('image_sha256', 64)->nullable();
                $table->string('metadata_hash', 64)->nullable();

                // MEASURED locally (k-means in Lab) — a fact, not an inference.
                $table->json('measured_colours')->nullable();

                $table->string('pattern', 191)->nullable();
                $table->json('motifs')->nullable();
                $table->text('cultural_meaning')->nullable();
                $table->text('appearance')->nullable();

                // --- M: photography intelligence (all nullable) --------------
                $table->unsignedInteger('source_width')->nullable();
                $table->unsignedInteger('source_height')->nullable();
                $table->float('sharpness')->nullable();
                $table->boolean('watermark_detected')->nullable();
                $table->boolean('packaging_obstruction')->nullable();
                $table->float('background_quality')->nullable();
                $table->float('lighting_quality')->nullable();
                $table->float('fabric_visibility')->nullable();
                $table->float('perspective_quality')->nullable();
                $table->float('colour_quality')->nullable();
                $table->float('photo_quality_score')->nullable();
                $table->boolean('reshoot_required')->default(false);
                $table->text('reshoot_reason')->nullable();
                $table->text('recommended_photo_setup')->nullable();

                $table->string('analysis_version', 32)->nullable();
                $table->timestamp('analysed_at')->nullable();
                $table->timestamps();

                $table->unique(['product_id', 'channel'], 'fi_profiles_product_channel_uq');
                $table->index('reshoot_required', 'fi_profiles_reshoot_idx');
            });
        }

        // --- Provenance: every claim used in content traces to a row here ----
        if (! Schema::hasTable('fi_facts')) {
            Schema::create('fi_facts', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('product_id')->nullable();
                $table->string('field', 128);
                $table->text('value')->nullable();
                $table->enum('source_type', ['fact', 'ai_inference', 'web_research', 'low_confidence'])
                    ->default('ai_inference');
                $table->decimal('confidence', 4, 3)->nullable();
                $table->unsignedBigInteger('source_ref_id')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'field'], 'fi_facts_product_field_idx');
                $table->index('source_type', 'fi_facts_source_idx');
            });
        }

        if (! Schema::hasTable('fi_research_sources')) {
            Schema::create('fi_research_sources', function (Blueprint $table) {
                $table->id();
                $table->text('url')->nullable();
                $table->string('title', 500)->nullable();
                $table->string('publisher', 255)->nullable();
                $table->timestamp('retrieved_at')->nullable();
                $table->text('finding_summary')->nullable(); // own words only
                $table->string('topic', 64)->nullable();     // pattern|motif|season|creative
                $table->decimal('confidence', 4, 3)->nullable();
                $table->unsignedInteger('product_id')->nullable();
                $table->string('pattern', 191)->nullable();
                $table->timestamps();

                $table->index('topic', 'fi_research_topic_idx');
            });
        }

        // --- H: marketing memory (audience/creative learnings only) ----------
        if (! Schema::hasTable('fi_marketing_memory')) {
            Schema::create('fi_marketing_memory', function (Blueprint $table) {
                $table->id();
                $table->string('audience', 128)->nullable();
                $table->string('language', 16)->nullable();
                $table->text('statement');
                $table->enum('kind', ['observation', 'hypothesis', 'experiment_result', 'confirmed_learning'])
                    ->default('hypothesis');
                $table->json('evidence')->nullable();
                $table->decimal('confidence', 4, 3)->default(0);
                $table->json('supporting_creative_ids')->nullable();
                $table->json('supporting_order_ids')->nullable();
                $table->unsignedInteger('sample_size')->default(0);
                $table->timestamps();
                $table->timestamp('expires_at')->nullable();

                $table->index(['kind', 'audience'], 'fi_memory_kind_audience_idx');
            });
        }

        // --- Creative plans (shadow; never published in phase 1) -------------
        if (! Schema::hasTable('fi_creatives')) {
            Schema::create('fi_creatives', function (Blueprint $table) {
                $table->id();
                $table->text('concept')->nullable();
                $table->text('reason')->nullable();
                $table->text('hook')->nullable();
                $table->longText('caption')->nullable();
                $table->string('language', 16)->nullable();
                $table->string('template', 64)->nullable();
                $table->string('story', 128)->nullable();
                $table->string('cta', 255)->nullable();
                $table->unsignedInteger('product_id')->nullable();
                $table->string('audience', 128)->nullable();
                $table->string('platform', 32)->nullable();
                $table->timestamp('planned_time')->nullable();

                $table->string('provider', 64)->nullable();
                $table->string('model', 128)->nullable();
                $table->json('params')->nullable();
                $table->decimal('cost_jpy', 12, 2)->default(0);
                $table->float('quality_score')->nullable();
                $table->json('fabric_integrity')->nullable();

                $table->enum('approval_state', ['draft', 'rejected', 'needs_review', 'approved'])
                    ->default('draft');
                $table->string('publish_state', 32)->default('unpublished');
                $table->string('external_post_id', 191)->nullable();
                $table->string('tracking_ref', 191)->nullable();

                // Explainability — why this product/audience/story/CTA/time.
                $table->json('decision_trace')->nullable();

                $table->timestamps();

                $table->index(['approval_state', 'planned_time'], 'fi_creatives_state_time_idx');
                $table->index('product_id', 'fi_creatives_product_idx');
            });
        }

        // --- Cost + guard logging -------------------------------------------
        if (! Schema::hasTable('fi_ai_usage')) {
            Schema::create('fi_ai_usage', function (Blueprint $table) {
                $table->id();
                $table->string('operation', 128);
                $table->string('provider', 64)->nullable();
                $table->string('model', 128)->nullable();
                $table->unsignedBigInteger('input_units')->default(0);
                $table->unsignedBigInteger('output_units')->default(0);
                $table->decimal('cost_jpy', 12, 4)->default(0);
                $table->boolean('cache_hit')->default(false);
                $table->unsignedBigInteger('creative_id')->nullable();
                $table->unsignedInteger('product_id')->nullable();
                $table->timestamps();

                $table->index('created_at', 'fi_usage_created_idx');
            });
        }

        if (! Schema::hasTable('fi_profit_guard_log')) {
            Schema::create('fi_profit_guard_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('product_id')->nullable();
                $table->unsignedBigInteger('creative_id')->nullable();
                $table->string('operation', 128)->nullable();
                $table->boolean('allowed')->default(false);
                $table->string('reason', 255)->nullable();
                $table->json('inputs')->nullable();
                $table->decimal('expected_value_jpy', 14, 4)->nullable();
                $table->decimal('estimated_cost_jpy', 12, 4)->nullable();
                $table->decimal('roi', 10, 4)->nullable();
                $table->boolean('low_data')->default(false);
                $table->timestamps();

                $table->index(['allowed', 'created_at'], 'fi_guard_allowed_idx');
            });
        }

        // --- Performance + attribution --------------------------------------
        if (! Schema::hasTable('fi_performance')) {
            Schema::create('fi_performance', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('creative_id')->nullable();
                $table->date('date');
                $table->unsignedBigInteger('reach')->default(0);
                $table->unsignedBigInteger('impressions')->default(0);
                $table->unsignedBigInteger('video_views')->default(0);
                $table->float('avg_watch_time')->nullable();
                $table->unsignedBigInteger('engagement')->default(0);
                $table->unsignedBigInteger('clicks')->default(0);
                $table->unsignedBigInteger('product_views')->default(0);
                $table->unsignedBigInteger('add_to_cart')->default(0);
                $table->unsignedBigInteger('orders')->default(0);
                $table->decimal('revenue', 14, 4)->default(0);
                $table->decimal('profit', 14, 4)->nullable();
                $table->string('source', 32)->default('manual'); // meta_api|manual|bagisto
                $table->timestamps();

                $table->unique(['creative_id', 'date', 'source'], 'fi_perf_creative_date_uq');
            });
        }

        if (! Schema::hasTable('fi_order_attribution')) {
            Schema::create('fi_order_attribution', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('creative_id')->nullable();
                $table->string('method', 32); // ref|utm|coupon|time-window
                $table->decimal('confidence', 4, 3)->default(0);
                $table->timestamps();

                $table->index('order_id', 'fi_attr_order_idx');
                $table->index('creative_id', 'fi_attr_creative_idx');
            });
        }
    }

    public function down(): void
    {
        // Reverse dependency order. Only ever drops tables this migration made.
        foreach ([
            'fi_order_attribution',
            'fi_performance',
            'fi_profit_guard_log',
            'fi_ai_usage',
            'fi_creatives',
            'fi_marketing_memory',
            'fi_research_sources',
            'fi_facts',
            'fi_product_profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
