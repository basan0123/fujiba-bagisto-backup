<?php

namespace Fujiba\FacebookCatalog\Services;

use Fujiba\FacebookCatalog\Exceptions\GraphApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    /* ---------------------------------------------------------------------
     | Page publishing (Fujiba\AutoPoster)
     |
     | Added for the Auto Poster module. The catalog methods above use a token
     | with catalog_management scope; everything below needs a PAGE access
     | token with pages_manage_posts + pages_read_engagement. Those are
     | different grants — debugToken() exists so the pre-flight check can prove
     | the configured token actually carries them instead of discovering it as
     | a 403 at publish time.
     |
     | Organic reach only: a true multi-card carousel with per-card CTAs is an
     | ads-API creative, so a multi-photo feed post plus an m.me link in the
     | message is the organic equivalent and what publishMultiPhotoPost builds.
     --------------------------------------------------------------------- */

    /**
     * Upload one photo to the page WITHOUT publishing it, returning its media id
     * for use as attached_media on a subsequent feed post.
     *
     * `url` makes Facebook fetch the image itself, so it must be publicly
     * reachable on the channel's storefront host (see ProductPayloadBuilder —
     * APP_URL-derived paths 404 for tenant media and surface as "Image fetch
     * failed").
     */
    public function uploadUnpublishedPhoto(string $pageId, string $imageUrl): array
    {
        $endpoint = sprintf('/%s/%s/photos', $this->apiVersion, $pageId);

        return $this->post($endpoint, [
            'access_token' => $this->accessToken,
            'url'          => $imageUrl,
            'published'    => 'false',
        ]);
    }

    /**
     * Publish a multi-photo feed post from already-uploaded media ids.
     *
     * @param  array<int,string>  $mediaIds  in display order
     */
    public function publishMultiPhotoPost(string $pageId, string $message, array $mediaIds): array
    {
        $endpoint = sprintf('/%s/%s/feed', $this->apiVersion, $pageId);

        $body = [
            'access_token' => $this->accessToken,
            'message'      => $message,
        ];

        // attached_media[0..n] — Graph wants indexed keys, not a JSON array.
        foreach (array_values($mediaIds) as $i => $mediaId) {
            $body["attached_media[{$i}]"] = json_encode(['media_fbid' => $mediaId]);
        }

        return $this->post($endpoint, $body);
    }

    /**
     * Publish a single-image post with a link (the m.me Messenger CTA).
     */
    public function publishLinkPost(string $pageId, string $message, string $link): array
    {
        $endpoint = sprintf('/%s/%s/feed', $this->apiVersion, $pageId);

        return $this->post($endpoint, [
            'access_token' => $this->accessToken,
            'message'      => $message,
            'link'         => $link,
        ]);
    }

    /**
     * Fetch a published post's permalink so post history can link out to it.
     */
    public function getPost(string $postId): array
    {
        $endpoint = sprintf('/%s/%s', $this->apiVersion, $postId);

        return $this->get($endpoint, [
            'access_token' => $this->accessToken,
            'fields'       => 'id,permalink_url,created_time',
        ]);
    }

    /**
     * Introspect the configured token: validity, expiry and granted scopes.
     *
     * Used by the pre-flight check to report a missing pages_manage_posts /
     * pages_read_engagement grant as an actionable item rather than letting
     * auto-posting be switched on against a token that cannot publish.
     *
     * Called against the token itself as both input and inspector; that works
     * for a page token issued to the same app and avoids requiring a separate
     * app token in config.
     */
    public function debugToken(?string $tokenToInspect = null): array
    {
        $endpoint = sprintf('/%s/debug_token', $this->apiVersion);

        return $this->get($endpoint, [
            'input_token'  => $tokenToInspect ?? $this->accessToken,
            'access_token' => $this->accessToken,
        ]);
    }

    /**
     * Page metadata + the scopes the token carries for it. Pre-flight uses this
     * to confirm the configured page id is reachable with this token.
     */
    public function getPage(string $pageId): array
    {
        $endpoint = sprintf('/%s/%s', $this->apiVersion, $pageId);

        return $this->get($endpoint, [
            'access_token' => $this->accessToken,
            'fields'       => 'id,name,link',
        ]);
    }

    protected function post(string $path, array $body): array
    {
        $response = $this->client()->asForm()->post($this->baseUrl . $path, $body);

        if ($response->failed()) {
            $this->logError('POST', $path, $response->status(), $response->body());

            throw $this->error('POST', $path, $response);
        }

        return $response->json() ?? [];
    }

    protected function get(string $path, array $query): array
    {
        $response = $this->client()->get($this->baseUrl . $path, $query);

        if ($response->failed()) {
            $this->logError('GET', $path, $response->status(), $response->body());

            throw $this->error('GET', $path, $response);
        }

        return $response->json() ?? [];
    }

    protected function client(): PendingRequest
    {
        return Http::timeout((int) config('fujiba-facebook-catalog.http_timeout', 60))
            // Only transport hiccups and throttles are worth re-sending. Without
            // this predicate the transport retried a permanent 4xx (bad token,
            // missing permission) the full 3 times before the caller ever saw
            // it, turning one fatal error into three identical Graph calls.
            ->retry(
                (int) config('fujiba-facebook-catalog.http_retries', 3),
                250,
                function ($exception, $request) {
                    $response = $exception->response ?? null;

                    if (! $response) {
                        return true; // connection error / timeout
                    }

                    $status = $response->status();

                    if ($status === 429 || $status >= 500) {
                        return true;
                    }

                    $code = $response->json('error.code');

                    return is_numeric($code)
                        && in_array((int) $code, GraphApiException::RETRYABLE_CODES, true);
                },
                throw: false,
            );
    }

    /**
     * Build a GraphApiException that carries the status and decoded body, so
     * callers can classify the failure instead of parsing the message text.
     */
    protected function error(string $method, string $path, \Illuminate\Http\Client\Response $response): GraphApiException
    {
        $json = $response->json();
        $body = is_array($json) ? $json : null;

        $exception = new GraphApiException(
            sprintf(
                'Facebook Graph API error %d: %s',
                $response->status(),
                $this->extractErrorMessage($body ?? []),
            ),
            $response->status(),
            $body,
        );

        Log::warning('[FacebookCatalog] Graph API failure classified', [
            'method'         => $method,
            'path'           => $path,
            'status'         => $response->status(),
            'graph_code'     => $exception->graphCode(),
            'graph_subcode'  => $exception->graphSubcode(),
            'classification' => $exception->classification(),
        ]);

        return $exception;
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
