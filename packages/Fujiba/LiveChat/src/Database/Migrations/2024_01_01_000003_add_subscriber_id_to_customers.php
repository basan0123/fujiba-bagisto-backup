<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'messenger_subscriber_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('messenger_subscriber_id')->nullable()->index()->after('remember_token');
                $table->string('messenger_page_id')->nullable()->after('messenger_subscriber_id');
                $table->timestamp('messenger_linked_at')->nullable()->after('messenger_page_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'messenger_subscriber_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn([
                    'messenger_subscriber_id',
                    'messenger_page_id',
                    'messenger_linked_at',
                ]);
            });
        }
    }
};
