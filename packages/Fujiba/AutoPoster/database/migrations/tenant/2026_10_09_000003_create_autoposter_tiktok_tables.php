<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One connected TikTok account per channel. Tokens are stored encrypted
        // (model casts); access lasts 24h, refresh 365d.
        Schema::create('autoposter_tiktok_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('channel_id')->unique();
            $table->string('open_id', 100);
            $table->string('display_name')->nullable();
            $table->text('avatar_url')->nullable();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->string('scope')->nullable();
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('refresh_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('autoposter_tiktok_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('channel_id');
            $table->unsignedBigInteger('queue_id')->nullable()->index();
            $table->string('mode', 20);            // direct | draft
            $table->string('media_type', 10);      // video | photo
            $table->string('privacy_level', 40)->nullable();
            $table->text('caption')->nullable();
            $table->string('publish_id', 100)->nullable()->index();
            $table->string('status', 40)->default('submitted');
            $table->text('error')->nullable();
            $table->json('last_response')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autoposter_tiktok_posts');
        Schema::dropIfExists('autoposter_tiktok_accounts');
    }
};
