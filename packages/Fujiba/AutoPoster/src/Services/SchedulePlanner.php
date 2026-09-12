<?php

namespace Fujiba\AutoPoster\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Fujiba\AutoPoster\Models\AutoPosterSetting;

/**
 * Works out WHEN posts should go out on a given day.
 *
 * Two sources, in priority order:
 *   1. settings.posting_times — explicit "HH:MM" slots the admin pinned.
 *   2. Auto-distribution across [posting_window_start, posting_window_end].
 *
 * All arithmetic happens in the channel's configured timezone (Asia/Tokyo by
 * default), which is what keeps "5 posts between 09:00 and 21:30" true across
 * DST-less JST and any future timezone an admin picks.
 *
 * Returned times are then converted to the APPLICATION timezone, not UTC.
 * That is deliberate: Eloquent serialises a datetime by formatting it in
 * app.timezone, and the worker compares scheduled_at against Carbon::now(),
 * which is also app-timezone. Returning UTC here would write a naive UTC
 * wall-clock value into a column the app reads back as app-local — on this
 * install (app.timezone = Asia/Tokyo) that silently shifted every post nine
 * hours earlier, into the middle of the night. Convert once, at the boundary.
 */
class SchedulePlanner
{
    /**
     * Slots for one day.
     *
     * @return array<int,CarbonInterface>  UTC timestamps, ascending
     */
    public function slotsForDay(AutoPosterSetting $settings, ?CarbonInterface $day = null): array
    {
        $tz  = $settings->timezone ?: 'Asia/Tokyo';
        $day = $day ? $day->copy()->setTimezone($tz) : Carbon::now($tz);

        $explicit = $this->explicitTimes($settings);

        $local = ! empty($explicit)
            ? $this->fromExplicitTimes($day, $explicit)
            : $this->autoDistribute($day, $settings);

        $appTz = config('app.timezone') ?: 'UTC';

        return array_map(fn (CarbonInterface $t) => $t->copy()->setTimezone($appTz), $local);
    }

    /**
     * Slots still in the future for the given day — what the planner should
     * actually fill. A run at 14:00 must not create posts for 09:00.
     *
     * @return array<int,CarbonInterface>
     */
    public function upcomingSlotsForDay(AutoPosterSetting $settings, ?CarbonInterface $day = null): array
    {
        // Same clock the worker's duePosts() query uses.
        $now = Carbon::now();

        return array_values(array_filter(
            $this->slotsForDay($settings, $day),
            fn (CarbonInterface $slot) => $slot->greaterThan($now)
        ));
    }

    /**
     * Normalised "HH:MM" list, or [] when auto-distributing.
     *
     * @return array<int,string>
     */
    public function explicitTimes(AutoPosterSetting $settings): array
    {
        $times = $settings->posting_times;

        if (! is_array($times) || empty($times)) {
            return [];
        }

        $valid = [];
        foreach ($times as $t) {
            $t = trim((string) $t);
            if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $t)) {
                $valid[$t] = true; // key dedupes
            }
        }

        $valid = array_keys($valid);
        sort($valid);

        return $valid;
    }

    /**
     * @param  array<int,string>  $times
     * @return array<int,CarbonInterface>
     */
    protected function fromExplicitTimes(CarbonInterface $day, array $times): array
    {
        $slots = [];

        foreach ($times as $t) {
            [$h, $m] = explode(':', $t);
            $slots[] = $day->copy()->setTime((int) $h, (int) $m, 0);
        }

        return $slots;
    }

    /**
     * Spread posts_per_day evenly across the window.
     *
     * Interior spacing: with N posts the window is cut into N slots and each
     * post sits at the slot's midpoint. That keeps the first and last posts off
     * the exact window edges (so a 09:00 window does not always fire at 09:00
     * sharp, which reads as robotic) while holding the gaps uniform.
     *
     * A small deterministic jitter is then applied — derived from the date and
     * slot index rather than rand(), so the same day always yields the same
     * times. Re-planning a day must not shuffle already-scheduled posts.
     *
     * @return array<int,CarbonInterface>
     */
    protected function autoDistribute(CarbonInterface $day, AutoPosterSetting $settings): array
    {
        $count = max(1, (int) $settings->posts_per_day);

        $start = $this->timeOnDay($day, $settings->posting_window_start ?: '09:00:00');
        $end   = $this->timeOnDay($day, $settings->posting_window_end ?: '21:30:00');

        // A window that ends before it starts (or is zero-length) cannot be
        // divided; fall back to the start instant rather than emitting times
        // that run backwards into the previous day.
        if ($end->lessThanOrEqualTo($start)) {
            return [$start];
        }

        $windowSeconds = $end->diffInSeconds($start);
        $slotSeconds   = (int) floor($windowSeconds / $count);

        // Jitter must never push a post into its neighbour's slot, so cap it at
        // a quarter of the slot on either side of the midpoint.
        $maxJitter = (int) floor($slotSeconds / 4);

        $slots = [];
        for ($i = 0; $i < $count; $i++) {
            $midpoint = (int) ($i * $slotSeconds + $slotSeconds / 2);
            $jitter   = $maxJitter > 0
                ? ($this->deterministicOffset($day, $i) % (2 * $maxJitter + 1)) - $maxJitter
                : 0;

            $slots[] = $start->copy()->addSeconds($midpoint + $jitter);
        }

        return $slots;
    }

    /**
     * Stable pseudo-random offset for (day, slot) — same input, same output.
     */
    protected function deterministicOffset(CarbonInterface $day, int $index): int
    {
        return (int) hexdec(substr(md5($day->format('Y-m-d') . ':' . $index), 0, 6));
    }

    protected function timeOnDay(CarbonInterface $day, string $time): CarbonInterface
    {
        $parts = explode(':', $time);

        return $day->copy()->setTime(
            (int) ($parts[0] ?? 0),
            (int) ($parts[1] ?? 0),
            (int) ($parts[2] ?? 0)
        );
    }
}
