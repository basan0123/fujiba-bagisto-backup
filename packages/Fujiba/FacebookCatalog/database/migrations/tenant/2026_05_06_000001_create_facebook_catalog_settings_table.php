<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facebook_catalog_settings')) {
            Schema::create('facebook_catalog_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('channel_id');
                $table->string('catalog_id', 64);
                $table->string('business_id', 64)->nullable();
                $table->string('page_id', 64)->nullable();
                $table->string('pixel_id', 64)->nullable();
                $table->text('access_token');
                $table->string('default_currency', 3)->default('JPY');
                $table->string('default_locale', 10)->default('ja_JP');
                $table->boolean('is_active')->default(false);
                $table->boolean('auto_sync_price_stock')->default(true);
                $table->timestamp('last_synced_at')->nullable();
                $table->text('last_sync_error')->nullable();
                $table->timestamps();

                $table->unique('channel_id');
            });
        }

        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'facebook_catalog_settings'
              AND CONSTRAINT_NAME = 'facebook_catalog_settings_channel_id_foreign'
        "))->isNotEmpty();

        if (! $fkExists) {
            Schema::table('facebook_catalog_settings', function (Blueprint $table) {
                $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'facebook_catalog_settings'
              AND CONSTRAINT_NAME = 'facebook_catalog_settings_channel_id_foreign'
        "))->isNotEmpty();

        if ($fkExists) {
            Schema::table('facebook_catalog_settings', function (Blueprint $table) {
                $table->dropForeign('facebook_catalog_settings_channel_id_foreign');
            });
        }

        Schema::dropIfExists('facebook_catalog_settings');
    }
};
