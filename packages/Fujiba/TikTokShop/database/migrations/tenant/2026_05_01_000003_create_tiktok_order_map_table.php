<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tiktok_order_map')) {
            Schema::create('tiktok_order_map', function (Blueprint $table) {
                $table->id();
                $table->string('tiktok_order_id', 100);
                $table->unsignedInteger('bagisto_order_id')->nullable();   // INT UNSIGNED — matches orders.id in this Bagisto build
                $table->unsignedInteger('channel_id');                     // INT UNSIGNED — matches channels.id
                $table->string('import_status', 30)->default('pending');
                $table->text('import_error')->nullable();
                $table->timestamps();

                $table->unique('tiktok_order_id');                         // dedup guard
                $table->index('bagisto_order_id');
            });
        }

        $fks = [
            'tiktok_order_map_bagisto_order_id_foreign' => ['bagisto_order_id', 'orders', 'set null'],
            'tiktok_order_map_channel_id_foreign'       => ['channel_id', 'channels', 'cascade'],
        ];

        foreach ($fks as $name => [$col, $refTable, $onDelete]) {
            $exists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'tiktok_order_map'
                  AND CONSTRAINT_NAME = ?
            ", [$name]))->isNotEmpty();

            if (! $exists) {
                Schema::table('tiktok_order_map', function (Blueprint $table) use ($col, $refTable, $onDelete) {
                    $table->foreign($col)->references('id')->on($refTable)->onDelete($onDelete);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['tiktok_order_map_bagisto_order_id_foreign', 'tiktok_order_map_channel_id_foreign'] as $name) {
            $exists = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'tiktok_order_map'
                  AND CONSTRAINT_NAME = ?
            ", [$name]))->isNotEmpty();

            if ($exists) {
                Schema::table('tiktok_order_map', function (Blueprint $table) use ($name) {
                    $table->dropForeign($name);
                });
            }
        }

        Schema::dropIfExists('tiktok_order_map');
    }
};
