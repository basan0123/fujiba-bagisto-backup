<?php

namespace Fujiba\AutoPoster\Services\Intel;

/**
 * Public product codes vs supplier codes.
 *
 * Public (customer-facing alias) codes: FV-83-C, FK-22D, FU-106-A, FY-155N-H,
 * FB-HS-PPL — series "F?" + separator + number(+suffix) or two letters +
 * colour letter(s).
 *
 * Supplier codes are INTERNAL ONLY (never in public fields or public CSV
 * columns): Cosmo "Fb_42901-2C" / "FB_42110_3E" / "Fb-3510H-11B" / "AP62902L",
 * Naka "1750-75A" / "1950-201-A". Use hasSupplierCode() / mask() before any
 * value leaves the staging tables.
 *
 * Pure string logic (no DB) so it can be unit-tested.
 */
final class PublicCode
{
    /** Tried in order; the earliest match in the string wins. Applied to strtoupper() input. */
    private const PUBLIC = [
        '/(?<![A-Z0-9])(F[A-Z])[-_](\d{1,3}[A-Z]?)[-_ ]?([A-Z]{1,3})(?![A-Z0-9])/',
        '/(?<![A-Z0-9])(F[A-Z])[-_]([A-Z]{2})[-_]([A-Z]{1,3})(?![A-Z0-9])/',
    ];

    /** Applied to strtoupper() input (ASCII-only upper-casing keeps byte offsets). */
    private const SUPPLIER = [
        '/(?<![A-Z0-9])FB[_\-](?:AP)?\d{4,5}[A-Z]?/',                    // Cosmo Fb_42901 / Fb-3510H / Fb_AP62902L
        '/(?<![A-Z0-9])AP\d{5}[A-Z]?(?![0-9])/',                         // Cosmo AP42901 / AP62902L
        '/(?<![A-Z0-9\-])\d{4}-\d{1,3}(?:-?[A-Z]{1,2})?(?![A-Z0-9]|-\d)/', // Naka 1750-75A / 1950-201-A (start-bounded; not dates)
        '/(?<![A-Z0-9])\d{4}-\d{1,3}-?[A-Z]{1,2}(?![A-Z0-9])/',          // Naka after a dash, colour letter required
    ];

    public const MASK = '[supplier-code]';

    /**
     * @return array{code:string,raw:string,series:string,number:string,colour:string,pattern:string,offset:int}|null
     *   code    normalised (no separators, upper case) e.g. FV83C
     *   pattern code minus colour, e.g. FV83 (siblings share it)
     */
    public static function parse(?string $s): ?array
    {
        $u = strtoupper((string) $s);
        if ($u === '') {
            return null;
        }

        $best = null;
        foreach (self::PUBLIC as $re) {
            if (preg_match($re, $u, $m, PREG_OFFSET_CAPTURE) && ($best === null || $m[0][1] < $best[0][1])) {
                $best = $m;
            }
        }
        if ($best === null) {
            return null;
        }

        return [
            'code'    => $best[1][0].$best[2][0].$best[3][0],
            'raw'     => $best[0][0],
            'series'  => $best[1][0],
            'number'  => $best[2][0],
            'colour'  => $best[3][0],
            'pattern' => $best[1][0].$best[2][0],
            'offset'  => $best[0][1],
        ];
    }

    /** Normalised public code (FV83C) or null. */
    public static function code(?string $s): ?string
    {
        return self::parse($s)['code'] ?? null;
    }

    /** True when the whole string (trimmed) is one public code, e.g. SKU "FK-22D". */
    public static function isPublicCode(?string $s): bool
    {
        $p = self::parse($t = trim((string) $s));

        return $p !== null && $p['offset'] === 0 && strlen($p['raw']) === strlen($t);
    }

    public static function hasSupplierCode(?string $s): bool
    {
        $u = strtoupper((string) $s);
        foreach (self::SUPPLIER as $re) {
            if ($u !== '' && preg_match($re, $u)) {
                return true;
            }
        }

        return false;
    }

    /** Replace every supplier code in $s with MASK (original casing elsewhere kept). */
    public static function mask(?string $s): string
    {
        $s = (string) $s;
        $u = strtoupper($s);
        $ranges = [];
        foreach (self::SUPPLIER as $re) {
            if (preg_match_all($re, $u, $mm, PREG_OFFSET_CAPTURE)) {
                foreach ($mm[0] as [$txt, $off]) {
                    $ranges[] = [$off, $off + strlen($txt)];
                }
            }
        }
        if (! $ranges) {
            return $s;
        }

        usort($ranges, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($ranges as $r) {
            if ($merged && $r[0] <= $merged[count($merged) - 1][1]) {
                $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $r[1]);
            } else {
                $merged[] = $r;
            }
        }

        $out = '';
        $pos = 0;
        foreach ($merged as [$a, $b]) {
            $out .= substr($s, $pos, $a - $pos).self::MASK;
            $pos = $b;
        }

        return $out.substr($s, $pos);
    }

    /** url-safe slug of a public code: "FK-22D" → "fk-22d". */
    public static function slug(string $raw): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($raw)), '-');
    }
}
