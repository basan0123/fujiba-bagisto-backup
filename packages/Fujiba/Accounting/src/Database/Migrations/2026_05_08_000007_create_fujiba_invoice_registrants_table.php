<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cache for the 国税庁 (NTA) Invoice Registrant Lookup API.
     * Primary key is the 14-character T-number ("T" + 13 digits).
     */
    public function up(): void
    {
        Schema::create('fujiba_invoice_registrants', function (Blueprint $table) {
            $table->string('t_number', 14)->primary();
            $table->string('name', 255);
            $table->string('kana_name', 255)->nullable();
            $table->string('trade_name', 255)->nullable();
            $table->string('address', 512)->nullable();
            $table->string('country', 8)->default('JP');
            $table->date('registration_date')->nullable();
            $table->date('disposition_date')->nullable();   // 取消年月日 etc.
            $table->string('process_type', 16)->nullable(); // 01=新規 等
            $table->string('latest_update_date', 16)->nullable();
            $table->json('raw_json')->nullable();
            $table->dateTime('fetched_at');
            $table->timestamps();

            $table->index('name');
            $table->index('registration_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_invoice_registrants');
    }
};
