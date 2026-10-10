<?php

require_once __DIR__.'/AutoPosterTestCase.php';

use Fujiba\AutoPoster\Console\Commands\KnowledgeCommand;
use Fujiba\AutoPoster\Console\Commands\V2DryRunCommand;
use Fujiba\AutoPoster\Services\Intel\DuplicateImageGuard;
use Fujiba\AutoPoster\Services\Intel\DuplicateImageSource;

uses(Tests\Unit\AutoPoster\AutoPosterTestCase::class);

function fakeSource(array $rows): DuplicateImageSource
{
    return new class($rows) implements DuplicateImageSource
    {
        public function __construct(private array $rows) {}

        public function rows(): iterable
        {
            return $this->rows;
        }
    };
}

function img(int $pid, ?string $sha, ?string $dhash): array
{
    return ['product_id' => $pid, 'sha256' => $sha, 'dhash' => $dhash];
}

it('computes 64-bit hamming distance without overflow', function () {
    expect(DuplicateImageGuard::hamming('ffffffffffffffff', '0000000000000000'))->toBe(64)
        ->and(DuplicateImageGuard::hamming('2746868b9ad8dac9', '2746868b9ad8dac9'))->toBe(0)
        ->and(DuplicateImageGuard::hamming('8000000000000000', '0000000000000001'))->toBe(2)
        ->and(DuplicateImageGuard::hamming('f', '000f'))->toBe(0);
});

it('blocks both products of an identical image pair', function () {
    $g = new DuplicateImageGuard(fakeSource([
        img(10, 'aaa', '0000000000000000'),
        img(11, 'aaa', 'ffffffffffffffff'),
        img(12, 'bbb', '00000000ffffffff'),
    ]), []);

    expect($g->blocked())->toBe([
        10 => 'blocked:duplicate_image identical:11',
        11 => 'blocked:duplicate_image identical:10',
    ])->and($g->isBlocked(12))->toBeFalse();
});

it('blocks near duplicates within the distance and not beyond it', function () {
    $g = new DuplicateImageGuard(fakeSource([
        img(1, 's1', '0000000000000000'),
        img(2, 's2', '000000000000000f'),   // d=4
        img(3, 's3', '00000000000000ff'),   // d=8 to 1, d=4 to 2
        img(4, 's4', 'ffff000000000000'),   // far
    ]), []);

    expect($g->reason(1))->toBe('blocked:duplicate_image near:2(d=4)')
        ->and($g->reason(2))->toBe('blocked:duplicate_image near:1(d=4),near:3(d=4)')
        ->and($g->isBlocked(3))->toBeTrue()
        ->and($g->isBlocked(4))->toBeFalse();
});

it('ignores matches between photos of the same product', function () {
    $g = new DuplicateImageGuard(fakeSource([img(7, 'x', '0000000000000000'), img(7, 'x', '0000000000000000')]), []);
    expect($g->blocked())->toBe([]);
});

it('tolerates missing or malformed hashes', function () {
    $g = new DuplicateImageGuard(fakeSource([img(1, null, null), img(2, null, 'zz'), img(3, '', null)]), []);
    expect($g->blocked())->toBe([]);
});

it('lets owner-resolved products through but still blocks the partner', function () {
    $g = new DuplicateImageGuard(fakeSource([img(5, 'h', null), img(6, 'h', null)]), [5]);
    expect($g->isBlocked(5))->toBeFalse()->and($g->isBlocked(6))->toBeTrue();
});

it('filters a product id list to the blocked subset', function () {
    $g = new DuplicateImageGuard(fakeSource([img(1, 'h', null), img(2, 'h', null), img(3, 'k', null)]), []);
    expect(array_keys($g->filter([2, 3, '1'])))->toBe([1, 2]);
});

it('is wired into the v2 commands only', function () {
    $h = (new ReflectionMethod(V2DryRunCommand::class, 'handle'))->getParameters();
    expect($h[0]->getType()->getName())->toBe(DuplicateImageGuard::class);
    $i = (new ReflectionMethod(KnowledgeCommand::class, 'import'))->getParameters();
    expect($i[0]->getType()->getName())->toBe(DuplicateImageGuard::class);

    $src = dirname(__DIR__, 3).'/packages/Fujiba/AutoPoster/src';
    foreach (['Services/PostPublisher.php', 'Services/QueueBuilder.php', 'Services/ProductSelectionService.php',
        'Services/CollectionPostBuilder.php', 'Console/Commands/DispatchPostsCommand.php', 'Console/Commands/PublishInstagramCommand.php'] as $f) {
        expect(file_get_contents("{$src}/{$f}"))->not->toContain('DuplicateImageGuard');
    }
});
