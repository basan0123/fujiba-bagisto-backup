<?php

namespace Fujiba\AutoPoster\Console\Commands;

use Carbon\Carbon;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
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
        {--dry-run : Show the slots that would be filled without writing.}';

    protected $description = 'Create scheduled Auto Poster posts for the day\'s remaining slots.';

    public function handle(QueueBuilder $builder, SchedulePlanner $planner): int
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

            $day = $this->option('day')
                ? Carbon::parse($this->option('day'), $setting->timezone ?: 'Asia/Tokyo')
                : null;

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
