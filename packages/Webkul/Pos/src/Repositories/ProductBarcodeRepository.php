<?php

namespace Webkul\Pos\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Webkul\Core\Eloquent\Repository;
use Webkul\Pos\Contracts\ProductBarcode;
use Webkul\Product\Repositories\ProductRepository;

class ProductBarcodeRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        Container $container,
        protected ProductRepository $productRepository,
    ) {
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ProductBarcode::class;
    }

    /**
     * Relative storage path for a product's barcode directory.
     * Uses the public disk which is re-pointed to the tenant root by
     * SetTenantStorageDisk middleware when a tenant is active.
     */
    protected function barcodeDirKey(int $productId): string
    {
        return "product/{$productId}";
    }

    /**
     * Generate Barcode for the product.
     *
     * Encoding priority:
     *   1. Per-product scan_type ('gtin' | 'sku' | 'product_id')
     *   2. Explicit $gtin param (backward-compat — treated as scan_type='gtin')
     *   3. Existing saved GTIN on the record
     *   4. Tenant-global "generate_with" config (id | sku | gtin)
     *
     * scan_value is computed and stored so the POS can resolve
     * any scan with a single column lookup (no type knowledge needed).
     *
     * @param  int         $id           Product ID
     * @param  string|null $barcodeType  Symbology override (code128, code39, etc.).
     *                                   When null the global config value is used.
     * @param  string|null $gtin         Real GTIN number (backward-compat shortcut for
     *                                   scan_type='gtin').
     * @param  string|null $scanType     Per-product encoding type: 'gtin' | 'sku' | 'product_id'
     */
    public function generateBarcode(
        int     $id,
        ?string $barcodeType = null,
        ?string $gtin        = null,
        ?string $scanType    = null,
    ): string {
        require_once __DIR__.'/../barcode.php';

        $width  = core()->getConfigData('pos.settings.barcode.width')  ?: 100;
        $height = core()->getConfigData('pos.settings.barcode.height') ?: 50;

        $product        = $this->productRepository->find($id);
        $existingRecord = $this->findOneWhere(['product_id' => $id]);

        // Resolve GTIN: explicit param > existing record
        $resolvedGtin = $gtin ?? $existingRecord?->gtin;

        // Resolve scan_type: explicit param > existing record > null (global config)
        $resolvedScanType = $scanType ?? $existingRecord?->scan_type;

        // Compute the canonical scan value stored in scan_value column.
        // This is what a scanner physically reads off the printed label.
        $scanValue = match ($resolvedScanType) {
            'product_id' => (string) $product->id,
            'sku'        => $product->sku,
            'gtin'       => $resolvedGtin ?? $product->sku,
            default      => null,  // NULL = resolved at scan time via fallback chain
        };

        // Determine what to encode on the barcode image.
        // Per-product scan_type takes priority over global config.
        if ($scanValue !== null) {
            $encodeValue = $scanValue;
        } elseif ($resolvedGtin) {
            $encodeValue = $resolvedGtin;
        } else {
            $generateWith = core()->getConfigData('pos.settings.barcode.generate_with') ?: 'sku';

            $encodeValue = match ($generateWith) {
                'id'   => (string) $product->id,
                'gtin' => $resolvedGtin ?? $product->sku,
                default => $product->sku,
            };
        }

        $prefix    = core()->getConfigData('pos.settings.barcode.prefix') ?? '';
        $imageName = $prefix . $encodeValue;

        $barcodeDirPath = Storage::disk('public')->path($this->barcodeDirKey($id));

        if (! file_exists($barcodeDirPath)) {
            mkdir($barcodeDirPath, 0777, true);
        }

        $filepath = $barcodeDirPath . '/' . $imageName . '.png';

        // Per-product symbology takes priority, then global config, then library default.
        $imageType = $barcodeType
            ?? $existingRecord?->barcode_type
            ?? core()->getConfigData('pos.settings.barcode.image_type')
            ?? 'code128';

        barcode($filepath, $imageName, $width, $height, $imageType);

        Image::cache(fn ($image) => $image->make($filepath)->resize(300, 200)->greyscale());

        $this->updateOrCreate(
            ['product_id' => $product->id],
            [
                'barcode'      => $imageName,
                'barcode_type' => $barcodeType ?? $existingRecord?->barcode_type,
                'gtin'         => $resolvedGtin,
                'scan_type'    => $resolvedScanType,
                'scan_value'   => $scanValue,
            ]
        );

        return $imageName;
    }

    /**
     * Get Barcode URL for the product (tenant-aware via Storage disk).
     */
    public function getBarcode(int $product_id): string
    {
        $barcode = $this->findOneWhere(['product_id' => $product_id]);

        $relativePath = fn (string $name) => $this->barcodeDirKey($product_id) . '/' . $name . '.png';

        $resolveUrl = function (string $name) use ($relativePath): ?string {
            if (Storage::disk('public')->exists($relativePath($name))) {
                return Storage::disk('public')->url($relativePath($name));
            }
            return null;
        };

        if (! empty($barcode->barcode)) {
            $url = $resolveUrl($barcode->barcode);
            if ($url) {
                return $url;
            }
        }

        $imageName = $this->generateBarcode($product_id);

        return $resolveUrl($imageName) ?? '';
    }
}
