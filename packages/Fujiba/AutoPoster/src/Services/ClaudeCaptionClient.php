<?php

namespace Fujiba\AutoPoster\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin client for the Anthropic Messages API.
 *
 * Uses Laravel's Http facade rather than the official anthropic-ai/sdk package
 * on purpose: adding a composer dependency to a live Bagisto install is a
 * deployment change, and every other outbound integration in this codebase
 * (FacebookCatalog\GraphClient, LiveChat\ChatpionFailoverService, BotFlowSender)
 * already calls its API this way. Swapping in the SDK later is a change behind
 * this class only — CaptionGenerator never sees the transport.
 *
 * Request shape follows the current Messages API:
 *   - `max_tokens` is required.
 *   - Structured output via `output_config.format` (NOT the deprecated
 *     top-level `output_format`), so the trilingual result comes back as
 *     schema-valid JSON instead of prose we would have to parse.
 *   - Thinking is left at the model default; caption writing is a short
 *     generation task and does not need an explicit thinking config.
 */
class ClaudeCaptionClient
{
    public const API_URL = 'https://api.anthropic.com/v1/messages';

    /** Pinned: the Messages API requires an explicit version header. */
    public const API_VERSION = '2023-06-01';

    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $model = null,
    ) {
        $this->apiKey ??= (string) config('autoposter.anthropic_api_key');
        $this->model  ??= (string) config('autoposter.anthropic_model', 'claude-opus-5');
    }

    public function isConfigured(): bool
    {
        return trim((string) $this->apiKey) !== '';
    }

    /**
     * Ask Claude for a caption, constrained to a JSON schema.
     *
     * @param  array<string,mixed>  $schema  JSON schema for the response
     * @return array<string,mixed>           decoded object matching $schema
     *
     * @throws RuntimeException on transport failure, refusal, or unusable output
     */
    public function generateJson(string $system, string $userPrompt, array $schema): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type'      => 'application/json',
            ])
            ->timeout((int) config('autoposter.caption_timeout', 60))
            // Retry transport blips only. A 4xx (bad key, bad request) is not
            // retryable and would just burn time before the same failure.
            ->retry(2, 500, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            }, throw: false)
            ->post(self::API_URL, [
                'model'      => $this->model,
                'max_tokens' => (int) config('autoposter.caption_max_tokens', 2000),
                'system'     => $system,
                'messages'   => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'output_config' => [
                    'format' => [
                        'type'   => 'json_schema',
                        'schema' => $schema,
                    ],
                ],
            ]);

        if ($response->failed()) {
            $body = $response->json();
            $msg  = $body['error']['message'] ?? $response->body();

            Log::warning('[AutoPoster] Claude API error', [
                'status' => $response->status(),
                'type'   => $body['error']['type'] ?? null,
                'body'   => mb_substr((string) $response->body(), 0, 1000),
            ]);

            throw new RuntimeException(sprintf(
                'Claude API error %d: %s',
                $response->status(),
                mb_substr((string) $msg, 0, 500)
            ));
        }

        $json = $response->json() ?? [];

        // A safety decline returns HTTP 200 with stop_reason "refusal" and no
        // usable content — check before reading content, or we would emit an
        // empty caption and treat it as success.
        if (($json['stop_reason'] ?? null) === 'refusal') {
            throw new RuntimeException(
                'Claude declined to generate this caption'
                . (isset($json['stop_details']['category'])
                    ? ' (' . $json['stop_details']['category'] . ')'
                    : '') . '.'
            );
        }

        $text = $this->extractText($json);

        if ($text === '') {
            throw new RuntimeException('Claude returned no text content.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Claude returned unparseable JSON: ' . mb_substr($text, 0, 200));
        }

        return $decoded;
    }

    /**
     * Concatenate text blocks, skipping thinking/tool blocks.
     *
     * content is polymorphic — indexing content[0]->text blindly breaks the
     * moment a thinking block leads the array.
     */
    protected function extractText(array $json): string
    {
        $out = '';

        foreach ($json['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $out .= $block['text'] ?? '';
            }
        }

        return trim($out);
    }
}
