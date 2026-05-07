<?php

namespace Tests\Feature\Sagawa;

use App\Events\Sagawa\AddressCheckFailed;
use App\Events\Sagawa\AddressVerified;
use App\Jobs\Sagawa\ValidateAddressJob;
use Fujiba\SagawaDelivery\Models\Credential;
use Fujiba\SagawaDelivery\Models\Shipment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ValidateAddressJobTest extends TestCase
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

    private function makeShipment(array $address = []): Shipment
    {
        return Shipment::create([
            'order_id'         => rand(10000, 99999),
            'status'           => Shipment::STATUS_PENDING_VERIFICATION,
            'original_address' => $address ?: ['postcode' => '1000001', 'prefecture' => '東京都', 'city' => '千代田区', 'address' => '千代田1-1'],
        ]);
    }

    public function test_successful_address_check_marks_verified(): void
    {
        Event::fake([AddressVerified::class, AddressCheckFailed::class]);

        $xml = '<?xml version="1.0" encoding="UTF-8"?><checkaddressResponse><resultCode>S0-0001</resultCode></checkaddressResponse>';
        Http::fake(['*' => Http::response($xml, 200)]);

        $shipment = $this->makeShipment();
        (new ValidateAddressJob($shipment->id))->handle(app(\Fujiba\SagawaDelivery\Services\CredentialResolver::class));

        $shipment->refresh();
        $this->assertEquals(Shipment::STATUS_ADDRESS_VERIFIED, $shipment->status);
        $this->assertNotNull($shipment->address_check_at);

        Event::assertDispatched(AddressVerified::class);
        Event::assertNotDispatched(AddressCheckFailed::class);
    }

    public function test_error_result_fires_check_failed_event(): void
    {
        Event::fake([AddressVerified::class, AddressCheckFailed::class]);

        $xml = '<?xml version="1.0" encoding="UTF-8"?><checkaddressResponse><resultCode>E2-0001</resultCode></checkaddressResponse>';
        Http::fake(['*' => Http::response($xml, 200)]);

        $shipment = $this->makeShipment();
        (new ValidateAddressJob($shipment->id))->handle(app(\Fujiba\SagawaDelivery\Services\CredentialResolver::class));

        $shipment->refresh();
        $this->assertEquals(Shipment::STATUS_PENDING_VERIFICATION, $shipment->status);

        Event::assertNotDispatched(AddressVerified::class);
        Event::assertDispatched(AddressCheckFailed::class, function ($e) {
            return $e->resultCode === 'E2-0001';
        });
    }

    public function test_missing_address_fires_check_failed(): void
    {
        Event::fake([AddressCheckFailed::class]);

        $shipment = $this->makeShipment(['postcode' => '', 'city' => '']);

        (new ValidateAddressJob($shipment->id))->handle(app(\Fujiba\SagawaDelivery\Services\CredentialResolver::class));

        Event::assertDispatched(AddressCheckFailed::class, function ($e) {
            return str_contains($e->message, 'missing');
        });
    }

    public function test_skips_if_status_already_advanced(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $shipment = $this->makeShipment();
        $shipment->update(['status' => Shipment::STATUS_WAYBILL_ISSUED]);

        (new ValidateAddressJob($shipment->id))->handle(app(\Fujiba\SagawaDelivery\Services\CredentialResolver::class));

        Http::assertNothingSent();
    }
}
