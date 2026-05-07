<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_admin_security')) {
            Schema::create('tenant_admin_security', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('tenant_id', 64)->unique();
                $table->string('secret_path', 64)->nullable();
                $table->enum('security_mode', ['none', 'basic_auth', 'ip_allowlist'])->default('none');
                $table->string('basic_auth_user')->nullable();
                $table->string('basic_auth_pass_hash')->nullable();
                $table->json('allowed_ips')->nullable();
                $table->boolean('block_default_admin')->default(false);
                $table->boolean('enabled')->default(false);
                $table->timestamp('last_test_at')->nullable();
                $table->string('last_test_result', 32)->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('tenant_admin_security', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_admin_security', 'tenant_id')) {
                $table->string('tenant_id', 64)->unique();
            }
            if (! Schema::hasColumn('tenant_admin_security', 'secret_path')) {
                $table->string('secret_path', 64)->nullable();
            }
            if (! Schema::hasColumn('tenant_admin_security', 'security_mode')) {
                $table->enum('security_mode', ['none', 'basic_auth', 'ip_allowlist'])->default('none');
            }
            if (! Schema::hasColumn('tenant_admin_security', 'basic_auth_user')) {
                $table->string('basic_auth_user')->nullable();
            }
            if (! Schema::hasColumn('tenant_admin_security', 'basic_auth_pass_hash')) {
                $table->string('basic_auth_pass_hash')->nullable();
            }
            if (! Schema::hasColumn('tenant_admin_security', 'allowed_ips')) {
                $table->json('allowed_ips')->nullable();
            }
            if (! Schema::hasColumn('tenant_admin_security', 'block_default_admin')) {
                $table->boolean('block_default_admin')->default(false);
            }
            if (! Schema::hasColumn('tenant_admin_security', 'enabled')) {
                $table->boolean('enabled')->default(false);
            }
            if (! Schema::hasColumn('tenant_admin_security', 'last_test_at')) {
                $table->timestamp('last_test_at')->nullable();
            }
            if (! Schema::hasColumn('tenant_admin_security', 'last_test_result')) {
                $table->string('last_test_result', 32)->nullable();
            }
            if (! Schema::hasColumn('tenant_admin_security', 'updated_by')) {
                $table->string('updated_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_admin_security');
    }
};
