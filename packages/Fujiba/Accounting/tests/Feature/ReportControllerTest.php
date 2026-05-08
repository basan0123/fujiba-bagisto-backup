<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\User\Models\Admin;

class ReportControllerTest extends TestCase
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

    public function test_trial_balance_html_renders(): void
    {
        $this->actingAdmin();
        $this->get(route('admin.accounting.reports.trial_balance', ['year_month' => '2026-04']))
            ->assertStatus(200)
            ->assertSeeText(__('accounting::app.reports.trial_balance'));
    }

    public function test_trial_balance_csv_streams(): void
    {
        $this->actingAdmin();
        $resp = $this->get(route('admin.accounting.reports.trial_balance', ['year_month' => '2026-04', 'format' => 'csv']));
        $resp->assertStatus(200);
        $resp->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $body = $resp->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'CSV must have UTF-8 BOM');
        $this->assertStringContainsString('code,name_ja', $body);
    }

    public function test_pl_html_renders(): void
    {
        $this->actingAdmin();
        $this->get(route('admin.accounting.reports.pl', ['year_month' => '2026-04']))
            ->assertStatus(200)
            ->assertSeeText(__('accounting::app.reports.pl_pretax'));
    }

    public function test_bs_html_renders_with_as_of(): void
    {
        $this->actingAdmin();
        $this->get(route('admin.accounting.reports.bs', ['as_of' => '2026-04-30']))
            ->assertStatus(200)
            ->assertSeeText(__('accounting::app.reports.bs_total_assets'));
    }

    public function test_journal_book_pdf_format_is_rejected(): void
    {
        $this->actingAdmin();
        // Disable global exception handling so abort(400) propagates as the HTTP status
        // (Bagisto's default handler renders an HTML 200 with the debugbar in dev).
        $this->withoutExceptionHandling();

        try {
            $this->get(route('admin.accounting.reports.journal', ['from' => '2026-04-01', 'to' => '2026-04-30', 'format' => 'pdf']));
            $this->fail('Expected an HttpException for format=pdf on the journal book');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(400, $e->getStatusCode());
        }
    }

    public function test_journal_book_csv_streams(): void
    {
        $this->actingAdmin();
        $resp = $this->get(route('admin.accounting.reports.journal', ['from' => '2026-04-01', 'to' => '2026-04-30', 'format' => 'csv']));
        $resp->assertStatus(200);
        $body = $resp->streamedContent();
        $this->assertStringContainsString('entry_no,entry_date', $body);
    }

    public function test_ledger_requires_account_code(): void
    {
        $this->actingAdmin();
        $resp = $this->get(route('admin.accounting.reports.ledger', ['from' => '2026-04-01', 'to' => '2026-04-30']));
        $resp->assertSessionHasErrors('account_code');
    }

    public function test_ledger_html_renders(): void
    {
        $this->actingAdmin();
        $this->get(route('admin.accounting.reports.ledger', ['account_code' => '1110', 'from' => '2026-04-01', 'to' => '2026-04-30']))
            ->assertStatus(200)
            ->assertSeeText('現金');
    }

    public function test_dashboard_renders_with_widget(): void
    {
        $this->actingAdmin();
        $this->get(route('admin.accounting.dashboard.index'))
            ->assertStatus(200)
            ->assertSeeText(__('accounting::app.reports.dash_this_month'));
    }
}
