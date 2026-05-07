<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Tests\TestCase;
use Webkul\User\Models\Admin;

class DashboardTest extends TestCase
{
    public function test_admin_dashboard_renders(): void
    {
        $admin = Admin::query()->where('status', 1)->first()
            ?? Admin::query()->first();

        if (! $admin) {
            $this->markTestSkipped('No admin user seeded; run installer first.');
        }

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/accounting');

        $response->assertStatus(200);
        $response->assertSeeText('Hello 経理');
    }
}
