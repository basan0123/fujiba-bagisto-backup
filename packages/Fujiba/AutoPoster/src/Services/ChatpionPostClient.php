<?php

namespace Fujiba\AutoPoster\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Publishes page posts through ChatPion's `fujiba_post/publish` endpoint, which
 * posts with the page access token ChatPion already stores. Used because the
 * Facebook Catalog system-user token only carries catalog_management and
 * cannot post to the page.
 *
 * The API key is read from central livechat_settings (the same key the
 * LiveChat package uses) and never logged.
 */
class ChatpionPostClient
{
    /**
     * @param  array<int,string>  $imageUrls  public https URLs, max 10
     * @return array{id:string}
     */
    public function publish(string $pageId, string $message, array $imageUrls, ?string $videoUrl = null): array
    {
        $response = Http::asForm()
            ->timeout(1200)
            ->post(rtrim(config('autoposter.chatpion_url'), '/').'/fujiba_post/publish', [
                'api_key'    => $this->apiKey(),
                'page_id'    => $pageId,
                'message'    => $message,
                'image_urls' => array_slice(array_values($imageUrls), 0, 60),
            ] + ($videoUrl ? ['video_url' => $videoUrl] : []));

        $body = $response->json() ?: [];

        if (! $response->successful() || (int) ($body['status'] ?? 0) !== 1 || empty($body['post_id'])) {
            throw new RuntimeException('ChatPion publish failed ('.$response->status().'): '.($body['error'] ?? substr($response->body(), 0, 300)));
        }

        return ['id' => (string) $body['post_id']];
    }

    /**
     * Same post on the Instagram account linked to the page. ChatPion turns
     * the images into Instagram-ready JPEGs (carousel when 2+), or posts the
     * video as a Reel.
     *
     * @return array{id:string,permalink:?string}
     */
    public function publishInstagram(string $pageId, string $message, array $imageUrls, ?string $videoUrl = null): array
    {
        $body = $this->call('publish_instagram', [
            'page_id'    => $pageId,
            'message'    => $message,
            'image_urls' => array_slice(array_values($imageUrls), 0, 10),
        ] + ($videoUrl ? ['video_url' => $videoUrl] : []), 900);

        if (empty($body['media_id'])) {
            throw new RuntimeException('Instagram publish returned no media id');
        }

        return ['id' => (string) $body['media_id'], 'permalink' => $body['permalink'] ?? null];
    }

    /** @return array{ig_user_id:string,username:?string,can_publish:bool,quota_usage:?int,error:?string} */
    public function instagramStatus(string $pageId): array
    {
        return $this->call('instagram_status', ['page_id' => $pageId], 60);
    }

    protected function call(string $action, array $params, int $timeout): array
    {
        $response = Http::asForm()
            ->timeout($timeout)
            ->post(rtrim(config('autoposter.chatpion_url'), '/').'/fujiba_post/'.$action, ['api_key' => $this->apiKey()] + $params);

        $body = $response->json() ?: [];

        if (! $response->successful() || (int) ($body['status'] ?? 0) !== 1) {
            throw new RuntimeException('ChatPion '.$action.' failed ('.$response->status().'): '.($body['error'] ?? substr($response->body(), 0, 300)));
        }

        return $body;
    }

    public function permalink(string $postId): string
    {
        return 'https://www.facebook.com/'.$postId;
    }

    protected function apiKey(): string
    {
        $key = DB::connection(config('tenancy.database.central_connection', 'mysql'))
            ->table('livechat_settings')
            ->whereNotNull('chatpion_api_key')
            ->where('chatpion_api_key', '<>', '')
            ->value('chatpion_api_key');

        if (! $key) {
            throw new RuntimeException('ChatPion API key is not configured in LiveChat settings');
        }

        return (string) $key;
    }
}
