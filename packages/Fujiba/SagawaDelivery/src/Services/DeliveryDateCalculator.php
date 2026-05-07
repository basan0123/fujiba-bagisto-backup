<?php

namespace Fujiba\SagawaDelivery\Services;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Fujiba\SagawaDelivery\Models\Setting;

class DeliveryDateCalculator
{
    /**
     * @param  array<string,int>  $zoneDays  prefecture => transit days
     * @param  array<int,string>  $blackoutDates ISO dates the warehouse is closed
     * @param  array<int,int>     $weeklyDefault weekday ints (0=Sun..6=Sat) we CAN prepare on
     * @param  array<int,string>  $extraPrepDates ISO dates that override weekly_default to allow prep
     */
    public function __construct(
        private int $processingDays,
        private int $cutoffHour,
        private int $cutoffMinute,
        private int $maxDaysAhead,
        private array $zoneDays,
        private array $blackoutDates = [],
        private array $weeklyDefault = [1, 2, 3, 4, 5],
        private array $extraPrepDates = [],
    ) {}

    public static function fromConfig(): self
    {
        $zoneDays = (array) config('sagawa.zone_days', []);

        if ($zoneDays === []) {
            foreach ((array) config('sagawa.prefectures', []) as $name => $row) {
                $zoneDays[$name] = (int) ($row['days'] ?? 2);
            }
        }

        return new self(
            processingDays: (int) config('sagawa.processing_days', 1),
            cutoffHour:     (int) config('sagawa.cutoff_hour', 22),
            cutoffMinute:   (int) config('sagawa.cutoff_minute', 30),
            maxDaysAhead:   (int) config('sagawa.max_days_ahead', 30),
            zoneDays:       $zoneDays,
            blackoutDates:  (array) config('sagawa.blackout_dates', []),
        );
    }

    /**
     * Build a calculator from the per-channel sagawa_shipping_settings row.
     * Falls back to config defaults for any field the row doesn't set.
     */
    public static function forChannel(int $channelId, ?string $tenantId = null): self
    {
        $tenantId ??= self::resolveTenantId();

        $row = Setting::query()
            ->where('tenant_id', $tenantId)
            ->where('channel_id', $channelId)
            ->first();

        $cfg = self::fromConfig();

        if (! $row) {
            return $cfg;
        }

        $weekly = $row->weekly_default;
        if (! is_array($weekly) || $weekly === []) {
            $weekly = [1, 2, 3, 4, 5];
        }

        return new self(
            processingDays: $row->processing_days ?? $cfg->processingDays,
            cutoffHour:     $row->cutoff_hour     ?? $cfg->cutoffHour,
            cutoffMinute:   $row->cutoff_minute   ?? $cfg->cutoffMinute,
            maxDaysAhead:   $row->max_days_ahead  ?? $cfg->maxDaysAhead,
            zoneDays:       is_array($row->zone_days) && $row->zone_days !== [] ? $row->zone_days : $cfg->zoneDays,
            blackoutDates:  is_array($row->blackout_dates) ? $row->blackout_dates : [],
            weeklyDefault:  array_map('intval', $weekly),
            extraPrepDates: is_array($row->extra_prep_dates) ? $row->extra_prep_dates : [],
        );
    }

    /**
     * Earliest deliverable date for a given prefecture.
     * Uses JST to evaluate the cutoff.
     */
    public function earliest(string $prefecture, ?Carbon $orderedAt = null): CarbonImmutable
    {
        $ordered = ($orderedAt ?: now('Asia/Tokyo'))->copy()->setTimezone('Asia/Tokyo');

        $transit = $this->zoneDays[$prefecture] ?? 2;

        $prepNeeded = $this->processingDays + ($this->isPastCutoff($ordered) ? 1 : 0);

        // Walk forward from today consuming prep-eligible days until we've earned
        // $prepNeeded of them. The next day after that is the dispatch day, then
        // add transit. This makes blackouts and weekly-off days extend the ETA
        // rather than just shifting the final delivery date.
        $cursor = CarbonImmutable::parse($ordered->format('Y-m-d'), 'Asia/Tokyo');
        $earned = 0;
        while ($earned < $prepNeeded) {
            if ($this->canPrepOn($cursor)) {
                $earned++;
            }
            $cursor = $cursor->addDay();
        }

        return $cursor->addDays($transit);
    }

    /**
     * @return array<int,string>
     */
    public function availableDates(string $prefecture, ?Carbon $orderedAt = null): array
    {
        $start = $this->earliest($prefecture, $orderedAt);
        $end   = $start->addDays(max(0, $this->maxDaysAhead - 1));

        $out = [];
        for ($d = $start; $d->lessThanOrEqualTo($end); $d = $d->addDay()) {
            $out[] = $d->format('Y-m-d');
        }

        return $out;
    }

    public function isSelectable(string $prefecture, string $isoDate, ?Carbon $orderedAt = null): bool
    {
        return in_array($isoDate, $this->availableDates($prefecture, $orderedAt), true);
    }

    private function isPastCutoff(Carbon $ordered): bool
    {
        $orderMinutes  = $ordered->hour * 60 + $ordered->minute;
        $cutoffMinutes = $this->cutoffHour * 60 + $this->cutoffMinute;

        return $orderMinutes >= $cutoffMinutes;
    }

    private function canPrepOn(CarbonImmutable $date): bool
    {
        $iso = $date->format('Y-m-d');

        if (in_array($iso, $this->blackoutDates, true)) {
            return false;
        }

        if (in_array($iso, $this->extraPrepDates, true)) {
            return true;
        }

        return in_array((int) $date->dayOfWeek, $this->weeklyDefault, true);
    }

    private static function resolveTenantId(): string
    {
        if (function_exists('tenant') && tenant()) {
            return (string) tenant()->id;
        }

        return 'central';
    }
}
