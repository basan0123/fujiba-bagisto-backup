<?php

namespace Fujiba\AutoPoster\Services;

use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Branded 1080x1080 cover image for a post: the post's product photos in a
 * collage under an indigo band with a rotating JA/EN headline and the price.
 * Rendered with ImageMagick into public/fb-posts/auto/<tenant>/<post>.jpg so
 * Facebook (via ChatPion) can fetch it from the storefront host.
 */
class PostCoverRenderer
{
    /** [Japanese headline, English line]. Picked per post, stable by post id. */
    public const HEADLINES = [
        ['和柄コットン 新入荷', 'NEW ARRIVALS · JAPANESE COTTON PRINTS'],
        ['職人の和柄を、手もとに', 'CRAFTED JAPANESE PATTERNS'],
        ['本日のおすすめ和柄', "TODAY'S PICK FROM FUJIBA"],
        ['金彩が映える伝統柄', 'GOLD-ACCENTED TRADITIONAL PRINTS'],
        ['手作りにぴったりの和柄', 'MADE FOR YOUR HANDMADE'],
        ['人気柄 在庫限り', 'POPULAR PRINTS · LIMITED STOCK'],
        ['季節を彩る和の生地', 'FABRICS OF THE JAPANESE SEASONS'],
    ];

    public function __construct(protected UrlResolver $urls) {}

    public function render(AutoPosterQueue $post): ?string
    {
        $paths = $this->sourcePaths($post);

        if (empty($paths)) {
            return null;
        }

        $out = $this->file($post);
        @mkdir(dirname($out), 0775, true);

        [$ja, $en] = self::HEADLINES[$post->id % count(self::HEADLINES)];
        $price     = $this->priceLine($post);
        $font      = $this->font();
        $tmp       = [];

        try {
            $tiles = $this->tileSpecs(count($paths));
            $args  = ['-size', '1080x1080', 'xc:#141B33'];

            foreach ($paths as $i => $src) {
                [$w, $h, $x, $y] = $tiles[$i];
                $tile  = tempnam(sys_get_temp_dir(), 'apc').'.jpg';
                $tmp[] = $tile;
                $this->run(['convert', $src.'[0]', '-auto-orient', '-resize', "{$w}x{$h}^", '-gravity', 'center', '-extent', "{$w}x{$h}", $tile]);
                $args = array_merge($args, [$tile, '-geometry', "+{$x}+{$y}", '-composite']);
            }

            $args = array_merge($args, [
                // indigo band + gold hairlines
                '-fill', 'rgba(20,27,51,0.90)', '-draw', 'rectangle 0,790 1080,1080',
                '-fill', '#D9B866', '-draw', 'rectangle 0,790 1080,793',
                '-font', $font, '-gravity', 'north',
                '-fill', '#D9B866', '-pointsize', '26', '-annotate', '+0+822', $en,
                '-fill', '#F4F1EA', '-pointsize', '62', '-annotate', '+0+866', $ja,
                '-fill', '#D9B866', '-pointsize', '30', '-annotate', '+0+968', $price,
                '-fill', 'rgba(244,241,234,0.75)', '-pointsize', '20', '-annotate', '+0+1028', 'FUJIBA ELEGANCE EMPORIUM · TOKYO',
                '-quality', '86', $out,
            ]);

            $this->run(array_merge(['convert'], $args));
        } catch (\Throwable $e) {
            Log::warning('[AutoPoster] cover render failed', ['queue_id' => $post->id, 'error' => $e->getMessage()]);

            return null;
        } finally {
            foreach ($tmp as $t) {
                @unlink($t);
            }
        }

        return is_file($out) ? $out : null;
    }

    /** Public URL of the rendered cover, or null when there is none. */
    public function url(AutoPosterQueue $post): ?string
    {
        if (! is_file($this->file($post))) {
            return null;
        }

        return rtrim($this->urls->storefrontBaseUrl((int) $post->channel_id), '/')
            .'/fb-posts/auto/'.$this->tenantKey().'/'.$post->id.'.jpg?v='.filemtime($this->file($post));
    }

    public function file(AutoPosterQueue $post): string
    {
        return public_path('fb-posts/auto/'.$this->tenantKey().'/'.$post->id.'.jpg');
    }

    /** Local files of the post's product photos, in post order, max 4. */
    protected function sourcePaths(AutoPosterQueue $post): array
    {
        $root  = storage_path('app/public/tenants/'.$post->channel_id.'/'.$this->tenantKey());
        $paths = [];

        foreach ($post->products()->orderBy('sort_order')->get() as $item) {
            $path = DB::table('product_images')
                ->where('product_id', $item->product_id)
                ->orderBy('position')
                ->orderBy('id')
                ->value('path');

            // Tenant media lives under tenants/<channel>/<tenant>; fall back to
            // the tenants/1 layout the Fujiba tenant actually uses.
            foreach ([$root, storage_path('app/public/tenants/1/'.$this->tenantKey())] as $base) {
                if ($path && is_file($base.'/'.$path)) {
                    $paths[] = $base.'/'.$path;
                    break;
                }
            }

            if (count($paths) === 4) {
                break;
            }
        }

        return $paths;
    }

    /** [w, h, x, y] per tile for 1–4 photos above the band. */
    protected function tileSpecs(int $n): array
    {
        $h = 790;

        return match ($n) {
            1       => [[1080, $h, 0, 0]],
            2       => [[538, $h, 0, 0], [538, $h, 542, 0]],
            3       => [[538, $h, 0, 0], [538, 393, 542, 0], [538, 393, 542, 397]],
            default => [[538, 393, 0, 0], [538, 393, 542, 0], [538, 393, 0, 397], [538, 393, 542, 397]],
        };
    }

    protected function priceLine(AutoPosterQueue $post): string
    {
        $min = DB::table('product_flat')
            ->whereIn('product_id', $post->products()->pluck('product_id'))
            ->where('channel', 'default')
            ->where('price', '>', 0)
            ->min('price');

        $yen = $min ? '¥'.number_format((float) $min) : '';

        return trim($yen.'〜(税込) · 11枚ごとに1枚分無料');
    }

    protected function font(): string
    {
        foreach (['/usr/share/fonts/opentype/ipafont-mincho/ipam.ttf', '/usr/share/fonts/truetype/fonts-japanese-mincho.ttf'] as $f) {
            if (is_file($f)) {
                return $f;
            }
        }

        $match = trim((string) shell_exec('fc-match -f "%{file}" IPAMincho 2>/dev/null'));

        return $match !== '' ? $match : 'DejaVu-Sans';
    }

    protected function tenantKey(): string
    {
        return (string) (tenancy()->tenant?->getTenantKey() ?? 'central');
    }

    protected function run(array $cmd): void
    {
        $line = implode(' ', array_map('escapeshellarg', $cmd)).' 2>&1';
        exec($line, $out, $code);

        if ($code !== 0) {
            throw new \RuntimeException('ImageMagick failed: '.implode("\n", array_slice($out, 0, 5)));
        }
    }
}
