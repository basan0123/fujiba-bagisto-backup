<?php

namespace Fujiba\AutoPoster\Services\Intel;

/**
 * Catalog consistency scanner — pure logic; the command feeds it product_flat
 * rows and persists the result into fi_catalog_issues (proposals only).
 *
 * Issue types (one row per product per type, affected locales comma-joined):
 *  sku_name_mismatch      SKU is itself a public code and a locale's name carries a different code
 *  colour_letter_mismatch description "Color: X (…)" where X ≠ the colour letter of the product code
 *  name_locale_mismatch   the code part of the name differs between en/ja/my
 *  duplicate_name         the same default-locale code on more than one product
 *  url_key_name_mismatch  a url_key carries a different public code than the same locale's name
 *
 * Products with a supplier code in any locale's name are NOT scanned (they are
 * covered by a separate rename proposal) — only counted.
 */
final class CatalogIssueScanner
{
    public const TYPES = ['sku_name_mismatch', 'colour_letter_mismatch', 'name_locale_mismatch', 'duplicate_name', 'url_key_name_mismatch'];

    /**
     * @param  array<string, array{proposed_fix?:string, evidence?:string, confidence?:float, severity?:string, source?:string}>  $reviewed
     *   human-reviewed notes keyed "issue_type:product_id"; they replace the generic fix and prefix the evidence.
     */
    public function __construct(
        private string $channel = 'default',
        private string $defaultLocale = 'ja',
        private array $reviewed = [],
    ) {}

    /**
     * @param  array<int, array{sku:?string, status:int|string, description:?string, available_qty?:int, locales: array<string, array{name:?string, url_key:?string}>}>  $products
     * @return array{issues: list<array<string,mixed>>, counts: array<string,int>}
     */
    public function scan(array $products): array
    {
        $counts = [
            'products_scanned' => 0, 'supplier_code_in_name_skipped' => 0, 'supplier_code_in_url_key' => 0,
            'supplier_code_in_sku' => 0, 'temporary_sku' => 0, 'url_key_copy_suffix' => 0, 'no_public_code' => 0,
        ];

        $eligible = [];
        foreach ($products as $pid => $p) {
            $counts['products_scanned']++;
            $sku = (string) ($p['sku'] ?? '');
            $names = array_map(fn ($l) => (string) ($l['name'] ?? ''), $p['locales']);
            $urls = array_map(fn ($l) => (string) ($l['url_key'] ?? ''), $p['locales']);

            if (PublicCode::hasSupplierCode($sku)) {
                $counts['supplier_code_in_sku']++;
            }
            if (array_filter($urls, fn ($u) => PublicCode::hasSupplierCode($u))) {
                $counts['supplier_code_in_url_key']++;
            }
            if (array_filter($names, fn ($n) => PublicCode::hasSupplierCode($n))) {
                $counts['supplier_code_in_name_skipped']++;

                continue;
            }
            if (str_starts_with(strtolower($sku), 'temporary-sku')) {
                $counts['temporary_sku']++;
            }
            if (array_filter($urls, fn ($u) => str_contains($u, 'のコピー'))) {
                $counts['url_key_copy_suffix']++;
            }
            if (! array_filter($names, fn ($n) => PublicCode::parse($n))) {
                $counts['no_public_code']++;
            }
            $eligible[(int) $pid] = $p;
        }

        // default-locale code index: duplicates, siblings, free colour letters
        $byCode = [];
        $byPattern = [];
        foreach ($eligible as $pid => $p) {
            if ($c = PublicCode::parse($this->defaultName($p))) {
                $byCode[$c['code']][] = $pid;
                $byPattern[$c['pattern']][$c['colour']][] = $pid;
            }
        }

        $issues = [];
        foreach ($eligible as $pid => $p) {
            foreach ([
                $this->skuName($pid, $p),
                $this->colourLetter($pid, $p, $eligible, $byPattern),
                $this->localeMismatch($pid, $p),
                $this->urlKey($pid, $p),
            ] as $issue) {
                if ($issue) {
                    $issues[] = $issue;
                }
            }
        }
        foreach ($byCode as $code => $pids) {
            if (count($pids) > 1) {
                array_push($issues, ...$this->duplicates($pids, $eligible, $byPattern));
            }
        }

        foreach ($issues as &$issue) {
            $note = $this->reviewed[$issue['issue_type'].':'.$issue['product_id']] ?? null;
            if ($note) {
                foreach (['proposed_fix', 'confidence', 'severity', 'source'] as $k) {
                    if (isset($note[$k])) {
                        $issue[$k] = $note[$k];
                    }
                }
                if (! empty($note['evidence'])) {
                    $issue['evidence'] = $note['evidence'].' || scan: '.$issue['evidence'];
                }
            }
        }
        unset($issue);

        return ['issues' => $issues, 'counts' => $counts];
    }

