<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sagawa API credentials — stored encrypted, scoped per tenant + channel.
     * Lives on the CENTRAL DB (connection 'mysql') so all tenants can be
     * managed from one admin panel without switching DB connections.
     */
    public function getConnection(): string
    {
        return config('database.default'); // central DB
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('sagawa_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedInteger('channel_id')->index();
            $table->string('channel_code')->default('');

            // Sagawa e-ネコ API credentials (stored encrypted)
            $table->text('api_customer_id');        // encrypted
            $table->text('api_password');           // encrypted

            // Sagawa API endpoint (staging vs production)
            $table->string('api_endpoint')->default('https://api.sgsystems.co.jp');
            $table->boolean('use_sandbox')->default(true);

            // Sender (origin) address — configurable per channel
            $table->string('sender_name')->default('');
            $table->string('sender_postcode')->default('');
            $table->string('sender_prefecture')->default('');
            $table->string('sender_city')->default('');
            $table->string('sender_address')->default('');
            $table->string('sender_phone')->default('');

            $table->boolean('enabled')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'channel_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('sagawa_credentials');
    }
};
