<?php

namespace Fujiba\AutoPoster\Console\Commands;

use Carbon\Carbon;
use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\AutoPoster\Services\PostPublisher;
use Illuminate\Console\Command;

/**
 * autoposter:dispatch
 *
 * Publishes posts whose scheduled_at has arrived, plus failed posts whose
 * backoff has elapsed. Claims each row atomically, so running two copies
 * concurrently is safe.
 *
 * Runs INSIDE a tenant context (via tenants:run).
 */
class DispatchPostsCommand extends Command
{
    protected $signature = 'autoposter:dispatch
        {--channel= : Only dispatch this channel id.}
        {--limit=10 : Max posts to publish per run.}
        {--post= : Publish one specific queue id (used by Post Now).}
        {--dry-run : List what would be published without calling Facebook.}';

    protected $description = 'Publish due Auto Poster posts to Facebook.';

    public function handle(PostPublisher $publisher): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        // Reclaim anything a dead worker left mid-flight before looking for
        // new work, or those rows would hold their slot forever.
        if (! $isDryRun) {
            $released = $publisher->releaseStale($this->option('channel') ? (int) $this->option('channel') : null);
            if ($released > 0) {
                $this->warn("Released {$released} stale lock(s) — these need manual review before retry.");
            }
        }

        $due = $this->duePosts();

        if ($due->isEmpty()) {
            $this->info('Nothing due.');
            return self::SUCCESS;
        }

        $published = 0;
        $failed    = 0;
        $skipped   = 0;

        foreach ($due as $post) {
            $tag = "post #{$post->id} channel={$post->channel_id}";

            if ($isDryRun) {
                $this->line("[$tag] would publish (scheduled " . optional($post->scheduled_at)->toDateTimeString() . ')');
                continue;
            }

            // Auto posting can be switched off between planning and dispatch;
            // honour the master switch at send time, not just at plan time.
            if (! $this->channelEnabled($post)) {
                $this->line("[$tag] skipped: auto posting disabled for channel");
                $skipped++;
                continue;
            }

            // A failed row being retried must go back to 'scheduled' so claim()
            // — which only matches 'scheduled' — can take it.
            if ($post->status === AutoPosterQueue::STATUS_FAILED) {
                AutoPosterQueue::where('id', $post->id)
                    ->where('status', AutoPosterQueue::STATUS_FAILED)
                    ->update(['status' => AutoPosterQueue::STATUS_SCHEDULED]);
                $post->refresh();
            }

            $token = $publisher->claim($post);

            if (! $token) {
                $this->line("[$tag] skipped: could not claim (another worker, or already published)");
                $skipped++;
                continue;
            }

            $result = $publisher->publish($post, $token);

            if (($result['status'] ?? '') === 'success') {
                $this->info("[$tag] published: " . ($result['post_id'] ?? '?'));
                $published++;
            } else {
                $this->error("[$tag] failed: " . ($result['message'] ?? 'unknown'));
                $failed++;
            }
        }

        $this->line('');
        $this->info(sprintf(
            'Done. published=%d failed=%d skipped=%d%s',
            $published, $failed, $skipped, $isDryRun ? ' [DRY RUN]' : ''
        ));

        return self::SUCCESS;
    }

    protected function duePosts()
    {
        $limit = max(1, (int) $this->option('limit'));

        // Single-post mode (Post Now) bypasses the schedule but not the locks.
        if ($postId = $this->option('post')) {
            return AutoPosterQueue::where('id', (int) $postId)
                ->whereIn('status', [AutoPosterQueue::STATUS_SCHEDULED, AutoPosterQueue::STATUS_FAILED])
                ->whereNull('facebook_post_id')
                ->get();
        }

        $now = Carbon::now();

        $query = AutoPosterQueue::whereNull('facebook_post_id')
            ->where(function ($q) use ($now) {
                $q->where(function ($q2) use ($now) {
                    $q2->where('status', AutoPosterQueue::STATUS_SCHEDULED)
                        ->whereNotNull('scheduled_at')
                        ->where('scheduled_at', '<=', $now);
                })->orWhere(function ($q2) use ($now) {
                    // Backoff retries
                    $q2->where('status', AutoPosterQueue::STATUS_FAILED)
                        ->whereNotNull('next_retry_at')
                        ->where('next_retry_at', '<=', $now);
                });
            })
            ->orderBy('scheduled_at');

        if ($channel = $this->option('channel')) {
            $query->where('channel_id', (int) $channel);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Master switch check. A manual "Post Now" is an explicit admin action and
     * is allowed through even when auto posting is off — turning off automation
     * should not disable the manual button.
     */
    protected function channelEnabled(AutoPosterQueue $post): bool
    {
        if ($post->origin === AutoPosterQueue::ORIGIN_MANUAL || $this->option('post')) {
            return true;
        }

        return (bool) AutoPosterSetting::where('channel_id', $post->channel_id)
            ->value('auto_posting_enabled');
    }
}
