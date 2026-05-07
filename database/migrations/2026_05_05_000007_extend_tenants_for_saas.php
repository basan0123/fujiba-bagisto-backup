<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->after('plan');
            }
            if (! Schema::hasColumn('tenants', 'owner_email')) {
                $table->string('owner_email', 255)->nullable()->after('email');
            }
            if (! Schema::hasColumn('tenants', 'ssl_status')) {
                $table->string('ssl_status', 32)->default('none')->after('status');
            }
            if (! Schema::hasColumn('tenants', 'ssl_expires_at')) {
                $table->timestamp('ssl_expires_at')->nullable()->after('ssl_status');
            }
            if (! Schema::hasColumn('tenants', 'ssl_last_checked_at')) {
                $table->timestamp('ssl_last_checked_at')->nullable()->after('ssl_expires_at');
            }
            if (! Schema::hasColumn('tenants', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('ssl_last_checked_at');
            }
            if (! Schema::hasColumn('tenants', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('suspended_at');
            }
        });

        // Backfill plan_id from string `plan` if plans table exists.
        if (Schema::hasTable('plans') && Schema::hasColumn('tenants', 'plan_id')) {
            DB::statement("
                UPDATE tenants t
                LEFT JOIN plans p ON LOWER(p.name) = LOWER(t.plan)
                SET t.plan_id = p.id
                WHERE t.plan_id IS NULL AND t.plan IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        // Non-destructive.
    }
};
