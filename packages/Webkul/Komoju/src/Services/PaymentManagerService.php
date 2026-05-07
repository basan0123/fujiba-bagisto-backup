<?php

namespace Webkul\Komoju\Services;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;

class PaymentManagerService
{
    protected const CONFIG_PATH = 'sales.payment_methods.komoju.';

    protected const ENDPOINT = 'https://komoju.com';

    protected const WEBHOOK_TOLERANCE_SECONDS = 300;

    protected const STATUS_MAP = [
        'captured' => 'captured',
        'authorized' => 'captured',
        'completed' => 'captured',
        'paid' => 'captured',
        'pending' => 'pending',
        'expired' => 'expired',
        'failed' => 'failed',
        'cancelled' => 'failed',
        'canceled' => 'failed',
        'refunded' => 'refunded',
    ];

    protected const EVENT_MAP = [
        'payment.captured' => 'captured',
        'payment.authorized' => 'captured',
        'payment.successful' => 'captured',
        'payment.pending' => 'pending',
        'payment.expired' => 'expired',
        'payment.failed' => 'failed',
        'payment.cancelled' => 'failed',
        'payment.refunded' => 'refunded',
    ];

    public function config(string $field, ?string $channelCode = null)
    {
        return core()->getConfigData(self::CONFIG_PATH.$field, $channelCode);
    }

    public function currency(?string $channelCode = null): string
    {
        $accepted = $this->config('accepted_currencies', $channelCode);

        if ($accepted) {
            $first = strtoupper(trim(explode(',', $accepted)[0] ?? ''));

            if ($first !== '') {
                return $first;
            }
        }

        return strtoupper(core()->getCurrentChannel()?->base_currency?->code ?: 'JPY');
    }

    public function normalizeStatus(?string $gatewayStatus): string
    {
        if (! $gatewayStatus) {
            return 'pending';
        }

        return self::STATUS_MAP[strtolower($gatewayStatus)] ?? 'pending';
    }

    public function normalizeEvent(?string $event): string
    {
        if (! $event) {
            return 'pending';
        }

        return self::EVENT_MAP[strtolower($event)] ?? 'pending';
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
            'base_uri' => self::ENDPOINT,
            'timeout' => 15,
            'connect_timeout' => 5,
            'handler' => $stack,
            'http_errors' => true,
        ]);
    }

    public function request(string $method, string $path, array $body = [], ?string $channelCode = null): array
    {
        $secret = $this->config('secret_key', $channelCode);

        $options = [
            'headers' => [
                'Authorization' => 'Basic '.base64_encode($secret.':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        if ($method !== 'GET' && $body) {
            $options['json'] = $body;
        }

        try {
            $res = $this->client($channelCode)->request($method, $path, $options);

            return json_decode($res->getBody()->getContents(), true) ?: [];
        } catch (\Throwable $e) {
            Log::error('komoju.request.failed', [
                'method' => $method,
                'path' => $path,
                'channel' => $channelCode,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    public function verifySession(string $sessionId, ?string $channelCode = null): array
    {
        $response = $this->request('GET', '/api/v1/sessions/'.$sessionId, [], $channelCode);
        $status = $response['status'] ?? ($response['payment']['status'] ?? null);
        $txId = $response['payment']['id'] ?? ($response['id'] ?? null);

        Log::info('komoju.session.verified', [
            'session_id' => $sessionId,
            'tx_id' => $txId,
            'gateway_status' => $status,
            'channel' => $channelCode,
        ]);

        return [
            'raw' => $response,
            'transaction_id' => $txId,
            'normalized_status' => $this->normalizeStatus($status),
            'metadata' => $response['metadata'] ?? ($response['payment']['metadata'] ?? []),
        ];
    }

    public function refund(string $paymentId, int $amount, ?string $channelCode = null): array
    {
        return $this->request('POST', '/api/v1/payments/'.$paymentId.'/refund', [
            'amount' => $amount,
            'currency' => $this->currency($channelCode),
        ], $channelCode);
    }

    public function verifyReturnUrl(\Illuminate\Http\Request $request, ?string $channelCode = null): bool
    {
        $secret = $this->config('secret_key', $channelCode);
        $hmac = (string) $request->query('hmac', '');

        if (! $secret || ! $hmac) {
            return false;
        }

        $params = $request->query();
        unset($params['hmac']);

        $ts = (int) ($params['timestamp'] ?? 0);
        if ($ts && abs(time() - $ts) > self::WEBHOOK_TOLERANCE_SECONDS) {
            \Illuminate\Support\Facades\Log::warning('komoju.callback.stale', ['ts' => $ts, 'now' => time()]);

            return false;
        }

        ksort($params);
        $query = http_build_query($params);
        $expected = hash_hmac('sha256', $query, $secret);

        return hash_equals($expected, $hmac);
    }

    public function verifyWebhook(string $rawBody, string $signature, ?int $timestamp, ?string $channelCode = null): bool
    {
        $secret = $this->config('webhook_secret', $channelCode);

        if (! $secret || ! $signature) {
            Log::warning('komoju.webhook.missing_credentials', ['channel' => $channelCode]);

            return false;
        }

        if ($timestamp !== null && abs(time() - $timestamp) > self::WEBHOOK_TOLERANCE_SECONDS) {
            Log::warning('komoju.webhook.stale', ['ts' => $timestamp, 'now' => time()]);

            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    public function isDuplicate(?string $transactionId): bool
    {
        if (! $transactionId) {
            return false;
        }

        $key = 'komoju:tx:'.$transactionId;
        if (Cache::has($key)) {
            Log::info('komoju.webhook.duplicate', ['tx_id' => $transactionId]);

            return true;
        }

        Cache::put($key, 1, now()->addDay());

        return false;
    }
}
