<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the human-readable entry_no (J-YYYYMM-#####) issued by JournalPoster.
     * Nullable for legacy rows; UNIQUE for forward integrity.
     */
    public function up(): void
    {
        Schema::table('fujiba_journal_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('fujiba_journal_entries', 'entry_no')) {
                $table->string('entry_no', 32)->nullable()->after('id');
                $table->unique('entry_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fujiba_journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('fujiba_journal_entries', 'entry_no')) {
                $table->dropUnique(['entry_no']);
                $table->dropColumn('entry_no');
            }
        });
    }
};
