<?php

require_once __DIR__.'/AutoPosterTestCase.php';
require_once __DIR__.'/Helpers.php';

use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Services\CollectionPostBuilder;
use Fujiba\AutoPoster\Services\UrlResolver;

use function Tests\Unit\AutoPoster\callProtected;
use function Tests\Unit\AutoPoster\setProtected;

uses(Tests\Unit\AutoPoster\AutoPosterTestCase::class);

function cpItem(int $id, string $code, int $price = 1980, string $colour = 'red', ?string $created = '2020-01-01 00:00:00', bool $soldOut = false): object
{
    $o = (object) ['product_id' => $id, 'code' => $code, 'price' => $price, 'length' => '2m',
        'pattern' => CollectionPostBuilder::pattern($code).'|2m|'.$price, 'colour' => $colour, 'created_at' => $created, 'photo_url' => "https://x/{$id}.jpg"];
    if ($soldOut) {
        $o->sold_out = true;
    }

    return $o;
}

/** Builder with pools pre-seeded so no DB query runs. */
function cpBuilder(array $pool, array $soldOut = [], array $lastUsed = []): CollectionPostBuilder
{
    $b = new CollectionPostBuilder(Mockery::mock(UrlResolver::class));
    setProtected($b, 'pool', collect($pool));
    setProtected($b, 'soldOut', collect($soldOut));
    setProtected($b, 'lastUsed', $lastUsed);

    return $b;
}

function cpPost(int $id): AutoPosterQueue
{
    return (new AutoPosterQueue)->forceFill(['id' => $id, 'channel_id' => 1, 'scheduled_at' => null]);
}

it('extracts the catalog code from a product name', function (string $name, ?string $code) {
    expect(CollectionPostBuilder::code($name))->toBe($code);
})->with([
    ['FK-15D (purple flowers)', 'FK-15D'],
    ['FV-44-A 2m', 'FV-44-A'],
    ['  FS_1950B cotton', 'FS_1950B'],
    ['Kimono FK-1', null],
    ['F', null],
]);

it('derives the pattern key by dropping the colour letter and 3m suffix', function (string $code, string $pattern) {
    expect(CollectionPostBuilder::pattern($code))->toBe($pattern);
})->with([
    ['FV-44-A', 'FV-44'],
    ['FK-11B', 'FK-11'],
    ['FK-11B(3m)', 'FK-11'],
    ['FK-11', 'FK-11'],
]);

it('detects collection posts from caption_meta', function () {
    expect(CollectionPostBuilder::isCollection((new AutoPosterQueue)->forceFill(['caption_meta' => ['collection' => 'pattern']])))->toBeTrue()
        ->and(CollectionPostBuilder::isCollection((new AutoPosterQueue)->forceFill(['caption_meta' => []])))->toBeFalse()
        ->and(CollectionPostBuilder::isCollection(new AutoPosterQueue))->toBeFalse();
});

it('groups a pattern post by pattern, preferring groups of two or more', function () {
    $b = cpBuilder([cpItem(1, 'FK-1A'), cpItem(2, 'FK-1B'), cpItem(3, 'FK-2A')]);
    [$kind, $key, $items] = callProtected($b, 'nextGroup', ['pattern', cpPost(1)]);
    expect($kind)->toBe('pattern')->and($key)->toBe('FK-1|2m|1980')
        ->and($items->pluck('product_id')->all())->toBe([1, 2]);
});

it('falls back to single-item patterns when no pattern has two colours', function () {
    $b = cpBuilder([cpItem(3, 'FK-2A')]);
    [, , $items] = callProtected($b, 'nextGroup', ['pattern', cpPost(1)]);
    expect($items->pluck('product_id')->all())->toBe([3]);
});

it('caps a pattern group at MAX_PHOTOS sorted by code', function () {
    $pool = [];
    foreach (range('J', 'A') as $i => $l) {
        $pool[] = cpItem($i + 1, 'FK-1'.$l);
    }
    [, , $items] = callProtected(cpBuilder($pool), 'nextGroup', ['pattern', cpPost(1)]);
    expect($items)->toHaveCount(CollectionPostBuilder::MAX_PHOTOS)
        ->and($items->first()->code)->toBe('FK-1A');
});

