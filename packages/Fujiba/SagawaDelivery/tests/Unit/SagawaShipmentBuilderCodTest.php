<?php

use Fujiba\SagawaDelivery\Exceptions\SagawaCodNotConfiguredException;
use Fujiba\SagawaDelivery\Services\SagawaShipmentBuilder;

function sagawaSender(): array
{
    return [
        'name'       => '株式会社フジバ',
        'postcode'   => '1500001',
        'prefecture' => '東京都',
        'city'       => '渋谷区',
        'address'    => 'テスト1-2-3',
        'phone'      => '0312345678',
    ];
}

function sagawaOrder(array $overrides = []): array
{
    return array_merge([
        'id'             => 731,
        'tenant_id'      => 'kimono',
        'channel_id'     => 1,
        'grand_total'    => 8800,
        'payment_method' => 'moneytransfer',
        'shipping_address' => [
            'postcode' => '1000001',
            'state'    => '東京都',
            'city'     => '千代田区',
            'address1' => '千代田1-1',
            'first_name' => '太郎',
            'last_name'  => '山田',
            'phone'    => '09011112222',
        ],
        'delivery_date' => '2026-05-10',
        'delivery_time' => 'am',
    ], $overrides);
}

it('uses binsyuCode 000 and no daibiki fields for prepaid orders', function () {
    $payload = (new SagawaShipmentBuilder(sagawaSender()))->build(sagawaOrder());

    expect($payload->binsyuCode)->toBe('000');
    expect($payload->daibikiKng)->toBeNull();
    expect($payload->daibikiNm)->toBeNull();
});

it('uses contract binsyuCode and grand_total for COD orders when configured', function () {
    $credential = [
        'daibiki_binsyu_code' => '021',
        'daibiki_payee_name'  => 'フジバ代引窓口',
    ];

    $payload = (new SagawaShipmentBuilder(sagawaSender(), [], $credential))
        ->build(sagawaOrder(['payment_method' => 'cashondelivery', 'grand_total' => 8800]));

    expect($payload->binsyuCode)->toBe('021');
    expect($payload->daibikiKng)->toBe('8800');
    expect($payload->daibikiNm)->toBe('フジバ代引窓口');
});

it('falls back to sender name when daibiki_payee_name is not set', function () {
    $credential = ['daibiki_binsyu_code' => '021'];

    $payload = (new SagawaShipmentBuilder(sagawaSender(), [], $credential))
        ->build(sagawaOrder(['payment_method' => 'cashondelivery']));

    expect($payload->daibikiNm)->toBe('株式会社フジバ');
});

it('throws when COD order has no daibiki_binsyu_code in credentials', function () {
    (new SagawaShipmentBuilder(sagawaSender(), [], []))
        ->build(sagawaOrder(['payment_method' => 'cashondelivery']));
})->throws(SagawaCodNotConfiguredException::class, 'tenant kimono has no daibiki_binsyu_code');
