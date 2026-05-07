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
            if (! Schema::connection($this->getConnection())->hasColumn('sagawa_credentials', 'daibiki_binsyu_code')) {
                $table->string('daibiki_binsyu_code', 8)->nullable()->after('api_endpoint');
            }
            if (! Schema::connection($this->getConnection())->hasColumn('sagawa_credentials', 'daibiki_payee_name')) {
                $table->string('daibiki_payee_name', 50)->nullable()->after('daibiki_binsyu_code');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->table('sagawa_credentials', function (Blueprint $table) {
            if (Schema::connection($this->getConnection())->hasColumn('sagawa_credentials', 'daibiki_payee_name')) {
                $table->dropColumn('daibiki_payee_name');
            }
            if (Schema::connection($this->getConnection())->hasColumn('sagawa_credentials', 'daibiki_binsyu_code')) {
                $table->dropColumn('daibiki_binsyu_code');
            }
        });
    }
};
