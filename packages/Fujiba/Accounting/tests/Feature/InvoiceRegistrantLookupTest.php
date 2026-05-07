<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\InvoiceRegistrant;
use Fujiba\Accounting\Services\InvoiceRegistrantLookup;
use Fujiba\Accounting\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;

class InvoiceRegistrantLookupTest extends TestCase
{
    use DatabaseTransactions;

    protected function buildLookup(array $responses): InvoiceRegistrantLookup
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $client = new Client(['handler' => $stack, 'http_errors' => false]);
        return new InvoiceRegistrantLookup(http: $client);
    }

    public function test_invalid_format_returns_null_without_api_call(): void
    {
        Config::set('accounting.nta_invoice_api.app_id', 'app-test');

        // No mock responses needed — must not call.
        $svc = $this->buildLookup([]);

        $this->assertNull($svc->lookup('not-a-t-number'));
        $this->assertNull($svc->lookup('T1234'));            // too short
        $this->assertNull($svc->lookup('X1234567890123'));   // wrong prefix
    }

    public function test_missing_app_id_logs_warning_and_returns_null(): void
    {
        Config::set('accounting.nta_invoice_api.app_id', null);

        $svc = $this->buildLookup([]);
        $this->assertNull($svc->lookup('T1234567890123'));
    }

    public function test_fresh_cache_hit_skips_api(): void
    {
        Config::set('accounting.nta_invoice_api.app_id', 'app-test');
        Config::set('accounting.nta_invoice_api.cache_days', 30);

        InvoiceRegistrant::create([
            't_number'    => 'T1234567890999',
            'name'        => 'キャッシュ株式会社',
            'fetched_at'  => now()->subDays(1),
        ]);

        $svc = $this->buildLookup([]); // would error if called
        $row = $svc->lookup('T1234567890999');

        $this->assertNotNull($row);
        $this->assertSame('キャッシュ株式会社', $row->name);
    }

    public function test_live_call_persists_response(): void
    {
        Config::set('accounting.nta_invoice_api.app_id', 'app-test');
        Config::set('accounting.nta_invoice_api.cache_days', 30);
        Config::set('accounting.nta_invoice_api.base_url', 'https://example.invalid/api');

        $resp = new Response(200, [], json_encode([
            'count' => 1,
            'announcement' => [[
                'sequenceNumber'  => '1',
                'name'            => '株式会社テスト',
                'kana'            => 'カブシキガイシャテスト',
                'address'         => '東京都渋谷区テスト1-2-3',
                'country'         => 'JP',
                'registratedDate' => '2023-10-01',
                'process'         => '01',
                'latestUpdateDate'=> '20231001',
            ]],
        ]));

        $svc = $this->buildLookup([$resp]);
        $row = $svc->lookup('T9999888877771');

        $this->assertNotNull($row);
        $this->assertSame('株式会社テスト', $row->name);
        $this->assertSame('カブシキガイシャテスト', $row->kana_name);
        $this->assertSame('JP', $row->country);
        $this->assertNotNull($row->fetched_at);

        // Re-lookup should hit cache (no second mock response provided).
        $row2 = $svc->lookup('T9999888877771');
        $this->assertSame($row->t_number, $row2->t_number);
    }
}
