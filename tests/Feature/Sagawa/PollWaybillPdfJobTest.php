<?php

namespace Tests\Feature\Sagawa;

use App\Events\Sagawa\WaybillFailed;
use App\Events\Sagawa\WaybillIssued;
use App\Jobs\Sagawa\PollWaybillPdfJob;
use Fujiba\SagawaDelivery\Models\Credential;
use Fujiba\SagawaDelivery\Models\Shipment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PollWaybillPdfJobTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Credential::create([
            'tenant_id'       => null,
            'channel_id'      => null,
            'channel_code'    => 'default',
            'api_customer_id' => 'CID',
            'api_password'    => 'PASS',
            'use_sandbox'     => true,
            'enabled'         => true,
        ]);
    }

    private function makeShipment(int $attempts = 0): Shipment
    {
        return Shipment::create([
            'order_id'          => rand(10000, 99999),
            'status'            => Shipment::STATUS_WAYBILL_PENDING,
            'print_request_id'  => 'PR-001',
            'tracking_number'   => '9876543210',
            'pdf_poll_attempts' => $attempts,
        ]);
    }

    public function test_pdf_ready_on_second_attempt(): void
    {
        Event::fake([WaybillIssued::class, WaybillFailed::class]);
        Bus::fake([PollWaybillPdfJob::class]);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<checkfileResponse><resultCode>S0-0001</resultCode>'
            . '<item><printRequestId>PR-001</printRequestId><url>https://example.com/pdf/123.pdf</url></item>'
            . '</checkfileResponse>';

        Http::fake(['*' => Http::response($xml, 200)]);

        $shipment = $this->makeShipment(attempts: 1);

        (new PollWaybillPdfJob($shipment->id))->handle(app(\Fujiba\SagawaDelivery\Services\CredentialResolver::class));

        $shipment->refresh();
        $this->assertEquals(Shipment::STATUS_WAYBILL_ISSUED, $shipment->status);
        $this->assertEquals('https://example.com/pdf/123.pdf', $shipment->pdf_url);
        $this->assertNotNull($shipment->waybill_issued_at);
        $this->assertEquals(2, $shipment->pdf_poll_attempts);

        Event::assertDispatched(WaybillIssued::class);
        Event::assertNotDispatched(WaybillFailed::class);
        Bus::assertNotDispatched(PollWaybillPdfJob::class);
    }

    public function test_pdf_not_ready_reschedules(): void
    {
        Event::fake([WaybillIssued::class, WaybillFailed::class]);
        Bus::fake([PollWaybillPdfJob::class]);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<checkfileResponse><resultCode>S0-0001</resultCode>'
            . '<item><printRequestId>PR-001</printRequestId></item>'
            . '</checkfileResponse>';

        Http::fake(['*' => Http::response($xml, 200)]);

        $shipment = $this->makeShipment(attempts: 0);

        (new PollWaybillPdfJob($shipment->id))->handle(app(\Fujiba\SagawaDelivery\Services\CredentialResolver::class));

        $shipment->refresh();
        $this->assertEquals(Shipment::STATUS_WAYBILL_PENDING, $shipment->status);
        $this->assertEquals(1, $shipment->pdf_poll_attempts);

        Event::assertNotDispatched(WaybillIssued::class);
        Bus::assertDispatched(PollWaybillPdfJob::class);
    }

    public function test_max_attempts_exceeded_marks_failed(): void
    {
        Event::fake([WaybillFailed::class, WaybillIssued::class]);
        Bus::fake([PollWaybillPdfJob::class]);

        config(['sagawa.pdf_poll_max_attempts' => 3]);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<checkfileResponse><resultCode>S0-0001</resultCode></checkfileResponse>';
        Http::fake(['*' => Http::response($xml, 200)]);

        $shipment = $this->makeShipment(attempts: 3);

        (new PollWaybillPdfJob($shipment->id))->handle(app(\Fujiba\SagawaDelivery\Services\CredentialResolver::class));

        $shipment->refresh();
        $this->assertEquals(Shipment::STATUS_WAYBILL_FAILED, $shipment->status);
        $this->assertStringContains('max attempts', $shipment->waybill_failed_reason);

        Event::assertDispatched(WaybillFailed::class);
        Event::assertNotDispatched(WaybillIssued::class);
        Bus::assertNotDispatched(PollWaybillPdfJob::class);
    }

    public function test_missing_print_request_id_fails_immediately(): void
    {
        Event::fake([WaybillFailed::class]);

        $shipment = Shipment::create([
            'order_id'          => rand(10000, 99999),
            'status'            => Shipment::STATUS_WAYBILL_PENDING,
            'print_request_id'  => null,
            'pdf_poll_attempts' => 0,
        ]);

        (new PollWaybillPdfJob($shipment->id))->handle(app(\Fujiba\SagawaDelivery\Services\CredentialResolver::class));

        $shipment->refresh();
        $this->assertEquals(Shipment::STATUS_WAYBILL_FAILED, $shipment->status);

        Event::assertDispatched(WaybillFailed::class);
    }

    private function assertStringContains(string $needle, ?string $haystack): void
    {
        $this->assertNotNull($haystack);
        $this->assertTrue(str_contains($haystack, $needle), "Expected '{$haystack}' to contain '{$needle}'");
    }
}
