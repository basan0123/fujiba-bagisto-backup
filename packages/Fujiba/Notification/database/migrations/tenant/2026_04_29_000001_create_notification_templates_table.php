<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('event', 64);
                $table->string('notification_channel', 32);
                $table->string('language', 8)->default('en');
                $table->string('subject', 255)->nullable();
                $table->longText('body');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasIndex('notification_templates', 'notification_templates_resolve_idx')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->unique(
                    ['event', 'notification_channel', 'language'],
                    'notification_templates_resolve_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
