<?php

namespace Fujiba\AutoPoster\Console\Commands;

use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Services\CollectionPostBuilder;
use Illuminate\Console\Command;

/**
 * autoposter:rebuild-collections
 *
 * Rewrites every still-scheduled post as a same-pattern / same-colour
 * collection (stamped photos, MY/JA/EN caption, some as videos). Keeps each
 * post's time slot. Runs inside a tenant context (tenants:run).
 */
class RebuildCollectionsCommand extends Command
{
    protected $signature = 'autoposter:rebuild-collections {--channel=1} {--dry-run}';

    protected $description = 'Rebuild scheduled Auto Poster posts as pattern/colour collections.';

    public function handle(CollectionPostBuilder $builder): int
    {
        $posts = AutoPosterQueue::where('channel_id', (int) $this->option('channel'))
            ->where('status', AutoPosterQueue::STATUS_SCHEDULED)
            ->whereNull('facebook_post_id')
            ->orderBy('scheduled_at')
            ->get();

        $this->info($posts->count().' scheduled post(s); pool '.$builder->pool()->count().' products.');

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        foreach ($posts as $post) {
            $ok   = $builder->fill($post);
            $meta = $post->fresh()->caption_meta ?? [];
            $this->line(sprintf('  #%d %s %-7s %-6s %s', $post->id, $post->scheduled_at?->format('m-d H:i'),
                $ok ? ($meta['collection'] ?? '') : 'FAILED', ($meta['video'] ?? false) ? 'video' : 'photos',
                implode(' ', $meta['codes'] ?? [])));
        }

        return self::SUCCESS;
    }
}
