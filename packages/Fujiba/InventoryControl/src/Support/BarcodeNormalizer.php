<?php

namespace Fujiba\InventoryControl\Support;

class BarcodeNormalizer
{
    /**
     * Normalize a barcode string typed by a human or read by a USB/Bluetooth
     * scanner so the POS resolver can match either source.
     *
     * - Burmese digits ၀-၉ (U+1040..U+1049) → ASCII 0-9
     * - Fullwidth digits ０-９ (U+FF10..U+FF19) → ASCII 0-9
     * - For numeric-looking codes (GTIN/EAN/UPC/JAN), strip every non-digit so
     *   typed-in Burmese letter prefixes (e.g. "ပ", "ဆန်ကြာ") and stray spaces
     *   don't prevent the scanner's pure-digit output from matching.
     * - SKU-style codes (anything that still has letters after digit translit)
     *   keep their letters but lose surrounding whitespace.
     */
    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = self::translitDigits($value);

        // If it has a long-enough digit run (>= 8 consecutive digits — a JAN/EAN/UPC/GTIN
        // is at least 8 digits), treat it as a scanner barcode and strip non-digits.
        // Otherwise, if the digit-fraction is high (>= 0.5), still extract digits — this
        // catches "ဖိုးထောင် 5060075356452" where the long run of leading non-digits would
        // otherwise drag the fraction below threshold.
        $digits = preg_replace('/\D+/u', '', $value) ?? '';
        $digitLen = strlen($digits);
        $totalLen = mb_strlen($value);

        $hasLongRun = (bool) preg_match('/\d{8,}/', $value);
        $highFrac   = $digitLen >= 6 && $totalLen > 0 && ($digitLen / $totalLen) >= 0.5;

        if ($hasLongRun || $highFrac) {
            return $digits;
        }

        return preg_replace('/\s+/u', '', $value) ?? $value;
    }

    private static function translitDigits(string $s): string
    {
        $map = [
            // Burmese ၀-၉
            "\u{1040}" => '0', "\u{1041}" => '1', "\u{1042}" => '2', "\u{1043}" => '3', "\u{1044}" => '4',
            "\u{1045}" => '5', "\u{1046}" => '6', "\u{1047}" => '7', "\u{1048}" => '8', "\u{1049}" => '9',
            // Fullwidth ０-９
            "\u{FF10}" => '0', "\u{FF11}" => '1', "\u{FF12}" => '2', "\u{FF13}" => '3', "\u{FF14}" => '4',
            "\u{FF15}" => '5', "\u{FF16}" => '6', "\u{FF17}" => '7', "\u{FF18}" => '8', "\u{FF19}" => '9',
        ];

        return strtr($s, $map);
    }
}
