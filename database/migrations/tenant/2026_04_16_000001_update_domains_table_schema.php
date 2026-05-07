<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('domains')) {
            return;
        }

        // Keep primary key as-is (INT UNSIGNED on old tenants, BIGINT UNSIGNED on fresh ones).
        // All new FKs referencing domains.id must match the existing type — see alignSslCertFk() below.

        Schema::table('domains', function (Blueprint $table) {
            if (! Schema::hasColumn('domains', 'domain_type')) {
                $table->enum('domain_type', ['path', 'subdomain', 'custom', 'custom_subdomain'])
                    ->default('subdomain')
                    ->after('domain');
            }

            if (! Schema::hasColumn('domains', 'is_primary')) {
                $table->boolean('is_primary')->default(false);
            }

            if (! Schema::hasColumn('domains', 'is_verified')) {
                $table->boolean('is_verified')->default(false);
            }

            if (! Schema::hasColumn('domains', 'verification_code')) {
                $table->string('verification_code')->nullable();
            }

            if (! Schema::hasColumn('domains', 'verification_method')) {
                $table->enum('verification_method', ['dns', 'http'])->default('dns');
            }

            if (! Schema::hasColumn('domains', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }

            if (! Schema::hasColumn('domains', 'status')) {
                $table->enum('status', ['pending', 'active', 'suspended', 'deleted'])
                    ->default('active');
            }
        });

        $this->addSslCertificateIdIfMissing();
        $this->addIndexIfMissing('domains_tenant_id_status_index', ['tenant_id', 'status']);
        $this->addIndexIfMissing('domains_domain_index', ['domain']);
    }

    public function down(): void
    {
        // Non-destructive: keep the added columns so that a down()/up() cycle does not lose data
        // on tenants that may already be using these fields.
    }

    /**
     * Match ssl_certificate_id to the existing domains.id type so the FK is valid.
     * On legacy tenants domains.id is INT UNSIGNED; on fresh ones it is BIGINT UNSIGNED.
     * ssl_certificates.id is controlled by its own migration; we match it there.
     */
    private function addSslCertificateIdIfMissing(): void
    {
        if (Schema::hasColumn('domains', 'ssl_certificate_id')) {
            return;
        }

        $sslIdIsBigInt = $this->columnIsBigInt('ssl_certificates', 'id');

        Schema::table('domains', function (Blueprint $table) use ($sslIdIsBigInt) {
            if ($sslIdIsBigInt) {
                $table->unsignedBigInteger('ssl_certificate_id')->nullable();
            } else {
                $table->unsignedInteger('ssl_certificate_id')->nullable();
            }
        });

        if (! Schema::hasTable('ssl_certificates')) {
            return;
        }

        $fkExists = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS n
               FROM information_schema.key_column_usage
              WHERE table_schema = DATABASE()
                AND table_name = ?
                AND column_name = ?
                AND referenced_table_name = ?',
            ['domains', 'ssl_certificate_id', 'ssl_certificates']
        );

        if ($fkExists) {
            return;
        }

        DB::statement(
            'ALTER TABLE `domains`
             ADD CONSTRAINT `domains_ssl_certificate_id_foreign`
             FOREIGN KEY (`ssl_certificate_id`) REFERENCES `ssl_certificates` (`id`)
             ON DELETE SET NULL'
        );
    }

    private function addIndexIfMissing(string $indexName, array $columns): void
    {
        $exists = DB::selectOne(
            'SELECT INDEX_NAME AS n
               FROM information_schema.statistics
              WHERE table_schema = DATABASE()
                AND table_name = ?
                AND index_name = ?
              LIMIT 1',
            ['domains', $indexName]
        );

        if ($exists) {
            return;
        }

        foreach ($columns as $col) {
            if (! Schema::hasColumn('domains', $col)) {
                return;
            }
        }

        $cols = implode(',', array_map(fn ($c) => "`{$c}`", $columns));
        DB::statement("CREATE INDEX `{$indexName}` ON `domains` ({$cols})");
    }

    private function columnIsBigInt(string $table, string $column): bool
    {
        if (! Schema::hasTable($table)) {
            return true;
        }

        $type = strtolower((string) DB::selectOne(
            'SELECT DATA_TYPE AS t
               FROM information_schema.columns
              WHERE table_schema = DATABASE()
                AND table_name = ?
                AND column_name = ?',
            [$table, $column]
        )?->t);

        return $type === 'bigint';
    }
};
