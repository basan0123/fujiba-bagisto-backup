<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fujiba Intelligence — AI (Gemini) image analyses.
 * Every row is AI INFERENCE about a product photo, not verified fact.
 * raw_response must never contain API keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_ai_image_analyses')) {
            return;
        }

        Schema::create('fi_ai_image_analyses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('product_id')->index();
            $table->string('sku', 191)->nullable();
            $table->string('public_name', 191)->nullable();
            $table->string('source_image_path', 512);
            $table->char('source_image_sha256', 64)->index();
            $table->string('model_id', 128);
            $table->json('dominant_colours')->nullable();
            $table->json('motifs')->nullable();
            $table->text('composition')->nullable();
            $table->json('pattern_details')->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->json('raw_response')->nullable();
            $table->decimal('cost_jpy', 12, 4)->default(0);
            $table->timestamp('analysed_at')->nullable();
            $table->string('status', 32)->default('ai_inference_unverified');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_ai_image_analyses');
    }
};
