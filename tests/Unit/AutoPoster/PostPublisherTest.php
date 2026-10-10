<?php

require_once __DIR__.'/AutoPosterTestCase.php';
require_once __DIR__.'/Helpers.php';

use Fujiba\AutoPoster\Models\AutoPosterPostProduct;
use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\AutoPoster\Services\CaptionGenerator;
use Fujiba\AutoPoster\Services\ChatpionPostClient;
use Fujiba\AutoPoster\Services\CollectionPostBuilder;
use Fujiba\AutoPoster\Services\PostCoverRenderer;
use Fujiba\AutoPoster\Services\PostPublisher;
use Fujiba\AutoPoster\Services\UrlResolver;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;

use function Tests\Unit\AutoPoster\callProtected;

uses(Tests\Unit\AutoPoster\AutoPosterTestCase::class);

function ppMake(?ChatpionPostClient $chatpion = null, ?PostCoverRenderer $covers = null)
{
    $p = Mockery::mock(PostPublisher::class, [
        Mockery::mock(UrlResolver::class),
        $chatpion ?? Mockery::mock(ChatpionPostClient::class),
        Mockery::mock(CaptionGenerator::class),
        $covers ?? Mockery::mock(PostCoverRenderer::class),
        Mockery::mock(CollectionPostBuilder::class),
    ])->makePartial()->shouldAllowMockingProtectedMethods();
    $p->shouldReceive('recordHistory')->andReturnNull(); // Eloquent history write — never hit a DB

    return $p;
}

/** Queue row whose writes are captured in-memory instead of hitting a DB. */
function ppPost(array $attrs, array $products = [], array &$updates = [])
{
    $post = Mockery::mock(AutoPosterQueue::class)->makePartial();
    $post->forceFill($attrs);
    $post->shouldReceive('update')->andReturnUsing(function ($d) use ($post, &$updates) {
        $updates[] = $d;
        $post->forceFill($d);

        return true;
    });
    $post->shouldReceive('fresh')->andReturn($post);
    $rel = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    $rel->shouldReceive('get')->andReturn(collect($products));
    $post->shouldReceive('products')->andReturn($rel);

    return $post;
}

function ppItem(int $id, string $url): AutoPosterPostProduct
{
    return (new AutoPosterPostProduct)->forceFill(['product_id' => $id, 'image_url' => $url]);
}

function ppStock(array $map): void
{
    $q = Mockery::mock();
    $q->shouldReceive('whereIn')->andReturnSelf();
    $q->shouldReceive('groupBy')->andReturnSelf();
    $q->shouldReceive('pluck')->andReturn(collect($map));
    DB::shouldReceive('table')->with('product_inventories')->andReturn($q);
    DB::shouldReceive('raw')->andReturnUsing(fn ($v) => new Expression($v));
    DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
}

it('appends the Messenger CTA unless the caption already has it', function () {
    $p = ppMake();
    $a = (new AutoPosterQueue)->forceFill(['caption' => ' Hi ', 'messenger_destination' => 'https://m.me/1']);
    $b = (new AutoPosterQueue)->forceFill(['caption' => 'Hi https://m.me/1', 'messenger_destination' => 'https://m.me/1']);
    $c = (new AutoPosterQueue)->forceFill(['caption' => 'Hi', 'messenger_destination' => '']);
    expect(callProtected($p, 'composeMessage', [$a]))->toBe("Hi\n\nhttps://m.me/1")
        ->and(callProtected($p, 'composeMessage', [$b]))->toBe('Hi https://m.me/1')
        ->and(callProtected($p, 'composeMessage', [$c]))->toBe('Hi');
});

it('classifies failures as retryable or permanent', function ($exception, ?array $response, bool $expected) {
    expect(callProtected(ppMake(), 'isRetryable', [$exception, $response]))->toBe($expected);
})->with([
    'graph throttle code 4'   => [null, ['error' => ['code' => 4]], true],
    'graph permission 200'    => [null, ['error' => ['code' => '200']], false],
    'unknown graph code'      => [null, ['error' => ['code' => 99999]], false],
    'connection timeout'      => [new ConnectionException('timeout'), null, true],
    'local pre-flight guard'  => [null, null, false],
    'unknown throwable'       => [new RuntimeException('boom'), ['message' => 'boom'], true],
]);

it('fails permanently without retry when no page id is configured for the channel', function () {
    config(['autoposter.page_ids' => [1 => '111']]);
    $updates = [];
    $post = ppPost(['id' => 5, 'channel_id' => 2, 'attempts' => 0], [], $updates);

    $res = callProtected(ppMake(), 'publishViaChatpion', [$post, null]);

    expect($res['status'])->toBe('error')
        ->and($res['message'])->toContain('No Facebook page id')
        ->and($res['will_retry'])->toBeFalse()
        ->and($updates[0]['status'])->toBe(AutoPosterQueue::STATUS_FAILED)
        ->and($updates[0]['next_retry_at'])->toBeNull();
});

