<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_provisioning_jobs')) {
            return;
        }

        Schema::create('tenant_provisioning_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 64)->nullable();
            $table->string('domain', 255);
            $table->string('source_tenant_id', 64)->nullable();
            $table->json('source_channels')->nullable();
            $table->string('admin_email', 255)->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->string('overall_status', 32)->default('pending');
            $table->json('steps')->nullable();
            $table->text('last_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('overall_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_provisioning_jobs');
    }
};
