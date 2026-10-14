<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * M — product photography intelligence.
 *
 * Measurements come from bin/intel/photo_audit.py (local cv2/numpy, no AI, no
 * network) so they are facts. Results are cached on image_hash + metadata_hash:
 * an unchanged photo is never re-analysed, which is what keeps AI cost at zero
 * for the parts that would otherwise need a vision model.
 */
class PhotoAuditService
{
    public const ANALYSIS_VERSION = 'm2';   // m2: local watermark_present detector

    /**
     * @return array{analysed:int,cache_hits:int,reshoot:int}
     */
    public function auditProducts(array $productIds, string $channel = 'default'): array
    {
        $analysed = $cacheHits = $reshoot = 0;

        foreach ($productIds as $productId) {
            $path = $this->photoPath((int) $productId);
            if (! $path) {
                continue;
            }

            $metadataHash = $this->metadataHash((int) $productId);
            $existing = DB::table('fi_product_profiles')
                ->where('product_id', $productId)->where('channel', $channel)->first();

            // Cache: same picture, same metadata, same analyser → reuse.
            if ($existing
                && $existing->image_sha256 === hash_file('sha256', $path)
                && $existing->metadata_hash === $metadataHash
                && $existing->analysis_version === self::ANALYSIS_VERSION) {
                $cacheHits++;
                $this->logUsage('photo_audit', (int) $productId, true);
                continue;
            }

            $result = $this->runAudit($path);
            if (! $result) {
                continue;
            }

            $row = [
                'product_id'              => $productId,
                'channel'                 => $channel,
                'image_hash'              => $result['image_hash'],
                'image_sha256'            => $result['image_sha256'],
                'metadata_hash'           => $metadataHash,
                'source_width'            => $result['source_width'],
                'source_height'           => $result['source_height'],
                'sharpness'               => $result['sharpness'],
                'colour_quality'          => $result['colour_quality'],
                'fabric_visibility'       => $result['fabric_visibility'],
                'background_quality'      => $result['background_quality'],
                'perspective_quality'     => $result['perspective_quality'],
                'watermark_detected'      => $result['watermark_present'] ?? $result['watermark_detected'],
                'packaging_obstruction'   => $result['packaging_obstruction'],
                'photo_quality_score'     => $result['photo_quality_score'],
                'reshoot_required'        => $result['reshoot_required'],
                'reshoot_reason'          => $result['reshoot_reason'],
                'recommended_photo_setup' => $result['recommended_photo_setup'],
                'analysis_version'        => self::ANALYSIS_VERSION,
                'analysed_at'             => Carbon::now(),
                'updated_at'              => Carbon::now(),
            ];

            if ($existing) {
                DB::table('fi_product_profiles')->where('id', $existing->id)->update($row);
            } else {
                DB::table('fi_product_profiles')->insert($row + [
                    'sku'        => DB::table('products')->where('id', $productId)->value('sku'),
                    'created_at' => Carbon::now(),
                ]);
            }

            $analysed++;
            $reshoot += $result['reshoot_required'] ? 1 : 0;
            $this->logUsage('photo_audit', (int) $productId, false);
        }

        return ['analysed' => $analysed, 'cache_hits' => $cacheHits, 'reshoot' => $reshoot];
    }

    /**
     * Owner-facing reshoot list, ordered by what costs the most money to leave
     * broken: in stock first, then sales velocity, then how bad the photo is.
     */
    public function reshootList(int $limit = 200): array
    {
        $rows = DB::table('fi_product_profiles as p')
            ->where('p.reshoot_required', true)
            ->leftJoin(DB::raw('(SELECT product_id, SUM(qty) q FROM product_inventories GROUP BY product_id) s'),
                's.product_id', '=', 'p.product_id')
            ->leftJoin(DB::raw('(SELECT oi.product_id, SUM(oi.qty_ordered) sold
                                 FROM order_items oi
                                 JOIN orders o ON o.id = oi.order_id
                                 WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
                                 GROUP BY oi.product_id) v'),
                'v.product_id', '=', 'p.product_id')
            ->selectRaw('p.*, COALESCE(s.q,0) stock, COALESCE(v.sold,0) sold')
            ->orderByRaw('(COALESCE(s.q,0) > 0) DESC, COALESCE(v.sold,0) DESC, p.photo_quality_score ASC')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'product_id' => $r->product_id,
            'sku'        => $r->sku,
            'stock'      => (int) $r->stock,
            'sold_12mo'  => (int) $r->sold,
            'score'      => (float) $r->photo_quality_score,
            'reason'     => $r->reshoot_reason,
            'setup'      => $r->recommended_photo_setup,
            'priority'   => ((int) $r->stock > 0 ? 'HIGH' : 'low'),
        ])->all();
    }

    protected function runAudit(string $path): ?array
    {
        $script = dirname(__DIR__, 3).'/bin/intel/photo_audit.py';
        $cfg = json_encode(config('fujiba_intel.photo_audit'));

        $cmd = sprintf('python3 %s %s --config-json %s 2>&1',
            escapeshellarg($script), escapeshellarg($path), escapeshellarg($cfg));

        exec($cmd, $lines, $rc);
        if ($rc !== 0) {
            Log::warning('[intel] photo_audit failed', ['path' => $path, 'out' => implode("\n", $lines)]);

            return null;
        }

        $decoded = json_decode(implode("\n", $lines), true);

        return is_array($decoded) && isset($decoded[0]) && ! isset($decoded[0]['error'])
            ? $decoded[0]
            : null;
    }

    /**
     * Resolve the product's primary source image on disk.
     *
     * Tenant media is stored per tenant/channel, mirroring
     * App\Http\Middleware\SetTenantStorageDisk — the bare storage/app/public
     * path only resolves for the central install, so it is tried last.
     */
    protected function photoPath(int $productId): ?string
    {
        $rel = DB::table('product_images')->where('product_id', $productId)
            ->orderBy('id')->value('path');

        if (! $rel) {
            return null;
        }

        $candidates = [];

        if (function_exists('tenant') && ($tenant = tenant())) {
            $tenantId = $tenant->getTenantKey();
            // Channel 1 is the fallback root the middleware itself falls back to.
            $candidates[] = storage_path("app/public/tenants/1/{$tenantId}/{$rel}");
        }

        $candidates[] = storage_path('app/public/'.$rel);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function metadataHash(int $productId): string
    {
        $row = DB::table('product_flat')->where('product_id', $productId)
            ->where('locale', 'ja')->first(['name', 'price', 'description']);

        return hash('sha256', json_encode([
            $row->name ?? null, $row->price ?? null, $row->description ?? null,
        ]));
    }

    protected function logUsage(string $operation, int $productId, bool $cacheHit): void
    {
        DB::table('fi_ai_usage')->insert([
            'operation'  => $operation,
            'provider'   => 'local',
            'model'      => 'cv2-'.self::ANALYSIS_VERSION,
            'cost_jpy'   => 0,       // local measurement is free, by design
            'cache_hit'  => $cacheHit,
            'product_id' => $productId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
