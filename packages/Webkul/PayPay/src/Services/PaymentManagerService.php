<?php

namespace Webkul\PayPay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;

class PaymentManagerService
{
    protected const CODE = 'paypay';

    protected const CONFIG_PATH = 'sales.payment_methods.paypay.';

    protected const WEBHOOK_TOLERANCE_SECONDS = 300;

    protected const STATUS_MAP = [
        'COMPLETED' => 'captured',
        'AUTHORIZED' => 'captured',
        'CREATED' => 'pending',
        'FAILED' => 'failed',
        'CANCELED' => 'failed',
        'EXPIRED' => 'expired',
        'REFUNDED' => 'refunded',
    ];

    public function config(string $field, ?string $channelCode = null)
    {
        return core()->getConfigData(self::CONFIG_PATH.$field, $channelCode);
    }

    public function endpoint(?string $channelCode = null): string
    {
        return $this->config('sandbox', $channelCode)
            ? 'https://stg-api.sandbox.paypay.ne.jp'
            : 'https://api.paypay.ne.jp';
    }

    public function currency(?string $channelCode = null): string
    {
        return strtoupper($this->config('currency', $channelCode) ?: 'JPY');
    }

    public function normalizeStatus(?string $gatewayStatus): string
    {
        if (! $gatewayStatus) {
            return 'pending';
        }

        return self::STATUS_MAP[strtoupper($gatewayStatus)] ?? 'pending';
    }

    public function bagistoOrderStatus(string $normalized, ?string $channelCode = null): string
    {
        $map = [
            'captured' => $this->config('order_status', $channelCode) ?: 'processing',
            'failed' => 'canceled',
            'expired' => 'canceled',
            'refunded' => 'closed',
            'pending' => 'pending',
        ];

        return $map[$normalized] ?? 'pending';
    }

    public function client(?string $channelCode = null): Client
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::retry(
            function ($retries, RequestInterface $req, ?Response $res, $err) {
                if ($retries >= 3) {
                    return false;
                }
                if ($err) {
                    return true;
                }
                if ($res && $res->getStatusCode() >= 500) {
                    return true;
                }

                return false;
            },
            fn ($retries) => 500 * (2 ** $retries)
        ));

        return new Client([
            'base_uri' => $this->endpoint($channelCode),
            'timeout' => 15,
            'connect_timeout' => 5,
            'handler' => $stack,
            'http_errors' => true,
        ]);
    }

    public function request(string $method, string $path, array $body = [], ?string $channelCode = null): array
    {
        $apiKey = $this->config('api_key', $channelCode);
        $apiSecret = $this->config('api_secret', $channelCode);
        $merchant = $this->config('merchant_id', $channelCode);

        $nonce = bin2hex(random_bytes(8));
        $epoch = time();
        $bodyJson = $body ? json_encode($body) : '';
        $hashPayload = 'empty';

        if ($method !== 'GET' && $bodyJson !== '') {
            $hashPayload = base64_encode(hash('md5', 'application/json;charset=UTF-8'.$bodyJson, true));
        }

        $sigBase = implode("\n", [$path, $method, $nonce, $epoch, 'application/json;charset=UTF-8', $hashPayload]);
        $signature = base64_encode(hash_hmac('sha256', $sigBase, $apiSecret, true));
        $authHeader = sprintf('hmac OPA-Auth:%s:%s:%s:%s:%s', $apiKey, $signature, $nonce, $epoch, $hashPayload);

        $options = [
            'headers' => [
                'Authorization' => $authHeader,
                'X-ASSUME-MERCHANT' => $merchant,
                'Content-Type' => 'application/json;charset=UTF-8',
            ],
        ];

        if ($method !== 'GET' && $bodyJson !== '') {
            $options['body'] = $bodyJson;
        }

        try {
            $res = $this->client($channelCode)->request($method, $path, $options);

            return json_decode($res->getBody()->getContents(), true) ?: [];
        } catch (\Throwable $e) {
            Log::error('paypay.request.failed', [
                'method' => $method,
                'path' => $path,
                'channel' => $channelCode,
                'error' => $e->getMessage(),
            ]);

            return ['resultInfo' => ['code' => 'NETWORK_ERROR', 'message' => $e->getMessage()]];
        }
    }

    public function verifyPayment(string $merchantPaymentId, ?string $channelCode = null): array
    {
        $response = $this->request('GET', '/v2/codes/payments/'.$merchantPaymentId, [], $channelCode);
        $gatewayStatus = $response['data']['status'] ?? null;
        $txId = $response['data']['paymentId'] ?? null;

        Log::info('paypay.payment.verified', [
            'mpid' => $merchantPaymentId,
            'tx_id' => $txId,
            'gateway_status' => $gatewayStatus,
            'channel' => $channelCode,
        ]);

        return [
            'raw' => $response,
            'transaction_id' => $txId,
            'normalized_status' => $this->normalizeStatus($gatewayStatus),
        ];
    }

    public function refund(string $paymentId, int $amount, string $refundId, ?string $channelCode = null): array
    {
        return $this->request('POST', '/v2/refunds', [
            'merchantRefundId' => $refundId,
            'paymentId' => $paymentId,
            'amount' => ['amount' => $amount, 'currency' => $this->currency($channelCode)],
            'reason' => 'Admin refund',
        ], $channelCode);
    }

    public function verifyWebhook(string $rawBody, string $signature, ?int $timestamp, ?string $channelCode = null): bool
    {
        $secret = $this->config('api_secret', $channelCode);

        if (! $secret || ! $signature) {
            Log::warning('paypay.webhook.missing_credentials', ['channel' => $channelCode]);

            return false;
        }

        if ($timestamp !== null && abs(time() - $timestamp) > self::WEBHOOK_TOLERANCE_SECONDS) {
            Log::warning('paypay.webhook.stale', ['ts' => $timestamp, 'now' => time()]);

            return false;
        }

        $expected = hash_hmac('sha256', ($timestamp ? $timestamp.'.' : '').$rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    public function isDuplicate(?string $transactionId): bool
    {
        if (! $transactionId) {
            return false;
        }

        $key = 'paypay:tx:'.$transactionId;
        if (Cache::has($key)) {
            Log::info('paypay.webhook.duplicate', ['tx_id' => $transactionId]);

            return true;
        }

        Cache::put($key, 1, now()->addDay());

        return false;
    }
}