    private function skuName(int $pid, array $p): ?array
    {
        $sku = trim((string) ($p['sku'] ?? ''));
        if (! PublicCode::isPublicCode($sku)) {
            return null;
        }
        $sc = PublicCode::parse($sku);

        $bad = [];
        $good = [];
        foreach ($p['locales'] as $loc => $v) {
            $nc = PublicCode::parse($v['name'] ?? '');
            if ($nc && $nc['code'] === $sc['code']) {
                $good[] = $loc;
            } else {
                $bad[$loc] = $nc;
            }
        }
        if (! $bad) {
            return null;
        }

        $cur = ["sku={$sku}"];
        $prop = [];
        foreach ($bad as $loc => $nc) {
            $name = (string) ($p['locales'][$loc]['name'] ?? '');
            $cur[] = "name[{$loc}]={$name}";
            $prop[] = "name[{$loc}] '{$name}' → '".($nc ? self::replaceCode($name, $nc, $sc['raw']) : trim($sc['raw'].' '.$name))."'";
        }

        if ($good) {
            $fix = 'rename to match the SKU: '.implode('; ', $prop);
            $ev = "SKU {$sku} agrees with name[".implode(',', $good).']';
            $conf = in_array($this->defaultLocale, $good, true) ? 0.8 : 0.6;
        } else {
            $codes = implode(' / ', array_unique(array_filter(array_map(fn ($c) => $c['raw'] ?? null, $bad))));
            $fix = "owner to confirm which is correct: SKU '{$sku}' or name '{$codes}'";
            $ev = 'no locale name agrees with the SKU';
            $conf = 0.3;
        }

        return $this->issue($pid, $p, 'sku_name_mismatch', array_keys($bad), implode('; ', $cur), $fix, $ev, $conf,
            $this->enabled($p) ? 'high' : 'medium');
    }

    private function colourLetter(int $pid, array $p, array $eligible, array $byPattern): ?array
    {
        $desc = (string) ($p['description'] ?? '');
        if (! preg_match('/(?i:colou?r)\s*[:：]\s*([A-Z]{1,3})(?![A-Za-z])(\s*\([^)<]*\))?/u', $desc, $m)) {
            return null;
        }

        $sku = trim((string) ($p['sku'] ?? ''));
        $ref = PublicCode::isPublicCode($sku) ? PublicCode::parse($sku) : PublicCode::parse($this->defaultName($p));
        if (! $ref) {
            foreach ($p['locales'] as $v) {
                if ($ref = PublicCode::parse($v['name'] ?? '')) {
                    break;
                }
            }
        }
        if (! $ref || $m[1] === $ref['colour']) {
            return null;
        }

        $line = trim($m[0]);
        $ev = ["code {$ref['raw']} ends in colour '{$ref['colour']}' but the description says '{$line}'"];
        if (preg_match('/Product code\s*[:：]\s*([^<\n]+)/iu', $desc, $pc) && ($pcc = PublicCode::parse($pc[1])) && $pcc['code'] === $ref['code']) {
            $ev[] = "the description's own 'Product code: ".trim($pc[1])."' agrees with the name";
        }
        foreach ($byPattern[$ref['pattern']][$m[1]] ?? [] as $sib) {
            if ($sib !== $pid && str_contains((string) ($eligible[$sib]['description'] ?? ''), $line)) {
                $ev[] = 'identical colour line on sibling '.$this->defaultName($eligible[$sib])." (#{$sib}) → copied from it";
                break;
            }
        }

        return $this->issue($pid, $p, 'colour_letter_mismatch', [], $line,
            "change '{$line}' → 'Color: {$ref['colour']} (colour name to confirm from the photo)'",
            implode('; ', $ev), 0.6, $this->enabled($p) ? 'medium' : 'low');
    }

