<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * WS-L fabric digital twin readiness (STAGING ONLY — never writes Bagisto product tables).
 *
 * Builds the per-product photo inventory (our product images, the owner's Drive photos, Mac
 * photos, new shoots in /root/incoming/twin-shoots/<SKU>/), classifies provenance, runs
 * bin/intel/twin_audit.py (deterministic audit + tile builder, one heavy job at a time) and
 * stores the result in fi_product_twin_readiness + fi_twin_attempts.
 *
 * Provenance: supplier photos (SupplierImageSync applied rows, or a dHash within 4 bits of a
 * cached supplier original) are INTERNAL reference only and are never a tile source.
 * 800x800 .jpg images are legacy ChatPion-era uploads (origin unverified).
 */
class TwinReadinessService
{
    public const VERSION = 'twin-1.0';

    public const TENANT = '1bd1181f-fcd5-4238-a12d-a207d6ea4006';

    public const INCOMING = '/root/incoming/twin-shoots';

    public const MAC_DIR = '/root/incoming/mac-kimono';

    /** Categories that are not yardage (Japanese Fan, Hair stick, Other). */
    public const NOT_YARDAGE_CATEGORIES = [17, 24, 25];

    public const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];

    public const SHOOT_HEADER = [
        'rank', 'product_id', 'sku', 'public_name', 'enabled', 'available_qty', 'sold_qty_12m', 'tier', 'tier_audit',
        'reason', 'best_photo_px', 'flat_vs_angled', 'full_repeat_visible', 'watermark_present', 'upload_folder',
        'what_to_shoot', 'product_note',
    ];

    public const CHECKLIST = '1) Iron it. Lay it FLAT on the floor, full width (selvedge to selvedge), at least 1.2 m long so '
        .'2 full repeats show each way. 2) Phone straight overhead, parallel to the fabric (no angle), whole piece in frame, '
        .'original camera file (no filter/edit). 3) Even daylight or shade; no flash, no direct sun, no shadows. '
        .'4) Colour card (or plain white A4 paper) AND a ruler/tape measure in the frame, on the fabric edge. '
        .'5) One close-up of the weave (~10 cm away). 6) One photo of a single full repeat, edge to edge. '
        .'7) No watermark, price text or stickers on the fabric.';

    private ?array $supplierHashes = null;

    public function __construct(
        private ?string $driveReport = null,
        private ?string $python = null,
    ) {
        $this->python = $python ?: 'python3';
    }

    public function baseDir(): string
    {
        return storage_path('app/intel/twin');
    }

    public function tenantPublicDir(): string
    {
        return storage_path('app/public/tenants/1/'.self::TENANT);
    }

    public function script(): string
    {
        return dirname(__DIR__, 3).'/bin/intel/twin_audit.py';
    }

    /** Audited universe: every product of the fi_catalog_items inventory (kimono tenant). */
    public function catalogProductIds(): array
    {
        return DB::table('fi_catalog_items')->orderBy('product_id')->pluck('product_id')->map(fn ($v) => (int) $v)->all();
    }

    // ------------------------------------------------------------------ context

    /**
     * @return array<int,array> product_id => context (names masked, stock, sales, images...)
     */
    public function context(array $ids, array $shootFiles = []): array
    {
        if (! $ids) {
            return [];
        }
        $flat = $this->flat($ids);
        $stock = DB::table('product_inventories')->whereIn('product_id', $ids)
            ->groupBy('product_id')->selectRaw('product_id, SUM(qty) q')->pluck('q', 'product_id');
        $reserved = DB::table('product_ordered_inventories')->whereIn('product_id', $ids)
            ->groupBy('product_id')->selectRaw('product_id, SUM(qty) q')->pluck('q', 'product_id');
        $sales = $this->sales12m($ids);
        $cats = DB::table('product_categories')->whereIn('product_id', $ids)->get(['product_id', 'category_id'])
            ->groupBy('product_id')->map(fn ($g) => $g->pluck('category_id')->map(fn ($v) => (int) $v)->all());
        $dims = $this->dims($ids, $flat);
        $images = $this->productImages($ids);
        $drive = $this->drivePhotos($flat);
        $mac = $this->macPhotos($flat);

        $out = [];
        foreach ($ids as $pid) {
            $f = $flat[$pid] ?? null;
            $code = PublicCode::parse($f->name ?? '');
            $c = $cats[$pid] ?? [];
            $notYardage = $c && ! array_diff($c, self::NOT_YARDAGE_CATEGORIES);
            $imgs = array_merge($images[$pid] ?? [], $drive[$pid] ?? [], $mac[$pid] ?? [], $shootFiles[$pid] ?? []);
            $out[$pid] = [
                'product_id' => $pid,
                'sku' => $f->sku ?? null,
                'public_name' => $code['raw'] ?? Str::limit(PublicCode::mask($f->name ?? ''), 180, ''),
                'enabled' => (int) ($f->status ?? 0) === 1,
                'applicable' => ! $notYardage,
                'available_qty' => (int) ($stock[$pid] ?? 0) - (int) ($reserved[$pid] ?? 0),
                'sold_qty_12m' => (int) ($sales[$pid] ?? 0),
                'dims' => $dims[$pid] ?? ['width_cm' => null, 'length_cm' => null, 'source' => null],
                'images' => $imgs,
            ];
        }

        return $out;
    }

    /** One product_flat row per product: default channel, its default locale. */
    private function flat(array $ids): array
    {
        $defaultLocale = DB::table('channels as c')->join('locales as l', 'l.id', '=', 'c.default_locale_id')
            ->selectRaw('c.code AS channel_code, l.code AS locale_code')->pluck('locale_code', 'channel_code')->all();
        $score = fn ($r) => ($r->channel === 'default' ? 2 : 0) + (($defaultLocale[$r->channel] ?? 'ja') === $r->locale ? 1 : 0);
        $out = [];
        foreach (DB::table('product_flat')->whereIn('product_id', $ids)->orderBy('id')
            ->get(['product_id', 'sku', 'name', 'status', 'channel', 'locale']) as $r) {
            if (! isset($out[$r->product_id]) || $score($r) > $score($out[$r->product_id])) {
                $out[(int) $r->product_id] = $r;
            }
        }

        return $out;
    }

    /** Net units sold in the last 12 months (orders not canceled; line cancels/refunds removed). */
    public function sales12m(array $ids): array
    {
        $net = 'GREATEST(i.qty_ordered - COALESCE(i.qty_canceled,0) - COALESCE(i.qty_refunded,0), 0)';

        return DB::table('order_items as i')->join('orders as o', 'o.id', '=', 'i.order_id')
            ->whereIn('i.product_id', $ids)->where('o.status', '<>', 'canceled')
            ->where('o.created_at', '>=', now()->subMonths(12))
            ->groupBy('i.product_id')->selectRaw("i.product_id, SUM($net) q")->pluck('q', 'product_id')
            ->map(fn ($v) => (int) $v)->all();
    }

    /** width/length only from catalog text (name "(3m)") or owner fill-in staging rows. */
    private function dims(array $ids, array $flat): array
    {
        $out = [];
        foreach ($ids as $pid) {
            $name = (string) ($flat[$pid]->name ?? '');
            if (preg_match('/(\d+(?:\.\d+)?)\s*[mM](?![a-zA-Z])/u', $name, $m) && (float) $m[1] >= 0.5 && (float) $m[1] <= 5) {
                $out[$pid] = ['width_cm' => null, 'length_cm' => round((float) $m[1] * 100, 1), 'source' => 'catalog_text_name'];
            }
        }
        $owner = DB::table('fi_product_staging_attributes')->whereIn('product_id', $ids)->where('source', 'owner_fill_in')
            ->whereIn('attribute', ['width_cm', 'length_m'])->get(['product_id', 'attribute', 'value']);
        foreach ($owner as $r) {
            $pid = (int) $r->product_id;
            $out[$pid] = $out[$pid] ?? ['width_cm' => null, 'length_cm' => null, 'source' => null];
            if ($r->attribute === 'width_cm' && is_numeric($r->value)) {
                $out[$pid]['width_cm'] = round((float) $r->value, 1);
            }
            if ($r->attribute === 'length_m' && is_numeric($r->value)) {
                $out[$pid]['length_cm'] = round((float) $r->value * 100, 1);
            }
            $out[$pid]['source'] = 'owner_fill_in';
        }

        return $out;
    }

    private function supplierHashes(): array
    {
        if ($this->supplierHashes === null) {
            $f = $this->baseDir().'/supplier_dhash.json';
            $this->supplierHashes = is_file($f) ? array_values(array_filter(json_decode((string) file_get_contents($f), true) ?: [])) : [];
        }

        return $this->supplierHashes;
    }

    private function isSupplierHash(?string $dhash): bool
    {
        if (! $dhash || strlen($dhash) !== 16) {
            return false;
        }
        foreach ($this->supplierHashes() as $h) {
            if (strlen($h) === 16 && self::hammingSplit($dhash, $h) <= 4) {
                return true;
            }
        }

        return false;
    }

    /** Hamming distance on two 32-bit halves (hexdec loses precision above 2^63). */
    public static function hammingSplit(string $a, string $b): int
    {
        $n = 0;
        foreach ([0, 8] as $o) {
            $n += substr_count(decbin(hexdec(substr($a, $o, 8)) ^ hexdec(substr($b, $o, 8))), '1');
        }

        return $n;
    }

    private function productImages(array $ids): array
    {
        $applied = DB::table('supplier_image_sync_logs')->where('status', 'applied')->whereIn('product_id', $ids)
            ->pluck('new_image')->flip()->all();
        $root = $this->tenantPublicDir();
        $out = [];
        foreach (DB::table('fi_catalog_images')->whereIn('product_id', $ids)->orderBy('product_id')->orderBy('position')
            ->get(['image_id', 'product_id', 'path', 'width', 'height', 'sha256', 'dhash']) as $r) {
            $abs = $root.'/'.$r->path;
            if (! is_file($abs)) {
                continue;
            }
            $ext = strtolower(pathinfo($r->path, PATHINFO_EXTENSION));
            if (isset($applied[$r->path]) || $this->isSupplierHash($r->dhash)) {
                $kind = 'supplier_ref';
            } elseif ((int) $r->width === 800 && (int) $r->height === 800 && $ext === 'jpg') {
                $kind = 'legacy';
            } else {
                $kind = 'site_own';
            }
            $out[(int) $r->product_id][] = ['key' => 'img'.$r->image_id, 'path' => $abs, 'kind' => $kind, 'sha256' => $r->sha256];
        }

        return $out;
    }

    /** code => [product ids] for public codes (FV83C style). */
    private function codeIndex(array $flat): array
    {
        $idx = [];
        foreach ($flat as $pid => $f) {
            if ($c = PublicCode::code($f->name ?? '')) {
                $idx[$c][] = $pid;
            }
        }

        return $idx;
    }

    /** Owner Drive photos already downloaded (report CSV from the Drive run); matched + not identical to site. */
    private function drivePhotos(array $flat): array
    {
        $out = [];
        if (! $this->driveReport || ! is_file($this->driveReport)) {
            return $out;
        }
        $idx = $this->codeIndex($flat);
        $sheet = CsvSheet::read($this->driveReport);
        foreach ($sheet['rows'] as $r) {
            if (($r['status'] ?? '') !== 'matched' || ($r['same_as_site'] ?? '') === 'True' || ! is_file($r['path'] ?? '')) {
                continue;
            }
            $pids = $idx[PublicCode::code($r['key'] ?? '') ?? ''] ?? [];
            if (count($pids) !== 1) {
                continue;            // ambiguous code → identity not certain, skip
            }
            $cut = stripos($r['path'], 'photoroom') !== false;
            $out[$pids[0]][] = ['key' => 'drv_'.substr((string) ($r['sha256'] ?? md5($r['path'])), 0, 12), 'path' => $r['path'],
                'kind' => $cut ? 'drive_cutout' : 'drive', 'sha256' => $r['sha256'] ?? null];
        }

        return $out;
    }

    private function macPhotos(array $flat): array
    {
        $out = [];
        if (! is_dir(self::MAC_DIR)) {
            return $out;
        }
        $idx = $this->codeIndex($flat);
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::MAC_DIR, \FilesystemIterator::SKIP_DOTS)) as $f) {
            if (! in_array(strtolower($f->getExtension()), self::IMAGE_EXT, true)) {
                continue;
            }
            $pids = $idx[PublicCode::code($f->getBasename()) ?? ''] ?? [];
            if (count($pids) === 1) {
                $out[$pids[0]][] = ['key' => 'mac_'.substr(sha1($f->getPathname()), 0, 12), 'path' => $f->getPathname(), 'kind' => 'mac', 'sha256' => null];
            }
        }

        return $out;
    }

    /** Resolve a twin-shoots folder name (SKU like KIMONO-2467 or public code like FV-80-H) to one product. */
    public function resolveFolder(string $name): ?int
    {
        $sku = DB::table('products')->whereRaw('UPPER(sku) = ?', [strtoupper(trim($name))])->value('id');
        if ($sku) {
            return (int) $sku;
        }
        $code = PublicCode::code($name);
        if (! $code) {
            return null;
        }
        $ids = $this->catalogProductIds();
        $hits = array_keys(array_filter($this->flat($ids), fn ($f) => PublicCode::code($f->name ?? '') === $code));

        return count($hits) === 1 ? (int) $hits[0] : null;
    }

    // ------------------------------------------------------------------- python

    /**
     * Run the deterministic audit on a list of contexts (heavy_guard inside the script serialises
     * every heavy job on the host). Returns decoded results keyed by product id.
     */
    public function run(array $contexts, string $runId, int $timeout = 3600): array
    {
        $dir = $this->baseDir().'/jobs';
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("cannot create {$dir}");
        }
        $in = "{$dir}/{$runId}.in.json";
        $out = "{$dir}/{$runId}.out.json";
        $job = [
            'run_id' => $runId,
            'tiles_dir' => $this->baseDir().'/tiles',
            'wm_templates' => $this->baseDir().'/wm_templates.npz',
            'products' => array_values(array_map(fn ($c) => [
                'product_id' => $c['product_id'], 'sku' => $c['sku'], 'public_name' => $c['public_name'],
                'applicable' => $c['applicable'], 'images' => $c['images'],
            ], $contexts)),
        ];
        file_put_contents($in, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $p = new Process(['nice', '-n', '10', $this->python, $this->script(), 'audit', $in, $out]);
        $p->setTimeout($timeout);
        $p->run();
        if (! $p->isSuccessful() || ! is_file($out) || filesize($out) === 0) {
            throw new RuntimeException('twin_audit.py failed rc='.$p->getExitCode().': '.Str::limit($p->getErrorOutput().$p->getOutput(), 800));
        }
        $res = json_decode((string) file_get_contents($out), true);
        if (! is_array($res)) {
            throw new RuntimeException('twin_audit.py produced unreadable JSON');
        }

        return collect($res)->keyBy('product_id')->all();
    }

    // ------------------------------------------------------------------ persist

    /** Upsert readiness + append attempts. Returns [rows, attempts]. */
    public function persist(array $contexts, array $results, string $runId): array
    {
        $now = now();
        $rows = 0;
        $atts = 0;
        foreach ($contexts as $pid => $c) {
            $r = $results[$pid] ?? null;
            if (! $r) {
                continue;
            }
            if (isset($r['error'])) {
                DB::table('fi_twin_attempts')->insert([
                    'product_id' => $pid, 'sku' => $c['sku'], 'run_id' => $runId, 'attempt_no' => 0, 'method' => 'audit',
                    'result' => 'error', 'worked' => false, 'note' => Str::limit($r['error'], 900),
                    'method_version' => self::VERSION, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $atts++;

                continue;
            }
            $s = $r['summary'];
            $shaByKey = collect($c['images'])->pluck('sha256', 'key')->all();
            $kindByKey = collect($c['images'])->pluck('kind', 'key')->all();
            $metrics = [
                'photos' => collect($r['images'])->map(fn ($a, $k) => array_intersect_key($a + ['key' => $k], array_flip([
                    'key', 'kind', 'w', 'h', 'sharpness', 'cast', 'coverage', 'clean_share', 'photo_class', 'watermark',
                    'overlay_price', 'label_present', 'code_sticker', 'lines', 'quad_evenness', 'quad_range_L', 'cutout', 'error',
                ])))->values()->all(),
                'supplier_ref' => $s['supplier_ref'] ?? null,
                'dims_note' => $c['dims']['source'] ?? null,
                'seconds' => $r['secs'] ?? null,
            ];
            $row = [
                'sku' => $c['sku'], 'public_name' => $c['public_name'], 'enabled' => $c['enabled'], 'applicable' => $c['applicable'],
                'available_qty' => $c['available_qty'], 'sold_qty_12m' => $c['sold_qty_12m'],
                'photos_count' => $s['photos_count'] ?? 0, 'own_photos_count' => $s['own_photos_count'] ?? 0,
                'drive_photos_count' => $s['drive_photos_count'] ?? 0, 'supplier_ref_count' => $s['supplier_ref_count'] ?? 0,
                'best_photo_px' => $s['best_photo_px'] ?? null, 'best_photo_sharpness' => $s['best_photo_sharpness'] ?? null,
                'flat_vs_angled' => $s['flat_vs_angled'] ?? null, 'lighting_evenness' => $s['lighting_evenness'] ?? null,
                'colour_reference' => $s['colour_reference'] ?? null, 'full_repeat_visible' => $s['full_repeat_visible'] ?? null,
                'wrinkles_shadows' => $s['wrinkles_shadows'] ?? null, 'watermark_present' => $s['watermark_present'] ?? null,
                'repeat_px_x' => $s['repeat_px_x'] ?? null, 'repeat_px_y' => $s['repeat_px_y'] ?? null,
                'repeat_lattice' => isset($s['repeat_lattice']) ? json_encode($s['repeat_lattice']) : null,
                'repeat_size_cm' => null,                         // no ruler in any photo yet
                'width_cm' => $c['dims']['width_cm'] ?? null, 'length_cm' => $c['dims']['length_cm'] ?? null,
                'dims_source' => $c['dims']['source'] ?? null,
                'tier' => $s['tier'], 'tier_audit' => $s['tier_audit'] ?? null, 'raised_by_step2' => (bool) ($s['raised_by_step2'] ?? false),
                'reason' => Str::limit(PublicCode::mask($s['reason'] ?? ''), 2000, ''),
                'best_source_path' => $s['best_source_path'] ?? null, 'best_source_kind' => $s['best_source_kind'] ?? null,
                'tile_path' => $s['tile_path'] ?? null, 'tile_w' => $s['tile_w'] ?? null, 'tile_h' => $s['tile_h'] ?? null,
                'seam_de00_mean' => $s['seam_de00_mean'] ?? null, 'seam_de00_p95' => $s['seam_de00_p95'] ?? null,
                'seam_ncc' => $s['seam_ncc'] ?? null, 'fixes_applied' => $s['fixes_applied'] ?? null,
                'supplier_ref_tileable' => isset($s['supplier_ref']['tileable']) ? (bool) $s['supplier_ref']['tileable'] : null,
                'metrics' => json_encode($metrics, JSON_UNESCAPED_UNICODE),
                'method_version' => self::VERSION, 'internal_only' => true, 'audited_at' => $now, 'updated_at' => $now,
            ];
            if (! empty($row['tile_path']) && ! empty($row['best_source_path']) && is_file($row['best_source_path'])
                && ! str_starts_with($row['best_source_path'], $this->tenantPublicDir())) {
                $keep = $this->baseDir().'/sources/'.$pid;
                if (! is_dir($keep)) {
                    mkdir($keep, 0775, true);
                }
                $dst = $keep.'/'.basename($row['best_source_path']);
                if (! is_file($dst)) {
                    copy($row['best_source_path'], $dst);
                }
                $row['best_source_path'] = $dst;
            }
            $exists = DB::table('fi_product_twin_readiness')->where('product_id', $pid)->exists();
            if ($exists) {
                DB::table('fi_product_twin_readiness')->where('product_id', $pid)->update($row);
            } else {
                DB::table('fi_product_twin_readiness')->insert($row + ['product_id' => $pid, 'created_at' => $now]);
            }
            $rows++;

            $n = 0;
            foreach ($r['images'] as $key => $a) {
                if (($kindByKey[$key] ?? '') === 'supplier_ref') {
                    continue;
                }
                DB::table('fi_twin_attempts')->insert([
                    'product_id' => $pid, 'sku' => $c['sku'], 'run_id' => $runId, 'attempt_no' => ++$n, 'method' => 'audit',
                    'input_kind' => $kindByKey[$key] ?? null, 'input_path' => Str::limit($a['path'] ?? $this->pathOf($c, $key), 500, ''),
                    'input_sha256' => $shaByKey[$key] ?? null, 'result' => ($a['ok'] ?? false) ? ($a['photo_class'] ?? 'measured') : 'error',
                    'worked' => (bool) ($a['ok'] ?? false), 'metrics' => json_encode($a, JSON_UNESCAPED_UNICODE),
                    'note' => isset($a['error']) ? Str::limit($a['error'], 900) : null,
                    'method_version' => self::VERSION, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $atts++;
            }
            foreach ($r['attempts'] as $a) {
                $key = $a['image'] ?? null;
                DB::table('fi_twin_attempts')->insert([
                    'product_id' => $pid, 'sku' => $c['sku'], 'run_id' => $runId, 'attempt_no' => ++$n,
                    'method' => Str::limit($a['method'] ?? 'crop', 60, ''), 'input_kind' => $a['kind'] ?? ($kindByKey[$key] ?? null),
                    'input_path' => Str::limit($a['path'] ?? $this->pathOf($c, $key), 500, ''),
                    'input_sha256' => $shaByKey[$key] ?? null, 'result' => Str::limit($a['result'] ?? 'unknown', 32, ''),
                    'worked' => (bool) ($a['worked'] ?? false),
                    'metrics' => json_encode(array_diff_key($a, array_flip(['path'])), JSON_UNESCAPED_UNICODE),
                    'note' => isset($a['note']) ? Str::limit((string) $a['note'], 900) : null,
                    'output_path' => $a['output_path'] ?? null,
                    'method_version' => self::VERSION, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $atts++;
            }
        }

        return [$rows, $atts];
    }

    private function pathOf(array $c, ?string $key): ?string
    {
        foreach ($c['images'] as $im) {
            if ($im['key'] === $key) {
                return $im['path'];
            }
        }

        return null;
    }

    // --------------------------------------------------------------- shoot list

    /** Tier C yardage products, in stock first, then best sellers, then enabled. */
    public function shootRows(): array
    {
        $rows = DB::table('fi_product_twin_readiness')->where('tier', 'C')->where('applicable', true)
            ->orderByRaw('(available_qty > 0) DESC')->orderByDesc('sold_qty_12m')->orderByDesc('enabled')->orderBy('product_id')
            ->get();
        $out = [];
        $i = 0;
        foreach ($rows as $r) {
            $out[] = [
                'rank' => ++$i, 'product_id' => $r->product_id, 'sku' => $r->sku, 'public_name' => PublicCode::mask($r->public_name),
                'enabled' => $r->enabled ? 1 : 0, 'available_qty' => $r->available_qty, 'sold_qty_12m' => $r->sold_qty_12m,
                'tier' => $r->tier, 'tier_audit' => $r->tier_audit, 'reason' => PublicCode::mask($r->reason),
                'best_photo_px' => $r->best_photo_px, 'flat_vs_angled' => $r->flat_vs_angled,
                'full_repeat_visible' => $r->full_repeat_visible === null ? 'unknown' : ($r->full_repeat_visible ? 'yes' : 'no'),
                'watermark_present' => $r->watermark_present === null ? 'unknown' : ($r->watermark_present ? 'yes' : 'no'),
                'upload_folder' => self::INCOMING.'/'.$r->sku.'/',
                'what_to_shoot' => self::CHECKLIST,
                'product_note' => $this->productNote($r),
            ];
        }

        return $out;
    }

    private function productNote(object $r): string
    {
        $n = [];
        $reason = (string) $r->reason;
        if (str_contains($reason, 'larger than') || str_contains($reason, 'no full repeat') || str_contains($reason, 'one repeat direction')) {
            $n[] = 'repeat is bigger than any photo so far: lay out the whole piece (or >= 1.5 m) so 2 repeats show';
        }
        if ($r->watermark_present) {
            $n[] = 'current photos carry the Fujiba watermark: shoot an unwatermarked original';
        }
        if (in_array($r->flat_vs_angled, ['folded', 'packaged'], true)) {
            $n[] = 'only folded/packaged views exist: take it out of the bag and lay one layer flat';
        }
        if ($r->flat_vs_angled === 'angled' || str_contains($reason, 'perspective')) {
            $n[] = 'hold the phone exactly parallel (use the grid; both fabric edges straight in the frame)';
        }
        if (str_contains($reason, 'no own photo')) {
            $n[] = 'no own photo at all (supplier image only): full new shoot';
        }
        if ($r->lighting_evenness !== null && (float) $r->lighting_evenness < 0.6) {
            $n[] = 'light was uneven: shoot in open shade or by a big window, no lamp';
        }

        return implode('; ', $n);
    }

    public function writeShootList(string $path): int
    {
        return CsvSheet::write($path, self::SHOOT_HEADER, $this->shootRows());
    }
}
