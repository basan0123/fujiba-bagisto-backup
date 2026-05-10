<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('gateway_tenants')) {
            return;
        }

        Schema::create('gateway_tenants', function (Blueprint $t) {
            $t->char('tenant_id', 36)->primary();
            $t->string('subdomain', 64)->unique();
            $t->string('store_name', 190);
            $t->unsignedBigInteger('owner_user_id');
            $t->enum('status', ['provisioning', 'active', 'suspended', 'failed'])
                ->default('provisioning');
            $t->text('provisioning_error')->nullable();
            $t->timestamps();

            $t->foreign('tenant_id', 'fk_gt_tenant')
                ->references('id')->on('tenants')
                ->onDelete('cascade');
            $t->foreign('owner_user_id', 'fk_gt_owner')
                ->references('id')->on('gateway_users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_tenants');
    }
};
