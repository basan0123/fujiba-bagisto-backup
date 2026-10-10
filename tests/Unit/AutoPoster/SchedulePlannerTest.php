<?php

require_once __DIR__.'/AutoPosterTestCase.php';

use Carbon\Carbon;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\AutoPoster\Services\SchedulePlanner;

uses(Tests\Unit\AutoPoster\AutoPosterTestCase::class);

function spSettings(array $a = []): AutoPosterSetting
{
    return (new AutoPosterSetting)->forceFill(array_merge(['timezone' => 'Asia/Tokyo', 'posts_per_day' => 3,
        'posting_window_start' => '09:00:00', 'posting_window_end' => '21:00:00', 'posting_times' => null], $a));
}

beforeEach(fn () => config(['app.timezone' => 'Asia/Tokyo']));
afterEach(fn () => Carbon::setTestNow());

it('normalises, validates, dedupes and sorts explicit times', function () {
    $s = spSettings(['posting_times' => ['18:00', ' 09:30 ', '25:00', '9:00', '18:00', 'x']]);
    expect((new SchedulePlanner)->explicitTimes($s))->toBe(['09:30', '18:00']);
});

it('uses explicit times when set', function () {
    $s = spSettings(['posting_times' => ['10:15', '20:00']]);
    $slots = (new SchedulePlanner)->slotsForDay($s, Carbon::parse('2026-10-12', 'Asia/Tokyo'));
    expect(array_map(fn ($t) => $t->format('Y-m-d H:i'), $slots))->toBe(['2026-10-12 10:15', '2026-10-12 20:00']);
});

it('auto-distributes posts_per_day inside the window, ascending', function () {
    $slots = (new SchedulePlanner)->slotsForDay(spSettings(), Carbon::parse('2026-10-12', 'Asia/Tokyo'));
    expect($slots)->toHaveCount(3);
    $start = Carbon::parse('2026-10-12 09:00', 'Asia/Tokyo');
    foreach ($slots as $i => $s) {
        // midpoint of 4h slot i ± 1h jitter cap
        $mid = $start->copy()->addHours(4 * $i + 2);
        expect(abs($s->diffInSeconds($mid, false)))->toBeLessThanOrEqual(3600);
    }
    expect($slots[0]->lessThan($slots[1]) && $slots[1]->lessThan($slots[2]))->toBeTrue();
});

it('is deterministic for the same day', function () {
    $d = Carbon::parse('2026-10-12', 'Asia/Tokyo');
    $a = array_map(fn ($t) => $t->toIso8601String(), (new SchedulePlanner)->slotsForDay(spSettings(), $d));
    $b = array_map(fn ($t) => $t->toIso8601String(), (new SchedulePlanner)->slotsForDay(spSettings(), $d));
    expect($a)->toBe($b);
});

it('falls back to the window start when the window is inverted', function () {
    $s = spSettings(['posting_window_start' => '20:00:00', 'posting_window_end' => '08:00:00']);
    $slots = (new SchedulePlanner)->slotsForDay($s, Carbon::parse('2026-10-12', 'Asia/Tokyo'));
    expect($slots)->toHaveCount(1)->and($slots[0]->format('H:i'))->toBe('20:00');
});

it('returns slots in the application timezone', function () {
    config(['app.timezone' => 'UTC']);
    $s = spSettings(['posting_times' => ['09:00']]);
    $slots = (new SchedulePlanner)->slotsForDay($s, Carbon::parse('2026-10-12', 'Asia/Tokyo'));
    expect($slots[0]->format('Y-m-d H:i e'))->toBe('2026-10-12 00:00 UTC');
});

it('only returns future slots from upcomingSlotsForDay', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-12 15:00', 'Asia/Tokyo'));
    $s = spSettings(['posting_times' => ['09:00', '14:59', '15:01', '20:00']]);
    $slots = (new SchedulePlanner)->upcomingSlotsForDay($s, Carbon::parse('2026-10-12', 'Asia/Tokyo'));
    expect(array_map(fn ($t) => $t->format('H:i'), $slots))->toBe(['15:01', '20:00']);
});
