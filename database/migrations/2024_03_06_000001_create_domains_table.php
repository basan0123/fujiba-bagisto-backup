<?php

use Illuminate\Database\Migrations\Migration;

/**
 * DEPRECATED — do not add columns here.
 *
 * The authoritative base schema for `domains` is
 * 2019_09_15_000020_create_domains_table.php (stancl/tenancy shape,
 * INT UNSIGNED primary key, varchar tenant_id).
 *
 * Column additions made by this file originally (domain_type, is_primary,
 * is_verified, verification_code, verification_method, verified_at,
 * ssl_certificate_id, status, plus indexes) have been moved to:
 *
 *   database/migrations/tenant/2026_04_16_000001_update_domains_table_schema.php
 *
 * That tenant migration is idempotent (Schema::hasColumn guards) and keeps the
 * existing primary-key type intact.
 *
 * This file is kept as a no-op so that tenants/central DBs that already ran it
 * do not see it regress to "Pending" and attempt to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        // intentionally empty — see docblock above
    }

    public function down(): void
    {
        // intentionally empty — columns are owned by the tenant update migration
    }
};
