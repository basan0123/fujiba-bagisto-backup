<?php

namespace Fujiba\SagawaDelivery\Services;

use Fujiba\SagawaDelivery\DTO\SagawaShipmentPayload;

/**
 * Builds a SagawaShipmentPayload from an order-like source.
 *
 * The source can be a Fujiba EC Order, a POS sale, or any array with the
 * same shape — that's why the input is array-based, not a specific model.
 * Reusable across EC and POS.
 */
class SagawaShipmentBuilder
{
    /**
     * @param  array<string,mixed>  $sender      keys: name, postcode, prefecture, city, address, phone
     * @param  array{ okuri_code?: string, binsyu_code?: string }  $options
     * @param  array<string,mixed>  $credential  per-tenant Sagawa contract; supported key: daibiki_payment_type ('0' どちらでも | '1' 現金 | '2' カード)
     */
    public function __construct(
        private array $sender,
        private array $options = [],
        private array $credential = [],
    ) {}

    /**
     * @param  array<string,mixed>  $order  keys: id, shipping_address, delivery_date (Y-m-d), delivery_time, haiso_kosu?
     */
    public function build(array $order): SagawaShipmentPayload
    {
        $ship = $order['shipping_address'] ?? [];

        $binsyuCode = $this->options['binsyu_code'] ?? '000';

        $isCod      = ($order['payment_method'] ?? null) === 'cashondelivery';
        $daibikiFlg = $isCod ? '1' : '0';
        $daibikiKng = $isCod ? (string) (int) ($order['grand_total'] ?? 0) : null;
        $daibikiTax = $isCod ? (string) (int) ($order['tax_amount']   ?? 0) : null;
        $daibikiType = $isCod
            ? ($this->credential['daibiki_payment_type'] ?? null)
            : null;

        return new SagawaShipmentPayload(
            userManageNumber: $this->userManageNumber($order),
            okuriCode:        $this->options['okuri_code']  ?? config('sagawa.default_okuricode', 'A501'),
            binsyuCode:       $binsyuCode,
            haisoKosu:        (int) ($order['haiso_kosu'] ?? 1),

            otodokeYubin: $this->normalizePostcode($ship['postcode'] ?? ''),
            otodokeAdd1:  (string) ($ship['state'] ?? $ship['prefecture'] ?? ''),
            otodokeAdd2:  (string) ($ship['city'] ?? ''),
            otodokeAdd3:  trim(($ship['address1'] ?? '').' '.($ship['address2'] ?? '')),
            otodokeNm1:   $this->consigneeName($ship),
            otodokeTel:   (string) ($ship['phone'] ?? $ship['telephone'] ?? ''),

            iraiYubin: $this->normalizePostcode((string) ($this->sender['postcode'] ?? '')),
            iraiAdd1:  (string) ($this->sender['prefecture'] ?? ''),
            iraiAdd2:  (string) ($this->sender['city'] ?? ''),
            iraiAdd3:  (string) ($this->sender['address'] ?? ''),
            iraiNm1:   (string) ($this->sender['name'] ?? ''),
            iraiTel:   (string) ($this->sender['phone'] ?? ''),

            shiteiDate:     $this->formatDate($order['delivery_date'] ?? null),
            shiteiTimeCode: $this->mapTimeCode($order['delivery_time'] ?? null),

            daibikiFlg:  $daibikiFlg,
            daibikiKng:  $daibikiKng,
            daibikiTax:  $daibikiTax,
            daibikiType: $daibikiType,

            meta: array_filter([
                'order_id'   => $order['id']         ?? null,
                'tenant_id'  => $order['tenant_id']  ?? null,
                'channel_id' => $order['channel_id'] ?? null,
            ]),
        );
    }

    private function userManageNumber(array $order): string
    {
        $tenant = (string) ($order['tenant_id'] ?? 'x');
        $id     = (string) ($order['id'] ?? '');

        return substr($tenant, 0, 8).'-'.$id;
    }

    private function normalizePostcode(string $raw): string
    {
        return preg_replace('/[^0-9]/', '', $raw) ?? '';
    }

    private function consigneeName(array $ship): string
    {
        $parts = array_filter([
            $ship['last_name']  ?? null,
            $ship['first_name'] ?? null,
        ]);

        return $parts ? implode(' ', $parts) : (string) ($ship['name'] ?? '');
    }

    private function formatDate(mixed $date): ?string
    {
        if (! $date) {
            return null;
        }

        return str_replace('-', '', substr((string) $date, 0, 10));
    }

    /** Map our slot keys to Sagawa's shiteiTimeCode. */
    private function mapTimeCode(mixed $slot): ?string
    {
        if (! $slot) {
            return null;
        }

        return match ((string) $slot) {
            'am'   => 'am',
            '1214' => '12',
            '1416' => '14',
            '1618' => '16',
            '1820' => '18',
            '1921' => '19',
            default => null,
        };
    }
}
