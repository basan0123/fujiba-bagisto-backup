<?php

namespace Fujiba\SagawaDelivery\Services;

use Fujiba\SagawaDelivery\Models\ApiLog;
use Fujiba\SagawaDelivery\Models\Credential;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP wrapper for Sagawa Biz-Logi API.
 *
 * Defaults to staging. Production requires an explicit per-tenant flip
 * of sagawa_credentials.use_sandbox to false AND a non-sandbox api_endpoint.
 */
class SagawaClient
{
    public const ENDPOINT_CHECK_ADDRESS = 'checkaddress';
    public const ENDPOINT_SHIPPING      = 'shipping';
    public const ENDPOINT_CHECK_FILE    = 'checkfile';
    public const ENDPOINT_RETRY_PRINT   = 'retryprint';
    public const ENDPOINT_RIYOUJISSEKI  = 'riyoujisseki';
    public const ENDPOINT_DATAOUTPUT    = 'dataoutput';
    public const ENDPOINT_UKEWATASHI    = 'ukewatashimeisai';

    public function __construct(
        private Credential $credential,
    ) {}

    /**
     * Validate a Japanese address. Safe, billing-neutral, good for smoke tests.
     *
     * @return array{result_code:?string, response:string, http_status:int, parsed:array<string,mixed>}
     */
    public function checkAddress(string $postcode, string $address): array
    {
        $xml = $this->buildRequest(self::ENDPOINT_CHECK_ADDRESS, [
            'requestYubin'   => preg_replace('/[^0-9]/', '', $postcode) ?? '',
            'requestAddress' => $address,
        ]);

        return $this->send(self::ENDPOINT_CHECK_ADDRESS, $xml);
    }

    /**
     * Issue or validate a waybill. Pass printOutFlg=0 to validate, 1 to issue (billable).
     *
     * @param  array<string,mixed>  $fields  merged over defaults from the payload DTO
     */
    public function issueWaybill(array $fields): array
    {
        $xml = $this->buildRequest(self::ENDPOINT_SHIPPING, $fields);

        return $this->send(self::ENDPOINT_SHIPPING, $xml);
    }

    /**
     * Poll for waybill PDF readiness.
     *
     * @return array{result_code:?string, response:string, http_status:int, parsed:array<string,mixed>, items:array<int,array<string,?string>>}
     */
    public function checkFile(string $printRequestId): array
    {
        $xml    = $this->buildRequest(self::ENDPOINT_CHECK_FILE, [
            'printRequestId' => $printRequestId,
        ]);
        $result = $this->send(self::ENDPOINT_CHECK_FILE, $xml);

        $result['items'] = $this->extractCheckFileItems($result['parsed'] ?? []);

        return $result;
    }

    /**
     * Request a reissue / retry-print for one or more tracking numbers.
     *
     * @param  array<int,string>  $trackingNumbers
     * @return array{result_code:?string, response:string, http_status:int, parsed:array<string,mixed>, print_request_id:?string, errors:array<int,array<string,?string>>}
     */
    public function retryPrint(array $trackingNumbers, bool $showBackground = true): array
    {
        $fields = [
            'showBackgroundFlg' => $showBackground ? '1' : '0',
        ];
        foreach (array_values($trackingNumbers) as $i => $n) {
            $fields['toiawasenumber'.($i + 1)] = $n;
        }

        $xml    = $this->buildRequest(self::ENDPOINT_RETRY_PRINT, $fields);
        $result = $this->send(self::ENDPOINT_RETRY_PRINT, $xml);

        $result['print_request_id'] = $this->findScalar($result['parsed'] ?? [], 'printRequestId');
        $result['errors']           = $this->extractRetryPrintErrors($result['parsed'] ?? []);

        return $result;
    }

