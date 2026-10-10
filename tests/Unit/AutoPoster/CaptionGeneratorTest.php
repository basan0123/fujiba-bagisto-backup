<?php

require_once __DIR__.'/AutoPosterTestCase.php';
require_once __DIR__.'/Helpers.php';

use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Models\AutoPosterTemplate;
use Fujiba\AutoPoster\Services\CaptionGenerator;
use Fujiba\AutoPoster\Services\ClaudeCaptionClient;

use function Tests\Unit\AutoPoster\callProtected;

uses(Tests\Unit\AutoPoster\AutoPosterTestCase::class);

function cgTemplate(array $attrs = []): AutoPosterTemplate
{
    return (new AutoPosterTemplate)->forceFill(array_merge([
        'language_mode'            => 'en_ja_my',
        'body_en'                  => 'EN {{product_names}} from {{price_from}}',
        'body_ja'                  => 'JA {{product_count}}点',
        'body_my'                  => 'MY {{messenger_link}}',
        'include_emojis'           => true,
        'include_hashtags'         => false,
        'include_new_arrival_text' => false,
        'include_campaign_text'    => false,
        'campaign_text'            => null,
    ], $attrs));
}

function cgFacts(array $names = ['FK-1A', 'FK-1B'], ?float $min = 1980.0): array
{
    return ['product_ids' => range(1, count($names)), 'names' => $names, 'min_price' => $min];
}

function cgPost(array $attrs = []): AutoPosterQueue
{
    return (new AutoPosterQueue)->forceFill(array_merge(['id' => 7, 'channel_id' => 1, 'messenger_destination' => 'https://m.me/123'], $attrs));
}

function cg(bool $ai = false): CaptionGenerator
{
    return new CaptionGenerator(new ClaudeCaptionClient($ai ? 'k' : '', 'm'));
}

it('fills every placeholder in all three languages', function () {
    config(['app.name' => 'Fujiba']);
    $out = callProtected(cg(), 'renderTemplate', [cgTemplate(), cgFacts(), cgPost()]);

    expect($out)->toBe("EN FK-1A / FK-1B from ¥1,980\n\nJA 2点\n\nMY https://m.me/123");
});

it('renders only the bodies selected by language_mode', function (string $mode, string $expected) {
    $out = callProtected(cg(), 'renderTemplate', [cgTemplate(['language_mode' => $mode, 'body_my' => 'MY']), cgFacts(), cgPost()]);
    expect($out)->toBe($expected);
})->with([
    ['en', 'EN FK-1A / FK-1B from ¥1,980'],
    ['ja', 'JA 2点'],
    ['my', 'MY'],
    ['en_ja', "EN FK-1A / FK-1B from ¥1,980\n\nJA 2点"],
    ['en_my', "EN FK-1A / FK-1B from ¥1,980\n\nMY"],
    ['bogus', "EN FK-1A / FK-1B from ¥1,980\n\nJA 2点\n\nMY"],
]);

it('caps product_names at five names', function () {
    $out = callProtected(cg(), 'renderTemplate', [cgTemplate(['language_mode' => 'en', 'body_en' => '{{product_names}}']), cgFacts(['a', 'b', 'c', 'd', 'e', 'f']), cgPost()]);
    expect($out)->toBe('a / b / c / d / e');
});

it('leaves price_from empty when no price is known', function () {
    $out = callProtected(cg(), 'renderTemplate', [cgTemplate(['language_mode' => 'en', 'body_en' => 'p={{price_from}}']), cgFacts(['x'], null), cgPost()]);
    expect($out)->toBe('p=');
});

it('skips empty language bodies instead of emitting blank paragraphs', function () {
    $out = callProtected(cg(), 'renderTemplate', [cgTemplate(['body_ja' => '   ', 'body_my' => 'MY']), cgFacts(), cgPost()]);
    expect($out)->toBe("EN FK-1A / FK-1B from ¥1,980\n\nMY");
});

it('falls back to the plain Messenger body when the template is all blank', function () {
    $out = callProtected(cg(), 'renderTemplate', [cgTemplate(['body_en' => '', 'body_ja' => '', 'body_my' => '']), cgFacts(['FK-9A']), cgPost()]);
    expect($out)->toStartWith("FK-9A\n\nMessage us on Messenger to order")
        ->and($out)->toContain('メッセージでご注文ください');
});

it('uses the fallback body with no template and no product names', function () {
    $out = callProtected(cg(), 'renderTemplate', [null, cgFacts([], null), cgPost()]);
    expect($out)->toStartWith('Message us on Messenger to order 💬');
});

