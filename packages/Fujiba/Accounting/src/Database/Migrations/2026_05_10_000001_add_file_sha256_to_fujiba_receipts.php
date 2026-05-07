<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 電子帳簿保存法 (Electronic Bookkeeping Records Act) evidence: SHA-256 of
     * the uploaded file at ingest time. Combined with the append-only
     * audit log, this gives a tamper-evident chain of custody (good-faith
     * compliance — not a certified TSA timestamp).
     */
    public function up(): void
    {
        Schema::table('fujiba_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('fujiba_receipts', 'file_sha256')) {
                $table->char('file_sha256', 64)->nullable()->after('file_size_bytes');
                $table->index('file_sha256');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fujiba_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('fujiba_receipts', 'file_sha256')) {
                $table->dropIndex(['file_sha256']);
                $table->dropColumn('file_sha256');
            }
        });
    }
};
