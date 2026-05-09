<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class KioskPinTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeStaff(?string $pin = null): Staff
    {
        $s = Staff::create([
            'staff_code' => 'PIN-' . uniqid(),
            'qr_token'   => 'pin-' . uniqid(),
            'name_ja'    => 'PIN テスト',
            'employment_type' => 'part_time',
            'is_active'  => true,
        ]);
        if ($pin !== null) {
            $s->pin = $pin;
            $s->save();
        }
        return $s->fresh();
    }

    public function test_staff_without_pin_punches_immediately(): void
    {
        $s = $this->makeStaff(null);
        $resp = $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token]);
        $resp->assertStatus(200);
        $resp->assertJsonPath('ok', true);
        $this->assertNotNull(Timecard::where('staff_id', $s->id)->first());
    }

    public function test_staff_with_pin_requires_pin(): void
    {
        $s = $this->makeStaff('1234');
        $resp = $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token]);
        $resp->assertStatus(401);
        $resp->assertJsonPath('pin_required', true);
        $this->assertNull(Timecard::where('staff_id', $s->id)->first(),
            'No timecard should be created until PIN is supplied');
    }

    public function test_correct_pin_punches(): void
    {
        $s = $this->makeStaff('5678');
        $resp = $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token, 'pin' => '5678']);
        $resp->assertStatus(200);
        $resp->assertJsonPath('ok', true);
        $this->assertNotNull(Timecard::where('staff_id', $s->id)->first());
    }

    public function test_wrong_pin_is_401(): void
    {
        $s = $this->makeStaff('9999');
        $resp = $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token, 'pin' => '0000']);
        $resp->assertStatus(401);
        $resp->assertJsonPath('pin_required', true);
        $this->assertNull(Timecard::where('staff_id', $s->id)->first());
    }

    public function test_idempotency_window_still_works_after_pin_gate(): void
    {
        $s = $this->makeStaff('1111');
        $first = $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token, 'pin' => '1111']);
        $first->assertStatus(200);

        $second = $this->postJson(route('accounting.kiosk.punch'), ['token' => $s->qr_token, 'pin' => '1111']);
        $second->assertStatus(200);
        $second->assertJsonPath('idempotent', true);
        $this->assertSame(1, Timecard::where('staff_id', $s->id)->count());
    }

    public function test_pin_hash_is_hidden_from_array_serialization(): void
    {
        $s = $this->makeStaff('4321');
        $arr = $s->toArray();
        $this->assertArrayNotHasKey('pin_hash', $arr);
    }

    public function test_pin_mutator_does_not_clobber_existing_when_blank(): void
    {
        $s = $this->makeStaff('9876');
        $hashBefore = $s->pin_hash;

        // Simulate edit form with pin field empty — must NOT overwrite the hash.
        $s->pin = '';
        $s->save();
        $s->refresh();

        $this->assertSame($hashBefore, $s->pin_hash);
        $this->assertTrue($s->checkPin('9876'));
    }
}