    /**
     * Low-level send with logging + retry. Exposed for endpoints not yet wrapped above.
     *
     * @return array{result_code:?string, response:string, http_status:int, parsed:array<string,mixed>}
     */
    public function send(string $endpoint, string $xml): array
    {
        $baseUrl  = $this->resolveBaseUrl();
        $url      = rtrim($baseUrl, '/').'/rest/getxml/'.$endpoint;
        $timeout  = (int) config('sagawa.timeout', 30);
        $maxTries = max(1, (int) config('sagawa.retry.max_attempts', 3));

        $attempt     = 0;
        $lastError   = null;
        $lastResult  = null;

        while ($attempt < $maxTries) {
            $attempt++;
            $startedAt = microtime(true);

            try {
                $response = $this->http($timeout)->send('POST', $url, [
                    'body' => $xml,
                ]);

                $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
                $lastResult = $this->handleResponse($endpoint, $url, $xml, $response, $attempt, $durationMs);

                if (! $this->shouldRetry($lastResult, $response)) {
                    return $lastResult;
                }

                $lastError = 'retryable result_code='.($lastResult['result_code'] ?? 'null').' http='.$response->status();
            } catch (\Throwable $e) {
                $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
                $lastError  = $e->getMessage();

                $this->log($endpoint, $url, $xml, null, null, $attempt, $durationMs, $lastError);

                if (! $this->shouldRetryException($e)) {
                    throw $e;
                }
            }

            if ($attempt < $maxTries) {
                usleep($this->backoffMicros($attempt));
            }
        }

        return $lastResult ?? [
            'result_code' => null,
            'response'    => '',
            'http_status' => 0,
            'parsed'      => [],
            'error'       => $lastError,
        ];
    }

    public function baseUrl(): string
    {
        return $this->resolveBaseUrl();
    }

    private function resolveBaseUrl(): string
    {
        if ($this->credential->use_sandbox) {
            return (string) config('sagawa.staging_base_url', 'https://api-stg.sgsystems.co.jp');
        }

        return $this->credential->api_endpoint
            ?: (string) config('sagawa.base_url', 'https://api.sgsystems.co.jp');
    }

    private function http(int $timeout): PendingRequest
    {
        return Http::withHeaders([
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Accept'       => 'application/xml',
        ])->timeout($timeout);
    }

