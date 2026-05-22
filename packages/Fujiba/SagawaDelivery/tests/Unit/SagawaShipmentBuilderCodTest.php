<?php

use Fujiba\SagawaDelivery\Services\SagawaShipmentBuilder;

function sagawaSender(): array
{
    return [
        'name'       => 'Ｆｕｊｉｂａ合同会社',
        'postcode'   => '1160014',
        'prefecture' => '東京都',
        'city'       => '荒川区',
        'address'    => '東日暮里6-28-5 サカイリグリンパーク402',
        'phone'      => '07042170028',
    ];
}

function sagawaOrder(array $overrides = []): array
{
    return array_merge([
        'id'             => 731,
        'tenant_id'      => 'kimono',
        'channel_id'     => 1,
        'grand_total'    => 8800,
        'tax_amount'     => 800,
        'payment_method' => 'moneytransfer',
        'shipping_address' => [
            'postcode'   => '1000001',
            'state'      => '東京都',
            'city'       => '千代田区',
            'address1'   => '千代田1-1',
            'first_name' => '太郎',
            'last_name'  => '山田',
            'phone'      => '09011112222',
        ],
        'delivery_date' => '2026-05-10',
        'delivery_time' => 'am',
    ], $overrides);
}

it('sets daibikiFlg=0 and null daibiki fields for prepaid orders', function () {
    $payload = (new SagawaShipmentBuilder(sagawaSender()))->build(sagawaOrder());

    expect($payload->daibikiFlg)->toBe('0');
    expect($payload->daibikiKng)->toBeNull();
    expect($payload->daibikiTax)->toBeNull();
    expect($payload->daibikiType)->toBeNull();
    expect($payload->binsyuCode)->toBe('000');
});

it('sets daibikiFlg=1 with grand_total and tax_amount for COD orders', function () {
    $payload = (new SagawaShipmentBuilder(sagawaSender()))
        ->build(sagawaOrder(['payment_method' => 'cashondelivery']));

    expect($payload->daibikiFlg)->toBe('1');
    expect($payload->daibikiKng)->toBe('8800');
    expect($payload->daibikiTax)->toBe('800');
});

it('passes daibikiType from credential when configured', function () {
    $credential = ['daibiki_payment_type' => '1']; // 現金のみ

    $payload = (new SagawaShipmentBuilder(sagawaSender(), [], $credential))
        ->build(sagawaOrder(['payment_method' => 'cashondelivery']));

    expect($payload->daibikiType)->toBe('1');
});

it('omits daibikiType (null = Sagawa system default) when credential not set', function () {
    $payload = (new SagawaShipmentBuilder(sagawaSender()))
        ->build(sagawaOrder(['payment_method' => 'cashondelivery']));

    expect($payload->daibikiType)->toBeNull();
});

it('keeps binsyuCode unchanged for COD orders (binsyuCode is便種, not COD switch)', function () {
    $payload = (new SagawaShipmentBuilder(sagawaSender(), ['binsyu_code' => '140']))
        ->build(sagawaOrder(['payment_method' => 'cashondelivery']));

    expect($payload->binsyuCode)->toBe('140'); // クール冷蔵 stays クール冷蔵 even with COD
    expect($payload->daibikiFlg)->toBe('1');
});
