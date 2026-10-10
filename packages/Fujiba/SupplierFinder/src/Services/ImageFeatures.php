<?php

namespace Fujiba\SupplierFinder\Services;

/**
 * Pure-GD image descriptors (no extra dependencies):
 *  - phash: 64-bit DCT perceptual hash → near-duplicate prints (EXACT)
 *  - palette: 5 dominant colours in CIE-LAB with weights → colour mood
 *  - edge: mean gradient (pattern busyness), gold: share of gold/yellow-metallic pixels (金彩)
 * Images are centre-cropped to 80% first to drop corner logos / watermarks / text bands.
 */
class ImageFeatures
{
    public static function fromFile(string $path): ?array
    {
        $img = @imagecreatefromstring((string) @file_get_contents($path));
        if (! $img) {
            return null;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $cw = (int) ($w * 0.8);
        $ch = (int) ($h * 0.8);
        $crop = imagecrop($img, ['x' => (int) (($w - $cw) / 2), 'y' => (int) (($h - $ch) / 2), 'width' => $cw, 'height' => $ch]);
        imagedestroy($img);

        $out = [
            'phash'   => self::phash($crop),
            'palette' => self::palette($crop),
        ] + self::texture($crop);
        imagedestroy($crop);

        return $out;
    }

    private static function sample(\GdImage $img, int $n): \GdImage
    {
        $s = imagecreatetruecolor($n, $n);
        imagecopyresampled($s, $img, 0, 0, 0, 0, $n, $n, imagesx($img), imagesy($img));

        return $s;
    }

    private static function phash(\GdImage $img): string
    {
        $N = 32;
        $s = self::sample($img, $N);
        $g = [];
        for ($y = 0; $y < $N; $y++) {
            for ($x = 0; $x < $N; $x++) {
                $c = imagecolorat($s, $x, $y);
                $g[$y][$x] = 0.299 * (($c >> 16) & 255) + 0.587 * (($c >> 8) & 255) + 0.114 * ($c & 255);
            }
        }
        imagedestroy($s);

        $cos = [];
        for ($u = 0; $u < 8; $u++) {
            for ($x = 0; $x < $N; $x++) {
                $cos[$u][$x] = cos((2 * $x + 1) * $u * M_PI / (2 * $N));
            }
        }
        $dct = [];
        for ($u = 0; $u < 8; $u++) {
            for ($v = 0; $v < 8; $v++) {
                $sum = 0.0;
                for ($y = 0; $y < $N; $y++) {
                    $row = $g[$y];
                    $cy = $cos[$v][$y];
                    for ($x = 0; $x < $N; $x++) {
                        $sum += $row[$x] * $cos[$u][$x] * $cy;
                    }
                }
                $dct[] = $sum;
            }
        }
        $ac = array_slice($dct, 1);
        sort($ac);
        $median = ($ac[31] + $ac[32]) / 2;
        $bits = '';
        foreach ($dct as $i => $d) {
            $bits .= ($i === 0 ? 0 : ($d > $median ? 1 : 0));
        }
        $hex = '';
        foreach (str_split($bits, 4) as $nib) {
            $hex .= dechex(bindec($nib));
        }

        return $hex;
    }

    public static function hamming(string $a, string $b): int
    {
        $d = 0;
        for ($i = 0; $i < 16; $i++) {
            $d += substr_count(decbin(hexdec($a[$i]) ^ hexdec($b[$i])), '1');
        }

        return $d;
    }

    private static function palette(\GdImage $img, int $k = 5): array
    {
        $s = self::sample($img, 48);
        $px = [];
        for ($y = 0; $y < 48; $y++) {
            for ($x = 0; $x < 48; $x++) {
                $c = imagecolorat($s, $x, $y);
                $px[] = self::lab(($c >> 16) & 255, ($c >> 8) & 255, $c & 255);
            }
        }
        imagedestroy($s);

        // deterministic init: luminance quantiles
        $sorted = $px;
        usort($sorted, fn ($a, $b) => $a[0] <=> $b[0]);
        $cent = [];
        for ($i = 0; $i < $k; $i++) {
            $cent[] = $sorted[(int) ((($i + 0.5) / $k) * count($sorted))];
        }
        $assign = [];
        for ($it = 0; $it < 8; $it++) {
            $acc = array_fill(0, $k, [0, 0, 0, 0]);
            foreach ($px as $i => $p) {
                $best = 0;
                $bd = INF;
                foreach ($cent as $j => $c) {
                    $d = ($p[0] - $c[0]) ** 2 + ($p[1] - $c[1]) ** 2 + ($p[2] - $c[2]) ** 2;
                    if ($d < $bd) {
                        $bd = $d;
                        $best = $j;
                    }
                }
                $assign[$i] = $best;
                $acc[$best][0] += $p[0];
                $acc[$best][1] += $p[1];
                $acc[$best][2] += $p[2];
                $acc[$best][3]++;
            }
            foreach ($acc as $j => $a) {
                if ($a[3]) {
                    $cent[$j] = [$a[0] / $a[3], $a[1] / $a[3], $a[2] / $a[3]];
                }
            }
        }
        $n = count($px);
        $out = [];
        foreach ($cent as $j => $c) {
            $w = $acc[$j][3] / $n;
            if ($w > 0.02) {
                $out[] = [round($c[0], 1), round($c[1], 1), round($c[2], 1), round($w, 3)];
            }
        }
        usort($out, fn ($a, $b) => $b[3] <=> $a[3]);

        return $out;
    }

    private static function texture(\GdImage $img): array
    {
        $N = 64;
        $s = self::sample($img, $N);
        $g = [];
        $gold = 0;
        $sat = 0.0;
        for ($y = 0; $y < $N; $y++) {
            for ($x = 0; $x < $N; $x++) {
                $c = imagecolorat($s, $x, $y);
                $r = ($c >> 16) & 255;
                $gg = ($c >> 8) & 255;
                $b = $c & 255;
                $g[$y][$x] = 0.299 * $r + 0.587 * $gg + 0.114 * $b;
                $max = max($r, $gg, $b);
                $min = min($r, $gg, $b);
                $sv = $max ? ($max - $min) / $max : 0;
                $sat += $sv;
                if ($max > 115 && $sv > 0.35 && $r >= $gg && $gg > $b && ($gg - $b) > 0.35 * ($r - $b)) {
                    $gold++;  // hue ≈ 35–60°: gold / ochre
                }
            }
        }
        imagedestroy($s);
        $edge = 0.0;
        for ($y = 0; $y < $N - 1; $y++) {
            for ($x = 0; $x < $N - 1; $x++) {
                $edge += abs($g[$y][$x] - $g[$y][$x + 1]) + abs($g[$y][$x] - $g[$y + 1][$x]);
            }
        }

        return [
            'edge' => round($edge / (($N - 1) ** 2 * 2 * 255), 4),
            'gold' => round($gold / ($N * $N), 4),
            'sat'  => round($sat / ($N * $N), 3),
        ];
    }

    public static function lab(int $r, int $g, int $b): array
    {
        $f = fn ($c) => ($c /= 255) > 0.04045 ? (($c + 0.055) / 1.055) ** 2.4 : $c / 12.92;
        [$r, $g, $b] = [$f($r), $f($g), $f($b)];
        $x = ($r * 0.4124 + $g * 0.3576 + $b * 0.1805) / 0.95047;
        $y = ($r * 0.2126 + $g * 0.7152 + $b * 0.0722);
        $z = ($r * 0.0193 + $g * 0.1192 + $b * 0.9505) / 1.08883;
        $t = fn ($v) => $v > 0.008856 ? $v ** (1 / 3) : (7.787 * $v) + 16 / 116;
        [$fx, $fy, $fz] = [$t($x), $t($y), $t($z)];

        return [116 * $fy - 16, 500 * ($fx - $fy), 200 * ($fy - $fz)];
    }

    /** 0..1 similarity of two weighted LAB palettes (symmetric nearest-colour distance). */
    public static function paletteSimilarity(array $a, array $b): float
    {
        if (! $a || ! $b) {
            return 0.0;
        }
        $dir = function ($p, $q) {
            $s = 0.0;
            foreach ($p as $c) {
                $min = INF;
                foreach ($q as $d) {
                    $min = min($min, sqrt(($c[0] - $d[0]) ** 2 + ($c[1] - $d[1]) ** 2 + ($c[2] - $d[2]) ** 2));
                }
                $s += $c[3] * $min;
            }

            return $s;
        };
        $dist = ($dir($a, $b) + $dir($b, $a)) / 2;

        return max(0.0, 1 - $dist / 40);
    }
}
