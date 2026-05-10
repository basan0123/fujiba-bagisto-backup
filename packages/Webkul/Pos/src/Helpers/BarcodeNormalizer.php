<?php

namespace Webkul\Pos\Helpers;

/**
 * Normalises a scanned/typed barcode string before comparing it against
 * pos_product_barcode rows.
 *
 *   - Translits Burmese (၀–၉) and full-width (０–９) digits to ASCII so a
 *     barcode stored as "1234567890" matches a scan that came in as
 *     "၁၂၃၄၅၆၇၈၉၀" or "１２３４５６７８９０".
 *   - If the input contains a long ASCII digit run (≥8) or is mostly
 *     digits (≥6 digits AND ≥50% of total length), strips non-digits so
 *     a scanner's pure-numeric output matches DB rows that carry typed
 *     prefix junk (e.g. " JAN 4901234567890\n").
 *   - Otherwise just collapses whitespace, leaving alphanumeric SKUs
 *     and short codes alone.
 *
 * Both write paths (admin barcode-link, product create) and the read
 * path (lookupByBarcode query) MUST use the same routine — otherwise
 * a barcode written under one rule won't be found by a scan run
 * through the other rule.
 */
final class BarcodeNormalizer
{
    public static function normalize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $value = strtr($value, [
            "\u{1040}" => '0', "\u{1041}" => '1', "\u{1042}" => '2', "\u{1043}" => '3', "\u{1044}" => '4',
            "\u{1045}" => '5', "\u{1046}" => '6', "\u{1047}" => '7', "\u{1048}" => '8', "\u{1049}" => '9',
            "\u{FF10}" => '0', "\u{FF11}" => '1', "\u{FF12}" => '2', "\u{FF13}" => '3', "\u{FF14}" => '4',
            "\u{FF15}" => '5', "\u{FF16}" => '6', "\u{FF17}" => '7', "\u{FF18}" => '8', "\u{FF19}" => '9',
        ]);
        $digits = preg_replace('/\D+/u', '', $value) ?? '';
        $totalLen = mb_strlen($value);
        $hasLongRun = (bool) preg_match('/\d{8,}/', $value);
        $highFrac = strlen($digits) >= 6 && $totalLen > 0 && (strlen($digits) / $totalLen) >= 0.5;
        if ($hasLongRun || $highFrac) {
            return $digits;
        }
        return preg_replace('/\s+/u', '', $value) ?? $value;
    }
}
