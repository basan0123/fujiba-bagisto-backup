<?php

namespace Fujiba\AutoPoster\Services;

use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Models\AutoPosterTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Builds post captions.
 *
 * Two paths behind one public surface:
 *
 *   AI       — Claude writes the EN/JA/MY bodies when ANTHROPIC_API_KEY is set.
 *   template — the saved template is rendered directly.
 *
 * generate() returns the same shape either way; `source` says which ran, so the
 * UI can label it honestly rather than implying AI output that did not happen.
 *
 * The template is not bypassed when AI is on — it is the brief. Its bodies give
 * Claude the shop's tone and structure, and the emoji / hashtag / campaign /
 * new-arrival toggles are applied to the AI result by exactly the same
 * applyToggles() call the template path uses. Turning the key on changes who
 * writes the sentences, not which controls apply.
 *
 * Any AI failure — missing key, API error, refusal, malformed JSON — falls back
 * to the template path and logs. A caption is never left empty because Claude
 * was unreachable, since an empty caption blocks publishing.
 */
class CaptionGenerator
{
    /** Placeholders a template may use. */
    public const PLACEHOLDERS = [
        '{{product_names}}',
        '{{product_count}}',
        '{{price_from}}',
        '{{shop_name}}',
        '{{messenger_link}}',
    ];

    public function __construct(
        protected ?ClaudeCaptionClient $claude = null,
    ) {
        $this->claude ??= new ClaudeCaptionClient();
    }

    public function isAiAvailable(): bool
    {
        return $this->claude->isConfigured();
    }

    /**
     * Generate a caption for a queued post.
     *
     * @return array{caption:string,source:string,meta:array}
     */
    public function generate(AutoPosterQueue $post, ?AutoPosterTemplate $template = null): array
    {
        $template ??= $this->resolveTemplate($post);
        $facts      = $this->productFacts($post);

        $source   = 'template';
        $fallback = null;

        if ($this->isAiAvailable()) {
            try {
                $caption = $this->generateWithAi($post, $template, $facts);
                $source  = 'ai';
            } catch (Throwable $e) {
                // Degrade, never fail: an empty caption blocks publishing, so a
                // template caption is strictly better than no caption.
                Log::warning('[AutoPoster] AI caption generation failed; using template', [
                    'queue_id' => $post->id,
                    'error'    => $e->getMessage(),
                ]);

                $fallback = $e->getMessage();
                $caption  = $this->renderTemplate($template, $facts, $post);
            }
        } else {
            $caption = $this->renderTemplate($template, $facts, $post);
        }

        return [
            'caption' => $caption,
            'source'  => $source,
            'meta'    => array_filter([
                'template_id'    => $template?->id,
                'language_mode'  => $template?->language_mode ?? $post->language_mode,
                'product_ids'    => $facts['product_ids'],
                'generated_at'   => now()->toIso8601String(),
                'ai_available'   => $this->isAiAvailable(),
                'source'         => $source,
                'model'          => $source === 'ai' ? config('autoposter.anthropic_model') : null,
                'ai_error'       => $fallback,
            ], fn ($v) => $v !== null),
        ];
    }

