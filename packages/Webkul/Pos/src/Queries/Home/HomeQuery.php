<?php

namespace Webkul\Pos\Queries\Home;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Pos\Helpers\BarcodeNormalizer;
use Webkul\Product\Helpers\BundleOption as BundleOptionHelper;
use Webkul\Product\Helpers\ConfigurableOption as ConfigurableOptionHelper;
use Webkul\Product\Repositories\ProductRepository;

class HomeQuery extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected ConfigurableOptionHelper $configurableOptionHelper,
        protected BundleOptionHelper $bundleOptionHelper
    ) {}

    /**
     * Get the home page products
     */
    public function getProducts($query, $input, GraphQLContext $context): Builder
    {
        $agent = $context->user();

        $params = array_merge($input, [
            'outlet_id'           => $agent->outlet_id,
            'inventory_source_id' => $agent->outlet->inventory_source_id,
        ]);

        $qb = $this->productRepository->query()->distinct();

        $customerGroupId = $this->customerGroupRepository->findOneWhere([
            'code' => 'general',
        ])->id;

        // Cost (原価) lives in product_attribute_values for the `cost` attribute
        // (type=price → float_value). Joined here so the POS can show
        // per-product profit to staff/admin without an extra round-trip.
        $costAttributeId = \DB::table('attributes')->where('code', 'cost')->value('id');

        $qb->select(
            'products.*',
            'product_flat.status as status',
            'product_inventories.qty as quantity',
            'pos_outlet_product.status as pos_status',
            'pos_product_barcode.barcode',
            'pos_product_barcode.gtin',
            'pos_product_barcode.scan_type',
            'pos_product_barcode.scan_value',
            'pav_cost.float_value as cost',
        )
            // Scope product_flat to ONE channel+locale row per product. Without
            // this, product_flat carries one row per locale (en/ja/my) → the
            // join multiplied every product 3× and the paginator's count came
            // back inflated (e.g. 613 for a 370-product outlet). Mirrors the
            // locale-scoped join in Pos\Queries\Products\ProductQuery.
            ->leftJoin('product_flat', function ($join) {
                $join->on('products.id', '=', 'product_flat.product_id')
                    ->where('product_flat.channel', core()->getCurrentChannelCode())
                    ->where('product_flat.locale', core()->getRequestedLocaleCode());
            })
            ->leftJoin('pos_product_barcode', 'products.id', '=', 'pos_product_barcode.product_id')
            ->leftJoin('pos_outlet_product', 'products.id', '=', 'pos_outlet_product.product_id')
            ->leftJoin('product_attribute_values as pav_cost', function ($join) use ($costAttributeId) {
                $join->on('products.id', '=', 'pav_cost.product_id')
                    ->where('pav_cost.attribute_id', '=', $costAttributeId);
            })
            ->leftJoin('product_inventories', function ($join) use ($params) {
                $join->on('products.id', '=', 'product_inventories.product_id')
                    ->where('product_inventories.inventory_source_id', $params['inventory_source_id']);
            })
            ->leftJoin('product_price_indices', function ($join) use ($customerGroupId) {
                $join->on('products.id', '=', 'product_price_indices.product_id')
                    ->where('product_price_indices.customer_group_id', $customerGroupId);
            })
            ->where('products.parent_id', null)
            ->where('pos_outlet_product.outlet_id', $params['outlet_id']);

        // Eager-load the relations the OutletProduct GraphQL type resolves
        // per row (images, categories, variants, super_attributes). Without
        // this each field lazy-loads per product → an N+1 of ~2 queries ×
        // every product (367 products = ~735 queries, ~950ms on the POS
        // catalog). With eager-loading it collapses to one query per relation
        // regardless of row count.
        $qb->with([
            'images',
            'categories',
            'variants',
            'super_attributes',
        ]);

        return $qb->groupBy('products.id');
    }

    /**
     * Get Price html of Product
     */
    public function getConvertedPrice($product): ?float
    {
        return core()->convertPrice($product->price);
    }

    /**
     * Get Price html of Product
     */
    public function getPriceHtml($product): string
    {
        return $product->getTypeInstance()->getPriceHtml();
    }

    /**
     * Get the variants config for the product
     */
    public function getVariantsConfig($product): array
    {
        $data = [];

        if ($product->type == 'configurable') {
            $data = $this->configurableOptionHelper->getConfigurationConfig($product);
        }

        return $data;
    }

    /**
     * Resolve the product's brand option as { id, label } for the POS filter.
     *
     * Brand is a core `select` attribute (code=brand) whose chosen option id is
     * stored in product_attribute_values.integer_value — it has no product_flat
     * column, so it can't be plucked from the flat join. We read it on demand
     * here. The brand attribute id and the option-id→label map are resolved once
     * per request (static caches) so paging through hundreds of products costs a
     * single options query, not one per row. Returns null when the tenant has no
     * brand attribute or the product has no brand set (most tenants/products) —
     * the POS only renders the brand filter section when some product returns a
     * non-null brand, so brand-less tenants are unaffected.
     */
    public function getBrand($product): ?array
    {
        static $brandAttributeId = false; // false = not yet resolved (distinct from null = absent)
        static $labels = null;            // option id => label

        if ($brandAttributeId === false) {
            $brandAttributeId = \DB::table('attributes')->where('code', 'brand')->value('id');
            $labels = [];
            if ($brandAttributeId) {
                $locale = core()->getRequestedLocaleCode();
                foreach (
                    \Webkul\Attribute\Models\AttributeOption::where('attribute_id', $brandAttributeId)->get()
                    as $opt
                ) {
                    $translated = optional($opt->translate($locale))->label;
                    $labels[$opt->id] = $translated ?: $opt->admin_name;
                }
            }
        }

        if (! $brandAttributeId) {
            return null;
        }

        $optionId = \DB::table('product_attribute_values')
            ->where('product_id', $product->id)
            ->where('attribute_id', $brandAttributeId)
            ->value('integer_value');

        if (! $optionId) {
            return null;
        }

        return [
            'id'    => (int) $optionId,
            'label' => $labels[$optionId] ?? (string) $optionId,
        ];
    }

    /**
     * Get the bundle options for the product
     */
    public function getBundleOptions($product): array
    {
        $data = [];

        if ($product->type == 'bundle') {
            $data = $this->bundleOptionHelper->getBundleConfig($product);
        }

        return $data['options'] ?? [];
    }

    /**
     * Resolve the intervention/imagecache `small` variant URL for a single
     * product image row (the GraphQL `ProductImage` root is the
     * \Webkul\Product\Models\ProductImage model). The POS catalog decodes
     * these into tiny grid tiles, so handing it the full-res /storage original
     * (the existing `url` field) wastes decode time and memory cache. Bagisto
     * already resizes on demand via the `cache/{template}/{path}` route
     * (config/imagecache.php → Webkul\Shop\CacheFilters\Small), so we just
     * point at it. Mirrors \Webkul\Product\ProductImage::getCachedImageUrls():
     * local disk → cache route; remote/non-local disk → passthrough original.
     * The url() helper resolves against the *request* host (the tenant POS
     * host the client called), not APP_URL, so the cache route lands on the
     * tenant that can serve it.
     */
    public function getSmallImageUrl($image): ?string
    {
        return $this->imageVariantUrl($image, 'small');
    }

    /**
     * Resolve the intervention/imagecache `medium` variant URL for a single
     * product image row. See getSmallImageUrl() for details.
     */
    public function getMediumImageUrl($image): ?string
    {
        return $this->imageVariantUrl($image, 'medium');
    }

    /**
     * Build the cached-variant URL for one product image, mirroring
     * \Webkul\Product\ProductImage::getCachedImageUrls() so behaviour stays in
     * lockstep with the rest of Bagisto (incl. the REST ProductImageResource).
     */
    protected function imageVariantUrl($image, string $template): ?string
    {
        $path = $image->path ?? null;

        if (! $path) {
            return null;
        }

        // Non-local disks (S3 etc.) don't serve the cache route — Bagisto
        // hands back the original there (ProductImage::getCachedImageUrls),
        // so do the same.
        if (! Storage::getAdapter() instanceof LocalFilesystemAdapter) {
            return Storage::url($path);
        }

        return url('cache/'.$template.'/'.$path);
    }

    /**
     * Resolver for the lookupByBarcode query. Returns the first product
     * whose pos_product_barcode row matches the scanned code (barcode →
     * gtin → scan_value), constrained to the cashier's outlet. Burmese
     * and full-width digits are translit'd to ASCII via the shared
     * BarcodeNormalizer so the same write-side rule applies on read.
     */
    public function lookupByBarcode($root, array $args, GraphQLContext $context)
    {
        $raw = (string) ($args['value'] ?? '');
        $candidates = array_values(array_unique(array_filter([
            trim($raw),
            BarcodeNormalizer::normalize($raw),
        ], fn ($v) => $v !== '')));

        if (empty($candidates)) {
            return null;
        }

        $agent = $context->user();
        $outletId = $agent->outlet_id;
        $inventorySourceId = $agent->outlet->inventory_source_id;

        $customerGroupId = $this->customerGroupRepository->findOneWhere([
            'code' => 'general',
        ])->id;

        return $this->productRepository->query()
            ->select(
                'products.*',
                'product_flat.status as status',
                'product_inventories.qty as quantity',
                'pos_outlet_product.status as pos_status',
                'pos_product_barcode.barcode',
                'pos_product_barcode.gtin',
                'pos_product_barcode.scan_type',
                'pos_product_barcode.scan_value',
            )
            ->leftJoin('product_flat', 'products.id', '=', 'product_flat.product_id')
            ->join('pos_product_barcode', 'products.id', '=', 'pos_product_barcode.product_id')
            ->join('pos_outlet_product', function ($j) use ($outletId) {
                $j->on('products.id', '=', 'pos_outlet_product.product_id')
                  ->where('pos_outlet_product.outlet_id', $outletId);
            })
            ->leftJoin('product_inventories', function ($join) use ($inventorySourceId) {
                $join->on('products.id', '=', 'product_inventories.product_id')
                    ->where('product_inventories.inventory_source_id', $inventorySourceId);
            })
            ->leftJoin('product_price_indices', function ($join) use ($customerGroupId) {
                $join->on('products.id', '=', 'product_price_indices.product_id')
                    ->where('product_price_indices.customer_group_id', $customerGroupId);
            })
            ->whereNull('products.parent_id')
            ->where(function ($q) use ($candidates) {
                foreach ($candidates as $c) {
                    $q->orWhere('pos_product_barcode.barcode', $c)
                      ->orWhere('pos_product_barcode.gtin', $c)
                      ->orWhere('pos_product_barcode.scan_value', $c);
                }
            })
            ->groupBy('products.id')
            ->first();
    }
}
