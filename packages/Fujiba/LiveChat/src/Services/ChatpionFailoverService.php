<?php

namespace Fujiba\LiveChat\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Fujiba\LiveChat\Models\LiveChatSetting;

class ChatpionFailoverService
{
    protected LiveChatSetting $setting;
    protected array $servers = [];

    public function __construct(LiveChatSetting $setting)
    {
        $this->setting = $setting;
        $this->servers = array_values(array_filter([
            $setting->chatpion_url_1,
            $setting->chatpion_url_2,
            $setting->chatpion_url_3,
        ]));
    }

    /**
     * Static factory: load setting by tenant + channel
     */
    public static function forChannel(string $tenantId, int $channelId): self
    {
        $setting = LiveChatSetting::where('tenant_id', $tenantId)
            ->where('channel_id', $channelId)
            ->firstOrFail();
        return new self($setting);
    }

    public function getApiKey(): string
    {
        return $this->setting->chatpion_api_key;
    }

    public function getBotId(): string
    {
        return $this->setting->fb_bot_id;
    }

    public function getActiveServerUrl(): string
    {
        $index = $this->setting->active_server_index ?? 0;
        return $this->servers[$index] ?? $this->servers[0];
    }

    /**
     * POST to Chatpion with automatic failover across servers
     */
    public function post(string $endpoint, array $data = []): array
    {
        $startIndex = $this->setting->active_server_index ?? 0;
        $serverCount = count($this->servers);

        for ($i = 0; $i < $serverCount; $i++) {
            $index = ($startIndex + $i) % $serverCount;
            $base  = $this->servers[$index];

            try {
                $response = Http::timeout(8)
                    ->retry(1, 500)
                    ->asForm()
                    ->post("{$base}/{$endpoint}", $data);

                if ($response->successful()) {
                    if ($index !== $this->setting->active_server_index) {
                        $this->setting->update(['active_server_index' => $index]);
                        Log::info("LiveChat: switched to server index {$index} ({$base})");
                    }
                    return $response->json() ?? [];
                }

                Log::warning("LiveChat: server {$index} returned HTTP {$response->status()} for {$endpoint}");

            } catch (\Exception $e) {
                Log::warning("LiveChat: server {$index} exception: {$e->getMessage()}");
            }
        }

        Log::error("LiveChat: ALL servers failed for endpoint {$endpoint}");
        return ['status' => 'error', 'message' => 'All Chatpion servers unavailable'];
    }

    /**
     * POST to Chatpion with failover — returns raw body (HTML) instead of JSON
     */
    public function postRaw(string $endpoint, array $data = []): string
    {
        $startIndex = $this->setting->active_server_index ?? 0;
        $serverCount = count($this->servers);

        for ($i = 0; $i < $serverCount; $i++) {
            $index = ($startIndex + $i) % $serverCount;
            $base  = $this->servers[$index];

            try {
                $response = Http::timeout(8)
                    ->retry(1, 500)
                    ->asForm()
                    ->post("{$base}/{$endpoint}", $data);

                if ($response->successful()) {
                    if ($index !== $this->setting->active_server_index) {
                        $this->setting->update(['active_server_index' => $index]);
                        Log::info("LiveChat: switched to server index {$index} ({$base})");
                    }
                    return $response->body();
                }

                Log::warning("LiveChat: server {$index} returned HTTP {$response->status()} for {$endpoint}");

            } catch (\Exception $e) {
                Log::warning("LiveChat: server {$index} exception: {$e->getMessage()}");
            }
        }

        Log::error("LiveChat: ALL servers failed for endpoint {$endpoint}");
        return '<div class="lc-empty">All servers unavailable</div>';
    }

    /**
     * GET with failover — returns raw body (HTML)
     */
    public function getRaw(string $endpoint, array $query = []): string
    {
        $startIndex = $this->setting->active_server_index ?? 0;
        $serverCount = count($this->servers);

        for ($i = 0; $i < $serverCount; $i++) {
            $index = ($startIndex + $i) % $serverCount;
            $base  = $this->servers[$index];

            try {
                $response = Http::timeout(8)->get("{$base}/{$endpoint}", $query);
                if ($response->successful()) {
                    if ($index !== $this->setting->active_server_index) {
                        $this->setting->update(['active_server_index' => $index]);
                    }
                    return $response->body();
                }
            } catch (\Exception $e) {
                Log::warning("LiveChat getRaw server {$index} failed: {$e->getMessage()}");
            }
        }

        return '<div class="lc-empty">All servers unavailable</div>';
    }

    /**
     * GET with failover
     */
    public function get(string $endpoint, array $query = []): array
    {
        $startIndex = $this->setting->active_server_index ?? 0;
        $serverCount = count($this->servers);

        for ($i = 0; $i < $serverCount; $i++) {
            $index = ($startIndex + $i) % $serverCount;
            $base  = $this->servers[$index];

            try {
                $response = Http::timeout(8)->get("{$base}/{$endpoint}", $query);
                if ($response->successful()) {
                    if ($index !== $this->setting->active_server_index) {
                        $this->setting->update(['active_server_index' => $index]);
                    }
                    return $response->json() ?? [];
                }
            } catch (\Exception $e) {
                Log::warning("LiveChat GET server {$index} failed: {$e->getMessage()}");
            }
        }

        return ['status' => 'error', 'message' => 'All Chatpion servers unavailable'];
    }

    /**
     * Health check all servers — stores result in DB
     */
    public function healthCheck(): array
    {
        $health = [];
        foreach ($this->servers as $index => $url) {
            try {
                $res = Http::timeout(4)->get("{$url}/api/health");
                $health[$index] = $res->successful() ? 'ok' : 'error';
            } catch (\Exception $e) {
                $health[$index] = 'down';
            }
        }

        $this->setting->update([
            'server_health'    => json_encode($health),
            'last_health_check' => now(),
        ]);

        return $health;
    }

    /**
     * Convenience: call Chatpion API endpoints with api_key injected
     */
    public function apiPost(string $apiEndpoint, array $data = []): array
    {
        return $this->post("api/{$apiEndpoint}", array_merge(
            ['api_key' => $this->setting->chatpion_api_key],
            $data
        ));
    }

    public function apiGet(string $apiEndpoint, array $query = []): array
    {
        return $this->get("api/{$apiEndpoint}", array_merge(
            ['api_key' => $this->setting->chatpion_api_key],
            $query
        ));
    }
}
