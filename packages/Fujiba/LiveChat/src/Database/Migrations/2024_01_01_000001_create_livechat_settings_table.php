<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livechat_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedInteger('channel_id')->index();
            $table->string('channel_code');
            // Chatpion servers — up to 3 for failover
            $table->string('chatpion_url_1');
            $table->string('chatpion_url_2')->nullable();
            $table->string('chatpion_url_3')->nullable();
            $table->string('chatpion_api_key');
            $table->string('chatpion_user_id')->nullable();
            // Facebook
            $table->string('fb_page_id');
            $table->string('fb_bot_id');
            // Feature flags
            $table->boolean('livechat_enabled')->default(true);
            $table->boolean('auto_login_enabled')->default(true);
            $table->boolean('order_notifications_enabled')->default(true);
            $table->boolean('customer_chat_plugin_enabled')->default(false);
            // Health tracking
            $table->tinyInteger('active_server_index')->default(0);
            $table->timestamp('last_health_check')->nullable();
            $table->json('server_health')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'channel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livechat_settings');
    }
};