it('publishes a non-collection post via ChatPion with the cover first and marks it published', function () {
    config(['autoposter.page_ids' => [1 => '111'], 'autoposter.instagram.enabled' => false]);
    ppStock([10 => 3, 11 => 1]);
    $updates = [];
    $post = ppPost(['id' => 5, 'channel_id' => 1, 'attempts' => 0, 'caption' => 'Cap', 'messenger_destination' => 'https://m.me/111', 'caption_meta' => null],
        [ppItem(10, 'https://i/10.jpg'), ppItem(11, 'https://i/11.jpg')], $updates);

    $covers = Mockery::mock(PostCoverRenderer::class);
    $covers->shouldReceive('url')->andReturn('https://i/cover.jpg');
    $chat = Mockery::mock(ChatpionPostClient::class);
    $chat->shouldReceive('publish')->once()
        ->with('111', "Cap\n\nhttps://m.me/111", ['https://i/cover.jpg', 'https://i/10.jpg', 'https://i/11.jpg'], null)
        ->andReturn(['id' => '111_999']);
    $chat->shouldReceive('permalink')->with('111_999')->andReturn('https://www.facebook.com/111_999');

    $res = callProtected(ppMake($chat, $covers), 'publishViaChatpion', [$post, null]);

    expect($res)->toBe(['status' => 'success', 'message' => 'Published', 'post_id' => '111_999'])
        ->and(end($updates)['facebook_post_id'])->toBe('111_999')
        ->and(end($updates)['status'])->toBe(AutoPosterQueue::STATUS_PUBLISHED);
});

it('queues the Instagram payload after a ChatPion publish when IG is enabled', function () {
    config(['autoposter.page_ids' => [1 => '111'], 'autoposter.instagram.enabled' => true]);
    ppStock([10 => 3]);
    $updates = [];
    $post = ppPost(['id' => 5, 'channel_id' => 1, 'attempts' => 0, 'caption' => 'Cap', 'messenger_destination' => null, 'caption_meta' => null],
        [ppItem(10, 'https://i/10.jpg')], $updates);
    $covers = Mockery::mock(PostCoverRenderer::class);
    $covers->shouldReceive('url')->andReturnNull();
    $chat = Mockery::mock(ChatpionPostClient::class);
    $chat->shouldReceive('publish')->andReturn(['id' => 'P1']);
    $chat->shouldReceive('permalink')->andReturn('u');

    callProtected(ppMake($chat, $covers), 'publishViaChatpion', [$post, null]);

    expect(end($updates))->toBe(['instagram_status' => 'pending', 'instagram_payload' => ['page_id' => '111', 'message' => 'Cap', 'images' => ['https://i/10.jpg'], 'video' => null]]);
});

it('records a retryable failure with backoff when ChatPion throws', function () {
    config(['autoposter.page_ids' => [1 => '111']]);
    ppStock([10 => 3]);
    $updates = [];
    $post = ppPost(['id' => 5, 'channel_id' => 1, 'attempts' => 0, 'caption' => 'Cap', 'caption_meta' => null], [ppItem(10, 'https://i/10.jpg')], $updates);
    $covers = Mockery::mock(PostCoverRenderer::class);
    $covers->shouldReceive('url')->andReturnNull();
    $chat = Mockery::mock(ChatpionPostClient::class);
    $chat->shouldReceive('publish')->andThrow(new RuntimeException('ChatPion publish failed (502)'));

    $res = callProtected(ppMake($chat, $covers), 'publishViaChatpion', [$post, null]);

    expect($res['classification'])->toBe('transient')->and($res['will_retry'])->toBeTrue()
        ->and(end($updates)['attempts'])->toBe(1)
        ->and(end($updates)['next_retry_at'])->not->toBeNull();
});

it('fails when every product is out of stock and the post is not a collection', function () {
    config(['autoposter.page_ids' => [1 => '111']]);
    ppStock([10 => 0]);
    $updates = [];
    $post = ppPost(['id' => 5, 'channel_id' => 1, 'attempts' => 0, 'caption' => 'Cap', 'caption_meta' => null], [ppItem(10, 'https://i/10.jpg')], $updates);
    $chat = Mockery::mock(ChatpionPostClient::class);
    $chat->shouldNotReceive('publish');

    $res = callProtected(ppMake($chat), 'publishViaChatpion', [$post, null]);
    expect($res['message'])->toBe('Every product in this post is out of stock');
});

it('stops retrying once attempts exceed max_retries', function () {
    $updates = [];
    $post = ppPost(['id' => 5, 'channel_id' => 1, 'attempts' => 3], [], $updates);
    $settings = (new AutoPosterSetting)->forceFill(['max_retries' => 3, 'auto_retry_enabled' => true]);
    $res = callProtected(ppMake(), 'fail', [$post, $settings, 'timeout', null, new ConnectionException('t')]);
    expect($res['classification'])->toBe('transient')->and($res['will_retry'])->toBeFalse()
        ->and($updates[0]['next_retry_at'])->toBeNull();
});
