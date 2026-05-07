<?php

namespace Webkul\Pos\Queries\Home;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Customer\Repositories\CustomerGroupRepository;
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

        $qb->select(
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
            ->leftJoin('pos_product_barcode', 'products.id', '=', 'pos_product_barcode.product_id')
            ->leftJoin('pos_outlet_product', 'products.id', '=', 'pos_outlet_product.product_id')
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
}
