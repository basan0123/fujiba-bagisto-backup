<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_page_map', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('facebook_page_id')->index();
            $table->unsignedInteger('channel_id');
            $table->string('channel_code');
            $table->string('tenant_db');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['facebook_page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_page_map');
    }
};
