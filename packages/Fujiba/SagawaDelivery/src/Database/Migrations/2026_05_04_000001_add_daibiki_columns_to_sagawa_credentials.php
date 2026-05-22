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
        Schema::connection($this->getConnection())->table('sagawa_credentials', function (Blueprint $table) {
            if (! Schema::connection($this->getConnection())->hasColumn('sagawa_credentials', 'daibiki_payment_type')) {
                // Sagawa Biz-Logi `daibikiType`: '0' どちらでも | '1' 現金 | '2' カード/Jデビット | null=システム設定
                $table->string('daibiki_payment_type', 1)->nullable()->after('api_endpoint');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->table('sagawa_credentials', function (Blueprint $table) {
            if (Schema::connection($this->getConnection())->hasColumn('sagawa_credentials', 'daibiki_payment_type')) {
                $table->dropColumn('daibiki_payment_type');
            }
        });
    }
};
