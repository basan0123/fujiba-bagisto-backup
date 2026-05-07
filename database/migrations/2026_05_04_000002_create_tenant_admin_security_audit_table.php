<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_admin_security_audit')) {
            Schema::create('tenant_admin_security_audit', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('tenant_id', 64)->index();
                $table->string('actor_email')->nullable();
                $table->string('actor_ip', 45)->nullable();
                $table->string('action', 64);
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->text('note')->nullable();
                $table->timestamp('created_at')->nullable();
            });
            return;
        }

        Schema::table('tenant_admin_security_audit', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_admin_security_audit', 'actor_email')) {
                $table->string('actor_email')->nullable();
            }
            if (! Schema::hasColumn('tenant_admin_security_audit', 'actor_ip')) {
                $table->string('actor_ip', 45)->nullable();
            }
            if (! Schema::hasColumn('tenant_admin_security_audit', 'before_state')) {
                $table->json('before_state')->nullable();
            }
            if (! Schema::hasColumn('tenant_admin_security_audit', 'after_state')) {
                $table->json('after_state')->nullable();
            }
            if (! Schema::hasColumn('tenant_admin_security_audit', 'note')) {
                $table->text('note')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_admin_security_audit');
    }
};
