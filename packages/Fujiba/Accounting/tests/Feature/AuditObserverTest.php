<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\AuditLog;
use Fujiba\Accounting\Models\FiscalPeriod;
use Fujiba\Accounting\Models\JournalEntry;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuditObserverTest extends TestCase
{
    use DatabaseTransactions;

    public function test_creating_a_journal_entry_writes_an_audit_row(): void
    {
        $period = FiscalPeriod::where('status', 'open')->first();
        $this->assertNotNull($period, 'A seeded open fiscal period is required');

        $before = AuditLog::count();

        $entry = JournalEntry::create([
            'fiscal_period_id'        => $period->id,
            'entry_date'              => now()->toDateString(),
            'channel_id'              => null,
            'source_type'             => 'manual',
            'source_idempotency_key'  => 'test-' . uniqid(),
            'memo'                    => 'AuditObserverTest',
            'status'                  => 'draft',
        ]);

        $after = AuditLog::count();
        $this->assertSame($before + 1, $after, 'Audit row not written on create');

        $row = AuditLog::orderByDesc('id')->first();
        $this->assertSame('fujiba_journal_entries', $row->table_name);
        $this->assertSame((int) $entry->id, (int) $row->record_id);
        $this->assertSame('created', $row->action);
        $this->assertIsArray($row->after_json);
        $this->assertSame('AuditObserverTest', $row->after_json['memo'] ?? null);
    }

    public function test_audit_log_rows_are_immutable(): void
    {
        $period = FiscalPeriod::where('status', 'open')->first();
        JournalEntry::create([
            'fiscal_period_id'        => $period->id,
            'entry_date'              => now()->toDateString(),
            'source_type'             => 'manual',
            'source_idempotency_key'  => 'immut-' . uniqid(),
            'memo'                    => 'immutability check',
            'status'                  => 'draft',
        ]);

        $row = AuditLog::orderByDesc('id')->first();

        $this->expectException(\RuntimeException::class);
        $row->user_name = 'tampered';
        $row->save();
    }
}
