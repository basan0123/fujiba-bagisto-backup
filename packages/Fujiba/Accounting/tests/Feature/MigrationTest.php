<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationTest extends TestCase
{
    public function test_all_accounting_tables_exist(): void
    {
        $tables = [
            'fujiba_fiscal_periods',
            'fujiba_accounts',
            'fujiba_tax_rates',
            'fujiba_journal_entries',
            'fujiba_journal_lines',
            'fujiba_receipts',
            'fujiba_invoice_registrants',
            'fujiba_staff',
            'fujiba_timecards',
            'fujiba_payroll_slips',
            'fujiba_audit_log',
            'fujiba_entry_sequences',
            'fujiba_settings',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_accounting_migrations_are_registered_in_tenancy_config(): void
    {
        $paths = config('tenancy.migration_parameters.--path', []);

        $registered = collect($paths)->contains(
            fn ($p) => str_contains($p, 'Fujiba/Accounting/src/Database/Migrations')
        );

        $this->assertTrue(
            $registered,
            'Accounting migration path must be listed in tenancy.migration_parameters.--path '.
            'so tenants:migrate runs Accounting tables on every tenant DB. '.
            'Without it admin/accounting throws 500 (see /admin/accounting 2026-05-11 incident).'
        );
    }

    public function test_create_table_migrations_guard_with_has_table(): void
    {
        $createMigrations = glob(__DIR__.'/../../src/Database/Migrations/*_create_*.php');

        $this->assertNotEmpty($createMigrations, 'No create_*.php migrations found');

        foreach ($createMigrations as $file) {
            $contents = file_get_contents($file);
            $name = basename($file);

            $this->assertMatchesRegularExpression(
                '/Schema::hasTable\(\s*[\'"]fujiba_[a-z_]+[\'"]\s*\)/',
                $contents,
                "Migration {$name} must guard Schema::create with Schema::hasTable() ".
                "to satisfy the project idempotency rule (feedback_migration_idempotency.md). ".
                "Without the guard, re-running tenants:migrate on a partially-migrated tenant crashes."
            );
        }
    }

    public function test_journal_lines_cascade_on_entry_delete(): void
    {
        // Walk MySQL information_schema to confirm the cascade FK is in place.
        $rows = DB::select("
            SELECT rc.DELETE_RULE
            FROM information_schema.REFERENTIAL_CONSTRAINTS rc
            JOIN information_schema.KEY_COLUMN_USAGE kcu
              ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
             AND rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = DATABASE()
              AND kcu.TABLE_NAME = 'fujiba_journal_lines'
              AND kcu.REFERENCED_TABLE_NAME = 'fujiba_journal_entries'
        ");

        $this->assertNotEmpty($rows, 'No FK from journal_lines → journal_entries');
        $this->assertSame('CASCADE', $rows[0]->DELETE_RULE);
    }

    public function test_source_idempotency_key_is_unique(): void
    {
        $columns = DB::select("
            SELECT COLUMN_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'fujiba_journal_entries'
              AND NON_UNIQUE = 0
              AND COLUMN_NAME = 'source_idempotency_key'
        ");

        $this->assertNotEmpty($columns, 'source_idempotency_key must be UNIQUE');
    }
}
