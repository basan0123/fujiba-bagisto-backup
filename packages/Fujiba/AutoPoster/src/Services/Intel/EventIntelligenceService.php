<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * K — season / event intelligence.
 *
 * Lunar and variable dates (Thingyan, Thadingyut, Dashain, Tihar, Obon …) move
 * every year, so none of them are hard-coded. An event whose date has not been
 * researched is stored with a NULL date, confidence 0 and status `unknown`, and
 * lands on the owner checklist. A planner may not boost on an unknown date.
 */
class EventIntelligenceService
{
    /**
     * The events that matter to Fujiba's audiences. `fixed` dates are
     * calendar-stable and safe to seed; `variable` ones must be researched per
     * year and are seeded WITHOUT a date on purpose.
     */
    public const CATALOGUE = [
        // Japan
        ['name' => 'New Year (正月)',        'culture' => 'JP', 'category' => 'holiday', 'fixed' => '01-01', 'lead' => 30],
        ['name' => "Mother's Day (母の日)",  'culture' => 'JP', 'category' => 'gifting', 'variable' => true,  'lead' => 21],
        ['name' => "Father's Day (父の日)",  'culture' => 'JP', 'category' => 'gifting', 'variable' => true,  'lead' => 21],
        ['name' => 'Obon (お盆)',            'culture' => 'JP', 'category' => 'holiday', 'variable' => true,  'lead' => 21],
        ['name' => 'Ochugen (お中元)',       'culture' => 'JP', 'category' => 'gifting', 'fixed' => '07-01', 'lead' => 21],
        ['name' => 'Oseibo (お歳暮)',        'culture' => 'JP', 'category' => 'gifting', 'fixed' => '12-01', 'lead' => 21],

        // Myanmar
        ['name' => 'Thingyan (သင်္ကြန်)',     'culture' => 'MM', 'category' => 'holiday', 'variable' => true, 'lead' => 30],
        ['name' => 'Thadingyut (သီတင်းကျွတ်)', 'culture' => 'MM', 'category' => 'gifting', 'variable' => true, 'lead' => 30],
        ['name' => 'Tazaungdaing (တန်ဆောင်တိုင်)', 'culture' => 'MM', 'category' => 'holiday', 'variable' => true, 'lead' => 30],
        ['name' => 'Myanmar wedding season', 'culture' => 'MM', 'category' => 'wedding', 'variable' => true, 'lead' => 45],

        // Nepal
        ['name' => 'Dashain (दशैं)',          'culture' => 'NP', 'category' => 'gifting', 'variable' => true, 'lead' => 30],
        ['name' => 'Tihar (तिहार)',           'culture' => 'NP', 'category' => 'gifting', 'variable' => true, 'lead' => 30],
        ['name' => 'Teej (तीज)',              'culture' => 'NP', 'category' => 'holiday', 'variable' => true, 'lead' => 21],
        ['name' => 'Nepali New Year',        'culture' => 'NP', 'category' => 'holiday', 'variable' => true, 'lead' => 21],
    ];

    /**
     * Seed the catalogue for a year. Fixed dates get a date and modest
     * confidence; variable ones are recorded as unknown, pending research.
     *
     * @return array{created:int,unknown:int}
     */
    public function seedYear(int $year): array
    {
        $created = $unknown = 0;

        foreach (self::CATALOGUE as $e) {
            $exists = DB::table('fi_events')
                ->where('name', $e['name'])->where('year', $year)->exists();

            if ($exists) {
                continue;
            }

            $isVariable = $e['variable'] ?? false;
            $date = $isVariable ? null : Carbon::parse("{$year}-{$e['fixed']}")->toDateString();

            DB::table('fi_events')->insert([
                'name'            => $e['name'],
                'culture'         => $e['culture'],
                'category'        => $e['category'],
                'year'            => $year,
                'start_date'      => $date,
                'end_date'        => $date,
                'lead_time_days'  => $e['lead'],
                'relevance_notes' => $isVariable
                    ? 'Variable/lunar date — must be researched for this year before use.'
                    : 'Calendar-fixed date.',
                // Even a fixed date is not owner-verified yet.
                'confidence'        => $isVariable ? 0 : 0.8,
                'verified_by_owner' => false,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ]);

            $created++;
            $unknown += $isVariable ? 1 : 0;
        }

        return ['created' => $created, 'unknown' => $unknown];
    }

    /** Events a planner may actually use: dated, and not expired. */
    public function usableEvents(?Carbon $from = null, int $horizonDays = 60)
    {
        $from ??= Carbon::now();

        return DB::table('fi_events')
            ->whereNotNull('start_date')
            ->where('confidence', '>', 0)
            ->whereBetween('start_date', [$from->toDateString(), $from->copy()->addDays($horizonDays)->toDateString()])
            ->orderBy('start_date')
            ->get();
    }

    /** Owner checklist — what still needs a researched date. */
    public function unresearched(int $year)
    {
        return DB::table('fi_events')
            ->where('year', $year)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('confidence', 0))
            ->orderBy('culture')->orderBy('name')
            ->get();
    }

    /**
     * Proximity boost for the planner: 1.0 at the ideal lead time, tapering to
     * 0 outside the window. Returns 0 for an unresearched event — an unknown
     * date may never influence a decision.
     */
    public function proximityBoost($event, ?Carbon $now = null): float
    {
        if (! $event->start_date || (float) $event->confidence <= 0) {
            return 0.0;
        }

        $now ??= Carbon::now();
        $days = $now->diffInDays(Carbon::parse($event->start_date), false);

        if ($days < 0 || $days > (int) $event->lead_time_days) {
            return 0.0;
        }

        // Peak when we are right at the lead time, easing off as the date nears.
        return round(min(1.0, ($event->lead_time_days - $days + 1) / max(1, $event->lead_time_days)), 3);
    }

    /** Record a researched date, always with its source. */
    public function recordResearchedDate(int $eventId, string $startDate, int $researchSourceId, float $confidence = 0.8): void
    {
        DB::table('fi_events')->where('id', $eventId)->update([
            'start_date'         => $startDate,
            'end_date'           => $startDate,
            'source_research_id' => $researchSourceId,
            'confidence'         => max(0.0, min(1.0, $confidence)),
            'updated_at'         => Carbon::now(),
        ]);
    }
}
