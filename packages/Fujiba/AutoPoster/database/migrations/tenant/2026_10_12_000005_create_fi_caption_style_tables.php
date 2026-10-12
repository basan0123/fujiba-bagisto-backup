<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-K — caption-style research (STAGING ONLY; v1 captions are never edited).
 *
 * fi_caption_samples  one row per collected caption (competitor TikTok oEmbed,
 *                     owner screenshot/paste, our own page's human phrases).
 *                     `text` is INTERNAL research data: generated captions must
 *                     never share a run of >=6 words / >=20 Burmese characters
 *                     with any row here (enforced by bin/intel/caption_style.py).
 * fi_caption_style    per-language aggregate metrics. method = measured |
 *                     model_knowledge (never mixed in one row); status =
 *                     provisional while a language has <10 included human samples.
 * fi_caption_scores   our published v1 captions scored per language segment;
 *                     rewrite_suggestion is a PROPOSAL only (status 'proposed').
 *
 * Additive only; down() drops exactly these three tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fi_caption_samples')) {
            Schema::create('fi_caption_samples', function (Blueprint $t) {
                $t->id();
                $t->string('fingerprint', 64)->unique();              // sha256(source|source_ref|text)
                $t->string('source', 32)->index();                    // tiktok_oembed|claude_read|tesseract_ocr|owner_paste|own_page_phrases|owner_screenshot
                $t->string('source_ref', 255)->nullable();            // fi_competitor_posts id / inbox file / code location
                $t->unsignedBigInteger('competitor_post_id')->nullable()->index();
                $t->string('platform', 32)->nullable();               // tiktok|facebook|instagram
                $t->string('handle', 191)->nullable();
                $t->string('language', 8)->index();                   // en|ja|my|ne|mixed|none
                $t->string('script', 24)->nullable();                 // my_unicode|my_zawgyi|ne_devanagari|ne_roman|ja|en|mixed
                $t->json('script_detail')->nullable();                // char counts per script, zawgyi signals, body vs hashtag scripts
                $t->decimal('read_confidence', 4, 3)->nullable();     // 1.000 for API text; lower for OCR / claude_read
                $t->text('text')->nullable();                         // INTERNAL ONLY (mentions/contacts already redacted)
                $t->text('overlay_text')->nullable();                 // on-screen/cover text, INTERNAL ONLY
                $t->unsignedInteger('word_count')->default(0);        // whitespace tokens excl. hashtags/mentions/urls
                $t->unsignedInteger('unit_count')->default(0);        // word units: Burmese syllables, ja chars/2, else words
                $t->unsignedSmallInteger('line_count')->default(0);
                $t->unsignedInteger('char_count')->default(0);
                $t->unsignedSmallInteger('emoji_count')->default(0);
                $t->json('emoji_list')->nullable();                   // {"✅":1,...}
                $t->unsignedSmallInteger('hashtag_count')->default(0);
                $t->json('hashtags')->nullable();
                $t->decimal('english_mix_ratio', 5, 3)->nullable();   // Latin word units / all word units
                $t->string('tone', 16)->nullable();                   // casual|neutral|formal
                $t->json('features')->nullable();                     // address terms, hook, selling points, price/CTA/delivery/stock wording
                $t->decimal('ai_likeness', 4, 3)->nullable();         // 0..1, rubric cs-1 (docs/intelligence/caption-style-method.md)
                $t->json('ai_reasons')->nullable();
                $t->boolean('included')->default(false);              // counted in style aggregates
                $t->string('exclusion_reason', 255)->nullable();
                $t->boolean('internal_only')->default(true);
                $t->string('method_version', 16)->default('cs-1.0');
                $t->timestamps();

                $t->index(['language', 'included'], 'fi_caption_samples_lang_incl_idx');
            });
        }

        if (! Schema::hasTable('fi_caption_style')) {
            Schema::create('fi_caption_style', function (Blueprint $t) {
                $t->id();
                $t->string('language', 8);                            // en|ja|my|ne
                $t->string('metric', 64);                             // length|emoji|hashtags|script|tone|address|hook|selling_points|price|cta|delivery_stock|phrases|...
                $t->json('value');
                $t->unsignedInteger('sample_size')->default(0);       // included human samples behind a measured row; 0 for model_knowledge
                $t->string('method', 32);                             // measured|model_knowledge
                $t->string('status', 16)->default('provisional');     // provisional (<10 human samples) | established
                $t->text('notes')->nullable();
                $t->string('method_version', 16)->default('cs-1.0');
                $t->timestamps();

                $t->unique(['language', 'metric', 'method'], 'fi_caption_style_lang_metric_method_uq');
            });
        }

        if (! Schema::hasTable('fi_caption_scores')) {
            Schema::create('fi_caption_scores', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('queue_id')->index();          // autoposter_queue.id (published v1 post)
                $t->string('segment_language', 8);                    // my|ne|ja|en|shared
                $t->text('segment_text')->nullable();
                $t->boolean('ai_sounding')->default(false);
                $t->decimal('ai_score', 4, 3)->nullable();
                $t->json('reasons')->nullable();
                $t->text('rewrite_suggestion')->nullable();           // staging proposal; exact queue-row facts only
                $t->json('facts_used')->nullable();                   // which queue-row facts the rewrite uses
                $t->json('overlap_check')->nullable();                // copy guard result for the rewrite
                $t->string('status', 16)->default('proposed');        // proposed|approved|rejected
                $t->string('method_version', 16)->default('cs-1.0');
                $t->timestamps();

                $t->unique(['queue_id', 'segment_language'], 'fi_caption_scores_queue_segment_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_caption_scores');
        Schema::dropIfExists('fi_caption_style');
        Schema::dropIfExists('fi_caption_samples');
    }
};
