<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\User\Models\Admin;

class StaffControllerTest extends TestCase
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
        $this->get(route('admin.accounting.staff.index'))->assertStatus(200);
    }

    public function test_create_renders(): void
    {
        $this->actingAdmin();
        $this->get(route('admin.accounting.staff.create'))
            ->assertStatus(200)
            ->assertSeeText(__('accounting::app.staff.create'));
    }

    public function test_store_auto_generates_code_and_qr_token(): void
    {
        $this->actingAdmin();
        $resp = $this->post(route('admin.accounting.staff.store'), [
            'name_ja'           => '田中太郎',
            'employment_type'   => 'part_time',
            'withholding_class' => 'A',
            'is_active'         => '1',
            'hourly_rate'       => 1200,
        ]);
        $resp->assertRedirect();

        $s = Staff::orderByDesc('id')->first();
        $this->assertSame('田中太郎', $s->name_ja);
        $this->assertNotEmpty($s->staff_code);
        $this->assertNotEmpty($s->qr_token);
        $this->assertSame(1200.0, (float) $s->hourly_rate);
    }

    public function test_destroy_deactivates_instead_of_deleting(): void
    {
        $this->actingAdmin();
        $s = Staff::create([
            'staff_code' => 'TST-' . uniqid(),
            'qr_token'   => 'tok-' . uniqid(),
            'name_ja'    => '退職者',
            'employment_type' => 'part_time',
            'is_active'  => true,
        ]);
        $this->delete(route('admin.accounting.staff.destroy', $s->id))->assertRedirect();
        $s->refresh();
        $this->assertFalse((bool) $s->is_active);
        $this->assertNotNull($s->termination_date);
    }

    public function test_qr_print_renders(): void
    {
        $this->actingAdmin();
        $s = Staff::create([
            'staff_code' => 'TST-' . uniqid(),
            'qr_token'   => 'tok-qr-' . uniqid(),
            'name_ja'    => '印刷テスト',
            'employment_type' => 'part_time',
            'is_active'  => true,
        ]);
        $this->get(route('admin.accounting.staff.qr', $s->id))
            ->assertStatus(200)
            ->assertSee($s->qr_token, false);   // token visible on print page
    }
}
