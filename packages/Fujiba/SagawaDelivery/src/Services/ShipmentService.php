<?php

namespace Fujiba\SagawaDelivery\Services;

use Fujiba\SagawaDelivery\DTO\SagawaShipmentPayload;
use Fujiba\SagawaDelivery\Models\Shipment;

class ShipmentService
{
    public function __construct(
        private SagawaShipmentBuilder $builder,
        private ?CredentialResolver $credentials = null,
    ) {}

    private function client(): SagawaClient
    {
        $resolver = $this->credentials ?? app(CredentialResolver::class);

        return new SagawaClient($resolver->forCurrent());
    }

    /**
     * Validate an address via Sagawa checkaddress. Billing-neutral.
     *
     * @return array{result_code:?string, response:string, http_status:int, parsed:array<string,mixed>}
     */
    public function verifyAddress(string $postcode, string $address): array
    {
        return $this->client()->checkAddress($postcode, $address);
    }

    /**
     * Issue a waybill for a shipment. Updates status based on the response.
     *
     * @return array{result_code:?string, response:string, http_status:int, parsed:array<string,mixed>}
     */
    public function issueWaybill(Shipment $shipment, bool $printOut = true): array
    {
        $payload = $this->buildPayload($shipment);

        $fields = array_merge(
            array_filter($payload->toArray(), fn ($v) => $v !== null && $v !== '' && ! is_array($v)),
            ['printOutFlg' => $printOut ? '1' : '0']
        );

        $result = $this->client()->issueWaybill($fields);

        $code = (string) ($result['result_code'] ?? '');

        if (str_starts_with($code, 'S')) {
            $tracking = $this->extractTracking($result['parsed']);
            $tracking ? $this->markShipped($shipment, $tracking, $result['parsed']) : $this->markReady($shipment);
        } else {
            $this->markFailed($shipment, $result['parsed']);
        }

        return $result;
    }

    /** @param  array<string,mixed>  $parsed */
    private function extractTracking(array $parsed): ?string
    {
        $found = null;
        array_walk_recursive($parsed, function ($value, $key) use (&$found) {
            if (! $found && is_string($key) && strtolower($key) === 'toiawasenumber' && is_string($value)) {
                $found = $value;
            }
        });

        return $found;
    }

    /**
     * Create (or update) the shipment row for an order. Idempotent on order_id.
     *
     * @param  object|array  $order  Fujiba order model or array with the same keys
     */
    public function createFromOrder(object|array $order): Shipment
    {
        $data = $this->normalizeOrder($order);

        return Shipment::updateOrCreate(
            ['order_id' => $data['id']],
            [
                'channel_id'       => $data['channel_id'] ?? null,
                'delivery_date'    => $data['delivery_date'] ?? null,
                'delivery_time'    => $data['delivery_time'] ?? null,
                'original_address' => $data['shipping_address'] ?? null,
                'status'           => Shipment::STATUS_PENDING_VERIFICATION,
            ]
        );
    }

    /**
     * Build the Sagawa payload for a shipment. Pure — does not hit the API.
     */
    public function buildPayload(Shipment $shipment): SagawaShipmentPayload
    {
        $order = $shipment->order_id ? $this->loadOrder($shipment->order_id) : [];

        return $this->builder->build(array_merge($order, [
            'id'            => $shipment->order_id,
            'delivery_date' => optional($shipment->delivery_date)->format('Y-m-d'),
            'delivery_time' => $shipment->delivery_time,
        ]));
    }

    public function markReady(Shipment $shipment): Shipment
    {
        return $shipment->markWaybillPending();
    }

    public function markShipped(Shipment $shipment, string $trackingNumber, array $rawResponse = []): Shipment
    {
        $shipment->update(['raw_response' => $rawResponse ?: $shipment->raw_response]);

        return $shipment->markWaybillIssued($trackingNumber);
    }

    public function markFailed(Shipment $shipment, array $rawResponse): Shipment
    {
        $shipment->update(['raw_response' => $rawResponse]);
        $reason = $rawResponse['resultCode'] ?? $rawResponse['error'] ?? 'unknown';

        return $shipment->markWaybillFailed((string) $reason);
    }

    /** @return array<string,mixed> */
    private function normalizeOrder(object|array $order): array
    {
        if (is_array($order)) {
            return $order;
        }

        return [
            'id'               => $order->id ?? null,
            'tenant_id'        => $order->tenant_id ?? (function_exists('tenant') ? tenant('id') : null),
            'channel_id'       => $order->channel_id ?? null,
            'delivery_date'    => $order->delivery_date ?? null,
            'delivery_time'    => $order->delivery_time ?? null,
            'shipping_address' => $this->addressToArray($order->shipping_address ?? null),
        ];
    }

    private function addressToArray(mixed $addr): array
    {
        if (! $addr) {
            return [];
        }

        if (is_array($addr)) {
            return $addr;
        }

        if (method_exists($addr, 'toArray')) {
            return $addr->toArray();
        }

        return (array) $addr;
    }

    /** @return array<string,mixed> */
    private function loadOrder(int $orderId): array
    {
        $order = \DB::table('orders')->where('id', $orderId)->first();

        if (! $order) {
            return [];
        }

        $shipping = \DB::table('addresses')
            ->where('order_id', $orderId)
            ->where('address_type', 'order_shipping')
            ->first();

        return [
            'id'               => $order->id,
            'channel_id'       => $order->channel_id ?? null,
            'tenant_id'        => function_exists('tenant') ? tenant('id') : null,
            'delivery_date'    => $order->delivery_date ?? null,
            'delivery_time'    => $order->delivery_time ?? null,
            'shipping_address' => $shipping ? (array) $shipping : [],
        ];
    }
}
