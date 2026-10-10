<?php

require_once __DIR__.'/AutoPosterTestCase.php';

use Fujiba\AutoPoster\Services\ChatpionPostClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(Tests\Unit\AutoPoster\AutoPosterTestCase::class);

function cpcClient(): ChatpionPostClient
{
    $c = Mockery::mock(ChatpionPostClient::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $c->shouldReceive('apiKey')->andReturn('test-key'); // real one reads central DB

    return $c;
}

beforeEach(function () {
    Http::preventStrayRequests();
    config(['autoposter.chatpion_url' => 'https://chatpion.test/']);
});

it('posts to fujiba_post/publish and returns the post id', function () {
    Http::fake(['chatpion.test/*' => Http::response(['status' => 1, 'post_id' => '1_2'])]);
    expect(cpcClient()->publish('1', 'msg', ['a', 'b'], 'https://v.mp4'))->toBe(['id' => '1_2']);
    Http::assertSent(fn (Request $r) => $r->url() === 'https://chatpion.test/fujiba_post/publish'
        && $r['page_id'] === '1' && $r['video_url'] === 'https://v.mp4' && $r['image_urls'] === ['a', 'b']);
});

it('throws when ChatPion reports failure', function () {
    Http::fake(['chatpion.test/*' => Http::response(['status' => 0, 'error' => 'token expired'], 200)]);
    cpcClient()->publish('1', 'msg', []);
})->throws(RuntimeException::class, 'token expired');

it('throws when ChatPion returns no post id', function () {
    Http::fake(['chatpion.test/*' => Http::response(['status' => 1])]);
    cpcClient()->publish('1', 'msg', []);
})->throws(RuntimeException::class, 'ChatPion publish failed (200)');

it('caps Instagram images at ten and returns media id + permalink', function () {
    Http::fake(['chatpion.test/*' => Http::response(['status' => 1, 'media_id' => 'M', 'permalink' => 'https://ig/p'])]);
    expect(cpcClient()->publishInstagram('1', 'm', range(1, 15)))->toBe(['id' => 'M', 'permalink' => 'https://ig/p']);
    Http::assertSent(fn (Request $r) => count($r['image_urls']) === 10 && ! isset($r['video_url']));
});

it('throws when Instagram publish returns no media id', function () {
    Http::fake(['chatpion.test/*' => Http::response(['status' => 1])]);
    cpcClient()->publishInstagram('1', 'm', []);
})->throws(RuntimeException::class, 'no media id');

it('builds the facebook permalink', function () {
    expect((new ChatpionPostClient)->permalink('1_2'))->toBe('https://www.facebook.com/1_2');
});
