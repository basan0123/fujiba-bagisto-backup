<?php
namespace Modules\FujibaLiveChat\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatpionService
{
    protected array $servers;
    protected string $apiKey;
    protected int $activeIndex = 0;

    public function __construct()
    {
        $channelId = request()->header('X-Channel-Id', core()->getCurrentChannel()->id ?? 1);
        $settings  = Cache::remember("livechat_settings_{$channelId}", 300, function() use ($channelId) {
            return \DB::table('fujiba_livechat_settings')
                ->where('channel_id', $channelId)->first();
        });

        // Build server list — up to 3, skip empty
        $this->servers = array_filter([
            $settings->chatpion_url   ?? config('fujiba_livechat.chatpion_url'),
            $settings->chatpion_url_2 ?? null,
            $settings->chatpion_url_3 ?? null,
        ]);
        $this->apiKey = $settings->api_key ?? config('fujiba_livechat.api_key');
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    // POST with automatic failover
    public function post(string $endpoint, array $data): mixed
    {
        foreach ($this->servers as $i => $base) {
            try {
                $res = Http::timeout(8)->post("{$base}/{$endpoint}", $data);
                if ($res->successful()) return $res->json();
            } catch (\Exception $e) {
                Log::warning("Chatpion server {$i} failed: {$e->getMessage()}");
            }
        }
        return ['status' => 'error', 'message' => 'All Chatpion servers unavailable'];
    }

    // GET with automatic failover
    public function get(string $endpoint, array $query = []): mixed
    {
        foreach ($this->servers as $i => $base) {
            try {
                $res = Http::timeout(8)->get("{$base}/{$endpoint}", $query);
                if ($res->successful()) return $res->json();
            } catch (\Exception $e) {
                Log::warning("Chatpion server {$i} GET failed: {$e->getMessage()}");
            }
        }
        return ['status' => 'error', 'message' => 'All Chatpion servers unavailable'];
    }

    // Check health of all servers
    public function healthCheck(): array
    {
        return collect($this->servers)->map(function($url, $i) {
            try {
                $res = Http::timeout(4)->get("{$url}/api/health");
                return ['url' => $url, 'status' => $res->successful() ? 'ok' : 'error'];
            } catch (\Exception $e) {
                return ['url' => $url, 'status' => 'down'];
            }
        })->toArray();
    }
}