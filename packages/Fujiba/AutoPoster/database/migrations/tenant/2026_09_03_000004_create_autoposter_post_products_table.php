<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Products attached to a queued post, in carousel order.
 *
 * image_url is snapshotted at build time rather than resolved at publish time:
 * the admin previews and reorders specific photos, and a product image edited
 * between scheduling and publishing must not silently swap the picture out from
 * under an approved post. Built with ProductPayloadBuilder's storefront-host
 * rule — Meta fetches these URLs directly, and APP_URL 404s for tenant media.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('autoposter_post_products')) {
            Schema::create('autoposter_post_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('queue_id');
                $table->unsignedInteger('product_id');

                // Drag-and-drop order in the carousel/multi-photo post.
                $table->unsignedSmallInteger('sort_order')->default(0);

                $table->text('image_url')->nullable();
                $table->text('product_url')->nullable();

                // Admin may override the photo for one card without touching
                // the catalog; NULL means "use image_url".
                $table->text('override_image_url')->nullable();

                // Per-photo id returned by the unpublished-photo upload step,
                // then referenced as attached_media on the feed post.
                $table->string('fb_media_id', 100)->nullable();

                $table->timestamps();

                $table->unique(['queue_id', 'product_id'], 'autoposter_pp_queue_product_uq');
                $table->index(['queue_id', 'sort_order'], 'autoposter_pp_order_idx');
                $table->index('product_id', 'autoposter_pp_product_idx');
            });
        }

        $fks = [
            'autoposter_pp_queue_id_foreign'   => ['queue_id', 'autoposter_queue'],
            'autoposter_pp_product_id_foreign' => ['product_id', 'products'],
        ];

        foreach ($fks as $name => [$col, $refTable]) {
            $exists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'autoposter_post_products'
                  AND CONSTRAINT_NAME = ?
            ", [$name]))->isNotEmpty();

            if (! $exists) {
                Schema::table('autoposter_post_products', function (Blueprint $table) use ($col, $refTable, $name) {
                    $table->foreign($col, $name)->references('id')->on($refTable)->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['autoposter_pp_queue_id_foreign', 'autoposter_pp_product_id_foreign'] as $name) {
            $exists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'autoposter_post_products'
                  AND CONSTRAINT_NAME = ?
            ", [$name]))->isNotEmpty();

            if ($exists) {
                Schema::table('autoposter_post_products', function (Blueprint $table) use ($name) {
                    $table->dropForeign($name);
                });
            }
        }

        Schema::dropIfExists('autoposter_post_products');
    }
};
