<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tiktok_product_map')) {
            Schema::create('tiktok_product_map', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('bagisto_product_id');   // INT UNSIGNED — matches products.id
                $table->string('tiktok_product_id', 100);
                $table->unsignedInteger('channel_id');           // INT UNSIGNED — matches channels.id
                $table->string('sync_status', 30)->default('pending');
                $table->text('sync_error')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(['bagisto_product_id', 'channel_id']);
                $table->index('tiktok_product_id');
            });
        }

        $fks = [
            'tiktok_product_map_bagisto_product_id_foreign' => ['bagisto_product_id', 'products'],
            'tiktok_product_map_channel_id_foreign'         => ['channel_id', 'channels'],
        ];

        foreach ($fks as $name => [$col, $refTable]) {
            $exists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'tiktok_product_map'
                  AND CONSTRAINT_NAME = ?
            ", [$name]))->isNotEmpty();

            if (! $exists) {
                Schema::table('tiktok_product_map', function (Blueprint $table) use ($col, $refTable) {
                    $table->foreign($col)->references('id')->on($refTable)->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['tiktok_product_map_bagisto_product_id_foreign', 'tiktok_product_map_channel_id_foreign'] as $name) {
            $exists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'tiktok_product_map'
                  AND CONSTRAINT_NAME = ?
            ", [$name]))->isNotEmpty();

            if ($exists) {
                Schema::table('tiktok_product_map', function (Blueprint $table) use ($name) {
                    $table->dropForeign($name);
                });
            }
        }

        Schema::dropIfExists('tiktok_product_map');
    }
};
