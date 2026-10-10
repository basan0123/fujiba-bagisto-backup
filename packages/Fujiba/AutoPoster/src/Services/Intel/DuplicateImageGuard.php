<?php

namespace Fujiba\AutoPoster\Services\Intel;

/**
 * Content Engine v2 safety block: a product whose photo is identical (same
 * sha256) or near-identical (dHash Hamming <= maxDistance) to a photo of a
 * DIFFERENT product is in an unresolved duplicate group — its photo may show
 * the wrong fabric, so v2 must not render or learn from it.
 *
 * Resolution: once the owner fixes the photos and WS-B re-measures, the match
 * disappears; a group accepted as-is can be whitelisted via
 * config('autoposter.v2_duplicate_resolved_products') (product ids).
 *
 * v2 only — NOT used by the v1 live posting path.
 */
class DuplicateImageGuard
{
    public const REASON = 'blocked:duplicate_image';

    /** @var array<int,string>|null */
    private ?array $blocked = null;

    private DuplicateImageSource $source;

    /** @var array<int,true> */
    private array $resolved;

    public function __construct(?DuplicateImageSource $source = null, ?array $resolved = null, private int $maxDistance = 4)
    {
        $this->source = $source ?? new DbDuplicateImageSource;
        $ids = $resolved ?? (array) (function_exists('config') ? config('autoposter.v2_duplicate_resolved_products', []) : []);
        $this->resolved = array_fill_keys(array_map('intval', $ids), true);
    }

    /** @return array<int,string> product_id => reason */
    public function blocked(): array
    {
        return $this->blocked ??= $this->compute();
    }

    public function reason(int $productId): ?string
    {
        return $this->blocked()[$productId] ?? null;
    }

    public function isBlocked(int $productId): bool
    {
        return $this->reason($productId) !== null;
    }

    /** @param int[] $productIds @return array<int,string> the blocked subset */
    public function filter(array $productIds): array
    {
        return array_intersect_key($this->blocked(), array_flip(array_map('intval', $productIds)));
    }

    private function compute(): array
    {
        $imgs = [];
        foreach ($this->source->rows() as $r) {
            $imgs[] = [(int) $r['product_id'], $r['sha256'] ?: null,
                ($r['dhash'] ?? null) && ctype_xdigit((string) $r['dhash']) ? (string) $r['dhash'] : null];
        }
        $hits = [];   // pid => [other pid => [type, distance]]
        $n = count($imgs);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                [$a, $sa, $da] = $imgs[$i];
                [$b, $sb, $db] = $imgs[$j];
                if ($a === $b) {
                    continue;
                }
                if ($sa !== null && $sa === $sb) {
                    $m = ['identical', 0];
                } elseif ($da !== null && $db !== null && ($d = self::hamming($da, $db)) <= $this->maxDistance) {
                    $m = ['near', $d];
                } else {
                    continue;
                }
                foreach ([[$a, $b], [$b, $a]] as [$x, $y]) {
                    if (! isset($hits[$x][$y]) || $m[1] < $hits[$x][$y][1]) {
                        $hits[$x][$y] = $m;
                    }
                }
            }
        }
        $out = [];
        foreach ($hits as $pid => $others) {
            if (isset($this->resolved[$pid])) {
                continue;
            }
            ksort($others);
            $parts = array_map(fn ($o, $m) => $m[0] === 'identical' ? "identical:{$o}" : "near:{$o}(d={$m[1]})", array_keys($others), $others);
            $out[$pid] = self::REASON.' '.implode(',', $parts);
        }
        ksort($out);

        return $out;
    }

    public static function hamming(string $a, string $b): int
    {
        $len = max(strlen($a), strlen($b));
        $a = str_pad(strtolower($a), $len, '0', STR_PAD_LEFT);
        $b = str_pad(strtolower($b), $len, '0', STR_PAD_LEFT);
        $d = 0;
        for ($k = 0; $k < $len; $k++) {   // nibble-wise: avoids 64-bit signed overflow
            $x = hexdec($a[$k]) ^ hexdec($b[$k]);
            $d += ($x & 1) + (($x >> 1) & 1) + (($x >> 2) & 1) + (($x >> 3) & 1);
        }

        return $d;
    }
}