    /**
     * Claude path: ask for the per-language bodies as JSON, then run them
     * through the SAME assembly and toggle logic the template path uses.
     *
     * Returning structured per-language text rather than one finished blob is
     * deliberate — it keeps language selection, joining and the toggles under
     * our control, so the AI cannot quietly drop a language the admin asked for
     * or bolt on hashtags they switched off.
     */
    protected function generateWithAi(AutoPosterQueue $post, ?AutoPosterTemplate $template, array $facts): string
    {
        $languages = $this->languagesFor($template, $post);

        $result = $this->claude->generateJson(
            $this->systemPrompt(),
            $this->userPrompt($post, $template, $facts, $languages),
            $this->responseSchema($languages),
        );

        $bodies = [];
        foreach ($languages as $lang) {
            $text = trim((string) ($result[$lang] ?? ''));

            if ($text !== '') {
                $bodies[] = $text;
            }
        }

        if (empty($bodies)) {
            throw new \RuntimeException('Claude returned no usable caption text.');
        }

        $caption = implode("\n\n", $bodies);

        // Identical post-processing to the template path — same toggles, same
        // emoji stripping, same trailing blocks.
        if ($template) {
            $caption = $this->applyToggles($caption, $template, $facts);
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", $caption));
    }

    /**
     * Which language keys this post needs, in output order.
     *
     * @return array<int,string>
     */
    protected function languagesFor(?AutoPosterTemplate $template, AutoPosterQueue $post): array
    {
        $mode = $template->language_mode ?? $post->language_mode ?? 'en_ja_my';

        return match ($mode) {
            'en'    => ['en'],
            'ja'    => ['ja'],
            'my'    => ['my'],
            'en_ja' => ['en', 'ja'],
            'en_my' => ['en', 'my'],
            default => ['en', 'ja', 'my'],
        };
    }

    protected function systemPrompt(): string
    {
        return <<<'TXT'
        You write short social captions for a Japanese kimono shop's Facebook page.

        House style:
        - Warm and concrete. Describe what the garment actually is; never invent
          fabric, age, provenance, condition or price details you were not given.
        - Each language version must read as though written by a native speaker
          for that audience — a natural rewrite, not a translation of the English.
        - Burmese (my) is for Myanmar customers living in Japan.
        - The goal is a Messenger reply, so end with a light invitation to message.
        - No hashtags and no closing links: those are appended afterwards by the
          system, and duplicating them looks like spam.
        - Keep each language under roughly 500 characters.

        Return only the JSON object described by the schema.
        TXT;
    }

    protected function userPrompt(AutoPosterQueue $post, ?AutoPosterTemplate $template, array $facts, array $languages): string
    {
        $lines = [];

        $lines[] = 'Write a Facebook caption for these ' . count($facts['product_ids']) . ' item(s):';

        foreach ($facts['names'] as $name) {
            $lines[] = '  - ' . $name;
        }

        if ($facts['min_price'] !== null) {
            $lines[] = 'Lowest price shown: ¥' . number_format($facts['min_price']);
        }

        $lines[] = '';
        $lines[] = 'Languages required: ' . implode(', ', $languages);

        if ($template) {
            $existing = array_filter([
                'en' => $template->body_en,
                'ja' => $template->body_ja,
                'my' => $template->body_my,
            ]);

            if (! empty($existing)) {
                $lines[] = '';
                $lines[] = 'Match the tone and structure of these house templates.'
                    . ' Placeholders in {{double braces}} show where details go —'
                    . ' write the real text instead of the placeholder:';

                foreach ($existing as $lang => $body) {
                    $lines[] = "[{$lang}] " . trim($body);
                }
            }

            if ($template->include_new_arrival_text) {
                $lines[] = '';
                $lines[] = 'These are being presented as new arrivals.';
            }

            if ($template->include_campaign_text && ! empty($template->campaign_text)) {
                $lines[] = 'Current campaign (do not restate verbatim, it is appended separately): '
                    . trim($template->campaign_text);
            }

            $lines[] = $template->include_emojis
                ? 'A few tasteful emoji are welcome.'
                : 'Do not use any emoji.';
        }

        return implode("\n", $lines);
    }

    /**
     * JSON schema pinning exactly the languages requested — additionalProperties
     * false so the model cannot return a language we did not ask for.
     *
     * @param  array<int,string>  $languages
     * @return array<string,mixed>
     */
    protected function responseSchema(array $languages): array
    {
        $properties = [];

        foreach ($languages as $lang) {
            $properties[$lang] = [
                'type'        => 'string',
                'description' => match ($lang) {
                    'en'    => 'English caption',
                    'ja'    => 'Japanese caption (日本語)',
                    'my'    => 'Burmese caption (မြန်မာ)',
                    default => 'Caption',
                },
            ];
        }

        return [
            'type'                 => 'object',
            'properties'           => $properties,
            'required'             => array_values($languages),
            'additionalProperties' => false,
        ];
    }

    public function resolveTemplate(AutoPosterQueue $post): ?AutoPosterTemplate
    {
        if ($post->template_id) {
            $t = AutoPosterTemplate::find($post->template_id);
            if ($t) {
                return $t;
            }
        }

        return AutoPosterTemplate::where('channel_id', $post->channel_id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * Facts about the post's products, used to fill placeholders.
     *
     * @return array{product_ids:array<int,int>,names:array<int,string>,min_price:float|null}
     */
    protected function productFacts(AutoPosterQueue $post): array
    {
        $ids = $post->products()->pluck('product_id')->map('intval')->all();

        if (empty($ids)) {
            return ['product_ids' => [], 'names' => [], 'min_price' => null];
        }

        $locale = DB::table('facebook_catalog_settings')
            ->where('channel_id', $post->channel_id)
            ->value('default_locale');
        $locale = strtolower(explode('_', (string) ($locale ?: 'ja'))[0]);

        $rows = DB::table('product_flat')
            ->whereIn('product_id', $ids)
            ->where('locale', $locale)
            ->get(['product_id', 'name', 'price', 'special_price']);

        $names  = [];
        $prices = [];

        foreach ($rows as $r) {
            if (! empty($r->name)) {
                $names[$r->product_id] = $r->name;
            }

            $p = ($r->special_price && $r->special_price > 0 && $r->special_price < $r->price)
                ? $r->special_price
                : $r->price;

            if ($p > 0) {
                $prices[] = (float) $p;
            }
        }

        return [
            'product_ids' => $ids,
            'names'       => array_values($names),
            'min_price'   => ! empty($prices) ? min($prices) : null,
        ];
    }

    protected function renderTemplate(?AutoPosterTemplate $template, array $facts, AutoPosterQueue $post): string
    {
        $bodies = $template
            ? $this->bodiesForMode($template)
            : [$this->fallbackBody($facts)];

        $bodies = array_values(array_filter(array_map('trim', $bodies)));

        if (empty($bodies)) {
            $bodies = [$this->fallbackBody($facts)];
        }

        $replacements = [
            '{{product_names}}'   => implode(' / ', array_slice($facts['names'], 0, 5)),
            '{{product_count}}'   => (string) count($facts['product_ids']),
            '{{price_from}}'      => $facts['min_price'] !== null
                ? '¥' . number_format($facts['min_price'])
                : '',
            '{{shop_name}}'       => (string) config('app.name', 'Fujiba'),
            '{{messenger_link}}'  => (string) ($post->messenger_destination ?? ''),
        ];

        $rendered = array_map(
            fn ($body) => strtr($body, $replacements),
            $bodies
        );

        $caption = implode("\n\n", $rendered);

        if ($template) {
            $caption = $this->applyToggles($caption, $template, $facts);
        }

        // Collapse any blank runs left by an unused placeholder.
        return trim(preg_replace("/\n{3,}/", "\n\n", $caption));
    }

    /**
     * Which language bodies to include, per the template's language_mode.
     *
     * @return array<int,string>
     */
    protected function bodiesForMode(AutoPosterTemplate $template): array
    {
        $map = [
            'en'       => ['body_en'],
            'ja'       => ['body_ja'],
            'my'       => ['body_my'],
            'en_ja'    => ['body_en', 'body_ja'],
            'en_my'    => ['body_en', 'body_my'],
            'en_ja_my' => ['body_en', 'body_ja', 'body_my'],
        ];

        $fields = $map[$template->language_mode] ?? ['body_en', 'body_ja', 'body_my'];

        return array_map(fn ($f) => (string) $template->{$f}, $fields);
    }

    protected function applyToggles(string $caption, AutoPosterTemplate $template, array $facts): string
    {
        $suffix = [];

        if ($template->include_new_arrival_text) {
            $suffix[] = '🆕 New Arrival / 新着 / အသစ်ရောက်';
        }

        if ($template->include_campaign_text && ! empty($template->campaign_text)) {
            $suffix[] = trim($template->campaign_text);
        }

        if ($template->include_hashtags) {
            $suffix[] = '#kimono #着物 #japanesefashion #vintagekimono #fujiba';
        }

        if (! empty($suffix)) {
            $caption .= "\n\n" . implode("\n", $suffix);
        }

        if (! $template->include_emojis) {
            $caption = $this->stripEmojis($caption);
        }

        return $caption;
    }

    /**
     * Remove emoji when the template has them switched off.
     *
     * The ranges matter: an earlier version used 1F300-1FAFF plus 2600-27BF,
     * which silently missed 🆕 (U+1F195) in the enclosed-alphanumeric-supplement
     * block, so the "New Arrival" line kept its emoji even with the toggle off.
     * Covered here: regional/enclosed (1F100-1F2FF), pictographs and extensions
     * (1F300-1FAFF), misc symbols and dingbats (2600-27BF), misc technical and
     * arrows (2190-21FF, 2B00-2BFF), plus the variation selector and zero-width
     * joiner that glue multi-codepoint sequences together — leaving those behind
     * produces invisible junk between characters.
     *
     * Whitespace is then repaired: stripping a leading emoji otherwise leaves
     * the line indented by the space that followed it.
     */
    protected function stripEmojis(string $caption): string
    {
        $pattern = '/['
            . '\x{1F100}-\x{1F2FF}'
            . '\x{1F300}-\x{1FAFF}'
            . '\x{2600}-\x{27BF}'
            . '\x{2190}-\x{21FF}'
            . '\x{2B00}-\x{2BFF}'
            . '\x{FE0F}\x{200D}\x{20E3}'
            . ']/u';

        $caption = preg_replace($pattern, '', $caption);

        // Collapse runs of spaces, then trim what is now leading/trailing
        // whitespace on each line.
        $caption = preg_replace('/[ \t]{2,}/', ' ', $caption);
        $caption = implode("\n", array_map('trim', explode("\n", $caption)));

        return $caption;
    }

    /**
     * Used when a channel has no template at all — a plain, honest caption
     * rather than an empty post.
     */
    protected function fallbackBody(array $facts): string
    {
        $names = implode(' / ', array_slice($facts['names'], 0, 3));

        return trim(($names !== '' ? $names . "\n\n" : '')
            . "Message us on Messenger to order 💬\nメッセージでご注文ください\nမက်ဆေ့ချ်ပို့ပြီး မှာယူနိုင်ပါတယ်");
    }
}
