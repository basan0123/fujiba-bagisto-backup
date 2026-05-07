<?php

namespace Webkul\Pos\Repositories;

use Illuminate\Container\Container;
use Webkul\Core\Eloquent\Repository;
use Webkul\Pos\Contracts\OutletProduct;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductRepository;

class OutletProductRepository extends Repository
{
    /**
     * The Booking Product Type
     *
     * @var string
     */
    private const BOOKING_PRODUCT_TYPE = 'booking';

    /**
     * The event Booking Type
     *
     * @var string
     */
    private const EVENT_BOOKING_TYPE = 'event';

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        Container $container,
        protected ProductRepository $productRepository,
        protected ProductInventoryRepository $productInventoryRepository,
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
        return OutletProduct::class;
    }

    /**
     * Get Product qty
     */
    public function getProductTotalQty(int $productId, int $inventorySourceId)
    {
        $product = $this->productRepository->find($productId);

        if ($product->type === self::BOOKING_PRODUCT_TYPE) {
            $bookingProduct = $product->booking_products()->first();

            return $bookingProduct->type === self::EVENT_BOOKING_TYPE
                ? 'N/A'
                : $bookingProduct->qty;
        }

        $productIds = $product->getTypeInstance()->isComposite()
            ? $product->getTypeInstance()->getChildrenIds()
            : [$product->id];

        return $this->productInventoryRepository->findWhereIn('product_id', $productIds)
            ->where('inventory_source_id', $inventorySourceId)
            ->sum('qty');
    }

    /**
     * Get Low Stock Products
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockProducts(array $params = [])
    {
        $query = $this->productRepository
            ->with([
                'images',
                'variants',
            ])->scopeQuery(function ($query) use ($params) {
                return $query->distinct()
                    ->select('products.*', 'product_inventories.qty as quantity')
                    ->leftJoin('product_inventories', 'products.id', '=', 'product_inventories.product_id')
                    ->leftJoin('pos_outlet_product', 'products.id', '=', 'pos_outlet_product.product_id')
                    ->where('product_inventories.qty', '<=', $params['low_stock_qty'])
                    ->where('product_inventories.inventory_source_id', $params['inventory_source_id'])
                    ->where('pos_outlet_product.outlet_id', $params['outlet_id'])
                    ->where('products.type', 'simple')
                    ->groupBy('products.id');
            });

        return $query->paginate($params['limit'] ?? 10);
    }
}
