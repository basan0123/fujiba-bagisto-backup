<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facebook_catalog_product_sync')) {
            Schema::create('facebook_catalog_product_sync', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('channel_id');
                $table->boolean('approved')->default(false);
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('sync_status', 30)->default('pending');
                $table->string('fb_retailer_id', 100)->nullable();
                $table->text('sync_error')->nullable();
                $table->string('last_payload_hash', 64)->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(['product_id', 'channel_id'], 'fb_sync_product_channel_uq');
                $table->index(['channel_id', 'approved', 'sync_status'], 'fb_sync_channel_state_idx');
                $table->index('fb_retailer_id');
            });
        }

        $fks = [
            'fb_sync_product_id_foreign' => ['product_id', 'products'],
            'fb_sync_channel_id_foreign' => ['channel_id', 'channels'],
        ];

        foreach ($fks as $name => [$col, $refTable]) {
            $exists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'facebook_catalog_product_sync'
                  AND CONSTRAINT_NAME = ?
            ", [$name]))->isNotEmpty();

            if (! $exists) {
                Schema::table('facebook_catalog_product_sync', function (Blueprint $table) use ($col, $refTable, $name) {
                    $table->foreign($col, $name)->references('id')->on($refTable)->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['fb_sync_product_id_foreign', 'fb_sync_channel_id_foreign'] as $name) {
            $exists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'facebook_catalog_product_sync'
                  AND CONSTRAINT_NAME = ?
            ", [$name]))->isNotEmpty();

            if ($exists) {
                Schema::table('facebook_catalog_product_sync', function (Blueprint $table) use ($name) {
                    $table->dropForeign($name);
                });
            }
        }

        Schema::dropIfExists('facebook_catalog_product_sync');
    }
};
