<?php

namespace Fujiba\AutoPoster\Console\Commands;

use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Services\ChatpionPostClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cross-posts Facebook-published posts to the linked Instagram account.
 *
 *   autoposter:instagram            send pending (and retry failed, < max attempts)
 *   autoposter:instagram --status   check the linked account / permission only
 *   autoposter:instagram --id=12    queue + send one already-published post
 *
 * instagram_media_id is the never-resend guard, like facebook_post_id.
 */
class PublishInstagramCommand extends Command
{
    protected $signature = 'autoposter:instagram {--status} {--id=} {--limit=5}';

    protected $description = 'Publish AutoPoster posts to Instagram (same caption/photos as Facebook)';

    public function handle(ChatpionPostClient $client): int
    {
        if ($this->option('status')) {
            foreach (config('autoposter.page_ids') as $channel => $pageId) {
                try {
                    $s = $client->instagramStatus((string) $pageId);
                    $this->info("channel {$channel}: @{$s['username']} ({$s['ig_user_id']}) can_publish=".json_encode($s['can_publish'])." quota_used_24h=".($s['quota_usage'] ?? '?').($s['error'] ? ' error: '.$s['error'] : ''));
                } catch (Throwable $e) {
                    $this->error("channel {$channel}: ".$e->getMessage());
                }
            }

            return self::SUCCESS;
        }

        if (! Schema::hasTable('autoposter_queue') || ! Schema::hasColumn('autoposter_queue', 'instagram_status')) {
            $this->line('skipped: instagram schema absent');

            return self::SUCCESS;
        }

        if ($id = $this->option('id')) {
            $post = AutoPosterQueue::findOrFail($id);
            if (! $post->facebook_post_id || $post->instagram_media_id) {
                $this->error('Post must be live on Facebook and not yet on Instagram');

                return self::FAILURE;
            }
            if (! $post->instagram_payload) {
                $this->error('No stored media for this post (published before Instagram was enabled)');

                return self::FAILURE;
            }
            $post->update(['instagram_status' => 'pending', 'instagram_attempts' => 0]);
            $posts = collect([$post]);
        } else {
            if (! config('autoposter.instagram.enabled')) {
                $this->line('Instagram cross-posting disabled (AUTOPOSTER_INSTAGRAM_ENABLED)');

                return self::SUCCESS;
            }

            $posts = AutoPosterQueue::query()
                ->whereIn('instagram_status', ['pending', 'failed'])
                ->whereNull('instagram_media_id')
                ->whereNotNull('facebook_post_id')
                ->where('instagram_attempts', '<', config('autoposter.instagram.max_attempts', 3))
                ->where('published_at', '>=', Carbon::now()->subDays(2))
                ->orderBy('published_at')
                ->limit((int) $this->option('limit'))
                ->get();
        }

        foreach ($posts as $post) {
            $p = $post->instagram_payload;
            $post->increment('instagram_attempts');

            try {
                $res = $client->publishInstagram($p['page_id'], $p['message'], $p['images'] ?? [], $p['video'] ?? null);
                $post->update([
                    'instagram_status'       => 'published',
                    'instagram_media_id'     => $res['id'],
                    'instagram_permalink'    => $res['permalink'],
                    'instagram_error'        => null,
                    'instagram_published_at' => Carbon::now(),
                ]);
                $this->info("#{$post->id} → Instagram {$res['permalink']}");
            } catch (Throwable $e) {
                $post->update(['instagram_status' => 'failed', 'instagram_error' => Str::limit($e->getMessage(), 2000, '')]);
                $this->error("#{$post->id} failed: ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
