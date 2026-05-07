<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Models\Receipt;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\User\Models\Admin;

class ReceiptUploadTest extends TestCase
{
    use DatabaseTransactions;

    protected function actingAdmin()
    {
        $admin = Admin::query()->where('status', 1)->first() ?? Admin::query()->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user available');
        }
        return $this->actingAs($admin, 'admin');
    }

    public function test_jpg_upload_creates_receipt_row_and_stores_file(): void
    {
        Storage::fake(config('accounting.receipts.storage_disk', 'local'));
        $this->actingAdmin();

        $file = UploadedFile::fake()->image('receipt.jpg', 800, 1200)->size(150);

        $resp = $this->post(route('admin.accounting.receipts.store'), [
            'file' => $file,
        ]);

        $resp->assertRedirect();
        $r = Receipt::orderByDesc('id')->first();
        $this->assertNotNull($r);
        $this->assertSame('uploaded', $r->status);
        $this->assertNotNull($r->file_sha256);
        $this->assertSame(64, strlen($r->file_sha256));
        $this->assertNotNull($r->storage_path);

        Storage::disk(config('accounting.receipts.storage_disk', 'local'))->assertExists($r->storage_path);
    }

    public function test_pdf_upload_is_accepted(): void
    {
        Storage::fake(config('accounting.receipts.storage_disk', 'local'));
        $this->actingAdmin();

        $file = UploadedFile::fake()->create('invoice.pdf', 200, 'application/pdf');

        $resp = $this->post(route('admin.accounting.receipts.store'), ['file' => $file]);
        $resp->assertRedirect();
        $r = Receipt::orderByDesc('id')->first();
        $this->assertSame('uploaded', $r->status);
        $this->assertSame('invoice.pdf', $r->original_filename);
    }

    public function test_full_flow_upload_then_ocr_then_confirm_creates_journal_entry(): void
    {
        Storage::fake(config('accounting.receipts.storage_disk', 'local'));
        $this->actingAdmin();

        // Upload
        $file = UploadedFile::fake()->image('receipt.jpg')->size(50);
        $this->post(route('admin.accounting.receipts.store'), ['file' => $file])->assertRedirect();
        $r = Receipt::orderByDesc('id')->first();

        // OCR (stub driver — sets status to ready_for_review without real extraction)
        $this->post(route('admin.accounting.receipts.ocr', $r->id))->assertRedirect();
        $r->refresh();
        $this->assertSame('ready_for_review', $r->status);

        // Operator manually fills required fields
        $expense = Account::where('code', '6260')->first();
        $r->update([
            'vendor_name'    => 'テスト商店',
            'receipt_date'   => '2026-05-08',
            'total_amount'   => 1100,
            'tax_amount'     => 100,
            'tax_category'   => 'standard',
            'payment_method' => 'cash',
            'account_id'     => $expense->id,
        ]);

        // Confirm → journal entry created, status=confirmed
        $this->post(route('admin.accounting.receipts.confirm', $r->id))->assertRedirect();
        $r->refresh();
        $this->assertSame('confirmed', $r->status);
        $this->assertNotNull($r->journal_entry_id);
        $this->assertNotNull($r->confirmed_at);

        $entry = $r->journalEntry;
        $this->assertNotNull($entry);
        $this->assertSame('receipt', $entry->source_type);
        $this->assertSame((int) $r->id, (int) $entry->source_id);
        $this->assertSame(1100, (int) $entry->lines->sum('debit'));
        $this->assertSame(1100, (int) $entry->lines->sum('credit'));
    }
}
