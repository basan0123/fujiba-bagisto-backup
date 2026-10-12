<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-L — fabric "digital twin" readiness (staging only, never touches Bagisto product tables).
 *
 * fi_product_twin_readiness: one row per product. Says whether we can render the EXACT print
 *   (110x200 cm, FB/IG, stories, mock wearing video) from REAL pixels we own:
 *     A = render exact now (own flat photo, sharp, >=1 full repeat, tile passes the seam gate, no fix)
 *     B = usable after a deterministic fix of an existing own photo (rotation/homography/
 *         labelled illumination flattening) — tile passes the seam gate after that fix
 *     C = needs a new shoot (or is not a yardage product: applicable = 0)
 *   Measured values only; anything not measurable is NULL (= unknown), never guessed.
 *   repeat_size_cm / width_cm / length_cm stay NULL unless measured (ruler) or given by
 *   catalog text / owner fill-in (fi_product_staging_attributes source owner_fill_in).
 *   best_source_path / tile_path are INTERNAL file paths. Supplier images are internal
 *   reference only and never become a tile source (supplier_ref_tileable is a note for the owner).
 *
 * fi_twin_attempts: append-only log, one row per product per tile/audit attempt — what was
 *   tried (method, input), what came out (result, metrics) and whether it worked.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fi_product_twin_readiness')) {
            Schema::create('fi_product_twin_readiness', function (Blueprint $t) {
                $t->id();
                $t->unsignedInteger('product_id')->unique();
                $t->string('sku', 191)->nullable()->index();
                $t->string('public_name', 191)->nullable();            // public code, supplier codes masked
                $t->boolean('enabled')->default(false);
                $t->boolean('applicable')->default(true);              // false = not yardage (e.g. fan)
                $t->integer('available_qty')->default(0);              // qty minus reserved
                $t->integer('sold_qty_12m')->default(0);               // order_items, last 12 months, excl. canceled
                $t->unsignedSmallInteger('photos_count')->default(0);  // all photos considered
                $t->unsignedSmallInteger('own_photos_count')->default(0);
                $t->unsignedSmallInteger('drive_photos_count')->default(0);
                $t->unsignedSmallInteger('supplier_ref_count')->default(0);
                $t->unsignedInteger('best_photo_px')->nullable();      // long side of best OWN photo
                $t->float('best_photo_sharpness')->nullable();         // Laplacian var @ max 800 px
                $t->string('flat_vs_angled', 24)->nullable();          // flat|flat_region|angled|folded|packaged|cutout|unknown
                $t->decimal('lighting_evenness', 5, 3)->nullable();    // 0..1 (1 = even)
                $t->boolean('colour_reference')->nullable();           // card/checker/white paper; NULL = unknown
                $t->boolean('full_repeat_visible')->nullable();        // >=1 full period in both axes
                $t->decimal('wrinkles_shadows', 5, 3)->nullable();     // 0..1 (0 = none)
                $t->boolean('watermark_present')->nullable();          // NULL = undetectable
                $t->float('repeat_px_x')->nullable();                  // rectangular super-cell, source px
                $t->float('repeat_px_y')->nullable();
                $t->json('repeat_lattice')->nullable();                // basis vectors + NCC
                $t->decimal('repeat_size_cm', 6, 2)->nullable();       // NULL unless measurable (ruler)
                $t->decimal('width_cm', 6, 1)->nullable();             // NULL unless catalog text / owner fill-in
                $t->decimal('length_cm', 7, 1)->nullable();
                $t->string('dims_source', 32)->nullable();
                $t->char('tier', 1)->default('C')->index();            // A|B|C (after step 2)
                $t->char('tier_audit', 1)->nullable();                 // tier from the audit, before step 2
                $t->boolean('raised_by_step2')->default(false);
                $t->text('reason')->nullable();
                $t->string('best_source_path', 512)->nullable();       // INTERNAL
                $t->string('best_source_kind', 24)->nullable();        // site_own|drive|drive_cutout|mac|legacy|shoot
                $t->string('tile_path', 512)->nullable();              // INTERNAL lossless PNG
                $t->unsignedInteger('tile_w')->nullable();
                $t->unsignedInteger('tile_h')->nullable();
                $t->decimal('seam_de00_mean', 6, 3)->nullable();
                $t->decimal('seam_de00_p95', 6, 3)->nullable();
                $t->decimal('seam_ncc', 5, 4)->nullable();
                $t->string('fixes_applied', 191)->nullable();          // e.g. rotate,illum_flatten
                $t->boolean('supplier_ref_tileable')->nullable();      // owner note only; never a source
                $t->json('metrics')->nullable();
                $t->string('method_version', 32)->nullable();
                $t->boolean('internal_only')->default(true);
                $t->timestamp('audited_at')->nullable();
                $t->timestamps();

                $t->index(['tier', 'enabled', 'available_qty'], 'fi_twin_ready_triage_idx');
            });
        }

        if (! Schema::hasTable('fi_twin_attempts')) {
            Schema::create('fi_twin_attempts', function (Blueprint $t) {
                $t->id();
                $t->unsignedInteger('product_id')->index();
                $t->string('sku', 191)->nullable();
                $t->string('run_id', 40)->nullable()->index();
                $t->unsignedSmallInteger('attempt_no')->default(1);
                $t->string('method', 64);                               // audit|lattice_crop|+rotate|+homography|+illum_flatten
                $t->string('input_kind', 24)->nullable();               // site_own|drive|drive_cutout|mac|legacy|shoot
                $t->string('input_path', 512)->nullable();              // INTERNAL
                $t->char('input_sha256', 64)->nullable();
                $t->string('result', 32);                               // tile_ok|seam_fail|no_lattice|too_small|occluded|not_flat|error|...
                $t->boolean('worked')->default(false);
                $t->json('metrics')->nullable();
                $t->text('note')->nullable();
                $t->string('output_path', 512)->nullable();
                $t->string('method_version', 32)->nullable();
                $t->timestamps();

                $t->index(['product_id', 'worked'], 'fi_twin_attempts_pid_worked_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_twin_attempts');
        Schema::dropIfExists('fi_product_twin_readiness');
    }
};
