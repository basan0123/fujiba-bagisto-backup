<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livechat_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('livechat_settings', 'auto_flow_enabled')) {
                $table->boolean('auto_flow_enabled')
                    ->default(false)
                    ->after('customer_chat_plugin_enabled');
            }
            if (! Schema::hasColumn('livechat_settings', 'auto_flow_postback_id')) {
                $table->string('auto_flow_postback_id', 64)
                    ->nullable()
                    ->default(null)
                    ->after('auto_flow_enabled');
            }
            if (! Schema::hasColumn('livechat_settings', 'auto_flow_lookback_minutes')) {
                $table->unsignedSmallInteger('auto_flow_lookback_minutes')
                    ->default(20)
                    ->after('auto_flow_postback_id');
            }
            if (! Schema::hasColumn('livechat_settings', 'auto_flow_grace_minutes')) {
                $table->unsignedSmallInteger('auto_flow_grace_minutes')
                    ->default(3)
                    ->after('auto_flow_lookback_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('livechat_settings', function (Blueprint $table) {
            foreach (['auto_flow_grace_minutes', 'auto_flow_lookback_minutes', 'auto_flow_postback_id', 'auto_flow_enabled'] as $col) {
                if (Schema::hasColumn('livechat_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