it('picks the least recently used pattern group', function () {
    $pool = [cpItem(1, 'FK-1A'), cpItem(2, 'FK-1B'), cpItem(3, 'FK-2A'), cpItem(4, 'FK-2B')];
    [, $key] = callProtected(cpBuilder($pool, [], [1 => 1000, 2 => 1000]), 'nextGroup', ['pattern', cpPost(1)]);
    expect($key)->toBe('FK-2|2m|1980');
});

it('needs at least three distinct patterns for a colour post', function () {
    $two = cpBuilder([cpItem(1, 'FK-1A'), cpItem(2, 'FK-2A')]);
    expect(callProtected($two, 'nextGroup', ['colour', cpPost(1)]))->toBeNull();

    $three = cpBuilder([cpItem(1, 'FK-1A'), cpItem(2, 'FK-1B'), cpItem(3, 'FK-2A'), cpItem(4, 'FK-3A')]);
    [$kind, $key, $items] = callProtected($three, 'nextGroup', ['colour', cpPost(1)]);
    expect($kind)->toBe('colour')->and($key)->toBe('red')
        ->and($items->pluck('pattern')->unique())->toHaveCount(3);
});

it('only uses recently created products for a new-arrivals post', function () {
    $recent = now()->subDays(5)->toDateTimeString();
    $b = cpBuilder([cpItem(1, 'FK-1A', created: '2001-01-01 00:00:00'), cpItem(2, 'FK-2A', created: $recent)]);
    [, , $items] = callProtected($b, 'nextGroup', ['new', cpPost(1)]);
    expect($items->pluck('product_id')->all())->toBe([2]);
});

it('returns null for an empty pool', function () {
    expect(callProtected(cpBuilder([]), 'nextGroup', ['pattern', cpPost(1)]))->toBeNull();
});

it('refuses to caption an empty collection', function () {
    cpBuilder([])->caption('pattern', 'x', collect());
})->throws(RuntimeException::class, 'no products');

it('captions a single-price pattern with letters, price and m.me link', function () {
    config(['autoposter.page_ids.1' => '999']);
    $c = cpBuilder([])->caption('pattern', 'FK-1|2m|1980', collect([cpItem(1, 'FK-1A'), cpItem(2, 'FK-1B')]));
    expect($c)->toContain('FK-1  [A · B]')
        ->and($c)->toContain('2m ¥1,980')
        ->and($c)->toContain('https://m.me/999')
        ->and($c)->toContain('同じ柄・2色');
});

it('lists prices per code and hides the code line when prices differ', function () {
    $c = cpBuilder([])->caption('pattern', 'k', collect([cpItem(1, 'FK-1A', 1980), cpItem(2, 'FK-1B', 2500)]));
    expect($c)->toContain('FK-1A  ¥1,980')->and($c)->toContain('FK-1B  ¥2,500')->not->toContain('[A · B]');
});

it('names the sold-out codes in the caption', function () {
    $c = cpBuilder([])->caption('pattern', 'k', collect([cpItem(1, 'FK-1A'), cpItem(2, 'FK-1B', soldOut: true)]));
    expect($c)->toContain('🔖 FK-1B — Sold out');
});

it('uses the colour name in all three languages for a colour post', function () {
    $c = cpBuilder([])->caption('colour', 'navy', collect([cpItem(1, 'FK-1A'), cpItem(2, 'FK-2A'), cpItem(3, 'FK-3A')]));
    expect($c)->toContain('紺地の和柄コレクション')->and($c)->toContain('Navy background · 3 patterns');
});

it('produces a deterministic caption for the same group', function () {
    $items = collect([cpItem(1, 'FK-1A'), cpItem(2, 'FK-1B')]);
    expect(cpBuilder([])->caption('pattern', 'k', $items))->toBe(cpBuilder([])->caption('pattern', 'k', $items));
});
