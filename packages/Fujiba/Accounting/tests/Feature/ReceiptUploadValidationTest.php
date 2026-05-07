<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\Receipt;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\User\Models\Admin;

class ReceiptUploadValidationTest extends TestCase
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

    public function test_executable_extension_is_rejected(): void
    {
        Storage::fake(config('accounting.receipts.storage_disk', 'local'));
        $this->actingAdmin();

        $file = UploadedFile::fake()->create('malware.exe', 50, 'application/octet-stream');

        $before = Receipt::count();
        $resp = $this->from(route('admin.accounting.receipts.create'))
            ->post(route('admin.accounting.receipts.store'), ['file' => $file]);

        $resp->assertSessionHasErrors('file');
        $this->assertSame($before, Receipt::count(), 'No receipt row should be created on validation failure');
    }

    public function test_missing_file_is_rejected(): void
    {
        $this->actingAdmin();

        $resp = $this->from(route('admin.accounting.receipts.create'))
            ->post(route('admin.accounting.receipts.store'), []);
        $resp->assertSessionHasErrors('file');
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake(config('accounting.receipts.storage_disk', 'local'));
        $this->actingAdmin();

        // 11 MB > 10 MB limit
        $file = UploadedFile::fake()->create('huge.pdf', 11 * 1024, 'application/pdf');

        $before = Receipt::count();
        $resp = $this->from(route('admin.accounting.receipts.create'))
            ->post(route('admin.accounting.receipts.store'), ['file' => $file]);
        $resp->assertSessionHasErrors('file');
        $this->assertSame($before, Receipt::count());
    }
}
