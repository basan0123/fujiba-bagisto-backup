<?php
/**
 * Migrate Chatpion product images into Bagisto tenant storage + product_images table.
 *
 * Source: /var/www/ecommerce_images/ecommerce 2/
 * Target: tenant storage product/{bagisto_product_id}/
 */

$host     = '127.0.0.1';
$port     = 3306;
$user     = 'bagisto';
$pass     = 'Bagisto2024';
$tenantDb = 'tenant_1bd1181f-fcd5-4238-a12d-a207d6ea4006';
$sourceDb = 'chatpion_import';

$imageSourceDir = '/var/www/ecommerce_images/ecommerce 2';
$tenantStorageDir = '/var/www/bagisto/storage/app/public/tenants/1/1bd1181f-fcd5-4238-a12d-a207d6ea4006/product';

$now = date('Y-m-d H:i:s');

$channelsByStore = [
    9  => ['sku_prefix' => 'MMFOOD-'],
    10 => ['sku_prefix' => 'KIMONO-'],
];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Get all chatpion products with image references
    $stmt = $pdo->query("
        SELECT cp.id, cp.store_id, cp.thumbnail, cp.featured_images
        FROM `$sourceDb`.ecommerce_product cp
        WHERE cp.deleted = '0'
          AND cp.store_id IN (9, 10)
          AND (
              (cp.thumbnail IS NOT NULL AND cp.thumbnail != '' AND cp.thumbnail NOT LIKE 'http%')
              OR (cp.featured_images IS NOT NULL AND cp.featured_images != '')
          )
    ");

    // Prepare insert for product_images
    $findProduct = $pdo->prepare("
        SELECT id FROM `$tenantDb`.products WHERE sku = :sku LIMIT 1
    ");

    $checkImage = $pdo->prepare("
        SELECT id FROM `$tenantDb`.product_images WHERE product_id = :product_id AND path = :path LIMIT 1
    ");

    $insertImage = $pdo->prepare("
        INSERT INTO `$tenantDb`.product_images (type, path, product_id, position)
        VALUES (:type, :path, :product_id, :position)
    ");

    $copiedCount = 0;
    $dbCount = 0;
    $skippedCount = 0;
    $missingCount = 0;

    while ($row = $stmt->fetch()) {
        $ch = $channelsByStore[$row['store_id']];
        $sku = $ch['sku_prefix'] . $row['id'];

        $findProduct->execute([':sku' => $sku]);
        $product = $findProduct->fetch();
        if (!$product) continue;

        $productId = $product['id'];
        $position = 0;

        // Collect images: thumbnail first, then featured
        $images = [];

        // Thumbnail
        $thumb = trim($row['thumbnail'] ?? '');
        if ($thumb !== '' && stripos($thumb, 'http') !== 0) {
            $images[] = $thumb;
        }

        // Featured images (can be comma-separated or single)
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
            // Find the image in extracted folder (case-insensitive)
            $sourcePath = $imageSourceDir . '/' . $imageFile;

            if (!file_exists($sourcePath)) {
                // Try case-insensitive search
                $found = glob($imageSourceDir . '/' . str_ireplace(
                    ['.jpg', '.jpeg', '.png', '.webp', '.gif', '.PNG', '.JPG', '.JPEG'],
                    ['.[jJ][pP][gG]', '.[jJ][pP][eE][gG]', '.[pP][nN][gG]', '.[wW][eE][bB][pP]', '.[gG][iI][fF]', '.[pP][nN][gG]', '.[jJ][pP][gG]', '.[jJ][pP][eE][gG]'],
                    $imageFile
                ));
                if (!empty($found)) {
                    $sourcePath = $found[0];
                } else {
                    $missingCount++;
                    continue;
                }
            }

            // Create target directory
            $targetDir = $tenantStorageDir . '/' . $productId;
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }

            // Copy file
            $targetFile = $targetDir . '/' . basename($sourcePath);
            if (!file_exists($targetFile)) {
                copy($sourcePath, $targetFile);
                $copiedCount++;
            }

            // Insert into product_images table
            $relativePath = 'product/' . $productId . '/' . basename($sourcePath);

            $checkImage->execute([':product_id' => $productId, ':path' => $relativePath]);
            if ($checkImage->fetch()) {
                $skippedCount++;
                continue;
            }

            $position++;
            $insertImage->execute([
                ':type'       => null,
                ':path'       => $relativePath,
                ':product_id' => $productId,
                ':position'   => $position,
            ]);
            $dbCount++;
        }
    }

    echo "========================================\n";
    echo "     IMAGE MIGRATION SUMMARY\n";
    echo "========================================\n";
    echo "  Files copied:      $copiedCount\n";
    echo "  DB records added:  $dbCount\n";
    echo "  Already existed:   $skippedCount\n";
    echo "  Missing files:     $missingCount\n";
    echo "========================================\n";

    // Fix ownership
    echo "Fixing permissions...\n";
    exec("chown -R www-data:www-data " . escapeshellarg($tenantStorageDir));

    echo "Done.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