    private function localeMismatch(int $pid, array $p): ?array
    {
        $codes = [];
        foreach ($p['locales'] as $loc => $v) {
            $codes[$loc] = PublicCode::parse($v['name'] ?? '');
        }
        if (count(array_unique(array_map(fn ($c) => $c['code'] ?? '-', $codes))) < 2 || ! array_filter($codes)) {
            return null;
        }

        $sku = trim((string) ($p['sku'] ?? ''));
        if (PublicCode::isPublicCode($sku)) {
            [$ref, $why, $conf] = [PublicCode::parse($sku), "SKU {$sku}", 0.8];
        } elseif ($codes[$this->defaultLocale] ?? null) {
            [$ref, $why, $conf] = [$codes[$this->defaultLocale], "default-locale ({$this->defaultLocale}) name", 0.6];
        } else {
            $tally = array_count_values(array_map(fn ($c) => $c['code'], array_filter($codes)));
            arsort($tally);
            $top = array_key_first($tally);
            $ref = array_values(array_filter($codes, fn ($c) => ($c['code'] ?? null) === $top))[0];
            [$why, $conf] = ['majority of locales', 0.4];
        }

        $bad = array_filter($codes, fn ($c) => ($c['code'] ?? null) !== $ref['code']);
        if (! $bad) {
            return null;
        }
        $prop = [];
        foreach ($bad as $loc => $nc) {
            $name = (string) ($p['locales'][$loc]['name'] ?? '');
            $prop[] = "name[{$loc}] '{$name}' → '".($nc ? self::replaceCode($name, $nc, $ref['raw']) : trim($ref['raw'].' '.$name))."'";
        }
        $cur = implode('; ', array_map(fn ($loc) => "name[{$loc}]=".($p['locales'][$loc]['name'] ?? ''), array_keys($codes)));

        return $this->issue($pid, $p, 'name_locale_mismatch', array_keys($bad), $cur,
            "align to {$ref['raw']} ({$why}): ".implode('; ', $prop),
            "codes per locale: ".implode(', ', array_map(fn ($loc, $c) => "{$loc}=".($c['raw'] ?? 'none'), array_keys($codes), $codes)),
            $conf, $this->enabled($p) ? 'high' : 'medium');
    }

    private function urlKey(int $pid, array $p): ?array
    {
        $refUrl = (string) ($p['locales'][$this->defaultLocale]['url_key'] ?? '');
        $refUrlCode = PublicCode::parse($refUrl);

        $bad = [];
        $cur = [];
        $prop = [];
        $copy = false;
        foreach ($p['locales'] as $loc => $v) {
            $url = (string) ($v['url_key'] ?? '');
            $uc = PublicCode::parse($url);
            if (! $uc) {
                continue;                                   // e.g. "kimono-2467" carries no code
            }
            $nc = PublicCode::parse($v['name'] ?? '');
            if ($nc && $nc['code'] === $uc['code']) {
                continue;
            }
            $bad[] = $loc;
            $copy = $copy || str_contains($url, 'のコピー');
            $cur[] = "url_key[{$loc}]={$url} (name[{$loc}]=".($v['name'] ?? '').')';
            if (! $nc) {
                $prop[] = "url_key[{$loc}]: name has no public code — owner to decide";
            } else {
                // reuse the default-locale url_key only when it is a valid Bagisto slug for this code
                $refOk = $refUrlCode && $refUrlCode['code'] === $nc['code'] && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $refUrl);
                $target = $refOk ? $refUrl : PublicCode::slug($nc['raw']);
                $prop[] = "url_key[{$loc}] → '{$target}'";
            }
        }
        if (! $bad) {
            return null;
        }

        $ev = "url_key carries another product's code";
        if ($copy) {
            $ev .= "; Bagisto copy leftover ('…のコピー')";
        }

