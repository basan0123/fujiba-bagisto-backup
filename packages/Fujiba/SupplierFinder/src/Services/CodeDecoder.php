<?php

namespace Fujiba\SupplierFinder\Services;

use Fujiba\SupplierFinder\Models\CodeRule;
use Illuminate\Support\Collection;

/**
 * Turns shop product codes into a grouping key + maker guess.
 * Normalise: half-width, upper-case, strip the shop's own prefixes (F1-, sz…)
 * then try sf_code_rules by priority. Unknown codes still get a key (code
 * minus colour suffix) so cross-shop grouping works for unknown makers.
 */
class CodeDecoder
{
    private ?Collection $rules = null;

    public function rules(): Collection
    {
        return $this->rules ??= CodeRule::where('enabled', true)->orderBy('priority')->orderBy('id')->get();
    }

    public function flush(): void
    {
        $this->rules = null;
    }

    public static function normalise(string $code, array $stripPrefixes = []): string
    {
        $c = strtoupper(trim(mb_convert_kana($code, 'as')));
        $c = preg_replace('/\s+/', '', $c);
        foreach ($stripPrefixes as $p) {
            $p = strtoupper($p);
            if ($p !== '' && str_starts_with($c, $p) && strlen($c) > strlen($p) + 2) {
                $c = substr($c, strlen($p));
                break;
            }
        }

        return ltrim($c, '-_');
    }

    /** @return array{key:string,family:?string,rule:?CodeRule,code:string}|null */
    public function decode(array $codes, array $stripPrefixes = []): ?array
    {
        $fallback = null;

        foreach ($codes as $raw) {
            $code = self::normalise((string) $raw, $stripPrefixes);
            if ($code === '' || ! preg_match('/\d{2}/', $code)) {
                continue;
            }
            foreach ($this->rules() as $rule) {
                if (@preg_match($rule->pattern, $code, $m) && isset($m['base'])) {
                    return ['key' => $rule->family.':'.str_replace('YK0', 'YK-0', $m['base']), 'family' => $rule->family, 'rule' => $rule, 'code' => $code];
                }
            }
            // unknown family: drop colour suffixes like -1C, -E, -A, trailing colour letter
            $base = preg_replace('/(?:-\d{1,2}C|-[A-Z]{1,2}|(?<=\d)[A-Z])$/', '', $code);
            $fallback ??= ['key' => 'unknown:'.$base, 'family' => null, 'rule' => null, 'code' => $code];
        }

        return $fallback;
    }
}
