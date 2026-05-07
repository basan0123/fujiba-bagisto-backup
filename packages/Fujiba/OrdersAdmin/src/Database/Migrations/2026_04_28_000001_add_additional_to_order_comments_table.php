<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_comments')) {
            return;
        }

        if (Schema::hasColumn('order_comments', 'additional')) {
            return;
        }

        Schema::table('order_comments', function (Blueprint $table) {
            $table->json('additional')->nullable()->after('customer_notified');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_comments')) {
            return;
        }

        if (! Schema::hasColumn('order_comments', 'additional')) {
            return;
        }

        Schema::table('order_comments', function (Blueprint $table) {
            $table->dropColumn('additional');
        });
    }
};
