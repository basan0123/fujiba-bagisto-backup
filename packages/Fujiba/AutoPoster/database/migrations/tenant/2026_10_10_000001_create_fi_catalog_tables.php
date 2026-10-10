<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Workstream B — catalog inventory + measured fabric facts.
 * Additive only. Every fact row carries method + method_version + confidence;
 * anything not measured is stored with status='unknown', never guessed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fi_catalog_items')) {
            Schema::create('fi_catalog_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedInteger('product_id')->unique();
                $t->string('sku', 191)->nullable()->index();
                $t->string('name', 255)->nullable();
                $t->string('type', 32)->nullable();
                $t->boolean('status')->nullable();
                $t->unsignedInteger('parent_id')->nullable();
                $t->unsignedInteger('child_count')->default(0);
                $t->boolean('visible_individually')->nullable();
                $t->decimal('price', 12, 4)->nullable();
                $t->integer('qty')->nullable();
                $t->unsignedInteger('image_count')->default(0);
                $t->unsignedInteger('images_on_disk')->default(0);
                $t->unsignedInteger('images_missing')->default(0);
                $t->unsignedBigInteger('primary_image_id')->nullable();
                $t->unsignedInteger('dup_sku_count')->default(1);
                $t->unsignedInteger('dup_name_count')->default(1);
                $t->unsignedInteger('dup_image_products')->default(0); // other products sharing an identical image
                $t->json('issues')->nullable();
                $t->timestamp('inventoried_at')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('fi_catalog_images')) {
            Schema::create('fi_catalog_images', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('image_id')->unique(); // product_images.id
                $t->unsignedInteger('product_id')->index();
                $t->string('path', 512);
                $t->unsignedInteger('position')->nullable();
                $t->boolean('exists_on_disk')->default(false);
                $t->unsignedBigInteger('bytes')->nullable();
                $t->unsignedInteger('width')->nullable();
                $t->unsignedInteger('height')->nullable();
                $t->string('sha256', 64)->nullable()->index();
                $t->string('dhash', 16)->nullable()->index();
                $t->unsignedBigInteger('exact_dup_of')->nullable();  // lowest image_id with same sha256
                $t->unsignedBigInteger('near_dup_of')->nullable();   // lowest image_id with dhash hamming <= 4
                $t->string('analysis_status', 16)->default('pending')->index(); // pending|done|missing|error
                $t->string('analysis_version', 32)->nullable();
                $t->text('error')->nullable();
                $t->timestamp('analysed_at')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('fi_catalog_facts')) {
            Schema::create('fi_catalog_facts', function (Blueprint $t) {
                $t->id();
                $t->unsignedInteger('product_id')->index();
                $t->unsignedBigInteger('image_id')->nullable();
                $t->string('fact', 48);                     // dominant_colours|dots|repeat_period|quality|colour_cast|fabric_coverage
                $t->string('status', 16);                   // measured|unknown
                $t->json('value')->nullable();
                $t->string('method', 191);
                $t->string('method_version', 32);
                $t->float('confidence')->nullable();
                $t->timestamps();
                $t->unique(['image_id', 'fact', 'method_version'], 'fi_catalog_facts_img_fact_ver');
            });
        }

        if (! Schema::hasTable('fi_catalog_runs')) {
            Schema::create('fi_catalog_runs', function (Blueprint $t) {
                $t->id();
                $t->string('mode', 32);
                $t->string('analysis_version', 32)->nullable();
                $t->unsignedInteger('pid')->nullable();
                $t->string('status', 16)->default('running'); // running|done|failed|paused
                $t->unsignedInteger('processed')->default(0);
                $t->unsignedInteger('errors')->default(0);
                $t->unsignedBigInteger('last_image_id')->nullable();
                $t->text('note')->nullable();
                $t->timestamp('started_at')->nullable();
                $t->timestamp('finished_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['fi_catalog_runs', 'fi_catalog_facts', 'fi_catalog_images', 'fi_catalog_items'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
