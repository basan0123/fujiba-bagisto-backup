<?php

namespace Fujiba\AutoPoster\Services\Intel;

use InvalidArgumentException;

/**
 * Parsing/validation for numbers the owner (or OCR) types into intel sheets.
 * Pure functions — no DB — so the import commands stay thin and testable.
 */
final class OwnerSheetValues
{
    public const WIDTH_MIN_CM = 80.0;
    public const WIDTH_MAX_CM = 150.0;
    public const LENGTH_MIN_M = 0.5;
    public const LENGTH_MAX_M = 5.0;

    private const EMPTY = ['', '-', '–', '—', 'n/a', 'na', 'none', '?'];

    /**
     * Width in cm. Returns [value|null, error|null]; [null, null] = left empty.
     *
     * @return array{0: ?float, 1: ?string}
     */
    public static function width(?string $raw): array
    {
        try {
            $v = self::measure($raw, 'cm');
        } catch (InvalidArgumentException $e) {
            return [null, 'width_cm: '.$e->getMessage()];
        }
        if ($v === null) {
            return [null, null];
        }
        if ($v < self::WIDTH_MIN_CM || $v > self::WIDTH_MAX_CM) {
            $hint = $v < 10 ? ' (looks like metres — enter cm)' : '';

            return [null, sprintf('width_cm %s out of range %d–%d cm%s', self::fmt($v), self::WIDTH_MIN_CM, self::WIDTH_MAX_CM, $hint)];
        }

        return [$v, null];
    }

    /**
     * Length in metres. Returns [value|null, error|null]; [null, null] = left empty.
     *
     * @return array{0: ?float, 1: ?string}
     */
    public static function length(?string $raw): array
    {
        try {
            $v = self::measure($raw, 'm');
        } catch (InvalidArgumentException $e) {
            return [null, 'length_m: '.$e->getMessage()];
        }
        if ($v === null) {
            return [null, null];
        }
        if ($v < self::LENGTH_MIN_M || $v > self::LENGTH_MAX_M) {
            $hint = $v >= 50 ? ' (looks like cm — enter metres)' : '';

            return [null, sprintf('length_m %s out of range %s–%s m%s', self::fmt($v), self::fmt(self::LENGTH_MIN_M), self::fmt(self::LENGTH_MAX_M), $hint)];
        }

        return [$v, null];
    }

    /**
     * Owner-typed measurement → float (null when empty). Accepts full-width
     * digits, "112cm", "1.5 m", "1,5". Rejects the other unit and any junk.
     */
    public static function measure(?string $raw, string $unit): ?float
    {
        $s = self::clean($raw);
        if ($s === null) {
            return null;
        }

        $hasCm = (bool) preg_match('/(cm|㎝|センチ)$/u', $s);
        $hasM = ! $hasCm && (bool) preg_match('/(m|meters?|metres?|メートル)$/u', $s);
        if ($unit === 'cm' && $hasM) {
            throw new InvalidArgumentException("'{$raw}' is in metres — width must be cm");
        }
        if ($unit === 'm' && $hasCm) {
            throw new InvalidArgumentException("'{$raw}' is in cm — length must be metres");
        }
        $s = preg_replace('/(cm|㎝|センチ|meters?|metres?|メートル|m)$/u', '', $s);

        if (preg_match('/^\d+,\d+$/', $s)) {
            $s = str_replace(',', '.', $s);            // decimal comma
        }
        if (! preg_match('/^\d+(\.\d+)?$/', $s)) {
            throw new InvalidArgumentException("'{$raw}' is not a number");
        }

        return (float) $s;
    }

    /**
     * Social-media count → int (null when empty).
     * "12.3K" → 12300, "1.2M" → 1200000, "1,234" → 1234, "1.2万" → 12000, "3億" → 300000000.
     */
    public static function count(?string $raw): ?int
    {
        $s = self::clean($raw);
        if ($s === null) {
            return null;
        }
        $s = preg_replace('/(views?|likes?|comments?|shares?|saves?|plays?|回再生|再生|件|回)$/u', '', $s);

        if (! preg_match('/^(\d{1,3}(?:,\d{3})+|\d+)(?:\.(\d+))?(k|m|b|万|億)?$/u', $s, $m)) {
            throw new InvalidArgumentException("'{$raw}' is not a count");
        }

        $int = str_replace(',', '', $m[1]);
        $frac = $m[2] ?? '';
        $mult = ['' => 1, 'k' => 1_000, 'm' => 1_000_000, 'b' => 1_000_000_000, '万' => 10_000, '億' => 100_000_000][$m[3] ?? ''];

        if ($frac !== '' && $mult === 1) {
            throw new InvalidArgumentException("'{$raw}' is not a whole number");
        }

        // integer arithmetic on the digits avoids float drift (12.3K must be exactly 12300)
        $scaled = (int) $int * $mult;
        if ($frac !== '') {
            $scaled += intdiv((int) $frac * $mult, 10 ** strlen($frac));
        }

        return $scaled;
    }

    /** Trim, full-width → half-width, lower-case, drop inner spaces; null when empty-ish. */
    private static function clean(?string $raw): ?string
    {
        $s = trim((string) $raw);
        if (function_exists('mb_convert_kana')) {
            $s = mb_convert_kana($s, 'as', 'UTF-8');
        }
        $s = strtolower(preg_replace('/\s+/u', '', $s));

        return in_array($s, self::EMPTY, true) ? null : $s;
    }

    public static function fmt(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }
}
