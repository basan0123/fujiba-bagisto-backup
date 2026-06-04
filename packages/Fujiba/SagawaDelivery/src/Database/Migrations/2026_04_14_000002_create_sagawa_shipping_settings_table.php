<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delivery rules, time slots, and zone config — per tenant + channel.
     * Also lives on the central DB so no tenant-switching is needed for admin.
     */
    public function getConnection(): string
    {
        return config('database.default');
    }

    public function up(): void
    {
        if (Schema::connection($this->getConnection())->hasTable('sagawa_shipping_settings')) {
            return;
        }

        Schema::connection($this->getConnection())->create('sagawa_shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedInteger('channel_id')->index();
            $table->string('channel_code')->default('');

            // Delivery date rules
            $table->unsignedTinyInteger('processing_days')->default(1);
            $table->unsignedTinyInteger('cutoff_hour')->default(15);      // 0-23 JST
            $table->unsignedTinyInteger('max_days_ahead')->default(30);

            // Zone transit days: JSON map of prefecture → days
            // e.g. {"東京都":1,"北海道":3,"沖縄県":4}
            $table->json('zone_days')->nullable();

            // Available delivery time slots: JSON array of {value, label} objects
            // e.g. [{"value":"am","label":"午前中 (9:00〜12:00)"},...]
            $table->json('time_slots')->nullable();

            // Blackout dates: JSON array of "YYYY-MM-DD" strings
            $table->json('blackout_dates')->nullable();

            // Feature flags
            $table->boolean('enabled')->default(true);
            $table->boolean('require_delivery_date')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'channel_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('sagawa_shipping_settings');
    }
};
