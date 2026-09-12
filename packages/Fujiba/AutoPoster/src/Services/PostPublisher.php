<?php

namespace Fujiba\AutoPoster\Services;

use Carbon\Carbon;
use Fujiba\AutoPoster\Models\AutoPosterProductHistory;
use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\FacebookCatalog\Exceptions\GraphApiException;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Fujiba\FacebookCatalog\Services\GraphClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Claims a queued post and publishes it to the Facebook Page.
 *
 * The claim/publish split is the heart of the never-publish-twice guarantee:
 *
 *   claim()   — atomic conditional UPDATE. Exactly one worker can move a row
 *               scheduled -> processing, so concurrent runs cannot both proceed.
 *   publish() — refuses outright if facebook_post_id is already set, which
 *               covers a row whose status was edited by hand or replayed.
 *
 * A crash between the Graph call returning and us writing facebook_post_id
 * leaves the row 'processing' with a lock. releaseStale() reclaims those only
 * after a generous timeout, and deliberately sends them to 'failed' rather than
 * back to 'scheduled' — we cannot know whether FB accepted the post, so a human
 * confirms rather than risking a duplicate on the page.
 */
class PostPublisher
{
    /** Rows stuck in processing longer than this are considered abandoned. */
    public const STALE_LOCK_MINUTES = 15;

    public function __construct(
        protected UrlResolver $urls,
    ) {}

