<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only audit trail. NO updated_at, NO soft deletes; rows are immutable.
     */
    public function up(): void
    {
        Schema::create('fujiba_audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('table_name', 64);
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['created', 'updated', 'deleted', 'restored']);
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->unsignedInteger('user_id')->nullable();   // admin user id
            $table->string('user_name', 128)->nullable();
            $table->string('ip', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['table_name', 'record_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_audit_log');
    }
};
