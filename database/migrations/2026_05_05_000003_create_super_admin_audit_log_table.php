<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('super_admin_audit_log')) {
            return;
        }

        Schema::create('super_admin_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('super_admin_id')->nullable();
            $table->string('actor_email', 255)->nullable();
            $table->string('action', 96);
            $table->string('target_tenant_id', 64)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('super_admin_id');
            $table->index('action');
            $table->index('target_tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_audit_log');
    }
};
