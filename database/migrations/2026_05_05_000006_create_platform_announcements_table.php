<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_announcements')) {
            return;
        }

        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->string('severity', 16)->default('info');
            $table->boolean('show_to_tenant_admins')->default(true);
            $table->boolean('show_to_super_admins')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
    }
};
