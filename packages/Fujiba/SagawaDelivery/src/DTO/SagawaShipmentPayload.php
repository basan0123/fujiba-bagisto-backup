<?php

namespace Fujiba\SagawaDelivery\DTO;

/**
 * Structured, API-agnostic representation of a Sagawa waybill request.
 * The HTTP/XML wrapper will be added in the client layer — this is pure data.
 */
final class SagawaShipmentPayload
{
    /**
     * @param  array<string,mixed>  $meta  free-form (order id, tenant id, channel id)
     */
    public function __construct(
        public readonly string $userManageNumber,
        public readonly string $okuriCode,        // A501 | A401 | L02
        public readonly string $binsyuCode,       // 000 | 030 | 140 | 150
        public readonly int    $haisoKosu,        // package count
        public readonly string $otodokeYubin,     // 7 digits, no hyphen
        public readonly string $otodokeAdd1,      // prefecture
        public readonly string $otodokeAdd2,      // city/ward
        public readonly string $otodokeAdd3,      // street + building
        public readonly string $otodokeNm1,       // consignee name
        public readonly string $otodokeTel,
        public readonly string $iraiYubin,        // sender
        public readonly string $iraiAdd1,
        public readonly string $iraiAdd2,
        public readonly string $iraiAdd3,
        public readonly string $iraiNm1,
        public readonly string $iraiTel,
        public readonly ?string $shiteiDate,      // yyyyMMdd or null
        public readonly ?string $shiteiTimeCode,  // am/12/14/16/18/19 or null
        public readonly ?string $daibikiKng = null, // COD amount in JPY (string of integer); null = prepaid
        public readonly ?string $daibikiNm  = null, // COD payee name; null = prepaid
        public readonly array  $meta = [],
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'userManageNumber' => $this->userManageNumber,
            'okuriCode'        => $this->okuriCode,
            'binsyuCode'       => $this->binsyuCode,
            'haisoKosu'        => $this->haisoKosu,
            'otodokeYubin'     => $this->otodokeYubin,
            'otodokeAdd1'      => $this->otodokeAdd1,
            'otodokeAdd2'      => $this->otodokeAdd2,
            'otodokeAdd3'      => $this->otodokeAdd3,
            'otodokeNm1'       => $this->otodokeNm1,
            'otodokeTel'       => $this->otodokeTel,
            'iraiYubin'        => $this->iraiYubin,
            'iraiAdd1'         => $this->iraiAdd1,
            'iraiAdd2'         => $this->iraiAdd2,
            'iraiAdd3'         => $this->iraiAdd3,
            'iraiNm1'          => $this->iraiNm1,
            'iraiTel'          => $this->iraiTel,
            'shiteiDate'       => $this->shiteiDate,
            'shiteiTimeCode'   => $this->shiteiTimeCode,
            'daibikiKng'       => $this->daibikiKng,
            'daibikiNm'        => $this->daibikiNm,
            'meta'             => $this->meta,
        ];
    }
}
