<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TenantImageService
{
    /**
     * Get the storage path for a tenant's product images.
     */
    public function getStoragePath(string $tenantId): string
    {
        return storage_path("app/public/tenants/1/{$tenantId}/product");
    }

    /**
     * Import a product image from a source file path.
     *
     * @return string|null The relative path stored in product_images, or null on failure.
     */
    public function importFromPath(
        string $sourcePath,
        string $tenantId,
        string $tenantDb,
        int $productId,
        int $position = 0,
    ): ?string {
        if (! file_exists($sourcePath)) {
            return null;
        }

        $filename = basename($sourcePath);
        $targetDir = $this->getStoragePath($tenantId) . '/' . $productId;

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $targetPath = $targetDir . '/' . $filename;

        if (! file_exists($targetPath)) {
            copy($sourcePath, $targetPath);
        }

        $relativePath = 'product/' . $productId . '/' . $filename;

        // Check if already in DB
        $exists = DB::connection('mysql')
            ->select(
                "SELECT id FROM `{$tenantDb}`.product_images WHERE product_id = ? AND path = ? LIMIT 1",
                [$productId, $relativePath]
            );

        if (! empty($exists)) {
            return $relativePath;
        }

        DB::connection('mysql')->insert(
            "INSERT INTO `{$tenantDb}`.product_images (type, path, product_id, position) VALUES (NULL, ?, ?, ?)",
            [$relativePath, $productId, $position]
        );

        return $relativePath;
    }

    /**
     * Batch import all images for a tenant from a Chatpion export directory.
     *
     * @return array Summary with counts.
     */
    public function importBatch(
        string $imageDir,
        string $tenantId,
        string $tenantDb,
        string $sourceDb,
        array $channelsByStore,
    ): array {
        $pdo = DB::connection('mysql')->getPdo();
        $copied = 0;
        $dbAdded = 0;
        $missing = 0;

        $storeIds = implode(',', array_keys($channelsByStore));

        $stmt = $pdo->query("
            SELECT cp.id, cp.store_id, cp.thumbnail, cp.featured_images
            FROM `{$sourceDb}`.ecommerce_product cp
            WHERE cp.deleted = '0' AND cp.store_id IN ({$storeIds})
              AND ((cp.thumbnail IS NOT NULL AND cp.thumbnail != '' AND cp.thumbnail NOT LIKE 'http%')
                   OR (cp.featured_images IS NOT NULL AND cp.featured_images != ''))
        ");

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $ch = $channelsByStore[$row['store_id']];
            $sku = $ch['sku_prefix'] . $row['id'];

            $product = $pdo->query("SELECT id FROM `{$tenantDb}`.products WHERE sku = '{$sku}' LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
            if (! $product) continue;

            $productId = (int) $product['id'];
            $position = 0;
            $images = [];

            $thumb = trim($row['thumbnail'] ?? '');
            if ($thumb !== '' && stripos($thumb, 'http') !== 0) {
                $images[] = $thumb;
            }

            $featured = trim($row['featured_images'] ?? '');
            if ($featured !== '') {
                foreach (explode(',', $featured) as $fi) {
                    $fi = trim($fi);
                    if ($fi !== '' && stripos($fi, 'http') !== 0) {
                        $images[] = $fi;
                    }
                }
            }

            foreach ($images as $imageFile) {
                $sourcePath = $imageDir . '/' . $imageFile;
                if (! file_exists($sourcePath)) {
                    $missing++;
                    continue;
                }

                $position++;
                $result = $this->importFromPath($sourcePath, $tenantId, $tenantDb, $productId, $position);

                if ($result) {
                    $dbAdded++;
                    $copied++;
                }
            }
        }

        // Fix ownership
        $storagePath = $this->getStoragePath($tenantId);
        if (is_dir($storagePath)) {
            exec("chown -R www-data:www-data " . escapeshellarg($storagePath));
        }

        return [
            'copied'  => $copied,
            'db_added' => $dbAdded,
            'missing' => $missing,
        ];
    }
}
