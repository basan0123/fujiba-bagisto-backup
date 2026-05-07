<?php

namespace Tests\Feature\Sagawa;

use App\Jobs\Sagawa\IssueWaybillJob;
use App\Jobs\Sagawa\PollWaybillPdfJob;
use App\Jobs\Sagawa\ValidateAddressJob;
use Fujiba\SagawaDelivery\Models\Credential;
use Fujiba\SagawaDelivery\Models\Shipment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShipmentQueueControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCredential(): Credential
    {
        return Credential::create([
            'tenant_id'       => 'test',
            'channel_id'      => 1,
            'channel_code'    => 'default',
            'api_customer_id' => 'CID001',
            'api_password'    => 'PASS',
            'use_sandbox'     => true,
            'enabled'         => true,
        ]);
    }

    private function makeShipment(string $status = Shipment::STATUS_PENDING_VERIFICATION, array $extra = []): Shipment
    {
        return Shipment::create(array_merge([
            'order_id'         => rand(10000, 99999),
            'status'           => $status,
            'original_address' => ['postcode' => '1000001', 'prefecture' => '東京都', 'city' => '千代田区', 'address' => '千代田1-1'],
        ], $extra));
    }

    private function adminUser()
    {
        return \DB::table('admins')->first()
            ?? throw new \RuntimeException('No admin user found for testing. Seed one first.');
    }

    public function test_index_returns_paginated_shipments(): void
    {
        $this->makeShipment();
        $this->makeShipment(Shipment::STATUS_WAYBILL_ISSUED);

        $admin = $this->adminUser();
        $response = $this->actingAs($admin, 'admin')
            ->getJson('/admin/sagawa/');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_index_filters_by_status(): void
    {
        $this->makeShipment();
        $this->makeShipment(Shipment::STATUS_WAYBILL_ISSUED);

        $admin = $this->adminUser();
        $response = $this->actingAs($admin, 'admin')
            ->getJson('/admin/sagawa/?status=waybill_issued');

        $response->assertOk();
        foreach ($response->json('data') as $item) {
            $this->assertEquals('waybill_issued', $item['status']);
        }
    }

    public function test_show_returns_single_shipment(): void
    {
        $s = $this->makeShipment();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'admin')
            ->getJson("/admin/sagawa/shipments/{$s->id}");

        $response->assertOk()
            ->assertJson(['id' => $s->id]);
    }

    public function test_address_check_dispatches_job(): void
    {
        Bus::fake([ValidateAddressJob::class]);
        $s = $this->makeShipment();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'admin')
            ->postJson("/admin/sagawa/shipments/{$s->id}/address-check");

        $response->assertOk()
            ->assertJson(['status' => 'queued']);

        Bus::assertDispatched(ValidateAddressJob::class, fn ($job) => $job->shipmentId === $s->id);
    }

    public function test_address_check_rejects_wrong_status(): void
    {
        $s = $this->makeShipment(Shipment::STATUS_WAYBILL_ISSUED);
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'admin')
            ->postJson("/admin/sagawa/shipments/{$s->id}/address-check");

        $response->assertStatus(422);
    }

    public function test_verify_approves_and_dispatches_waybill_job(): void
    {
        Bus::fake([IssueWaybillJob::class]);
        $s = $this->makeShipment();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'admin')
            ->postJson("/admin/sagawa/shipments/{$s->id}/verify", [
                'corrected_address' => ['postcode' => '1000001', 'prefecture' => '東京都', 'city' => '千代田区', 'address' => '千代田1-1-1'],
            ]);

        $response->assertOk()->assertJson(['status' => 'verified']);

        $s->refresh();
        $this->assertEquals(Shipment::STATUS_ADDRESS_VERIFIED, $s->status);
        $this->assertEquals((int) $admin->id, $s->verified_by);

        Bus::assertDispatched(IssueWaybillJob::class);
    }

    public function test_reject_requires_reason(): void
    {
        $s = $this->makeShipment();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'admin')
            ->postJson("/admin/sagawa/shipments/{$s->id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_reject_sets_status_and_reason(): void
    {
        $s = $this->makeShipment();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'admin')
            ->postJson("/admin/sagawa/shipments/{$s->id}/reject", [
                'reason' => '住所不正確',
            ]);

        $response->assertOk()->assertJson(['status' => 'rejected']);

        $s->refresh();
        $this->assertEquals(Shipment::STATUS_ADDRESS_REJECTED, $s->status);
        $this->assertEquals('住所不正確', $s->rejection_reason);
    }

    public function test_reissue_fails_when_window_expired(): void
    {
        Http::fake();
        $this->makeCredential();

        $s = $this->makeShipment(Shipment::STATUS_WAYBILL_ISSUED, [
            'tracking_number'   => '1234567890',
            'waybill_issued_at' => now()->subHours(100),
        ]);
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'admin')
            ->postJson("/admin/sagawa/shipments/{$s->id}/reissue");

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Reissue window (72h) has expired.']);
    }

    public function test_reissue_succeeds_within_window(): void
    {
        Bus::fake([PollWaybillPdfJob::class]);

        $sagawaXml = '<?xml version="1.0" encoding="UTF-8"?><retryprintResponse><resultCode>S0-0001</resultCode><printRequestId>PR-999</printRequestId></retryprintResponse>';
        Http::fake(['*' => Http::response($sagawaXml, 200)]);

        $this->makeCredential();
        $s = $this->makeShipment(Shipment::STATUS_WAYBILL_ISSUED, [
            'tracking_number'   => '1234567890',
            'waybill_issued_at' => now()->subHours(10),
        ]);
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'admin')
            ->postJson("/admin/sagawa/shipments/{$s->id}/reissue");

        $response->assertOk()
            ->assertJson(['status' => 'reissue_requested', 'print_request_id' => 'PR-999']);

        $s->refresh();
        $this->assertEquals(Shipment::STATUS_REISSUE_REQUESTED, $s->status);
        $this->assertEquals('PR-999', $s->print_request_id);
    }
}
