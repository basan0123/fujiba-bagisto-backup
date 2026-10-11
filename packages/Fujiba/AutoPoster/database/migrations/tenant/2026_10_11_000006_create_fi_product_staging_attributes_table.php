<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-I — proposed product attribute improvements (STAGING ONLY).
 * Nothing here is written back to Bagisto product tables; a human approves
 * first. Every row carries source + confidence + evidence.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_product_staging_attributes')) {
            return;
        }

        Schema::create('fi_product_staging_attributes', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('product_id')->index();
            $t->string('attribute', 64);
            $t->text('value')->nullable();
            $t->string('source', 64);                         // catalog_text|measured_colour|ai_inference|competitor_evidence|inventory
            $t->decimal('confidence', 4, 3)->nullable();
            $t->text('evidence')->nullable();
            $t->string('status', 16)->default('proposed');
            $t->boolean('internal_only')->default(false);
            $t->timestamps();

            $t->unique(['product_id', 'attribute', 'source'], 'fi_psa_product_attr_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_product_staging_attributes');
    }
};
