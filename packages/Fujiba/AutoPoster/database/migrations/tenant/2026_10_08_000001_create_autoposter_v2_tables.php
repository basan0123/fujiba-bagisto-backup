<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Content Engine v2 — analyse each product once, reuse forever (re-analyse only when image_hash changes).
        Schema::create('autoposter_pattern_knowledge', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id')->unique();
            $table->string('sku', 64);
            $table->string('name');
            $table->char('image_hash', 64);                 // sha256 over the source image files, in order
            $table->json('images');                         // [{path, w, h}]
            $table->json('closeup');                        // {image, box:[x,y,w,h], sharpness}
            $table->json('colors');                         // [{hex, role, share, grade:'A'}] measured
            $table->json('motifs');                         // [{en, ja, grade, note}]
            $table->json('pattern');                        // {category, repeat, scale, background, texture, metallic}
            $table->json('meanings')->nullable();           // [{motif, claim, grade, source}]
            $table->json('seasons')->nullable();
            $table->json('occasions')->nullable();          // [{occasion, grade, note}]
            $table->text('fingerprint');                    // what must never change on any asset
            $table->json('photo_needs')->nullable();        // e.g. ["clean flat shot — watermark across fabric"]
            $table->json('hooks')->nullable();              // {en:[], ja:[], my:[], ne:[]}
            $table->string('analysed_by', 60);
            $table->timestamp('analysed_at');
            $table->timestamps();
        });

        Schema::create('autoposter_creative_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('queue_id')->nullable();   // null = dry-run
            $table->json('product_ids');
            $table->string('kind', 10);                           // image | video
            $table->string('template', 40);                       // A..J key
            $table->string('scenario', 60)->nullable();
            $table->string('character', 60)->nullable();
            $table->json('combo')->nullable();                    // {motion, transition, text_anim, music, pace, duration}
            $table->string('combo_key', 64)->nullable()->index(); // hash for the 7-day no-repeat rule
            $table->json('gate')->nullable();                     // quality-gate results
            $table->boolean('gate_passed')->default(false);
            $table->string('file')->nullable();
            $table->unsignedInteger('cost_jpy')->default(0);
            $table->boolean('dry_run')->default(true);
            $table->timestamps();
            $table->index(['template', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autoposter_creative_log');
        Schema::dropIfExists('autoposter_pattern_knowledge');
    }
};
