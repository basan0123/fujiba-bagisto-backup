<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return config('database.default');
    }

    public function up(): void
    {
        if (Schema::connection($this->getConnection())->hasTable('sagawa_api_logs')) {
            return;
        }

        Schema::connection($this->getConnection())->create('sagawa_api_logs', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id')->nullable()->index();
            $table->unsignedInteger('channel_id')->nullable();

            $table->string('endpoint', 64);
            $table->string('base_url');
            $table->boolean('sandbox')->default(true);

            $table->mediumText('request_xml');       // customerId/loginPassword redacted
            $table->mediumText('response_body')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('result_code', 16)->nullable();

            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('error')->nullable();

            $table->timestamps();

            $table->index(['endpoint', 'result_code']);
            $table->index(['tenant_id', 'endpoint']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('sagawa_api_logs');
    }
};
