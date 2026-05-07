<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('messenger_page_channel_map')) {
            return;
        }

        Schema::create('messenger_page_channel_map', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('tenant_db');
            $table->string('facebook_page_id');
            $table->unsignedInteger('channel_id');
            $table->string('channel_code');
            $table->string('chatpion_api_key')->nullable();
            $table->unsignedInteger('chatpion_store_id')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'facebook_page_id'], 'mpcm_tenant_page_unique');
            $table->unique(['tenant_id', 'channel_id'], 'mpcm_tenant_channel_unique');
            $table->index(['facebook_page_id', 'is_active'], 'mpcm_page_active_idx');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_page_channel_map');
    }
};
