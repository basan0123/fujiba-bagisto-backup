<?php

namespace Fujiba\FacebookCatalog\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GraphClient
{
    public function __construct(
        protected string $accessToken,
        protected ?string $apiVersion = null,
        protected ?string $baseUrl = null,
    ) {
        $this->apiVersion ??= (string) config('fujiba-facebook-catalog.graph_api_version', 'v19.0');
        $this->baseUrl    ??= (string) config('fujiba-facebook-catalog.graph_base_url', 'https://graph.facebook.com');
    }

    public function postItemsBatch(string $catalogId, array $requests): array
    {
        $endpoint = sprintf('/%s/%s/items_batch', $this->apiVersion, $catalogId);

        return $this->post($endpoint, [
            'access_token' => $this->accessToken,
            'item_type'    => 'PRODUCT_ITEM',
            'requests'     => json_encode(array_values($requests), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function getCatalog(string $catalogId): array
    {
        $endpoint = sprintf('/%s/%s', $this->apiVersion, $catalogId);

        return $this->get($endpoint, [
            'access_token' => $this->accessToken,
            'fields'       => 'id,name,vertical,product_count',
        ]);
    }

    protected function post(string $path, array $body): array
    {
        $response = $this->client()->asForm()->post($this->baseUrl . $path, $body);

        if ($response->failed()) {
            $this->logError('POST', $path, $response->status(), $response->body());

            throw new RuntimeException(sprintf(
                'Facebook Graph API error %d: %s',
                $response->status(),
                $this->extractErrorMessage($response->json() ?? []),
            ));
        }

        return $response->json() ?? [];
    }

    protected function get(string $path, array $query): array
    {
        $response = $this->client()->get($this->baseUrl . $path, $query);

        if ($response->failed()) {
            $this->logError('GET', $path, $response->status(), $response->body());

            throw new RuntimeException(sprintf(
                'Facebook Graph API error %d: %s',
                $response->status(),
                $this->extractErrorMessage($response->json() ?? []),
            ));
        }

        return $response->json() ?? [];
    }

    protected function client(): PendingRequest
    {
        return Http::timeout((int) config('fujiba-facebook-catalog.http_timeout', 60))
            ->retry((int) config('fujiba-facebook-catalog.http_retries', 3), 250);
    }

    protected function extractErrorMessage(array $json): string
    {
        return $json['error']['message']
            ?? $json['error']['error_user_msg']
            ?? 'unknown error';
    }

    protected function logError(string $method, string $path, int $status, string $body): void
    {
        Log::warning('[FacebookCatalog] Graph API error', [
            'method' => $method,
            'path'   => $path,
            'status' => $status,
            'body'   => mb_substr($body, 0, 2000),
        ]);
    }
}
