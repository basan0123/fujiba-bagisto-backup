<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TimecardKioskTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeStaff(array $overrides = []): Staff
    {
        return Staff::create(array_merge([
            'staff_code' => 'KSK-' . uniqid(),
            'qr_token'   => 'kiosk-' . uniqid(),
            'name_ja'    => 'キオスクテスト',
            'employment_type' => 'part_time',
            'is_active'  => true,
        ], $overrides));
    }

    public function test_index_loads_without_auth(): void
    {
        $this->get(route('accounting.kiosk.index'))->assertStatus(200);
    }

    public function test_punch_in_creates_timecard_with_clock_in(): void
    {
        $s = $this->makeStaff();

        $resp = $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token]);
        $resp->assertStatus(200);
        $resp->assertJsonPath('ok', true);
        $resp->assertJsonPath('idempotent', false);

        $card = Timecard::where('staff_id', $s->id)->first();
        $this->assertNotNull($card);
        $this->assertNotNull($card->clock_in);
        $this->assertNull($card->clock_out);
        $this->assertSame('open', $card->status);
    }

    public function test_punch_out_closes_today_card(): void
    {
        $s = $this->makeStaff();

        Timecard::create([
            'staff_id'   => $s->id,
            'work_date'  => now()->toDateString(),
            'clock_in'   => now()->subHours(6),
            'channel_id' => $s->channel_id,
            'status'     => 'open',
            // Force updated_at into the past so we don't trip the 30 s idempotency window.
            'updated_at' => now()->subMinutes(5),
        ]);

        $resp = $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token]);
        $resp->assertStatus(200);

        $card = Timecard::where('staff_id', $s->id)->first();
        $this->assertNotNull($card->clock_out);
        $this->assertSame('closed', $card->status);
        $this->assertGreaterThan(0, $card->work_minutes);
    }

    public function test_double_scan_within_30s_is_idempotent(): void
    {
        $s = $this->makeStaff();

        $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token])->assertOk();
        $resp = $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token]);

        $resp->assertStatus(200);
        $resp->assertJsonPath('idempotent', true);

        $count = Timecard::where('staff_id', $s->id)->count();
        $this->assertSame(1, $count);
    }

    public function test_unknown_token_is_404(): void
    {
        $resp = $this->postJson(route('accounting.kiosk.punch'), ['token' => 'no-such-token-' . uniqid()]);
        $resp->assertStatus(404);
        $resp->assertJsonPath('ok', false);
    }

    public function test_inactive_staff_is_403(): void
    {
        $s = $this->makeStaff(['is_active' => false]);
        $resp = $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token]);
        $resp->assertStatus(403);
        $resp->assertJsonPath('ok', false);
    }

    public function test_empty_token_is_422(): void
    {
        $resp = $this->postJson(route('accounting.kiosk.punch'), ['token' => '']);
        $resp->assertStatus(422);
    }
}
