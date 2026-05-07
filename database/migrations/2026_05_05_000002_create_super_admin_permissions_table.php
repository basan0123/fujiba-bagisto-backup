<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('super_admin_permissions')) {
            return;
        }

        Schema::create('super_admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('super_admin_id');
            $table->string('permission_key', 64);
            $table->boolean('value')->default(false);
            $table->timestamps();

            $table->unique(['super_admin_id', 'permission_key'], 'sap_admin_perm_unique');
            $table->index('permission_key');
            $table->foreign('super_admin_id')
                ->references('id')->on('super_admins')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_permissions');
    }
};
