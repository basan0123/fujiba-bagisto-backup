<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * O/P/R — visual product evidence.
 *
 * Compares a Fujiba product photo against a candidate image and records the
 * verdict with every signal that produced it. The matcher
 * (bin/intel/fabric_match.py) requires agreement across independent signal
 * families, so colour similarity alone can never produce a same-product claim.
 *
 * Scope note: this service COMPARES images we already hold. Finding candidate
 * images on the open web needs a visual-search provider, and none is
 * configured — see WebVisualSearchProvider, which is deliberately a null
 * implementation rather than a pretend one.
 */
class VisualEvidenceService
{
    /** Verdicts that assert "the same physical fabric". */
    public const SAME_PRODUCT = ['exact_match', 'probable_match'];

    /**
     * Compare two local image files and persist the evidence row.
     *
     * @return array|null the matcher output, or null if it could not run
     */
    public function compareAndRecord(int $productId, string $referencePath, string $candidatePath, array $opts = []): ?array
    {
        $result = $this->compare($referencePath, $candidatePath);

        if (! $result) {
            return null;
        }

        DB::table('fi_product_visual_evidence')->insert([
            'product_id'                => $productId,
            'source_url'                => $opts['source_url'] ?? null,
            'source_domain'             => isset($opts['source_url'])
                ? parse_url($opts['source_url'], PHP_URL_HOST) : null,
            'source_type'               => $opts['source_type'] ?? 'local',
            'image_hash'                => $opts['image_hash'] ?? null,
            'match_type'                => $result['match_type'],
            'match_confidence'          => $result['match_confidence'],
            'fabric_similarity'         => $result['geometry_similarity'],
            'pattern_similarity'        => $result['geometry_similarity'],
            'colour_similarity'         => $result['colour_similarity'],
            'motif_similarity'          => $result['texture_similarity'],
            'geometry_similarity'       => $result['geometry_similarity'],
            'matched_region_percentage' => $result['matched_region_percentage'],
            'matched_features'          => json_encode($result['matched_features']),
            'contradicting_features'    => json_encode($result['contradicting_features']),
            'evidence_summary'          => $this->summarise($result),
            'research_id'               => $opts['research_id'] ?? null,
            'retrieved_at'              => Carbon::now(),
            // A machine verdict is never "verified"; a human confirms.
            'verification_status'       => 'unverified',
            'created_at'                => Carbon::now(),
            'updated_at'                => Carbon::now(),
        ]);

        return $result;
    }

    public function compare(string $referencePath, string $candidatePath): ?array
    {
        if (! is_file($referencePath) || ! is_file($candidatePath)) {
            return null;
        }

        $script = dirname(__DIR__, 3).'/bin/intel/fabric_match.py';

        $cmd = sprintf('python3 %s --reference %s --candidate %s 2>&1',
            escapeshellarg($script), escapeshellarg($referencePath), escapeshellarg($candidatePath));

        exec($cmd, $lines, $rc);

        if ($rc !== 0) {
            Log::warning('[intel] fabric_match failed', ['out' => implode("\n", $lines)]);

            return null;
        }

        $decoded = json_decode(implode("\n", $lines), true);

        return is_array($decoded) && ! isset($decoded['error']) ? $decoded : null;
    }

    /**
     * Plain-language evidence line. States what agreed AND what did not, so a
     * reader can judge the verdict rather than trust it.
     */
    protected function summarise(array $r): string
    {
        $claim = in_array($r['match_type'], self::SAME_PRODUCT, true)
            ? 'Same fabric claimed'
            : 'NOT claimed as the same fabric';

        return sprintf(
            '%s (%s, confidence %.2f). Agreeing: %s. Disagreeing: %s. '.
            'Geometry %.2f, colour %.2f, texture %.2f, frequency %.2f. '.
            'Matched region ~%.0f%% of the candidate. %s',
            $claim,
            $r['match_type'],
            $r['match_confidence'],
            implode('/', $r['matched_features']) ?: 'none',
            implode('/', $r['contradicting_features']) ?: 'none',
            $r['geometry_similarity'], $r['colour_similarity'],
            $r['texture_similarity'], $r['frequency_similarity'],
            $r['matched_region_percentage'],
            $r['note'] ?? ''
        );
    }

    /** Evidence a human has confirmed — the only kind safe to state as fact. */
    public function confirmedEvidence(int $productId)
    {
        return DB::table('fi_product_visual_evidence')
            ->where('product_id', $productId)
            ->where('verification_status', 'confirmed')
            ->get();
    }
}
