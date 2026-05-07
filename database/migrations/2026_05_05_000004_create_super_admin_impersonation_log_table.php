<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('super_admin_impersonation_log')) {
            return;
        }

        Schema::create('super_admin_impersonation_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('super_admin_id');
            $table->string('tenant_id', 64);
            $table->string('target_admin_email', 255)->nullable();
            $table->text('reason');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();

            $table->index('super_admin_id');
            $table->index('tenant_id');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_impersonation_log');
    }
};
