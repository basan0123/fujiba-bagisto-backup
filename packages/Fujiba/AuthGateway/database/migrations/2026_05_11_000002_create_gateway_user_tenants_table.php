<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('gateway_user_tenants')) {
            return;
        }

        Schema::create('gateway_user_tenants', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id');
            $t->char('tenant_id', 36);
            $t->string('tenant_subdomain', 64);
            $t->enum('role', ['owner', 'manager', 'cashier'])->default('owner');
            $t->boolean('is_owner')->default(false);
            $t->timestamps();

            $t->unique(['user_id', 'tenant_id'], 'uq_gut_user_tenant');
            $t->index('tenant_id', 'idx_gut_tenant');

            $t->foreign('user_id', 'fk_gut_user')
                ->references('id')->on('gateway_users')
                ->onDelete('cascade');
            $t->foreign('tenant_id', 'fk_gut_tenant')
                ->references('id')->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_user_tenants');
    }
};
