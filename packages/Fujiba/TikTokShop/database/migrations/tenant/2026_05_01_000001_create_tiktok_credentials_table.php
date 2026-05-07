<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tiktok_credentials')) {
            Schema::create('tiktok_credentials', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('channel_id');           // INT UNSIGNED — matches channels.id
                $table->string('tiktok_app_key', 100);
                $table->string('tiktok_app_secret', 100);        // encrypted cast on model
                $table->string('tiktok_shop_id', 100)->nullable();
                $table->string('tiktok_shop_name', 255)->nullable();
                $table->text('access_token')->nullable();         // encrypted cast on model
                $table->text('refresh_token')->nullable();        // encrypted cast on model
                $table->timestamp('token_expires_at')->nullable();
                $table->boolean('is_active')->default(false);
                $table->boolean('sync_products')->default(true);
                $table->boolean('sync_orders')->default(true);
                $table->boolean('sync_stock')->default(true);
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique('channel_id');
                $table->unique('tiktok_shop_id');                 // MySQL allows multiple NULLs
            });
        }

        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'tiktok_credentials'
              AND CONSTRAINT_NAME = 'tiktok_credentials_channel_id_foreign'
        "))->isNotEmpty();

        if (! $fkExists) {
            Schema::table('tiktok_credentials', function (Blueprint $table) {
                $table->foreign('channel_id')->references('id')->on('channels');
            });
        }
    }

    public function down(): void
    {
        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'tiktok_credentials'
              AND CONSTRAINT_NAME = 'tiktok_credentials_channel_id_foreign'
        "))->isNotEmpty();

        if ($fkExists) {
            Schema::table('tiktok_credentials', function (Blueprint $table) {
                $table->dropForeign('tiktok_credentials_channel_id_foreign');
            });
        }

        Schema::dropIfExists('tiktok_credentials');
    }
};
