<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('gateway_users')) {
            return;
        }

        Schema::create('gateway_users', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('email', 190)->unique();
            $t->string('password_hash', 255);
            $t->string('name', 190)->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->enum('status', ['active', 'suspended', 'pending'])->default('active');
            $t->timestamp('last_login_at')->nullable();
            $t->timestamps();
            $t->index('status', 'idx_gw_users_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_users');
    }
};