        return $this->issue($pid, $p, 'url_key_name_mismatch', $bad, implode('; ', $cur),
            implode('; ', $prop).' (old URL stops resolving — add a redirect if it had traffic)',
            $ev, 0.8, $this->enabled($p) ? 'medium' : 'low');
    }

    /** One row per product in a duplicate-code group. */
    private function duplicates(array $pids, array $eligible, array $byPattern): array
    {
        $enabled = count(array_filter($pids, fn ($id) => $this->enabled($eligible[$id])));
        $severity = $enabled >= 2 ? 'high' : 'low';
        $labels = [];
        foreach ($pids as $id) {
            $labels[$id] = self::lengthLabel($this->defaultName($eligible[$id]));
        }
        $lengthVariants = count(array_unique($labels)) > 1;
        $parsed = PublicCode::parse($this->defaultName($eligible[$pids[0]]));
        $free = self::nextFreeColour(array_keys($byPattern[$parsed['pattern']] ?? []));
        $freeCode = $free ? substr($parsed['raw'], 0, -strlen($parsed['colour'])).$free : null;

        $out = [];
        foreach ($pids as $id) {
            $p = $eligible[$id];
            $others = array_map(fn ($o) => "#{$o} ".($eligible[$o]['sku'] ?? '').' "'.$this->defaultName($eligible[$o]).'" ('
                .($this->enabled($eligible[$o]) ? 'enabled' : 'disabled').', avail '.($eligible[$o]['available_qty'] ?? '?').', '.$labels[$o].')',
                array_values(array_diff($pids, [$id])));
            $ev = "same code {$parsed['raw']} also on ".implode(', ', $others)."; {$enabled} of ".count($pids).' enabled';

            if ($lengthVariants) {
                $fix = "probably intentional (length variants: {$labels[$id]} vs ".implode('/', array_unique(array_diff($labels, [$labels[$id]])))
                    .') — optional: give the cut-length listing its own suffix so search/labels can tell them apart';
                $conf = 0.5;
            } else {
                $fix = 'owner to confirm: same fabric → keep one listing and move its stock there; different colourway → rename one'
                    .($freeCode ? " (next free code in the series: {$freeCode})" : '');
                $conf = 0.5;
            }
            $out[] = $this->issue($id, $p, 'duplicate_name', [], "{$parsed['raw']} on ".count($pids).' products', $fix, $ev, $conf, $severity);
        }

        return $out;
    }

    private function issue(int $pid, array $p, string $type, array $locales, string $current, string $fix, string $evidence, float $conf, string $severity): array
    {
        return [
            'product_id'    => $pid,
            'sku'           => PublicCode::mask($p['sku'] ?? ''),
            'name'          => PublicCode::mask($this->defaultName($p)),
            'channel'       => $this->channel,
            'locale'        => $locales ? implode(',', $locales) : null,
            'issue_type'    => $type,
            'current_value' => PublicCode::mask($current),
            'proposed_fix'  => PublicCode::mask($fix),
            'evidence'      => PublicCode::mask($evidence),
            'confidence'    => $conf,
            'severity'      => $severity,
            'source'        => 'ws_j_catalog_scan',
            'fingerprint'   => "{$type}:{$this->channel}:{$pid}",
        ];
    }

    private function defaultName(array $p): string
    {
        $n = (string) ($p['locales'][$this->defaultLocale]['name'] ?? '');
        if ($n !== '') {
            return $n;
        }
        foreach ($p['locales'] as $v) {
            if (($v['name'] ?? '') !== '') {
                return (string) $v['name'];
            }
        }

        return '';
    }

    private function enabled(array $p): bool
    {
        return (int) ($p['status'] ?? 0) === 1;
    }

    /** Swap the public code inside $name for $newRaw, keeping any descriptive suffix. */
    public static function replaceCode(string $name, array $parsed, string $newRaw): string
    {
        return substr_replace($name, $newRaw, $parsed['offset'], strlen($parsed['raw']));
    }

    /** "FV-40-A(3m) ဝမ်းဆက်စာ" → "3m"; no length in the name → "regular". */
    public static function lengthLabel(string $name): string
    {
        return preg_match('/(?<![A-Za-z0-9])(\d+(?:\.\d+)?)\s*m(?![A-Za-z])/i', preg_replace('/^\S*?-\d+[A-Z]?-?[A-Z]{1,3}/i', '', $name), $m)
            ? $m[1].'m' : 'regular';
    }

    /** Letter after the highest single-letter colour used in the series (A..Y), else null. */
    public static function nextFreeColour(array $used): ?string
    {
        $single = array_filter($used, fn ($c) => strlen($c) === 1);
        if (! $single) {
            return null;
        }
        $next = chr(ord(max($single)) + 1);

        return $next <= 'Z' && ! in_array($next, $used, true) ? $next : null;
    }
}
