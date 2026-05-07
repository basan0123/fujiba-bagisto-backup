<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('messenger_bridge_nonces')) {
            return;
        }

        Schema::create('messenger_bridge_nonces', function (Blueprint $table) {
            $table->char('nonce', 6)->primary();
            $table->timestamp('seen_at')->useCurrent();
            $table->index('seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_bridge_nonces');
    }
};
