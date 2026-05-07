<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\InvoiceRegistrant;
use Fujiba\Accounting\Models\Receipt;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\User\Models\Admin;

class ReceiptControllerTest extends TestCase
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

    public function test_index_renders(): void
    {
        $this->actingAdmin();
        $this->get(route('admin.accounting.receipts.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAdmin();
        $this->get(route('admin.accounting.receipts.create'))
            ->assertStatus(200)
            ->assertSeeText(__('accounting::app.receipts.upload'));
    }

    public function test_invoice_registrant_api_returns_422_on_bad_format(): void
    {
        $this->actingAdmin();
        $resp = $this->getJson('/admin/accounting/api/invoice-registrants/not-a-tnumber');
        $resp->assertStatus(422);
        $resp->assertJsonPath('valid', false);
    }

    public function test_invoice_registrant_api_returns_cached_row(): void
    {
        $this->actingAdmin();
        InvoiceRegistrant::create([
            't_number' => 'T1111222233334',
            'name' => 'API テスト株式会社',
            'fetched_at' => now(),
        ]);

        $resp = $this->getJson('/admin/accounting/api/invoice-registrants/T1111222233334');
        $resp->assertStatus(200);
        $resp->assertJsonPath('valid', true);
        $resp->assertJsonPath('name', 'API テスト株式会社');
    }

    public function test_destroy_blocked_for_confirmed(): void
    {
        Storage::fake(config('accounting.receipts.storage_disk', 'local'));
        $this->actingAdmin();

        $r = Receipt::create([
            'channel_id' => 1,
            'total_amount' => 100,
            'tax_amount' => 0,
            'tax_category' => 'standard',
            'status' => 'confirmed',
            'storage_path' => 'fake/path.jpg',
        ]);
        $this->delete(route('admin.accounting.receipts.destroy', $r->id))
            ->assertRedirect();
        $this->assertNotNull(Receipt::find($r->id));
    }

    public function test_destroy_allowed_for_uploaded(): void
    {
        Storage::fake(config('accounting.receipts.storage_disk', 'local'));
        $this->actingAdmin();

        $r = Receipt::create([
            'channel_id' => 1,
            'total_amount' => 100,
            'tax_amount' => 0,
            'tax_category' => 'standard',
            'status' => 'uploaded',
            'storage_path' => 'fake/path.jpg',
        ]);
        $this->delete(route('admin.accounting.receipts.destroy', $r->id))
            ->assertRedirect();
        $this->assertNull(Receipt::find($r->id));
    }
}
