<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\FiscalPeriod;
use Fujiba\Accounting\Services\JournalPoster;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\User\Models\Admin;

class JournalControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function actingAdmin()
    {
        $admin = Admin::query()->where('status', 1)->first()
            ?? Admin::query()->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user available');
        }
        return $this->actingAs($admin, 'admin');
    }

    public function test_index_renders_with_seeded_entry(): void
    {
        $this->actingAdmin();

        app(JournalPoster::class)->post([
            'entry_date'      => '2026-05-08',
            'channel_id'      => 1,
            'memo'            => 'controller-test entry',
            'idempotency_key' => 'jc-test-' . uniqid(),
            'lines' => [
                ['account_code' => '1110', 'debit' => 1100, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,    'credit' => 1100],
            ],
        ]);

        $response = $this->get('/admin/accounting/journal');
        $response->assertStatus(200);
        $response->assertSeeText('controller-test entry');
    }

    public function test_filter_by_source_type_narrows_results(): void
    {
        $this->actingAdmin();

        app(JournalPoster::class)->post([
            'entry_date'      => '2026-05-08',
            'channel_id'      => 1,
            'memo'            => 'manual-only-entry',
            'source_type'     => 'manual',
            'idempotency_key' => 'jc-filter-m-' . uniqid(),
            'lines' => [
                ['account_code' => '1110', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,   'credit' => 100],
            ],
        ]);
        app(JournalPoster::class)->post([
            'entry_date'      => '2026-05-08',
            'channel_id'      => 1,
            'memo'            => 'order-only-entry',
            'source_type'     => 'order',
            'idempotency_key' => 'jc-filter-o-' . uniqid(),
            'lines' => [
                ['account_code' => '1110', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,   'credit' => 100],
            ],
        ]);

        $r = $this->get('/admin/accounting/journal?source_type=order');
        $r->assertStatus(200);
        $r->assertSeeText('order-only-entry');
        $r->assertDontSeeText('manual-only-entry');
    }

    public function test_show_renders_lines(): void
    {
        $this->actingAdmin();

        $entry = app(JournalPoster::class)->post([
            'entry_date'      => '2026-05-08',
            'channel_id'      => 1,
            'memo'            => 'show-me',
            'idempotency_key' => 'jc-show-' . uniqid(),
            'lines' => [
                ['account_code' => '1110', 'debit' => 1000, 'credit' => 0, 'memo' => 'cash-in'],
                ['account_code' => '4110', 'debit' => 0,    'credit' => 1000, 'memo' => 'sale'],
            ],
        ]);

        $r = $this->get("/admin/accounting/journal/{$entry->id}");
        $r->assertStatus(200);
        $r->assertSeeText('cash-in');
        $r->assertSeeText('sale');
        $r->assertSeeText($entry->entry_no);
    }
}
