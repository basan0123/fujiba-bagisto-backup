<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add role enum to pos_users.
 *
 * Roles (least -> most privileged):
 *   cashier  — basic register operations
 *   manager  — adjust credit, approve over-limit discounts, etc.
 *   owner    — full settings access, multi-store, billing
 *
 * Schema default = 'cashier' (new staff invited via the team-invite
 * flow start as cashier and a manager promotes them).
 *
 * Backfill on existing tenants: every current pos_users row is
 * de-facto an owner (they signed up + run the store), so backfill
 * to 'owner' once at migration time. Future rows take the schema
 * default of 'cashier'.
 *
 * Used initially by the customerCreditAdjust mutation
 * (manager+owner only); future admin-only mutations gate on the
 * same enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pos_users', 'role')) {
            Schema::table('pos_users', function (Blueprint $table) {
                $table->enum('role', ['cashier', 'manager', 'owner'])
                    ->default('cashier')
                    ->after('status')
                    ->comment('Authorization gate for admin-only mutations');
            });

            // Backfill existing rows to 'owner' so the people who set up
            // the tenant don't lose access to credit-adjust mid-shift.
            DB::table('pos_users')->update(['role' => 'owner']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_users', 'role')) {
            Schema::table('pos_users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