it('appends new-arrival, campaign and hashtag lines in that order', function () {
    $t = cgTemplate(['language_mode' => 'en', 'body_en' => 'Body', 'include_new_arrival_text' => true, 'include_campaign_text' => true, 'campaign_text' => ' Sale 10% ', 'include_hashtags' => true]);
    $out = callProtected(cg(), 'renderTemplate', [$t, cgFacts(), cgPost()]);
    expect($out)->toBe("Body\n\n🆕 New Arrival / 新着 / အသစ်ရောက်\nSale 10%\n#kimono #着物 #japanesefashion #vintagekimono #fujiba");
});

it('ignores the campaign toggle when campaign text is empty', function () {
    $t = cgTemplate(['language_mode' => 'en', 'body_en' => 'Body', 'include_campaign_text' => true, 'campaign_text' => '']);
    expect(callProtected(cg(), 'renderTemplate', [$t, cgFacts(), cgPost()]))->toBe('Body');
});

it('strips emoji including U+1F195 when emojis are off', function () {
    $t = cgTemplate(['language_mode' => 'en', 'body_en' => '🌸 Lovely ✨ kimono ❤️', 'include_emojis' => false, 'include_new_arrival_text' => true]);
    $out = callProtected(cg(), 'renderTemplate', [$t, cgFacts(), cgPost()]);
    expect($out)->toBe("Lovely kimono\n\nNew Arrival / 新着 / အသစ်ရောက်")
        ->and($out)->not->toContain("\u{FE0F}");
});

it('keeps Japanese and Burmese text intact when stripping emoji', function () {
    expect(callProtected(cg(), 'stripEmojis', ["着物 🎌 ကီမိုနို"]))->toBe('着物 ကီမိုနို');
});

it('collapses runs of blank lines left by unused placeholders', function () {
    $t = cgTemplate(['language_mode' => 'en', 'body_en' => "A\n\n{{price_from}}\n\nB"]);
    expect(callProtected(cg(), 'renderTemplate', [$t, cgFacts(['x'], null), cgPost()]))->toBe("A\n\nB");
});

it('maps language modes to AI language keys', function (?string $tMode, ?string $pMode, array $expected) {
    $t = $tMode ? cgTemplate(['language_mode' => $tMode]) : null;
    expect(callProtected(cg(), 'languagesFor', [$t, cgPost(['language_mode' => $pMode])]))->toBe($expected);
})->with([
    ['en', null, ['en']],
    ['en_my', 'ja', ['en', 'my']],
    [null, 'ja', ['ja']],
    [null, null, ['en', 'ja', 'my']],
]);

it('pins the AI response schema to exactly the requested languages', function () {
    $s = callProtected(cg(), 'responseSchema', [['en', 'ja']]);
    expect(array_keys($s['properties']))->toBe(['en', 'ja'])
        ->and($s['required'])->toBe(['en', 'ja'])
        ->and($s['additionalProperties'])->toBeFalse();
});

it('tells the model not to use emoji when the template disables them', function () {
    $p = callProtected(cg(), 'userPrompt', [cgPost(), cgTemplate(['include_emojis' => false]), cgFacts(), ['en']]);
    expect($p)->toContain('Do not use any emoji.')
        ->and($p)->toContain('Lowest price shown: ¥1,980')
        ->and($p)->toContain('  - FK-1A');
});

it('assembles AI bodies through the same toggles and drops empty languages', function () {
    $claude = Mockery::mock(ClaudeCaptionClient::class);
    $claude->shouldReceive('isConfigured')->andReturnTrue();
    $claude->shouldReceive('generateJson')->once()->andReturn(['en' => ' Hello 🌸 ', 'ja' => '', 'my' => 'Mingalar']);
    $gen = new CaptionGenerator($claude);

    $out = callProtected($gen, 'generateWithAi', [cgPost(), cgTemplate(['include_emojis' => false, 'include_hashtags' => true]), cgFacts()]);
    expect($out)->toBe("Hello\n\nMingalar\n\n#kimono #着物 #japanesefashion #vintagekimono #fujiba");
});

it('throws when the AI returns no usable text so generate() can fall back', function () {
    $claude = Mockery::mock(ClaudeCaptionClient::class);
    $claude->shouldReceive('generateJson')->andReturn(['en' => '  ']);
    callProtected(new CaptionGenerator($claude), 'generateWithAi', [cgPost(), cgTemplate(['language_mode' => 'en']), cgFacts()]);
})->throws(RuntimeException::class, 'no usable caption');

it('reports AI availability from the client key', function () {
    expect(cg(true)->isAiAvailable())->toBeTrue()
        ->and(cg(false)->isAiAvailable())->toBeFalse();
});
