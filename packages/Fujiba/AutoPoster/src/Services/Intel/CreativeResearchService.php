<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * L — public creative / competitor research.
 *
 * What this stores: our own analysis of a public example — the hook, the story
 * structure, the composition, the pacing, the CTA — written in our own words,
 * plus the extracted principle.
 *
 * What it refuses to store: the competitor's images, video, captions, logos or
 * other distinctive assets. storeFinding() rejects anything that looks like a
 * verbatim caption, because "store the analysis, not the ad" has to be enforced
 * rather than merely intended.
 *
 * Fetching is out of scope here: no scraping, no login, no paywall or CAPTCHA
 * bypass, no rate-limit evasion. Sources arrive as URLs the owner pastes in, or
 * from an official API when one is available.
 */
class CreativeResearchService
{
    /** A caption-shaped blob this long is content, not analysis. */
    public const MAX_QUOTE_CHARS = 200;

    public const ANALYSIS_FIELDS = [
        'hook', 'opening_visual', 'product_reveal', 'story_structure', 'model_type',
        'scene', 'camera_style', 'lighting_style', 'colour_style', 'text_style',
        'caption_structure', 'cta', 'video_length', 'pacing', 'season_event',
        'target_audience', 'language', 'creative_category',
    ];

    /**
     * Record one public example as analysis.
     *
     * @param  array  $analysis  keys from ANALYSIS_FIELDS, values in our own words
     * @throws InvalidArgumentException when the payload carries copied content
     */
    public function storeFinding(string $url, array $analysis, array $opts = []): int
    {
        $this->rejectCopiedContent($analysis);

        $unknown = array_diff(array_keys($analysis), self::ANALYSIS_FIELDS);
        if ($unknown) {
            throw new InvalidArgumentException('unknown analysis fields: '.implode(', ', $unknown));
        }

        return DB::table('fi_research_sources')->insertGetId([
            'url'             => $url,
            'title'           => $opts['title'] ?? null,
            'publisher'       => $opts['publisher'] ?? parse_url($url, PHP_URL_HOST),
            'retrieved_at'    => Carbon::now(),
            'finding_summary' => json_encode($analysis, JSON_UNESCAPED_UNICODE),
            'topic'           => 'creative',
            'confidence'      => $opts['confidence'] ?? 0.5,
            'product_id'      => $opts['product_id'] ?? null,
            'pattern'         => $opts['pattern'] ?? null,
            'created_at'      => Carbon::now(),
            'updated_at'      => Carbon::now(),
        ]);
    }

    /**
     * Turn a recurring observation into a reusable principle — and optionally
     * a creative component tagged created_by=research.
     *
     * Stored as `observed_visual_pattern`. It only becomes
     * `verified_performance_result` once OUR OWN creatives using it produce
     * measured results; seeing it work for someone else is not evidence.
     */
    public function recordPrinciple(string $patternType, string $description, array $sourceIds, array $opts = []): int
    {
        return DB::table('fi_visual_patterns')->insertGetId([
            'pattern_type'       => $patternType,
            'description'        => $description,
            'evidence_count'     => count($sourceIds),
            'supporting_sources' => json_encode(array_values($sourceIds)),
            'confidence'         => $opts['confidence'] ?? 0.3,
            'audience'           => $opts['audience'] ?? null,
            'language'           => $opts['language'] ?? null,
            'platform'           => $opts['platform'] ?? null,
            'season'             => $opts['season'] ?? null,
            'kind'               => 'observed_visual_pattern',
            'expires_at'         => Carbon::now()->addDays((int) config('fujiba_intel.evidence.default_ttl_days', 180)),
            'created_at'         => Carbon::now(),
            'updated_at'         => Carbon::now(),
        ]);
    }

    /**
     * Promote an observed pattern once OUR creatives prove it. Requires
     * attributed orders — impressions and likes are not proof.
     */
    public function promoteWithOwnResults(int $patternId, array $creativeIds, array $orderIds, float $confidence): bool
    {
        $minOrders = (int) config('fujiba_intel.evidence.min_attributed_orders', 5);

        if (count($orderIds) < $minOrders) {
            return false;
        }

        DB::table('fi_visual_patterns')->where('id', $patternId)->update([
            'kind'                 => 'verified_performance_result',
            'supporting_creatives' => json_encode(array_values($creativeIds)),
            'supporting_orders'    => json_encode(array_values($orderIds)),
            'confidence'           => max(0.0, min(1.0, $confidence)),
            'updated_at'           => Carbon::now(),
        ]);

        return true;
    }

    /**
     * Guard against a caller pasting the advertisement itself into the record.
     */
    protected function rejectCopiedContent(array $analysis): void
    {
        foreach ($analysis as $field => $value) {
            if (! is_string($value)) {
                continue;
            }

            if (mb_strlen($value) > self::MAX_QUOTE_CHARS) {
                throw new InvalidArgumentException(
                    "field '{$field}' is ".mb_strlen($value).' chars — store your own analysis, '.
                    'not the competitor\'s copy (max '.self::MAX_QUOTE_CHARS.')'
                );
            }

            // Data URIs / embedded media are never analysis.
            if (preg_match('#^data:image/|^https?://\S+\.(jpg|jpeg|png|gif|mp4|webp)#i', trim($value))) {
                throw new InvalidArgumentException(
                    "field '{$field}' looks like embedded or linked media; store analysis only"
                );
            }
        }
    }
}
