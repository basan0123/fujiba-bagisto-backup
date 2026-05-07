<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return config('database.default');
    }

    public function up(): void
    {
        $conn = $this->getConnection();

        if (! Schema::connection($conn)->hasTable('sagawa_shipping_settings')) {
            return;
        }

        Schema::connection($conn)->table('sagawa_shipping_settings', function (Blueprint $table) use ($conn) {
            if (! Schema::connection($conn)->hasColumn('sagawa_shipping_settings', 'cutoff_minute')) {
                $table->unsignedTinyInteger('cutoff_minute')->default(0)->after('cutoff_hour');
            }

            if (! Schema::connection($conn)->hasColumn('sagawa_shipping_settings', 'weekly_default')) {
                // JSON array of weekday ints (0=Sun ... 6=Sat) we CAN prepare on.
                // null = use sensible default (Mon-Fri).
                $table->json('weekly_default')->nullable()->after('blackout_dates');
            }

            if (! Schema::connection($conn)->hasColumn('sagawa_shipping_settings', 'extra_prep_dates')) {
                // JSON array "YYYY-MM-DD" — force-on dates that override weekly_default.
                $table->json('extra_prep_dates')->nullable()->after('weekly_default');
            }
        });
    }

    public function down(): void
    {
        $conn = $this->getConnection();

        if (! Schema::connection($conn)->hasTable('sagawa_shipping_settings')) {
            return;
        }

        Schema::connection($conn)->table('sagawa_shipping_settings', function (Blueprint $table) use ($conn) {
            foreach (['extra_prep_dates', 'weekly_default', 'cutoff_minute'] as $col) {
                if (Schema::connection($conn)->hasColumn('sagawa_shipping_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
