<?php

namespace Fujiba\Dashboard\Support;

/**
 * Canonical 47-prefecture registry for Japan. Stable code (JIS X 0401)
 * → Japanese label + English label. Used by the OrdersByRegion widget
 * to map address.state strings (which arrive in Japanese) to consistent
 * codes that match the inline SVG's `data-pref` attributes.
 *
 * Codes are 2-digit zero-padded (01..47). Prefecture order follows
 * the standard Japanese government ordering (Hokkaido → Okinawa).
 */
final class JapanPrefectures
{
    /** @var array<string, array{ja: string, en: string}> */
    public const PREFECTURES = [
        '01' => ['ja' => '北海道',     'en' => 'Hokkaido'],
        '02' => ['ja' => '青森県',     'en' => 'Aomori'],
        '03' => ['ja' => '岩手県',     'en' => 'Iwate'],
        '04' => ['ja' => '宮城県',     'en' => 'Miyagi'],
        '05' => ['ja' => '秋田県',     'en' => 'Akita'],
        '06' => ['ja' => '山形県',     'en' => 'Yamagata'],
        '07' => ['ja' => '福島県',     'en' => 'Fukushima'],
        '08' => ['ja' => '茨城県',     'en' => 'Ibaraki'],
        '09' => ['ja' => '栃木県',     'en' => 'Tochigi'],
        '10' => ['ja' => '群馬県',     'en' => 'Gunma'],
        '11' => ['ja' => '埼玉県',     'en' => 'Saitama'],
        '12' => ['ja' => '千葉県',     'en' => 'Chiba'],
        '13' => ['ja' => '東京都',     'en' => 'Tokyo'],
        '14' => ['ja' => '神奈川県',   'en' => 'Kanagawa'],
        '15' => ['ja' => '新潟県',     'en' => 'Niigata'],
        '16' => ['ja' => '富山県',     'en' => 'Toyama'],
        '17' => ['ja' => '石川県',     'en' => 'Ishikawa'],
        '18' => ['ja' => '福井県',     'en' => 'Fukui'],
        '19' => ['ja' => '山梨県',     'en' => 'Yamanashi'],
        '20' => ['ja' => '長野県',     'en' => 'Nagano'],
        '21' => ['ja' => '岐阜県',     'en' => 'Gifu'],
        '22' => ['ja' => '静岡県',     'en' => 'Shizuoka'],
        '23' => ['ja' => '愛知県',     'en' => 'Aichi'],
        '24' => ['ja' => '三重県',     'en' => 'Mie'],
        '25' => ['ja' => '滋賀県',     'en' => 'Shiga'],
        '26' => ['ja' => '京都府',     'en' => 'Kyoto'],
        '27' => ['ja' => '大阪府',     'en' => 'Osaka'],
        '28' => ['ja' => '兵庫県',     'en' => 'Hyogo'],
        '29' => ['ja' => '奈良県',     'en' => 'Nara'],
        '30' => ['ja' => '和歌山県',   'en' => 'Wakayama'],
        '31' => ['ja' => '鳥取県',     'en' => 'Tottori'],
        '32' => ['ja' => '島根県',     'en' => 'Shimane'],
        '33' => ['ja' => '岡山県',     'en' => 'Okayama'],
        '34' => ['ja' => '広島県',     'en' => 'Hiroshima'],
        '35' => ['ja' => '山口県',     'en' => 'Yamaguchi'],
        '36' => ['ja' => '徳島県',     'en' => 'Tokushima'],
        '37' => ['ja' => '香川県',     'en' => 'Kagawa'],
        '38' => ['ja' => '愛媛県',     'en' => 'Ehime'],
        '39' => ['ja' => '高知県',     'en' => 'Kochi'],
        '40' => ['ja' => '福岡県',     'en' => 'Fukuoka'],
        '41' => ['ja' => '佐賀県',     'en' => 'Saga'],
        '42' => ['ja' => '長崎県',     'en' => 'Nagasaki'],
        '43' => ['ja' => '熊本県',     'en' => 'Kumamoto'],
        '44' => ['ja' => '大分県',     'en' => 'Oita'],
        '45' => ['ja' => '宮崎県',     'en' => 'Miyazaki'],
        '46' => ['ja' => '鹿児島県',   'en' => 'Kagoshima'],
        '47' => ['ja' => '沖縄県',     'en' => 'Okinawa'],
    ];

    /**
     * Resolve a free-form `state` string (Japanese label, English label,
     * or partial) to the canonical 2-digit code, or null if no match.
     */
    public static function codeFromState(?string $state): ?string
    {
        if ($state === null) {
            return null;
        }

        $needle = trim($state);
        if ($needle === '') {
            return null;
        }

        // Exact JA match
        foreach (self::PREFECTURES as $code => $names) {
            if ($names['ja'] === $needle) {
                return $code;
            }
        }

        // Exact EN match (case-insensitive)
        $needleLower = mb_strtolower($needle);
        foreach (self::PREFECTURES as $code => $names) {
            if (mb_strtolower($names['en']) === $needleLower) {
                return $code;
            }
        }

        // JA partial — strip 都府県 suffix
        $stripped = preg_replace('/[都道府県]$/u', '', $needle);
        if ($stripped !== $needle) {
            foreach (self::PREFECTURES as $code => $names) {
                $jaStripped = preg_replace('/[都道府県]$/u', '', $names['ja']);
                if ($jaStripped === $stripped) {
                    return $code;
                }
            }
        }

        return null;
    }
}
