<?php

require_once __DIR__.'/AutoPosterTestCase.php';
require_once __DIR__.'/Helpers.php';

use Carbon\Carbon;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\AutoPoster\Services\ProductSelectionService;
use Fujiba\AutoPoster\Services\QueueBuilder;
use Fujiba\AutoPoster\Services\SchedulePlanner;
use Fujiba\AutoPoster\Services\UrlResolver;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Illuminate\Support\Facades\DB;

use function Tests\Unit\AutoPoster\callProtected;

uses(Tests\Unit\AutoPoster\AutoPosterTestCase::class);

function qsSettings(array $a = []): AutoPosterSetting
{
    return (new AutoPosterSetting)->forceFill(array_merge(['channel_id' => 1, 'products_per_post' => 3, 'repeat_cooldown_days' => 0], $a));
}

/** Builder over the (unreachable) mysql connection: compiled to SQL, never executed. */
function qsQuery()
{
    return DB::connection('mysql')->table('product_flat as pf')->select('pf.product_id');
}

// ---- ProductSelectionService ----

it('returns manual product ids minus exclusions, capped at the limit, without querying', function () {
    $svc = new ProductSelectionService(Mockery::mock(UrlResolver::class));
    $s = qsSettings(['selection_mode' => AutoPosterSetting::MODE_MANUAL, 'selection_config' => ['product_ids' => ['5', 6, 7, 8, 9]], 'products_per_post' => 3]);
    expect($svc->select($s, [6]))->toBe([5, 7, 8])
        ->and($svc->select($s, [], 1))->toBe([5]);
});

it('counts manual selections from config', function () {
    $svc = new ProductSelectionService(Mockery::mock(UrlResolver::class));
    expect($svc->eligibleCount(qsSettings(['selection_mode' => 'manual', 'selection_config' => ['product_ids' => [1, 2]]])))->toBe(2);
});

it('adds no cooldown filter when cooldown is zero', function () {
    $q = qsQuery();
    callProtected(new ProductSelectionService(Mockery::mock(UrlResolver::class)), 'applyCooldown', [$q, qsSettings()]);
    expect($q->toSql())->not->toContain('autoposter_product_history');
});

it('excludes recently posted and already-queued products during cooldown', function () {
    $q = qsQuery();
    callProtected(new ProductSelectionService(Mockery::mock(UrlResolver::class)), 'applyCooldown', [$q, qsSettings(['repeat_cooldown_days' => 7])]);
    $sql = $q->toSql();
    expect($sql)->toContain('not exists (select 1 from `autoposter_product_history` as `hc`')
        ->and($sql)->toContain('`pq`.`status` in (?, ?, ?)')
        ->and($q->getBindings())->toContain('draft', 'scheduled', 'processing');
});

it('orders least-recently-posted with never-posted first', function () {
    $q = qsQuery();
    callProtected(new ProductSelectionService(Mockery::mock(UrlResolver::class)), 'applyMode', [$q, AutoPosterSetting::MODE_LEAST_RECENTLY_POSTED, [], qsSettings()]);
    expect($q->toSql())->toContain('order by hl.last_posted_at IS NULL DESC, `hl`.`last_posted_at` asc');
});

it('filters category mode by category ids', function () {
    $q = qsQuery();
    callProtected(new ProductSelectionService(Mockery::mock(UrlResolver::class)), 'applyMode', [$q, AutoPosterSetting::MODE_CATEGORY, ['category_ids' => ['4', 9]], qsSettings()]);
    expect($q->toSql())->toContain('`pc`.`category_id` in (?, ?)')->and($q->getBindings())->toBe([4, 9]);
});

it('limits new-arrivals mode to the configured window, newest first', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');
    $q = qsQuery();
    callProtected(new ProductSelectionService(Mockery::mock(UrlResolver::class)), 'applyMode', [$q, AutoPosterSetting::MODE_NEW_ARRIVALS, ['new_arrival_days' => 10], qsSettings()]);
    expect($q->toSql())->toContain('`pf`.`created_at` >= ?')->toContain('order by `pf`.`created_at` desc')
        ->and((string) $q->getBindings()[0])->toStartWith('2026-09-30 12:00:00');
    Carbon::setTestNow();
});

// ---- QueueBuilder ----

it('builds nothing when selection returns no products', function () {
    $sel = Mockery::mock(ProductSelectionService::class);
    $sel->shouldReceive('select')->once()->andReturn([]);
    expect((new QueueBuilder($sel, Mockery::mock(UrlResolver::class)))->build(qsSettings()))->toBeNull();
});

it('prefers the admin messenger override, then the catalog page, else null', function () {
    $urls = Mockery::mock(UrlResolver::class);
    $urls->shouldReceive('messengerUrl')->with('PAGE', 'ref1')->andReturn('https://m.me/PAGE?ref=ref1');
    $qb = new QueueBuilder(Mockery::mock(ProductSelectionService::class), $urls);
    $cat = (new FacebookCatalogSetting)->forceFill(['page_id' => 'PAGE']);

    expect(callProtected($qb, 'messengerDestination', [qsSettings(['messenger_destination' => 'https://x']), $cat]))->toBe('https://x')
        ->and(callProtected($qb, 'messengerDestination', [qsSettings(['messenger_ref' => 'ref1']), $cat]))->toBe('https://m.me/PAGE?ref=ref1')
        ->and(callProtected($qb, 'messengerDestination', [qsSettings(), null]))->toBeNull();
});

it('plans nothing for a day with no upcoming slots', function () {
    $planner = Mockery::mock(SchedulePlanner::class);
    $planner->shouldReceive('upcomingSlotsForDay')->andReturn([]);
    $qb = new QueueBuilder(Mockery::mock(ProductSelectionService::class), Mockery::mock(UrlResolver::class));
    expect($qb->planDay(qsSettings(), $planner))->toBe([]);
});

it('bulk planning stops at the day horizon when no slots exist', function () {
    $planner = Mockery::mock(SchedulePlanner::class);
    $planner->shouldReceive('upcomingSlotsForDay')->times(3)->andReturn([]);
    $qb = new QueueBuilder(Mockery::mock(ProductSelectionService::class), Mockery::mock(UrlResolver::class));
    expect($qb->planBulk(qsSettings(), $planner, [1, 2, 3, 4], Carbon::parse('2026-10-12'), 3))->toBe([]);
});
