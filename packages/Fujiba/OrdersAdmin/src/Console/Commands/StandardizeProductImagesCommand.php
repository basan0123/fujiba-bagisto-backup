<?php

namespace Fujiba\OrdersAdmin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;

/**
 * Standardize product images for the live tenant:
 *  - 800x800 white square canvas, contain (no crop, no upsize)
 *  - Always JPEG output (quality 88, light sharpen)
 *  - Update product_images.path rows when extension changes (.png/.webp -> .jpg)
 *  - Clear Bagisto's image-cache directory for each affected product so the
 *    next request regenerates thumbnails from the standardized source
 *  - Originals backed up to product_originals_YYYYMMDD/ once per run
 */
class StandardizeProductImagesCommand extends Command
{
    protected $signature = 'fujiba:standardize-images
        {--dry-run     : Report what would change, do not modify files}
        {--limit=      : Process at most N images}
        {--product=    : Process only the given product ID}
        {--force       : Re-process even if image already 800x800}';

    protected $description = 'Standardize live-tenant product images to 800x800 with white padding (always JPEG).';

    private const TENANT_KEY        = '1bd1181f-fcd5-4238-a12d-a207d6ea4006';
    private const TENANT_DB         = 'tenant_1bd1181f-fcd5-4238-a12d-a207d6ea4006';
    private const REL_PRODUCT_DIR   = 'tenants/1/1bd1181f-fcd5-4238-a12d-a207d6ea4006/product';
    private const TARGET_SIZE       = 800;
    private const JPEG_QUALITY      = 88;
    private const SHARPEN_AMOUNT    = 5;

    public function handle(): int
    {
        $sourceDir = storage_path('app/public/' . self::REL_PRODUCT_DIR);
        if (! is_dir($sourceDir)) {
            $this->error("Source dir not found: {$sourceDir}");
            return self::FAILURE;
        }

        $dryRun     = (bool) $this->option('dry-run');
        $limit      = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $productOpt = $this->option('product') !== null ? (int) $this->option('product') : null;
        $force      = (bool) $this->option('force');

        // Backup originals once per run (skipped on --dry-run).
        $backupDir = dirname($sourceDir) . '/product_originals_' . date('Ymd');
        if ($dryRun) {
            $this->line("[dry-run] would back up originals -> {$backupDir}");
        } elseif (! is_dir($backupDir)) {
            $this->info("Backing up originals -> {$backupDir}");
            File::copyDirectory($sourceDir, $backupDir);
        } else {
            $this->line("Backup dir already exists, skipping copy: {$backupDir}");
        }

        // Collect target files.
        $files = [];
        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir));
        foreach ($iter as $f) {
            if (! $f->isFile()) continue;
            $ext = strtolower($f->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) continue;

            $relPath = ltrim(str_replace($sourceDir, '', $f->getPathname()), '/');
            $parts = explode('/', $relPath);
            if (count($parts) < 2) continue;
            $thisProductId = (int) $parts[0];
            if ($productOpt !== null && $thisProductId !== $productOpt) continue;

            $files[] = [
                'abs_path'   => $f->getPathname(),
                'rel_path'   => self::REL_PRODUCT_DIR . '/' . $relPath,
                'db_path'    => 'product/' . $relPath, // product_images.path is stored without tenants/ prefix
                'product_id' => $thisProductId,
                'ext'        => $ext,
            ];
        }

        if ($limit !== null) $files = array_slice($files, 0, $limit);

        $this->info(sprintf(
            '%d files to process%s%s',
            count($files),
            $productOpt ? " (product={$productOpt})" : '',
            $dryRun ? ' [DRY RUN]' : ''
        ));

        $manager = new ImageManager(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);

        $processed = 0;
        $skipped   = 0;
        $errors    = 0;
        $touchedProducts = [];

        foreach ($files as $i => $f) {
            try {
                $img = $manager->make($f['abs_path']);
                $w   = $img->width();
                $h   = $img->height();

                if (! $force && $w === self::TARGET_SIZE && $h === self::TARGET_SIZE && $f['ext'] === 'jpg') {
                    $skipped++;
                    $this->line("  SKIP (already 800x800 JPG): {$f['db_path']}");
                    continue;
                }

                if ($dryRun) {
                    $this->line(sprintf(
                        '  WOULD PROCESS  %dx%d -> 800x800  product=%d  %s',
                        $w, $h, $f['product_id'], $f['db_path']
                    ));
                    $processed++;
                    continue;
                }

                // CONTAIN (no upsize): only shrink to fit; small images stay native size.
                $img->resize(self::TARGET_SIZE, self::TARGET_SIZE, function ($c) {
                    $c->aspectRatio();
                    // No upsize() — small images keep their native dimensions.
                });

                $canvas = $manager->canvas(self::TARGET_SIZE, self::TARGET_SIZE, '#ffffff');
                $canvas->insert($img, 'center');
                $canvas->sharpen(self::SHARPEN_AMOUNT);

                // Always write JPEG.
                $newAbs = preg_replace('/\.(jpg|jpeg|png|webp)$/i', '.jpg', $f['abs_path']);
                $newDb  = preg_replace('/\.(jpg|jpeg|png|webp)$/i', '.jpg', $f['db_path']);

                $canvas->save($newAbs, self::JPEG_QUALITY, 'jpg');

                // Remove old file if extension changed.
                if ($newAbs !== $f['abs_path'] && file_exists($f['abs_path'])) {
                    unlink($f['abs_path']);
                }

                // Update DB row in tenant connection.
                if ($newDb !== $f['db_path']) {
                    DB::connection('mysql')->statement('USE `' . self::TENANT_DB . '`');
                    DB::connection('mysql')
                        ->table('product_images')
                        ->where('path', $f['db_path'])
                        ->update(['path' => $newDb]);
                }

                $touchedProducts[$f['product_id']] = true;
                $processed++;
                $this->line(sprintf(
                    '  OK  %dx%d -> 800x800  product=%d  %s',
                    $w, $h, $f['product_id'], $newDb
                ));

                if ($processed > 0 && $processed % 25 === 0) {
                    $this->info("  ... {$processed} / " . count($files));
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->error("  ERROR  {$f['abs_path']} :: {$e->getMessage()}");
            }
        }

        // Clear Bagisto's image cache for affected products so the next request regenerates.
        if (! $dryRun && ! empty($touchedProducts)) {
            $cleared = $this->clearImageCache(array_keys($touchedProducts));
            $this->info("Cleared image cache for " . count($touchedProducts) . " products ({$cleared} cache files removed).");
        }

        $this->info("Done. processed={$processed}  skipped={$skipped}  errors={$errors}");
        return self::SUCCESS;
    }

    /**
     * Bagisto's image-cache writes to storage/framework/cache/intervention-image and/or
     * storage/app/public/cache/. Since the cache is keyed by full URL hash, the safest
     * approach is to wipe both well-known cache locations entirely.
     */
    private function clearImageCache(array $productIds): int
    {
        $deleted = 0;
        $candidateRoots = [
            storage_path('framework/cache/intervention-image'),
            storage_path('app/public/cache'),
            storage_path('framework/cache/laravel-image-cache'),
        ];

        foreach ($candidateRoots as $root) {
            if (! is_dir($root)) continue;
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iter as $f) {
                if ($f->isFile()) {
                    @unlink($f->getPathname());
                    $deleted++;
                }
            }
        }
        return $deleted;
    }
}
