<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('gateway_signup_attempts')) {
            return;
        }

        Schema::create('gateway_signup_attempts', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('email', 190)->nullable();
            $t->string('ip', 45);
            $t->string('user_agent', 500)->nullable();
            $t->boolean('succeeded')->default(false);
            $t->string('failure_reason', 120)->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->index(['ip', 'created_at'], 'idx_gsa_ip_time');
            $t->index(['email', 'created_at'], 'idx_gsa_email_time');
            $t->index(['failure_reason', 'created_at'], 'idx_gsa_reason_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_signup_attempts');
    }
};
