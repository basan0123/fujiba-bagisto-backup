<?php

namespace Fujiba\AutoPoster\Console\Commands;

use Carbon\Carbon;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\AutoPoster\Services\CaptionGenerator;
use Fujiba\AutoPoster\Services\CollectionPostBuilder;
use Fujiba\AutoPoster\Services\PostCoverRenderer;
use Fujiba\AutoPoster\Services\QueueBuilder;
use Fujiba\AutoPoster\Services\SchedulePlanner;
use Illuminate\Console\Command;

/**
 * autoposter:plan
 *
 * Fills today's remaining slots with scheduled posts. Idempotent: re-running
 * skips slots that already hold a post, so it is safe on a frequent schedule.
 *
 * Runs INSIDE a tenant context (via tenants:run) — the scheduler itself runs on
 * the central DB, so this must never be scheduled bare.
 */
class PlanPostsCommand extends Command
{
    protected $signature = 'autoposter:plan
        {--channel= : Only plan this channel id.}
        {--day= : Plan for this date (Y-m-d) instead of today.}
        {--days=1 : Plan this many days starting today (or --day), e.g. 7 for a week ahead.}
        {--dry-run : Show the slots that would be filled without writing.}';

    protected $description = 'Create scheduled Auto Poster posts for the day\'s remaining slots.';

    public function handle(QueueBuilder $builder, SchedulePlanner $planner, CaptionGenerator $captions, PostCoverRenderer $covers, CollectionPostBuilder $collections): int
    {
        $settings = $this->targets();

        if ($settings->isEmpty()) {
            $this->info('No channels with auto posting enabled.');
            return self::SUCCESS;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $total    = 0;

        foreach ($settings as $setting) {
            $tag = "channel={$setting->channel_id}";

            $tz    = $setting->timezone ?: 'Asia/Tokyo';
            $start = $this->option('day') ? Carbon::parse($this->option('day'), $tz) : null;
            $days  = max(1, (int) $this->option('days'));

            for ($d = 0; $d < $days; $d++) {
                // Day 0 keeps the original "remaining slots today" behaviour (null);
                // later days are planned whole.
                $day = $d === 0 ? $start : ($start ?? Carbon::now($tz))->copy()->startOfDay()->addDays($d);

                if ($isDryRun) {
                    $slots = $planner->upcomingSlotsForDay($setting, $day);
                    $this->line("[$tag] " . count($slots) . ' upcoming slot(s):');
                    foreach ($slots as $slot) {
                        $this->line('  ' . $slot->copy()->setTimezone($setting->timezone ?: 'Asia/Tokyo')->format('Y-m-d H:i T'));
                    }
                    continue;
                }

                $created = $builder->planDay($setting, $planner, $day);
                $total  += count($created);

                // Caption + cover at plan time, so every planned post is ready to
                // review on the phone screen before it goes out. Failures leave
                // the post in place; the publisher fills in a missing caption.
                foreach ($created as $post) {
                    try {
                        // Same-pattern / same-colour collection with stamped photos;
                        // the old random-trio caption + cover is only a fallback.
                        if ($collections->fill($post)) {
                            continue;
                        }

                        $generated = $captions->generate($post->fresh('products'));
                        $post->update(['caption' => $generated['caption'], 'caption_meta' => $generated['meta']]);
                        $covers->render($post);
                    } catch (\Throwable $e) {
                        $this->warn("  #{$post->id} caption/cover failed: ".$e->getMessage());
                    }
                }

                $this->line("[$tag] created " . count($created) . ' post(s)');

                foreach ($created as $post) {
                    $this->line(sprintf(
                        '  #%d scheduled=%s products=%d',
                        $post->id,
                        optional($post->scheduled_at)->toDateTimeString(),
                        $post->products->count()
                    ));
                }
            }
        }

        $this->info("Done. {$total} post(s) created" . ($isDryRun ? ' [DRY RUN]' : '') . '.');

        return self::SUCCESS;
    }

    protected function targets()
    {
        $query = AutoPosterSetting::where('auto_posting_enabled', true);

        if ($channel = $this->option('channel')) {
            $query->where('channel_id', (int) $channel);
        }

        return $query->get();
    }
}