    /**
     * Atomically claim a row for this worker.
     *
     * @return string|null  the lock token on success, null if someone else won
     */
    public function claim(AutoPosterQueue $post): ?string
    {
        $token = (string) Str::uuid();

        $claimed = AutoPosterQueue::where('id', $post->id)
            ->where('status', AutoPosterQueue::STATUS_SCHEDULED)
            ->whereNull('lock_token')
            ->whereNull('facebook_post_id')
            ->update([
                'status'     => AutoPosterQueue::STATUS_PROCESSING,
                'lock_token' => $token,
                'locked_at'  => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

        return $claimed === 1 ? $token : null;
    }

    /**
     * Publish a claimed post. Caller must hold the lock token from claim().
     *
     * @return array{status:string,message:string,post_id?:string}
     */
    public function publish(AutoPosterQueue $post, string $lockToken): array
    {
        $post->refresh();

        // Guard 3 — hard stop, regardless of status.
        if (! empty($post->facebook_post_id)) {
            Log::warning('[AutoPoster] refused to republish a post that already has a facebook_post_id', [
                'queue_id'         => $post->id,
                'facebook_post_id' => $post->facebook_post_id,
            ]);

            return ['status' => 'error', 'message' => 'Already published'];
        }

        if ($post->lock_token !== $lockToken) {
            return ['status' => 'error', 'message' => 'Lock lost — another worker owns this post'];
        }

        $settings = AutoPosterSetting::where('channel_id', $post->channel_id)->first();
        $catalog  = FacebookCatalogSetting::where('channel_id', $post->channel_id)->first();

        if (! $catalog || empty($catalog->page_id) || empty($catalog->access_token)) {
            return $this->fail($post, $settings, 'Facebook page/token not configured for this channel', null);
        }

        if (empty(trim((string) $post->caption))) {
            return $this->fail($post, $settings, 'Post has no caption', null);
        }

        $images = $post->products()->get()
            ->map(fn ($p) => $p->effectiveImageUrl())
            ->filter()
            ->values()
            ->all();

        if (empty($images)) {
            return $this->fail($post, $settings, 'Post has no usable product images', null);
        }

        $client  = new GraphClient($catalog->access_token);
        $message = $this->composeMessage($post);

        try {
            $response = $this->sendToFacebook($client, $post, $catalog->page_id, $message, $images);

            $postId = $response['id'] ?? null;

            if (! $postId) {
                return $this->fail($post, $settings, 'Facebook returned no post id', $response);
            }

            $permalink = null;
            try {
                $permalink = $client->getPost($postId)['permalink_url'] ?? null;
            } catch (Throwable $e) {
                // Cosmetic only — the post IS live. Never fail a published post
                // because the permalink lookup hiccuped.
                Log::info('[AutoPoster] permalink lookup failed (post is published)', [
                    'queue_id' => $post->id,
                    'post_id'  => $postId,
                    'error'    => $e->getMessage(),
                ]);
            }

            $this->markPublished($post, $postId, $permalink, $response);

            return ['status' => 'success', 'message' => 'Published', 'post_id' => $postId];
        } catch (Throwable $e) {
            // A GraphApiException carries the status and decoded body; anything
            // else (timeout, local error) falls back to scraping what structure
            // it can — the admin UI's failure panel is much more useful with
            // the raw Facebook payload than a flattened message string.
            $response = $e instanceof GraphApiException
                ? $e->errorBody()
                : $this->extractApiResponse($e);

            return $this->fail($post, $settings, $e->getMessage(), $response, $e);
        }
    }

    /**
     * Best-effort recovery of the raw Graph payload from a thrown error.
     * Returns null when the exception carries no JSON body (e.g. a timeout).
     */
    protected function extractApiResponse(Throwable $e): ?array
    {
        // Laravel's RequestException exposes the response directly.
        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            $json = $e->response->json();

            return is_array($json) ? $json : ['raw_body' => Str::limit($e->response->body(), 4000, '')];
        }

        // GraphClient's RuntimeException embeds the message only; keep the JSON
        // fragment if one is present so error codes/subcodes survive.
        if (preg_match('/\{.*\}/s', $e->getMessage(), $m)) {
            $decoded = json_decode($m[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['message' => Str::limit($e->getMessage(), 2000, '')];
    }

    /**
     * Format dispatch. multi_photo uploads each image unpublished, then makes
     * one feed post referencing them all; single_image uses a link post so the
     * m.me CTA renders as a real link preview.
     */
    protected function sendToFacebook(
        GraphClient $client,
        AutoPosterQueue $post,
        string $pageId,
        string $message,
        array $images,
    ): array {
        if ($post->format === AutoPosterSetting::FORMAT_SINGLE_IMAGE) {
            $link = $post->messenger_destination ?: $this->urls->messengerUrl($pageId, $post->messenger_ref);

            return $client->publishLinkPost($pageId, $message, $link);
        }

        $mediaIds = [];
        foreach ($post->products()->get() as $item) {
            $url = $item->effectiveImageUrl();
            if (! $url) {
                continue;
            }

            $uploaded = $client->uploadUnpublishedPhoto($pageId, $url);
            $mediaId  = $uploaded['id'] ?? null;

            if ($mediaId) {
                $mediaIds[] = $mediaId;
                $item->update(['fb_media_id' => $mediaId]);
            } else {
                // One bad photo should not sink the whole post; log and carry on
                // with the rest, provided at least one survives.
                Log::warning('[AutoPoster] photo upload returned no media id', [
                    'queue_id'   => $post->id,
                    'product_id' => $item->product_id,
                    'image_url'  => $url,
                ]);
            }
        }

        if (empty($mediaIds)) {
            throw new \RuntimeException('No photos could be uploaded to Facebook');
        }

        return $client->publishMultiPhotoPost($pageId, $message, $mediaIds);
    }

    /**
     * Caption + Messenger CTA. The CTA is appended to the message body because
     * an organic multi-photo post has no button — the m.me link in the text is
     * the Send Message affordance.
     */
    protected function composeMessage(AutoPosterQueue $post): string
    {
        $caption = trim((string) $post->caption);
        $cta     = trim((string) $post->messenger_destination);

        if ($cta === '' || str_contains($caption, $cta)) {
            return $caption;
        }

        return $caption . "\n\n" . $cta;
    }

    protected function markPublished(AutoPosterQueue $post, string $postId, ?string $permalink, array $response): void
    {
        DB::transaction(function () use ($post, $postId, $permalink, $response) {
            $post->update([
                'status'             => AutoPosterQueue::STATUS_PUBLISHED,
                'facebook_post_id'   => $postId,
                'facebook_permalink' => $permalink,
                'published_at'       => Carbon::now(),
                'last_api_response'  => $response,
                'last_error'         => null,
                'last_error_at'      => null,
                'lock_token'         => null,
                'locked_at'          => null,
                'next_retry_at'      => null,
            ]);

            $this->recordHistory($post, $postId);
        });
    }

    /**
     * Bump per-product history — the cooldown/rotation source of truth.
     * Written on success only: a failed post must not burn a product's cooldown.
     */
    protected function recordHistory(AutoPosterQueue $post, string $postId): void
    {
        foreach ($post->products()->get() as $item) {
            $history = AutoPosterProductHistory::firstOrNew([
                'product_id' => $item->product_id,
                'channel_id' => $post->channel_id,
            ]);

            $history->last_posted_at   = Carbon::now();
            $history->post_count       = (int) $history->post_count + 1;
            $history->facebook_post_id = $postId;
            $history->status           = AutoPosterQueue::STATUS_PUBLISHED;
            $history->save();
        }
    }

    /**
     * Record a failure and schedule a backoff retry only when retrying could
     * plausibly succeed. The lock is always released so the row is not stranded.
     *
     * Permanent errors must NOT be retried. A Graph #200 ("not allowed to
     * publish"), #190 (bad token) or #100 (wrong object id) is a configuration
     * problem: re-sending it on a 5/25/125-minute timer just reproduces the
     * same 403 three more times, buries the real cause under duplicate failure
     * rows, and spends the app's rate-limit budget. Those rows go straight to
     * failed with next_retry_at = NULL and wait for an admin to fix the config
     * and press Retry. Only throttles and transport/5xx failures back off.
     */
    protected function fail(
        AutoPosterQueue $post,
        ?AutoPosterSetting $settings,
        string $error,
        ?array $response,
        ?Throwable $exception = null,
    ): array {
        $attempts   = (int) $post->attempts + 1;
        $maxRetries = (int) ($settings->max_retries ?? 3);

        $retryable      = $this->isRetryable($exception, $response);
        $classification = $retryable ? 'transient' : 'permanent';

        $canRetry = $retryable
            && ($settings?->auto_retry_enabled ?? true)
            && $attempts <= $maxRetries;

        // Exponential backoff: 5min, 25min, 125min...
        $nextRetry = $canRetry
            ? Carbon::now()->addMinutes(5 ** min($attempts, 3))
            : null;

        $post->update([
            'status'            => AutoPosterQueue::STATUS_FAILED,
            'attempts'          => $attempts,
            'last_error'        => Str::limit($error, 2000, ''),
            'last_error_at'     => Carbon::now(),
            'last_api_response' => $response,
            'next_retry_at'     => $nextRetry,
            'lock_token'        => null,
            'locked_at'         => null,
        ]);

        Log::error('[AutoPoster] publish failed', [
            'queue_id'       => $post->id,
            'channel_id'     => $post->channel_id,
            'attempts'       => $attempts,
            'error'          => $error,
            'classification' => $classification,
            'graph_code'     => $exception instanceof GraphApiException ? $exception->graphCode() : null,
            'next_retry'     => $nextRetry?->toDateTimeString(),
        ]);

        return [
            'status'         => 'error',
            'message'        => $error,
            'classification' => $classification,
            'will_retry'     => $canRetry,
        ];
    }

    /**
     * Is this failure worth another attempt?
     *
     * Prefers the typed exception, which carries the HTTP status and the parsed
     * Graph body. Falls back to the recovered response array so a failure that
     * arrived without a GraphApiException (a legacy caller, or a body scraped
     * out of a plain exception message) is still classified rather than
     * defaulting to "retry" and reintroducing the hammering.
     *
     * A local pre-flight failure (no exception, no response — "no caption",
     * "no usable images") is a permanent content problem, not a transport
     * blip: nothing about waiting five minutes adds a caption.
     */
    protected function isRetryable(?Throwable $exception, ?array $response): bool
    {
        if ($exception instanceof GraphApiException) {
            return $exception->isRetryable();
        }

        $code = $response['error']['code'] ?? null;

        if (is_numeric($code)) {
            $code = (int) $code;

            if (in_array($code, GraphApiException::RETRYABLE_CODES, true)) {
                return true;
            }

            if (in_array($code, GraphApiException::PERMANENT_CODES, true)) {
                return false;
            }

            // An unrecognised Graph error code still came from Facebook
            // rejecting the call, so treat it as permanent.
            return false;
        }

        // A connection-level exception (timeout, DNS) never produced a body.
        if ($exception instanceof ConnectionException) {
            return true;
        }

        // No exception at all: a local guard rejected the post before any
        // outbound call. Permanent until the content or config changes.
        if (! $exception) {
            return false;
        }

        // Unknown throwable with no Graph body — allow the normal backoff.
        return true;
    }

    /**
     * Reclaim rows abandoned mid-publish by a dead worker.
     *
     * These go to 'failed', never back to 'scheduled': the row was claimed and
     * an outbound call may have reached Facebook, so auto-retrying could double
     * post to a live page. An admin checks the page and hits Retry.
     *
     * @return int  rows released
     */
    public function releaseStale(?int $channelId = null): int
    {
        $cutoff = Carbon::now()->subMinutes(self::STALE_LOCK_MINUTES);

        $query = AutoPosterQueue::where('status', AutoPosterQueue::STATUS_PROCESSING)
            ->whereNotNull('locked_at')
            ->where('locked_at', '<', $cutoff)
            ->whereNull('facebook_post_id');

        if ($channelId !== null) {
            $query->where('channel_id', $channelId);
        }

        $stale = $query->get();

        foreach ($stale as $post) {
            $post->update([
                'status'        => AutoPosterQueue::STATUS_FAILED,
                'last_error'    => 'Worker did not finish within '
                    . self::STALE_LOCK_MINUTES
                    . ' minutes; lock released. Check the Facebook page before retrying — '
                    . 'the post may or may not have gone out.',
                'last_error_at' => Carbon::now(),
                'lock_token'    => null,
                'locked_at'     => null,
                'next_retry_at' => null,
            ]);

            Log::warning('[AutoPoster] released stale lock', ['queue_id' => $post->id]);
        }

        return $stale->count();
    }
}
