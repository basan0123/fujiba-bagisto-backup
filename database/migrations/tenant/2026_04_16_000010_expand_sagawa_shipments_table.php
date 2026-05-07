<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Expand sagawa_shipments (tenant DB) to support the admin address-verification
 * and waybill-issuance queue. Idempotent: safe to re-run on any tenant.
 */
return new class extends Migration
{
    private const NEW_ENUM = [
        'pending_verification',
        'address_verified',
        'address_rejected',
        'waybill_pending',
        'waybill_issued',
        'waybill_failed',
        'reissue_requested',
    ];

    private const STATUS_MAP = [
        'pending' => 'pending_verification',
        'ready'   => 'waybill_pending',
        'shipped' => 'waybill_issued',
        'failed'  => 'waybill_failed',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('sagawa_shipments')) {
            return;
        }

        Schema::table('sagawa_shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('sagawa_shipments', 'channel_id')) {
                $table->unsignedInteger('channel_id')->nullable()->after('order_id');
            }
            if (! Schema::hasColumn('sagawa_shipments', 'original_address')) {
                $table->json('original_address')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'corrected_address')) {
                $table->json('corrected_address')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'address_check_result')) {
                $table->json('address_check_result')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'address_check_at')) {
                $table->timestamp('address_check_at')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'verified_by')) {
                $table->unsignedInteger('verified_by')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'print_request_id')) {
                $table->string('print_request_id', 255)->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'pdf_url')) {
                $table->text('pdf_url')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'pdf_expires_at')) {
                $table->timestamp('pdf_expires_at')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'pdf_poll_attempts')) {
                $table->unsignedTinyInteger('pdf_poll_attempts')->default(0);
            }
            if (! Schema::hasColumn('sagawa_shipments', 'sagawa_raw_request')) {
                $table->longText('sagawa_raw_request')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'sagawa_raw_response')) {
                $table->longText('sagawa_raw_response')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'waybill_issued_at')) {
                $table->timestamp('waybill_issued_at')->nullable();
            }
            if (! Schema::hasColumn('sagawa_shipments', 'waybill_failed_reason')) {
                $table->text('waybill_failed_reason')->nullable();
            }
        });

        // Widen tracking_number if still 64.
        if ($this->columnMaxLen('sagawa_shipments', 'tracking_number') === 64) {
            DB::statement('ALTER TABLE sagawa_shipments MODIFY COLUMN tracking_number VARCHAR(255) NULL');
        }

        // Expand enum + remap. DDL (ALTER) in MySQL implicitly commits, so a
        // DB::transaction wrapper here would leave no active transaction for
        // Laravel to close — run each step sequentially instead.
        if ($this->needsEnumExpansion()) {
            DB::statement(sprintf(
                "ALTER TABLE sagawa_shipments MODIFY COLUMN status ENUM(%s) NOT NULL DEFAULT 'pending_verification'",
                $this->enumList(array_unique(array_merge(
                    ['pending', 'ready', 'shipped', 'failed'],
                    self::NEW_ENUM,
                )))
            ));

            foreach (self::STATUS_MAP as $old => $new) {
                DB::table('sagawa_shipments')->where('status', $old)->update(['status' => $new]);
            }

            DB::statement(sprintf(
                "ALTER TABLE sagawa_shipments MODIFY COLUMN status ENUM(%s) NOT NULL DEFAULT 'pending_verification'",
                $this->enumList(self::NEW_ENUM)
            ));
        }

        if (Schema::hasColumn('sagawa_shipments', 'tenant_id') && ! $this->indexExists('sagawa_shipments', 'sagawa_shipments_tenant_status_idx')) {
            DB::statement('CREATE INDEX sagawa_shipments_tenant_status_idx ON sagawa_shipments (tenant_id, status)');
        }
        if (! $this->indexExists('sagawa_shipments', 'sagawa_shipments_order_status_idx')) {
            DB::statement('CREATE INDEX sagawa_shipments_order_status_idx ON sagawa_shipments (order_id, status)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sagawa_shipments')) {
            return;
        }

        if ($this->indexExists('sagawa_shipments', 'sagawa_shipments_tenant_status_idx')) {
            DB::statement('DROP INDEX sagawa_shipments_tenant_status_idx ON sagawa_shipments');
        }
        if ($this->indexExists('sagawa_shipments', 'sagawa_shipments_order_status_idx')) {
            DB::statement('DROP INDEX sagawa_shipments_order_status_idx ON sagawa_shipments');
        }

        // Revert enum: remap new → old, then shrink. See up() note on DDL + transactions.
        if (Schema::hasColumn('sagawa_shipments', 'status')) {
            DB::statement(sprintf(
                "ALTER TABLE sagawa_shipments MODIFY COLUMN status ENUM(%s) NOT NULL DEFAULT 'pending'",
                $this->enumList(array_unique(array_merge(
                    ['pending', 'ready', 'shipped', 'failed'],
                    self::NEW_ENUM,
                )))
            ));

            $reverse = [
                'pending_verification' => 'pending',
                'address_verified'     => 'pending',
                'address_rejected'     => 'failed',
                'waybill_pending'      => 'ready',
                'waybill_issued'       => 'shipped',
                'waybill_failed'       => 'failed',
                'reissue_requested'    => 'ready',
            ];
            foreach ($reverse as $new => $old) {
                DB::table('sagawa_shipments')->where('status', $new)->update(['status' => $old]);
            }

            DB::statement("ALTER TABLE sagawa_shipments MODIFY COLUMN status ENUM('pending','ready','shipped','failed') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('sagawa_shipments', function (Blueprint $table) {
            foreach ([
                'channel_id', 'original_address', 'corrected_address', 'address_check_result',
                'address_check_at', 'verified_by', 'verified_at', 'rejection_reason',
                'print_request_id', 'pdf_url', 'pdf_expires_at', 'pdf_poll_attempts',
                'sagawa_raw_request', 'sagawa_raw_response', 'waybill_issued_at', 'waybill_failed_reason',
            ] as $col) {
                if (Schema::hasColumn('sagawa_shipments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function needsEnumExpansion(): bool
    {
        $row = DB::selectOne(
            "SELECT COLUMN_TYPE AS t FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'sagawa_shipments' AND column_name = 'status'"
        );
        if (! $row) {
            return false;
        }

        foreach (self::NEW_ENUM as $v) {
            if (! str_contains((string) $row->t, "'".$v."'")) {
                return true;
            }
        }

        return false;
    }

    private function columnMaxLen(string $table, string $column): ?int
    {
        $row = DB::selectOne(
            'SELECT CHARACTER_MAXIMUM_LENGTH AS len FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, $column]
        );

        return $row?->len !== null ? (int) $row->len : null;
    }

    private function indexExists(string $table, string $index): bool
    {
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics
              WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index]
        );

        return (bool) $row;
    }

    private function enumList(array $values): string
    {
        return implode(',', array_map(fn ($v) => "'".addslashes($v)."'", $values));
    }
};
