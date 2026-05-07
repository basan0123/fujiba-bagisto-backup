<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('super_admins')) {
            return;
        }

        Schema::table('super_admins', function (Blueprint $table) {
            if (! Schema::hasColumn('super_admins', 'mfa_secret')) {
                $table->string('mfa_secret', 64)->nullable()->after('password');
            }
            if (! Schema::hasColumn('super_admins', 'mfa_enabled_at')) {
                $table->timestamp('mfa_enabled_at')->nullable()->after('mfa_secret');
            }
            if (! Schema::hasColumn('super_admins', 'ip_allowlist')) {
                $table->json('ip_allowlist')->nullable()->after('mfa_enabled_at');
            }
            if (! Schema::hasColumn('super_admins', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('ip_allowlist');
            }
            if (! Schema::hasColumn('super_admins', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
            if (! Schema::hasColumn('super_admins', 'is_root')) {
                $table->boolean('is_root')->default(false)->after('last_login_ip');
            }
            if (! Schema::hasColumn('super_admins', 'force_password_reset')) {
                $table->boolean('force_password_reset')->default(false)->after('is_root');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep columns to avoid losing TOTP state on rollback.
    }
};
