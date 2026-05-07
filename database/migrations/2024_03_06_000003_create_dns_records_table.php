<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $domainIdIsBigInt = $this->domainsIdIsBigInt();

        if (! Schema::hasTable('dns_records')) {
            Schema::create('dns_records', function (Blueprint $table) use ($domainIdIsBigInt) {
                $table->id();

                if ($domainIdIsBigInt) {
                    $table->unsignedBigInteger('domain_id');
                } else {
                    $table->unsignedInteger('domain_id');
                }

                $table->string('record_type');
                $table->string('record_name');
                $table->string('record_value');
                $table->boolean('is_verified')->default(false);
                $table->timestamps();

                $table->unique(['domain_id', 'record_type', 'record_name']);

                $table->charset = 'utf8mb4';

                $table->foreign('domain_id')
                    ->references('id')
                    ->on('domains')
                    ->cascadeOnDelete();
            });

            return;
        }

        // Table exists from a prior partial run — align domain_id type and add FK if missing.
        $this->alignDomainIdColumn($domainIdIsBigInt);
        $this->addForeignKeyIfMissing();
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }

    private function domainsIdIsBigInt(): bool
    {
        if (! Schema::hasTable('domains')) {
            return true;
        }

        $type = strtolower((string) DB::selectOne(
            'SELECT DATA_TYPE AS t FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            ['domains', 'id']
        )?->t);

        return $type === 'bigint';
    }

    private function alignDomainIdColumn(bool $domainIdIsBigInt): void
    {
        $current = strtolower((string) DB::selectOne(
            'SELECT DATA_TYPE AS t FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            ['dns_records', 'domain_id']
        )?->t);

        $targetIsBigInt = $domainIdIsBigInt;
        $currentIsBigInt = $current === 'bigint';

        if ($targetIsBigInt === $currentIsBigInt) {
            return;
        }

        $sql = $targetIsBigInt
            ? 'ALTER TABLE `dns_records` MODIFY `domain_id` BIGINT UNSIGNED NOT NULL'
            : 'ALTER TABLE `dns_records` MODIFY `domain_id` INT UNSIGNED NOT NULL';

        DB::statement($sql);
    }

    private function addForeignKeyIfMissing(): void
    {
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS n
             FROM information_schema.key_column_usage
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND column_name = ?
               AND referenced_table_name = ?',
            ['dns_records', 'domain_id', 'domains']
        );

        if ($exists) {
            return;
        }

        DB::statement(
            'ALTER TABLE `dns_records`
             ADD CONSTRAINT `dns_records_domain_id_foreign`
             FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE'
        );
    }
};
