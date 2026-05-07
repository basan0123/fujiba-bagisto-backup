<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFujibaLivechatSettings extends Migration
{
    public function up()
    {
        Schema::create('fujiba_livechat_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->unique();
            $table->string('chatpion_url');
            $table->string('chatpion_url_2')->nullable();
            $table->string('chatpion_url_3')->nullable();
            $table->string('api_key');
            $table->string('fb_page_id');
            $table->string('bot_id');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fujiba_livechat_settings');
    }
}