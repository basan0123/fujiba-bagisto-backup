<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-product posting history — the cooldown and rotation source of truth.
 *
 * One row per (product, channel), updated on successful publish only. The
 * selection service reads last_posted_at against settings.repeat_cooldown_days
 * to decide eligibility, and orders by last_posted_at ASC for the
 * least-recently-posted mode. A product with no row here has never been posted,
 * which is what the never_posted mode selects — so absence is meaningful and
 * rows must not be pre-seeded for the whole catalog.
 *
 * facebook_post_id records the MOST RECENT post featuring the product; the full
 * many-to-many history lives in autoposter_post_products joined to the queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('autoposter_product_history')) {
            Schema::create('autoposter_product_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('channel_id');

                $table->timestamp('last_posted_at')->nullable();
                $table->unsignedInteger('post_count')->default(0);
                $table->string('facebook_post_id', 100)->nullable();

                // Mirrors the last queue row's status for this product; lets the
                // admin grid show state without a join.
                $table->string('status', 20)->nullable();

                // Admin can park a product so rotation never picks it.
                $table->boolean('is_excluded')->default(false);

                $table->timestamps();

                $table->unique(['product_id', 'channel_id'], 'autoposter_hist_product_channel_uq');

                // Drives both cooldown filtering and least-recently-posted order.
                $table->index(['channel_id', 'is_excluded', 'last_posted_at'], 'autoposter_hist_rotation_idx');
            });
        }

        $fks = [
            'autoposter_hist_product_id_foreign' => ['product_id', 'products'],
            'autoposter_hist_channel_id_foreign' => ['channel_id', 'channels'],
        ];

        foreach ($fks as $name => [$col, $refTable]) {
            $exists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'autoposter_product_history'
                  AND CONSTRAINT_NAME = ?
            ", [$name]))->isNotEmpty();

            if (! $exists) {
                Schema::table('autoposter_product_history', function (Blueprint $table) use ($col, $refTable, $name) {
                    $table->foreign($col, $name)->references('id')->on($refTable)->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['autoposter_hist_product_id_foreign', 'autoposter_hist_channel_id_foreign'] as $name) {
            $exists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'autoposter_product_history'
                  AND CONSTRAINT_NAME = ?
            ", [$name]))->isNotEmpty();

            if ($exists) {
                Schema::table('autoposter_product_history', function (Blueprint $table) use ($name) {
                    $table->dropForeign($name);
                });
            }
        }

        Schema::dropIfExists('autoposter_product_history');
    }
};
