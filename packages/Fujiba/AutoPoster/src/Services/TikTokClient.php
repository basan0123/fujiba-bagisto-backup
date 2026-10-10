<?php

namespace Fujiba\AutoPoster\Services;

use Carbon\Carbon;
use Fujiba\AutoPoster\Models\TikTokAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * TikTok Login Kit + Content Posting API (v2). Media is always sent as
 * PULL_FROM_URL, so every URL must sit under a verified URL property
 * (https://kimono.fujiba.co.jp/). Tokens are never logged.
 */
class TikTokClient
{
    protected const API = 'https://open.tiktokapis.com/v2';

    public function authorizeUrl(string $state): string
    {
        return 'https://www.tiktok.com/v2/auth/authorize/?'.http_build_query([
            'client_key'    => config('autoposter.tiktok.client_key'),
            'scope'         => config('autoposter.tiktok.scopes'),
            'response_type' => 'code',
            'redirect_uri'  => config('autoposter.tiktok.redirect_uri'),
            'state'         => $state,
        ]);
    }

    public function connect(int $channelId, string $code): TikTokAccount
    {
        $token = $this->tokenRequest([
            'code'         => $code,
            'grant_type'   => 'authorization_code',
            'redirect_uri' => config('autoposter.tiktok.redirect_uri'),
        ]);

        $account = TikTokAccount::firstOrNew(['channel_id' => $channelId]);
        $this->fillToken($account, $token);
        $account->open_id = $token['open_id'];
        $account->save();

        $this->refreshProfile($account);

        return $account;
    }

    public function disconnect(TikTokAccount $account): void
    {
        try {
            Http::asForm()->timeout(20)->post(self::API.'/oauth/revoke/', [
                'client_key'    => config('autoposter.tiktok.client_key'),
                'client_secret' => config('autoposter.tiktok.client_secret'),
                'token'         => $account->access_token,
            ]);
        } finally {
            $account->delete();
        }
    }

    public function refreshProfile(TikTokAccount $account): void
    {
        $data = $this->get($account, '/user/info/', ['fields' => 'open_id,avatar_url,display_name']);

        $account->update([
            'display_name' => $data['user']['display_name'] ?? $account->display_name,
            'avatar_url'   => $data['user']['avatar_url'] ?? $account->avatar_url,
        ]);
    }

    /**
     * Required before every post: nickname, allowed privacy levels, which
     * interactions the creator disabled, and max video length.
     */
    public function creatorInfo(TikTokAccount $account): array
    {
        return $this->post($account, '/post/publish/creator_info/query/', []);
    }

    /** @param  array{title:string,privacy_level:string,disable_comment:bool,disable_duet:bool,disable_stitch:bool,brand_content_toggle:bool,brand_organic_toggle:bool}  $postInfo */
    public function directPostVideo(TikTokAccount $account, string $videoUrl, array $postInfo): string
    {
        return $this->sendVideo($account, '/post/publish/video/init/', $videoUrl, ['post_info' => $postInfo]);
    }

    public function draftVideo(TikTokAccount $account, string $videoUrl): string
    {
        return $this->sendVideo($account, '/post/publish/inbox/video/init/', $videoUrl, []);
    }

    /**
     * PULL_FROM_URL needs the URL prefix verified on the same app environment
     * (sandbox and production verify separately). When TikTok refuses the URL,
     * download the file and push it with FILE_UPLOAD, which needs no verification.
     */
    protected function sendVideo(TikTokAccount $account, string $path, string $videoUrl, array $extra): string
    {
        try {
            return $this->post($account, $path, $extra + [
                'source_info' => ['source' => 'PULL_FROM_URL', 'video_url' => $videoUrl],
            ])['publish_id'];
        } catch (RuntimeException $e) {
            if (! str_contains($e->getMessage(), 'url_ownership_unverified')) {
                throw $e;
            }
        }

        $bytes = $this->download($videoUrl);
        $size  = strlen($bytes);

        // Chunks: 5–64MB each, last chunk takes the remainder; under 5MB is one chunk.
        $chunk = $size < 5 * 1024 * 1024 ? $size : 10 * 1024 * 1024;
        $count = max(1, intdiv($size, $chunk));

        $data = $this->post($account, $path, $extra + [
            'source_info' => [
                'source'            => 'FILE_UPLOAD',
                'video_size'        => $size,
                'chunk_size'        => $chunk,
                'total_chunk_count' => $count,
            ],
        ]);

        for ($i = 0; $i < $count; $i++) {
            $start = $i * $chunk;
            $end   = $i === $count - 1 ? $size - 1 : $start + $chunk - 1;

            $response = Http::timeout(300)
                ->withHeaders(['Content-Range' => "bytes {$start}-{$end}/{$size}"])
                ->withBody(substr($bytes, $start, $end - $start + 1), 'video/mp4')
                ->put($data['upload_url']);

            if (! $response->successful()) {
                throw new RuntimeException('TikTok video upload failed at chunk '.($i + 1).'/'.$count.': '.$response->status());
            }
        }

        return $data['publish_id'];
    }

    protected function download(string $url): string
    {
        // Our own media: read from public/ instead of looping through nginx.
        $local = public_path(ltrim((string) parse_url($url, PHP_URL_PATH), '/'));
        if (str_starts_with(realpath($local) ?: '', public_path()) && is_file($local)) {
            return file_get_contents($local);
        }

        $response = Http::timeout(300)->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('Could not download video for TikTok upload: '.$response->status());
        }

        return $response->body();
    }

    /** @param  array<int,string>  $imageUrls  JPEG/WebP only, max 35 */
    public function postPhotos(TikTokAccount $account, array $imageUrls, array $postInfo, bool $direct): string
    {
        return $this->post($account, '/post/publish/content/init/', [
            'post_info'   => $this->photoPostInfo($postInfo, $direct),
            'source_info' => [
                'source'            => 'PULL_FROM_URL',
                'photo_cover_index' => 0,
                'photo_images'      => array_slice(array_values($imageUrls), 0, 35),
            ],
            'post_mode'  => $direct ? 'DIRECT_POST' : 'MEDIA_UPLOAD',
            'media_type' => 'PHOTO',
        ])['publish_id'];
    }

    /**
     * Photo posts split text: title max 90 chars, description max 4000.
     * Video-only fields (duet/stitch) are not accepted here.
     */
    protected function photoPostInfo(array $postInfo, bool $direct): array
    {
        $text  = trim((string) ($postInfo['title'] ?? ''));
        $first = trim(strtok($text, "\n") ?: '');
        $info  = [
            'title'       => mb_substr($first, 0, 90),
            'description' => mb_substr($text, 0, 4000),
        ];

        if (! $direct) {
            return $info;
        }

        return $info + [
            'privacy_level'        => $postInfo['privacy_level'],
            'disable_comment'      => (bool) $postInfo['disable_comment'],
            'auto_add_music'       => true,
            'brand_content_toggle' => (bool) $postInfo['brand_content_toggle'],
            'brand_organic_toggle' => (bool) $postInfo['brand_organic_toggle'],
        ];
    }

    public function publishStatus(TikTokAccount $account, string $publishId): array
    {
        return $this->post($account, '/post/publish/status/fetch/', ['publish_id' => $publishId]);
    }

    /** Returns an account whose access token is valid for at least 5 minutes. */
    public function fresh(TikTokAccount $account): TikTokAccount
    {
        if ($account->access_expires_at && $account->access_expires_at->gt(now()->addMinutes(5))) {
            return $account;
        }

        $token = $this->tokenRequest([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ]);
        $this->fillToken($account, $token);
        $account->save();

        return $account;
    }

    protected function fillToken(TikTokAccount $account, array $token): void
    {
        $account->access_token       = $token['access_token'];
        $account->refresh_token      = $token['refresh_token'];
        $account->scope              = $token['scope'] ?? $account->scope;
        $account->access_expires_at  = Carbon::now()->addSeconds((int) ($token['expires_in'] ?? 86400));
        $account->refresh_expires_at = Carbon::now()->addSeconds((int) ($token['refresh_expires_in'] ?? 31536000));
    }

    protected function tokenRequest(array $params): array
    {
        $response = Http::asForm()->timeout(30)->post(self::API.'/oauth/token/', $params + [
            'client_key'    => config('autoposter.tiktok.client_key'),
            'client_secret' => config('autoposter.tiktok.client_secret'),
        ]);
        $body = $response->json() ?: [];

        if (empty($body['access_token'])) {
            throw new RuntimeException('TikTok token request failed: '.($body['error_description'] ?? $body['error'] ?? $response->status()));
        }

        return $body;
    }

    protected function get(TikTokAccount $account, string $path, array $query): array
    {
        $response = Http::withToken($this->fresh($account)->access_token)->timeout(30)->get(self::API.$path, $query);

        return $this->unwrap($response, $path);
    }

    protected function post(TikTokAccount $account, string $path, array $json): array
    {
        $response = Http::withToken($this->fresh($account)->access_token)
            ->timeout(60)
            ->withBody($json ? json_encode($json, JSON_UNESCAPED_UNICODE) : '{}', 'application/json; charset=UTF-8')
            ->post(self::API.$path);

        return $this->unwrap($response, $path);
    }

    protected function unwrap($response, string $path): array
    {
        $body  = $response->json() ?: [];
        $error = $body['error'] ?? [];

        if (! $response->successful() || (($error['code'] ?? 'ok') !== 'ok')) {
            throw new RuntimeException('TikTok '.$path.' failed: '.($error['code'] ?? $response->status()).' '.($error['message'] ?? substr($response->body(), 0, 300)));
        }

        return $body['data'] ?? [];
    }
}
