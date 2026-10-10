<?php

namespace Fujiba\AutoPoster\Services;

use Carbon\Carbon;
use Fujiba\AutoPoster\Models\AutoPosterProductHistory;
use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Turns a queued post into a collection post: either one pattern in all its
 * colours (FV-44-A/B/C/D) or one colour across patterns. Photos are the
 * product's own fabric shot, cropped/enhanced/stamped by bin/post_photo.py with
 * the FUJIBA mark and catalog code; no cover. Caption is Burmese (phrases taken
 * from the page's own past posts), Japanese and English, with exact prices.
 */
class CollectionPostBuilder
{
    public const MAX_PHOTOS = 8;

    public const COLOUR_PER_POST = 6;

    /** Every Nth post (by id) goes out as a vertical video instead of photos. */
    public const VIDEO_EVERY = 3;

    /**
     * A/B arm for reels: alternating video posts get a mild tone lift so the
     * two arms can be compared on reach and income. Recorded in caption_meta
     * as 'enhanced' so the results can be read back per post.
     */
    public const ENHANCE_EVERY = 2;

    /** Products created within this many days count as new arrivals. */
    public const NEW_DAYS = 60;

    protected const COLOURS = [
        // key => [Burmese, Japanese, English]
        'black'  => ['အနက်ရောင်', '黒', 'Black'],
        'white'  => ['အဖြူရောင်', '白', 'White'],
        'grey'   => ['မီးခိုးရောင်', 'グレー', 'Grey'],
        'red'    => ['အနီရောင်', '赤', 'Red'],
        'orange' => ['လိမ္မော်ရောင်', 'オレンジ', 'Orange'],
        'gold'   => ['ရွှေဝါရောင်', '金・黄', 'Gold'],
        'brown'  => ['အညိုရောင်', '茶', 'Brown'],
        'green'  => ['အစိမ်းရောင်', '緑', 'Green'],
        'blue'   => ['အပြာရောင်', '青', 'Blue'],
        'navy'   => ['အပြာရင့်ရောင်', '紺', 'Navy'],
        'purple' => ['ခရမ်းရောင်', '紫', 'Purple'],
        'pink'   => ['ပန်းရောင်', 'ピンク', 'Pink'],
    ];

    protected ?Collection $pool = null;

    /** product_id => last used (published or already queued), for rotation. */
    protected array $lastUsed = [];

    public function __construct(protected UrlResolver $urls) {}

    /**
     * Rebuild one post as the next collection in rotation.
     */
    public function fill(AutoPosterQueue $post, ?string $kind = null): bool
    {
        $kind ??= match (true) {
            $post->id % 5 === 0 => 'new',
            $post->id % 3 === 0 => 'colour',
            default             => 'pattern',
        };
        $group = $this->nextGroup($kind, $post) ?? $this->nextGroup('pattern', $post);

        if (! $group) {
            return false;
        }

        $this->apply($post, $group);

        return true;
    }

    /**
     * Re-caption after products dropped out (sold out) — keeps the same group.
     */
    public function refresh(AutoPosterQueue $post): void
    {
        $meta  = $post->caption_meta ?? [];
        $ids   = $post->products()->pluck('product_id');
        $items = $this->pool()->concat($this->soldOutPool())->whereIn('product_id', $ids)->sortBy('code')->values();

        $post->update(['caption' => $this->caption($meta['collection'] ?? 'pattern', $meta['key'] ?? '', $items)]);

        if ($meta['video'] ?? false) {
            $this->renderVideo($post, $items);
        }
    }

    public function videoFile(AutoPosterQueue $post): string
    {
        return public_path('fb-posts/auto/'.$this->tenantKey().'/reels/'.$post->id.'.mp4');
    }

    /** Public URL of the post's video, or null when it is a photo post / not rendered. */
    public function videoUrl(AutoPosterQueue $post): ?string
    {
        $file = $this->videoFile($post);

        if (! (($post->caption_meta ?? [])['video'] ?? false) || ! is_file($file)) {
            return null;
        }

        return rtrim($this->urls->storefrontBaseUrl(1), '/').'/fb-posts/auto/'.$this->tenantKey().'/reels/'.$post->id.'.mp4?v='.filemtime($file);
    }

    /** Vertical reel of the group's stamped photos (bin/post_reel.py). */
    public function renderVideo(AutoPosterQueue $post, Collection $items): bool
    {
        $photos = $items->map(fn ($p) => public_path('fb-posts/auto/'.$this->tenantKey().'/photos/'.$p->product_id.'.jpg'))
            ->filter(fn ($f) => is_file($f))->values()->all();

        if (! $photos) {
            return false;
        }

        $first = $items->first();
        [$en, $my] = match (true) {
            $first->length === '3m'     => ['Japanese Kimono Fabric 110cm × 300cm', 'ဝမ်းဆက်စာ 3m'],
            (int) $first->price >= 1500 => ['Japanese Lungi 110cm × 200cm', 'လုံချည်တစ်ကွင်းစာ ၂ကိုက်တစ်ထွာ'],
            default                     => ['Japanese Kimono Fabric', 'ဂျပန် ကီမိုနို အထည်'],
        };

        $out = $this->videoFile($post);
        @mkdir(dirname($out), 0775, true);

        // A/B arm — alternate enhanced / untouched across video posts, and
        // record which arm this one used so reach can be compared later.
        $enhanced = intdiv((int) $post->id, self::VIDEO_EVERY) % self::ENHANCE_EVERY === 0;

        $meta = $post->caption_meta ?? [];
        if (($meta['enhanced'] ?? null) !== $enhanced) {
            $meta['enhanced'] = $enhanced;
            $post->caption_meta = $meta;
            $post->save();
        }

        $cmd = array_merge(['python3', dirname(__DIR__, 2).'/bin/post_reel.py', $out, $en, $my, 'Page Messenger မှာ ဆက်သွယ်မှာယူနိုင်ပါတယ်ရှင့်'], $photos);
        exec('REEL_ENHANCE='.($enhanced ? '1' : '0').' '.implode(' ', array_map('escapeshellarg', $cmd)).' 2>&1', $lines, $rc);

        return $rc === 0 && is_file($out);
    }

    /**
     * Turn a post into one part of a restock round-up (all received products
     * of a reorder batch, split over $parts posts).
     */
    public function restock(AutoPosterQueue $post, int $batchId, int $part, int $parts): int
    {
        $ids   = DB::table('reorder_items')->where('reorder_batch_id', $batchId)->where('to_stock_qty', '>', 0)->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        $items = $this->pool()->whereIn('product_id', $ids)->sortBy('code')->values();
        $chunk = $items->chunk((int) ceil($items->count() / $parts))->values()[$part - 1] ?? collect();

        if ($chunk->isEmpty()) {
            return 0;
        }

        $this->apply($post, ['restock', "batch{$batchId}:{$part}/{$parts}", $chunk->values()]);

        return $chunk->count();
    }

    public static function isCollection(AutoPosterQueue $post): bool
    {
        return ! empty(($post->caption_meta ?? [])['collection']);
    }

    protected function apply(AutoPosterQueue $post, array $group): void
    {
        [$kind, $key, $items] = $group;

        DB::transaction(function () use ($post, $kind, $key, $items) {
            $post->products()->delete();

            foreach ($items->values() as $i => $p) {
                $post->products()->create([
                    'product_id'         => $p->product_id,
                    'sort_order'         => $i,
                    'image_url'          => $p->photo_url,
                    'override_image_url' => $p->photo_url,
                    'product_url'        => $this->urls->productUrlFor($p->product_id, (int) $post->channel_id),
                ]);
            }

            $post->update([
                'caption'      => $this->caption($kind, $key, $items),
                'caption_meta' => ['collection' => $kind, 'key' => $key, 'codes' => $items->pluck('code')->all(), 'sold_out' => $items->filter(fn ($p) => $p->sold_out ?? false)->pluck('product_id')->values()->all(), 'video' => $kind !== 'restock' && $post->id % self::VIDEO_EVERY === 0],
                'format'       => 'multi_photo',
            ]);
        });

        @unlink(public_path('fb-posts/auto/'.$this->tenantKey().'/'.$post->id.'.jpg'));

        if ($post->fresh()->caption_meta['video'] ?? false) {
            $this->renderVideo($post->fresh(), $items);
        }

        foreach ($items as $p) {
            $this->lastUsed[$p->product_id] = $post->scheduled_at?->timestamp ?? time();
        }
    }

    /**
     * The least-recently-used group of the requested kind, avoiding products
     * already posted the same day.
     *
     * @return array{0:string,1:string,2:Collection}|null
     */
    protected function nextGroup(string $kind, AutoPosterQueue $post): ?array
    {
        $pool    = $this->pool();
        $sameDay = $this->productsOnDay($post);
        $fresh   = $pool->reject(fn ($p) => in_array($p->product_id, $sameDay, true));

        $byPattern = fn ($items, int $min) => $items->groupBy('pattern')->filter(fn ($g) => $g->count() >= $min)
            ->map(fn ($g) => $g->sortBy('code')->take(self::MAX_PHOTOS)->values());

        $groups = match ($kind) {
            'new'    => $byPattern($fresh->filter(fn ($p) => $p->created_at >= now()->subDays(self::NEW_DAYS)->toDateTimeString()), 1),
            // One background colour across different patterns (one per pattern).
            'colour' => $fresh->groupBy('colour')
                ->map(fn ($g) => $g->sortBy(fn ($p) => [$this->lastUsed[$p->product_id] ?? 0, crc32($p->code.$post->id)])
                    ->unique('code')->unique('pattern')->take(self::COLOUR_PER_POST)->values())
                ->filter(fn ($g) => $g->count() >= 3),
            default  => $byPattern($fresh, 2)->whenEmpty(fn () => $byPattern($fresh, 1)),
        };

        if ($groups->isEmpty()) {
            return null;
        }

        $key = $groups->sortBy(fn ($g, $k) => [$g->avg(fn ($p) => $this->lastUsed[$p->product_id] ?? 0), crc32($k.$post->id)])->keys()->first();

        $items = $groups[$key];

        // Sometimes (about 1 post in 3) show the full A-D set even when one
        // colour is sold out — it is still the same pattern, customers ask
        // for restocks. Never more than one sold-out photo per post.
        if ($kind === 'pattern' && crc32('oos'.$post->id) % 3 === 0) {
            $soldOut = $this->soldOutPool()
                ->where('pattern', $items->first()->pattern)
                ->reject(fn ($p) => $items->contains('product_id', $p->product_id))
                ->first();

            if ($soldOut && $items->count() < self::MAX_PHOTOS) {
                $items = $items->push($soldOut)->sortBy('code')->values();
            }
        }

        return [$kind, (string) $key, $items];
    }

    protected function productsOnDay(AutoPosterQueue $post): array
    {
        if (! $post->scheduled_at) {
            return [];
        }

        $day = $post->scheduled_at->copy();

        return DB::table('autoposter_post_products as pp')
            ->join('autoposter_queue as q', 'q.id', '=', 'pp.queue_id')
            ->where('q.id', '<>', $post->id)
            ->where('q.channel_id', $post->channel_id)
            ->whereIn('q.status', [AutoPosterQueue::STATUS_SCHEDULED, AutoPosterQueue::STATUS_PUBLISHED])
            ->whereBetween('q.scheduled_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->pluck('pp.product_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * In-stock, enabled kimono fabrics with a processed post photo.
     */
    public function pool(): Collection
    {
        return $this->pool ??= $this->loadPool(true);
    }

    protected ?Collection $soldOut = null;

    /** Kimono fabrics that are out of stock or disabled, for the odd full-set post. */
    public function soldOutPool(): Collection
    {
        return $this->soldOut ??= $this->loadPool(false)->each(fn ($p) => $p->sold_out = true);
    }

    protected function loadPool(bool $inStock): Collection
    {
        $rows = DB::table('product_flat as f')
            ->leftJoin(DB::raw('(SELECT product_id, SUM(qty) q FROM product_inventories GROUP BY product_id) s'), 's.product_id', '=', 'f.product_id')
            ->where('f.channel', 'default')->where('f.locale', 'ja')
            ->where('f.visible_individually', 1)
            ->where('f.price', '>', 0)
            ->when($inStock,
                fn ($q) => $q->where('f.status', 1)->where('s.q', '>', 0),
                fn ($q) => $q->where(fn ($w) => $w->where('f.status', '<>', 1)->orWhereNull('s.q')->orWhere('s.q', '<=', 0)))
            ->where('f.name', 'like', 'F%')
            ->where('f.name', 'not like', 'FB-HS%') // unknown size, not a 2m piece
            // "Fb_…/Fb-…" names still carry the supplier's own code — never post them.
            ->whereRaw("BINARY LEFT(f.name, 2) <> 'Fb'")
            ->join('products as pr', 'pr.id', '=', 'f.product_id')
            ->get(['f.product_id', 'f.name', 'f.price', 'f.url_key', 'pr.created_at']);

        $meta = $this->photoMeta();

        $pool = $rows->map(function ($r) use (&$meta) {
            $code = self::code($r->name);
            if (! $code) {
                return null;
            }

            $photo = $this->photo((int) $r->product_id, $code, $meta, (string) $r->name);
            if (! $photo) {
                return null;
            }

            $r->product_id = (int) $r->product_id;
            $r->code       = $code;
            $r->length     = preg_match('/3\s?m/i', $r->name) ? '3m' : '2m';
            $r->pattern    = self::pattern($code).'|'.$r->length.'|'.(int) $r->price;
            $r->colour     = $photo['colour'];
            $r->photo_url  = $photo['url'];

            return $r;
        })->filter()->values();

        $this->savePhotoMeta($meta);

        if ($inStock) {
            $this->lastUsed = AutoPosterProductHistory::query()->pluck('last_posted_at', 'product_id')
                ->map(fn ($t) => $t ? Carbon::parse($t)->timestamp : 0)->all();
        }

        return $pool;
    }

    /** Catalog code from a product name: "FK-15D (purple…)" → "FK-15D". */
    public static function code(string $name): ?string
    {
        return preg_match('/^(F[A-Za-z]{0,2}[-_][A-Za-z0-9]+(?:[-_][A-Za-z0-9]+)*)/', trim($name), $m) ? $m[1] : null;
    }

    /** Pattern key: the code minus its colour letter. FV-44-A → FV-44, FK-11B → FK-11. */
    public static function pattern(string $code): string
    {
        return rtrim(preg_replace('/[A-Z]$/i', '', preg_replace('/\(?3m\)?$/i', '', $code)), '-_ ');
    }

    /**
     * Processed photo for a product (cached by source mtime).
     *
     * @return array{url:string,colour:string}|null
     */
    protected function photo(int $productId, string $code, array &$meta, string $name = ''): ?array
    {
        $base = storage_path('app/public/tenants/1/'.$this->tenantKey());
        $srcs = DB::table('product_images')->where('product_id', $productId)->orderBy('position')->orderBy('id')
            ->pluck('path')->map(fn ($p) => $base.'/'.$p)->filter(fn ($p) => is_file($p))->values()->all();

        if (! $srcs) {
            return null;
        }

        $sig = md5('v3|'.$name.'|'.$code.'|'.implode('|', array_map(fn ($p) => $p.filemtime($p), $srcs)));
        $rel = 'fb-posts/auto/'.$this->tenantKey().'/photos/'.$productId.'.jpg';
        $out = public_path($rel);

        if (($meta[$productId]['sig'] ?? null) !== $sig || ! is_file($out)) {
            $cmd = array_merge(['python3', dirname(__DIR__, 2).'/bin/post_photo.py', $out, $code], $srcs);
            exec('POST_NAME='.escapeshellarg(trim($name) ?: $code).' '.implode(' ', array_map('escapeshellarg', $cmd)).' 2>&1', $lines, $rc);
            $res = $rc === 0 ? json_decode((string) end($lines), true) : null;

            if (! $res) {
                return null;
            }

            $meta[$productId] = ['sig' => $sig, 'colour' => $meta[$productId]['manual'] ?? $res['colour'], 'manual' => $meta[$productId]['manual'] ?? null];
        }

        return [
            'url'    => rtrim($this->urls->storefrontBaseUrl(1), '/').'/'.$rel.'?v='.substr($sig, 0, 8),
            'colour' => $meta[$productId]['manual'] ?? $meta[$productId]['colour'],
        ];
    }

    /**
     * Burmese-first caption in the page's own words (phrases taken from its
     * past posts), with exact prices, then short JA/EN lines.
     */
    public function caption(string $kind, string $key, Collection $items): string
    {
        if ($items->isEmpty()) {
            throw new RuntimeException('Collection has no products');
        }

        $link   = 'https://m.me/'.config('autoposter.page_ids.1');
        $length = $items->first()->length;
        $yen    = fn ($v) => '¥'.number_format($v);

        $c   = $kind === 'colour' ? (self::COLOURS[$key] ?? null) : null;
        $pick = fn (array $opts) => $opts[crc32($kind.$key.$items->pluck('code')->implode(',')) % count($opts)];

        [$head, $intro, $ja, $en] = match ($kind) {
            'new' => ['🎀 𝒩𝐸𝒲 𝒜𝑅𝑅𝐼𝒱𝒜𝐿 🎀', "New arrival\nအဆင်သစ်တွေအများကြီးရောက်ထားပါတယ်ရှင့်\nလှလှလေးတွေ ရချင်ရင် page မှာလာကြည့်လို့ရပါတယ် 🥰", '新入荷の和柄', 'New arrival'],
            'restock' => ['✨ 𝑅𝐸𝒮𝒯𝒪𝒞𝒦 ✨', "ပစ္စည်းရောက်ထားတာလေးတွေ Instock ဖြည့်ထားပါတယ်\nအခြား ပစ္စည်းပြတ်နေတဲ့အထည်လေးလည်း ပြန်ဖြည့်ထားပါတယ်ရှင့်", '人気柄 再入荷', 'Back in stock'],
            'colour' => [
                $pick(['🎨 '.($c[2] ?? '').' Collection 🎨', '🌸 '.($c[2] ?? '').' Kimono Lungi 🌸', '✨ '.($c[2] ?? '').' Background Collection ✨']),
                $pick([($c[0] ?? '').' အခံလေးတွေ စုထားပါတယ်ရှင့် 🥰', ($c[0] ?? '').' အခံ အဆင်မျိုးစုံ ရွေးလို့ရပါတယ်ရှင့်', ($c[0] ?? '').' ကြိုက်သူတွေအတွက် အဆင်လှလှလေးတွေပါရှင့် 💕']),
                ($c[1] ?? '').'地の和柄コレクション', ($c[2] ?? '').' background · '.$items->count().' patterns',
            ],
            default => [
                $pick(['🌸 𝐹𝒰𝒥𝐼𝐵𝒜 · Japanese Kimono Cotton 🌸', '🎌 Pattern Collection · 柄コレクション 🎌', '💮 One Pattern · All Colours 💮']),
                $pick(['ဒီအဆင်ထဲက ခု ရနိုင်တဲ့ အရောင်တွေပါရှင့်', 'အဆင်တူ အရောင်ကွဲလေးတွေ ရွေးလို့ရပါတယ်ရှင့် 🥰', 'ကြိုက်တဲ့အရောင်လေး ရွေးပြီး Messenger မှာ မှာယူနိုင်ပါတယ်ရှင့်']),
                '同じ柄・'.$items->count().'色', 'One pattern, '.$items->count().' colours',
            ],
        };

        $soldOut = $items->filter(fn ($p) => $p->sold_out ?? false)->pluck('code');
        $nepali  = $pick([
            "🇳🇵 जापानबाट नेपाल फर्किंदा प्रियजनलाई कोसेली 🎁 — जापानी लुङ्गी कपडा",
            "🇳🇵 नेपाल लैजाने कोसेली खोज्दै हुनुहुन्छ? जापानी किमोनो लुङ्गी 🎁",
            "🇳🇵 Japanese Lungi — नेपाल फर्किंदा उत्तम कोसेली 🎁",
        ]);

        if ($kind === 'pattern' && $items->count() > 1) {
            $name  = self::pattern($items->first()->code);
            $codes = $name.'  ['.$items->pluck('code')->map(fn ($c) => substr(preg_replace('/\(?3m\)?$/i', '', $c), -1))->implode(' · ').']';
        } else {
            $codes = $items->pluck('code')->implode(' / ');
        }

        $byPrice = $items->groupBy(fn ($p) => (int) $p->price);
        $prices  = $byPrice->count() === 1
            ? "{$length} {$yen((int) $items->first()->price)}"
            : $byPrice->map(fn ($g, $v) => $g->pluck('code')->implode(' / ').'  '.$yen($v))->implode("\n");

        if ($byPrice->count() > 1 || $kind === 'restock') {
            $codes = '';
        }

        // Facebook collapses a caption behind "See more" after ~3 lines, so the
        // ordering CTA goes near the top where it survives the fold. The full
        // link still appears at the bottom for anyone who expands the post.
        $body = implode("\n\n", array_filter([
            $head,
            "🙏 नमस्ते 🙏 မင်္ဂလာပါရှင့်🛍️",
            "📩 Messenger မှာ မှာယူနိုင်ပါတယ်ရှင့် · Order on Messenger 👉 {$link}",
            $intro,
            trim($codes."\n".$prices),
            $soldOut->isNotEmpty() ? '🔖 '.$soldOut->implode(' / ')." — Sold out ဖြစ်နေပါတယ်၊ ပြန်ရောက်ရင် အကြောင်းကြားပေးပါမယ်ရှင့် (再入荷待ち)" : null,
            "japan ပြန်လက်ဆောင် လေး ကီမိုနိုလုံချည် လှလှလေးပေးချင်သူများအတွက်\nPage Messenger shop ကနေ ကိုယ်တိုင်ကြည့်ရှုပြီး စိတ်တိုင်းကျ မှာယူနိုင်ပါတယ်ရှင့်\nအိမ်ရောက်ငွေချေပို့ပေးပါတယ်ရှင့် ( Credit Card, PayPay, Bank OK )",
            "ဂျပန်ပြည်တွင်း&မြန်မာနိုင်ငံအိမ်အရောက်ပို့ပေးပါတယ်ရှင့်။\nယုံကြည်ပီးအားပေးသူတိုင်းအတွက်\nအမြဲကျေးဇူးတင်ပါတယ်ရှင့်",
            $nepali,
            "🇯🇵 {$ja}・税込・11枚ごとに1枚分無料\n🇬🇧 {$en} · tax incl. · buy 11, get 1 free",
            "📩 {$link}",
            '#japaneselungi #koseli #कोसेली #ဂျပန်လက်ဆောင် #လုံချည် #ကီမိုနို #madeinjapan #kimono #fujiba',
        ]));

        return $body;
    }

    protected function photoMeta(): array
    {
        $file = storage_path('app/autoposter/photo-meta-'.$this->tenantKey().'.json');

        return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    }

    protected function savePhotoMeta(array $meta): void
    {
        $file = storage_path('app/autoposter/photo-meta-'.$this->tenantKey().'.json');
        @mkdir(dirname($file), 0775, true);
        file_put_contents($file, json_encode($meta, JSON_PRETTY_PRINT));
    }

    protected function tenantKey(): string
    {
        return (string) (tenancy()->tenant?->getTenantKey() ?? 'central');
    }
}
