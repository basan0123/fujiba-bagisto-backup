<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fujiba_receipts')) {
            return;
        }

        Schema::create('fujiba_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('channel_id')->nullable();
            $table->date('receipt_date')->nullable();
            $table->string('vendor_name', 128)->nullable();
            $table->string('vendor_t_number', 14)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('tax_category', 24)->default('standard');
            $table->string('payment_method', 32)->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->string('storage_path', 512)->nullable();
            $table->string('mime_type', 64)->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->json('ocr_raw_json')->nullable();
            $table->enum('status', ['uploaded', 'ocr_pending', 'ready_for_review', 'confirmed', 'rejected'])
                  ->default('uploaded');
            $table->unsignedBigInteger('account_id')->nullable();      // tentative debit account
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->unsignedInteger('confirmed_by')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('channel_id');
            $table->index('status');
            $table->index('receipt_date');
            $table->index('vendor_t_number');

            $table->foreign('account_id')
                ->references('id')->on('fujiba_accounts')
                ->onDelete('restrict');

            $table->foreign('journal_entry_id')
                ->references('id')->on('fujiba_journal_entries')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_receipts');
    }
};
