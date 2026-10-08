<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fujiba Intelligence Agent — Phase 1 group 2 (I, J, K, L) and the visual
 * intelligence addendum (O-AE).
 *
 * Additive only. Nothing here touches the live v1 tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- I: experiments -------------------------------------------------
        if (! Schema::hasTable('fi_experiments')) {
            Schema::create('fi_experiments', function (Blueprint $table) {
                $table->id();
                $table->string('name', 191);
                $table->unsignedBigInteger('hypothesis_id')->nullable(); // → fi_marketing_memory
                $table->string('variable_tested', 64);                   // ONE variable per experiment
                $table->unsignedBigInteger('control_creative_id')->nullable();
                $table->json('variant_creative_ids')->nullable();
                $table->unsignedInteger('product_id')->nullable();
                $table->string('audience', 128)->nullable();
                $table->string('language', 16)->nullable();
                $table->string('platform', 32)->nullable();
                // Organic posts cannot be randomised; the design is recorded so
                // its weaker confidence is visible.
                $table->enum('design', ['alternating_time', 'paired_day', 'paid_split'])
                    ->default('alternating_time');
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->string('winner', 32)->nullable();
                $table->decimal('win_probability', 5, 4)->nullable();
                $table->enum('status', ['planned', 'running', 'insufficient_data', 'concluded', 'cancelled'])
                    ->default('planned');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['status', 'variable_tested'], 'fi_exp_status_var_idx');
            });
        }

        if (! Schema::hasTable('fi_experiment_arms')) {
            Schema::create('fi_experiment_arms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('experiment_id');
                $table->string('arm', 64);                 // control | variant_a | …
                $table->unsignedBigInteger('creative_id')->nullable();
                $table->unsignedBigInteger('impressions')->default(0);
                $table->unsignedBigInteger('clicks')->default(0);
                $table->unsignedBigInteger('orders')->default(0);
                $table->decimal('revenue', 14, 4)->default(0);
                $table->decimal('profit', 14, 4)->nullable();
                $table->timestamps();

                $table->unique(['experiment_id', 'arm'], 'fi_arm_exp_arm_uq');
            });
        }

        // --- J: creative DNA ------------------------------------------------
        if (! Schema::hasTable('fi_creative_components')) {
            Schema::create('fi_creative_components', function (Blueprint $table) {
                $table->id();
                $table->enum('type', [
                    'hook', 'opening_frame', 'scene', 'character', 'camera_move',
                    'lighting', 'composition', 'story_structure', 'pattern_reveal',
                    'cultural_explanation', 'colour_reveal', 'cta', 'ending',
                ]);
                $table->string('language', 16)->nullable();
                $table->json('content');
                $table->string('tags', 500)->nullable();
                $table->enum('created_by', ['owner', 'ai', 'research'])->default('ai');

                // Aggregated from linked creatives' outcomes.
                $table->unsignedInteger('uses')->default(0);
                $table->unsignedBigInteger('total_clicks')->default(0);
                $table->unsignedBigInteger('total_orders')->default(0);
                $table->decimal('total_revenue', 14, 4)->default(0);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['type', 'created_by'], 'fi_component_type_idx');
            });
        }

        if (! Schema::hasTable('fi_creative_component_links')) {
            Schema::create('fi_creative_component_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('creative_id');
                $table->unsignedBigInteger('component_id');
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['creative_id', 'component_id'], 'fi_cclink_uq');
                $table->index('component_id', 'fi_cclink_component_idx');
            });
        }

        // --- K: season / event intelligence ---------------------------------
        if (! Schema::hasTable('fi_events')) {
            Schema::create('fi_events', function (Blueprint $table) {
                $table->id();
                $table->string('name', 191);
                $table->string('culture', 16);     // JP | MM | NP | global
                $table->string('category', 32);    // holiday|gifting|wedding|community
                $table->unsignedSmallInteger('year')->nullable();
                // Lunar/variable dates are per-year and researched, never
                // hard-coded; null date + confidence 0 means "unknown, ask".
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->unsignedSmallInteger('lead_time_days')->default(14);
                $table->text('relevance_notes')->nullable();
                $table->unsignedBigInteger('source_research_id')->nullable();
                $table->decimal('confidence', 4, 3)->default(0);
                $table->boolean('verified_by_owner')->default(false);
                $table->timestamps();

                $table->index(['culture', 'year'], 'fi_events_culture_year_idx');
                $table->index('start_date', 'fi_events_start_idx');
            });
        }

        // --- O/P/R: visual product evidence (addendum) ----------------------
        if (! Schema::hasTable('fi_product_visual_evidence')) {
            Schema::create('fi_product_visual_evidence', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('product_id');
                $table->text('source_url')->nullable();
                $table->string('source_domain', 191)->nullable();
                $table->string('source_type', 64)->nullable();
                $table->string('image_hash', 128)->nullable();

                // A visual match is never assumed to be the same product.
                $table->enum('match_type', [
                    'exact_match', 'probable_match', 'similar_pattern',
                    'similar_style', 'unrelated', 'unknown',
                ])->default('unknown');
                $table->decimal('match_confidence', 4, 3)->default(0);

                // Independent signals — colour alone may never decide.
                $table->decimal('fabric_similarity', 4, 3)->nullable();
                $table->decimal('pattern_similarity', 4, 3)->nullable();
                $table->decimal('colour_similarity', 4, 3)->nullable();
                $table->decimal('motif_similarity', 4, 3)->nullable();
                $table->decimal('geometry_similarity', 4, 3)->nullable();
                $table->decimal('matched_region_percentage', 5, 2)->nullable();
                $table->json('matched_features')->nullable();
                $table->json('contradicting_features')->nullable();

                $table->text('evidence_summary')->nullable();
                $table->unsignedBigInteger('research_id')->nullable();
                $table->timestamp('retrieved_at')->nullable();
                $table->string('verification_status', 32)->default('unverified');
                $table->timestamps();

                $table->index(['product_id', 'match_type'], 'fi_visev_product_match_idx');
            });
        }

        // --- S/T/W: visual + creative patterns observed ---------------------
        if (! Schema::hasTable('fi_visual_patterns')) {
            Schema::create('fi_visual_patterns', function (Blueprint $table) {
                $table->id();
                $table->string('pattern_type', 64);
                $table->text('description');
                $table->unsignedInteger('evidence_count')->default(0);
                $table->json('supporting_sources')->nullable();
                $table->json('supporting_creatives')->nullable();
                $table->json('supporting_orders')->nullable();
                $table->decimal('confidence', 4, 3)->default(0);
                $table->string('audience', 128)->nullable();
                $table->string('language', 16)->nullable();
                $table->string('platform', 32)->nullable();
                $table->string('season', 64)->nullable();
                // Observation vs proof — never conflated.
                $table->enum('kind', ['observed_visual_pattern', 'verified_performance_result'])
                    ->default('observed_visual_pattern');
                $table->timestamps();
                $table->timestamp('expires_at')->nullable();

                $table->index(['pattern_type', 'kind'], 'fi_vispat_type_kind_idx');
            });
        }

        // --- Y: generation readiness (machine-readable per product) ---------
        if (! Schema::hasTable('fi_product_visual_profiles')) {
            Schema::create('fi_product_visual_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('product_id')->unique();
                $table->json('colour_profile')->nullable();      // measured Lab/hex + %
                $table->json('geometry_profile')->nullable();    // motif spacing/direction
                $table->json('texture_profile')->nullable();
                $table->text('pattern_description')->nullable();
                $table->text('motif_description')->nullable();
                $table->string('fabric_mask_path', 500)->nullable();
                $table->json('fabric_reference_images')->nullable();
                $table->json('approved_scene_references')->nullable();
                $table->string('embedding_hash', 128)->nullable();
                $table->json('embedding')->nullable();
                $table->string('analysis_version', 32)->nullable();
                $table->timestamp('analysed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'fi_product_visual_profiles',
            'fi_visual_patterns',
            'fi_product_visual_evidence',
            'fi_events',
            'fi_creative_component_links',
            'fi_creative_components',
            'fi_experiment_arms',
            'fi_experiments',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
