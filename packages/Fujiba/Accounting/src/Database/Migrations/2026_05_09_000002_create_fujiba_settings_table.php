<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lightweight key-value bag for cron cursors and runtime settings.
     * Keep it tiny — full settings UI lives in app config.
     */
    public function up(): void
    {
        Schema::create('fujiba_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_settings');
    }
};
