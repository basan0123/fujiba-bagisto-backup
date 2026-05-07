<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSslCertificatesTable extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id(); // bigint unsigned primary key
            $table->unsignedInteger('domain_id'); // INT UNSIGNED to match domains.id
            $table->string('domain');
            $table->longText('certificate');
            $table->longText('private_key');
            $table->longText('certificate_chain')->nullable();
            $table->string('issuer')->default('Lets Encrypt');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('renewal_attempted_at')->nullable();
            $table->enum('status', ['pending', 'issued', 'expired', 'renewal_failed', 'revoked'])->default('pending');
            $table->timestamps();

            // foreign key
            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();

            // indexes
            $table->index('domain');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
}