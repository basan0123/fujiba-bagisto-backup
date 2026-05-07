<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Exceptions\OcrServiceException;
use Fujiba\Accounting\Services\Ocr\AzureDocIntelligenceOcr;
use Fujiba\Accounting\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class AzureDocIntelligenceOcrTest extends TestCase
{
    protected function buildClient(array $responses): Client
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        return new Client(['handler' => $stack, 'http_errors' => false]);
    }

    public function test_happy_path_maps_azure_response(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'azocr');
        file_put_contents($tmp, 'jpg-bytes');
        rename($tmp, $tmp . '.jpg');
        $tmp .= '.jpg';

        $analyzeResp = new Response(202, [
            'apim-request-id' => 'req-abc',
            'Operation-Location' => 'https://example.invalid/operations/op-1',
        ], '');

        $pollSucceeded = new Response(200, [], json_encode([
            'status' => 'succeeded',
            'analyzeResult' => [
                'content' => "領収書\nABCマート\nT1234567890123\n合計 ¥1,100",
                'documents' => [[
                    'confidence' => 0.92,
                    'fields' => [
                        'MerchantName'    => ['valueString' => 'ABC マート'],
                        'TransactionDate' => ['valueDate'   => '2026-05-01'],
                        'Total'           => ['valueCurrency' => ['amount' => 1100]],
                        'TotalTax'        => ['valueCurrency' => ['amount' => 100]],
                        'PaymentMethod'   => ['valueString' => 'cash'],
                    ],
                ]],
            ],
        ]));

        $svc = new AzureDocIntelligenceOcr(
            endpoint: 'https://example.invalid',
            key: 'test-key',
            pollIntervalMs: 1,           // fast tests
            pollMaxAttempts: 5,
            http: $this->buildClient([$analyzeResp, $pollSucceeded]),
        );

        $out = $svc->extract($tmp);

        $this->assertSame('ABC マート', $out['vendor_name']);
        $this->assertSame('T1234567890123', $out['vendor_t_number']);
        $this->assertSame('2026-05-01', $out['receipt_date']);
        $this->assertSame(1100, $out['total_amount']);
        $this->assertSame(100, $out['tax_amount']);
        $this->assertSame('cash', $out['payment_method']);
        $this->assertSame(0.92, $out['confidence']);
        $this->assertSame(10.0, $out['tax_rate']);   // 100/1000 = 10%

        @unlink($tmp);
    }

    public function test_failure_status_throws(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'azocr') . '.jpg';
        file_put_contents($tmp, 'bytes');

        $analyzeResp = new Response(202, ['Operation-Location' => 'https://example.invalid/op-2'], '');
        $pollFailed = new Response(200, [], json_encode([
            'status' => 'failed',
            'error' => ['code' => 'BadRequest', 'message' => 'unsupported document'],
        ]));

        $svc = new AzureDocIntelligenceOcr(
            endpoint: 'https://example.invalid',
            key: 'test-key',
            pollIntervalMs: 1,
            pollMaxAttempts: 5,
            http: $this->buildClient([$analyzeResp, $pollFailed]),
        );

        $this->expectException(OcrServiceException::class);
        $svc->extract($tmp);

        @unlink($tmp);
    }

    public function test_non_202_analyze_response_throws(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'azocr') . '.jpg';
        file_put_contents($tmp, 'bytes');

        $svc = new AzureDocIntelligenceOcr(
            endpoint: 'https://example.invalid',
            key: 'test-key',
            pollIntervalMs: 1,
            pollMaxAttempts: 1,
            http: $this->buildClient([new Response(401, [], '{"error":{"code":"Unauthorized"}}')]),
        );

        $this->expectException(OcrServiceException::class);
        $svc->extract($tmp);

        @unlink($tmp);
    }
}
