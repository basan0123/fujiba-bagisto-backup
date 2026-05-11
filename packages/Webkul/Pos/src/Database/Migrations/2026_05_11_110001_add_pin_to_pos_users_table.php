<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('pos_users', 'pin')) {
            Schema::table('pos_users', function (Blueprint $t) {
                $t->string('pin', 80)->nullable()->after('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_users', 'pin')) {
            Schema::table('pos_users', function (Blueprint $t) {
                $t->dropColumn('pin');
            });
        }
    }
};
