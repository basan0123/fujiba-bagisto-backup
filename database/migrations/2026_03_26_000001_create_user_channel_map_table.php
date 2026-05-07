<?php

// database/migrations/2026_03_26_000001_create_user_channel_map_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_channel_map')) {
            return;
        }

        Schema::create('user_channel_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('channel_id');
            $table->string('tenant_id');
            $table->unsignedInteger('inventory_source_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['user_id', 'channel_id']);
            $table->index(['user_id', 'channel_id', 'is_active'], 'ucm_lookup_idx');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_channel_map');
    }
};
