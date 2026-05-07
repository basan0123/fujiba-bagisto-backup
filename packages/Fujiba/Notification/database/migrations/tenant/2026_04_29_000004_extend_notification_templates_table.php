<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            return;
        }

        Schema::table('notification_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_templates', 'from_name')) {
                $table->string('from_name', 100)->nullable()->after('subject');
            }

            if (! Schema::hasColumn('notification_templates', 'from_email')) {
                $table->string('from_email', 100)->nullable()->after('from_name');
            }

            if (! Schema::hasColumn('notification_templates', 'buttons')) {
                $table->json('buttons')->nullable()->after('body');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            return;
        }

        Schema::table('notification_templates', function (Blueprint $table) {
            foreach (['from_name', 'from_email', 'buttons'] as $col) {
                if (Schema::hasColumn('notification_templates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
