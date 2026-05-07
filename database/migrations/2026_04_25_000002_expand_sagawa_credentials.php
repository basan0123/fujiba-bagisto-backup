<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds per-channel toggles for Sagawa rate usage, preparation time,
 * priority, and support contact info.
 *
 * Central DB (sagawa_credentials lives there).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sagawa_credentials')) {
            return;
        }

        Schema::table('sagawa_credentials', function (Blueprint $table) {
            if (! Schema::hasColumn('sagawa_credentials', 'use_sagawa_rate')) {
                $table->boolean('use_sagawa_rate')->default(false)->after('enabled');
            }
            if (! Schema::hasColumn('sagawa_credentials', 'priority')) {
                $table->unsignedSmallInteger('priority')->default(20)->after('use_sagawa_rate');
            }
            if (! Schema::hasColumn('sagawa_credentials', 'preparation_days')) {
                $table->unsignedSmallInteger('preparation_days')->default(1)->after('priority');
            }
            if (! Schema::hasColumn('sagawa_credentials', 'support_phone')) {
                $table->string('support_phone')->default('')->after('preparation_days');
            }
            if (! Schema::hasColumn('sagawa_credentials', 'support_hours')) {
                $table->string('support_hours')->default('')->after('support_phone');
            }
            if (! Schema::hasColumn('sagawa_credentials', 'support_branch')) {
                $table->string('support_branch')->default('')->after('support_hours');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sagawa_credentials')) {
            return;
        }

        Schema::table('sagawa_credentials', function (Blueprint $table) {
            foreach (['use_sagawa_rate','priority','preparation_days','support_phone','support_hours','support_branch'] as $col) {
                if (Schema::hasColumn('sagawa_credentials', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
