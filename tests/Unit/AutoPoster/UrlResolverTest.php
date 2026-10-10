<?php

require_once __DIR__.'/AutoPosterTestCase.php';

use Fujiba\AutoPoster\Services\UrlResolver;

uses(Tests\Unit\AutoPoster\AutoPosterTestCase::class);

it('builds m.me links with optional encoded ref', function (string $page, ?string $ref, string $url) {
    $r = (new ReflectionClass(UrlResolver::class))->newInstanceWithoutConstructor();
    expect($r->messengerUrl($page, $ref))->toBe($url);
})->with([
    ['123', null, 'https://m.me/123'],
    [' /123 ', '  ', 'https://m.me/123'],
    ['123', 'post 7&x', 'https://m.me/123?ref=post%207%26x'],
]);