    /** @param  array<string,mixed>  $fields */
    private function buildRequest(string $endpoint, array $fields): string
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.$endpoint.'Request></'.$endpoint.'Request>');

        $root->addChild('customerId', $this->xmlSafe($this->credential->api_customer_id));
        $root->addChild('loginPassword', $this->xmlSafe($this->credential->api_password));

        foreach ($fields as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $root->addChild($key, $this->xmlSafe((string) $value));
        }

        return $root->asXML() ?: '';
    }

    private function xmlSafe(?string $v): string
    {
        return htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** @return array{result_code:?string, response:string, http_status:int, parsed:array<string,mixed>} */
    private function handleResponse(
        string $endpoint,
        string $url,
        string $requestXml,
        Response $response,
        int $attempt,
        int $durationMs,
    ): array {
        $body       = $response->body();
        $parsed     = $this->parseXml($body);
        $resultCode = $this->extractResultCode($parsed);

        $this->log($endpoint, $url, $requestXml, $body, $response->status(), $attempt, $durationMs, null, $resultCode);

        return [
            'result_code' => $resultCode,
            'response'    => $body,
            'http_status' => $response->status(),
            'parsed'      => $parsed,
        ];
    }

    /** @return array<string,mixed> */
    private function parseXml(string $body): array
    {
        if ($body === '') {
            return [];
        }

        libxml_use_internal_errors(true);

        try {
            $sx = simplexml_load_string($body);
            if ($sx === false) {
                return [];
            }

            return json_decode(json_encode($sx), true) ?: [];
        } finally {
            libxml_clear_errors();
        }
    }

    private function extractResultCode(array $parsed): ?string
    {
        array_walk_recursive($parsed, function ($value, $key) use (&$found) {
            if (! $found && is_string($key) && str_ends_with(strtolower($key), 'resultcode') && is_string($value)) {
                $found = $value;
            }
        });

        return $found ?? null;
    }

    private function shouldRetry(array $result, Response $response): bool
    {
        if ($response->status() >= 500) {
            return true;
        }

        $code = $result['result_code'] ?? null;
        if (! is_string($code) || $code === '') {
            return false;
        }

        // Result-code family Y: 8=process error, 9=system error. Retry these.
        // Errors with Y=1 (validation), 2 (address), 3 (auth) are NOT retryable.
        return (bool) preg_match('/^[EW][89]-/', $code);
    }

    private function shouldRetryException(\Throwable $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, 'cURL error')
            || str_contains($msg, 'Connection')
            || str_contains($msg, 'timeout')
            || str_contains($msg, 'Could not resolve host');
    }

    private function backoffMicros(int $attempt): int
    {
        $baseMs = (int) config('sagawa.retry.base_ms', 300);
        $capMs  = (int) config('sagawa.retry.cap_ms', 5000);
        $ms     = min($capMs, $baseMs * (2 ** ($attempt - 1)));
        $jitter = random_int(0, (int) max(1, $ms / 3));

        return ($ms + $jitter) * 1000;
    }

    private function log(
        string $endpoint,
        string $url,
        string $requestXml,
        ?string $responseBody,
        ?int $httpStatus,
        int $attempt,
        int $durationMs,
        ?string $error = null,
        ?string $resultCode = null,
    ): void {
        try {
            ApiLog::create([
                'tenant_id'     => $this->credential->tenant_id,
                'channel_id'    => $this->credential->channel_id,
                'endpoint'      => $endpoint,
                'base_url'      => $url,
                'sandbox'       => (bool) $this->credential->use_sandbox,
                'request_xml'   => $this->redact($requestXml),
                'response_body' => $responseBody,
                'http_status'   => $httpStatus,
                'result_code'   => $resultCode,
                'duration_ms'   => $durationMs,
                'attempt'       => $attempt,
                'error'         => $error,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[SagawaClient] Failed to log API call: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string,mixed>  $parsed
     * @return array<int,array<string,?string>>
     */
    private function extractCheckFileItems(array $parsed): array
    {
        $items = [];
        $walk  = function ($node) use (&$walk, &$items) {
            if (! is_array($node)) {
                return;
            }
            if (array_key_exists('url', $node) || array_key_exists('printRequestId', $node)) {
                $items[] = [
                    'print_request_id' => isset($node['printRequestId']) && is_scalar($node['printRequestId']) ? (string) $node['printRequestId'] : null,
                    'create_date'      => isset($node['createDate']) && is_scalar($node['createDate']) ? (string) $node['createDate'] : null,
                    'result_code'      => isset($node['resultCode']) && is_scalar($node['resultCode']) ? (string) $node['resultCode'] : null,
                    'url'              => isset($node['url']) && is_scalar($node['url']) ? (string) $node['url'] : null,
                ];
            }
            foreach ($node as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };
        $walk($parsed);

        return $items;
    }

    /**
     * @param  array<string,mixed>  $parsed
     * @return array<int,array<string,?string>>
     */
    private function extractRetryPrintErrors(array $parsed): array
    {
        $errors = [];
        $walk   = function ($node) use (&$walk, &$errors) {
            if (! is_array($node)) {
                return;
            }
            if (array_key_exists('errorCode', $node) || array_key_exists('errorMessage', $node)) {
                $errors[] = [
                    'tracking_number' => isset($node['toiawasenumber']) && is_scalar($node['toiawasenumber']) ? (string) $node['toiawasenumber'] : null,
                    'code'            => isset($node['errorCode']) && is_scalar($node['errorCode']) ? (string) $node['errorCode'] : null,
                    'message'         => isset($node['errorMessage']) && is_scalar($node['errorMessage']) ? (string) $node['errorMessage'] : null,
                ];
            }
            foreach ($node as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };
        $walk($parsed);

        return $errors;
    }

    /** @param  array<string,mixed>  $parsed */
    private function findScalar(array $parsed, string $key): ?string
    {
        $found = null;
        array_walk_recursive($parsed, function ($value, $k) use (&$found, $key) {
            if (! $found && is_string($k) && $k === $key && is_scalar($value)) {
                $found = (string) $value;
            }
        });

        return $found;
    }

    /** Redact customerId + loginPassword from stored XML (handles namespaced tags). */
    private function redact(string $xml): string
    {
        return preg_replace(
            ['#<([a-z0-9]+:)?customerId>.*?</([a-z0-9]+:)?customerId>#si',
             '#<([a-z0-9]+:)?loginPassword>.*?</([a-z0-9]+:)?loginPassword>#si'],
            ['<$1customerId>***</$2customerId>',
             '<$1loginPassword>***</$2loginPassword>'],
            $xml
        ) ?? $xml;
    }
}
