<?php

namespace Webkul\Pos\Queries\Products;

use App\Http\Controllers\Controller;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Repositories\OutletProductRepository;
use Webkul\Pos\Repositories\ProductRequestRepository;

class ProductQuery extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OutletProductRepository $outletProductRepository,
        protected ProductRequestRepository $productRequestRepository,
    ) {}

    /**
     * Get the products for the home page
     */
    public function getLowStockProducts($rootValue, array $args, GraphQLContext $context)
    {
        $agent = $context->user();

        $params = [
            'low_stock_qty'       => $agent->outlet->low_stock_qty ?? 10,
            'outlet_id'           => $agent->outlet_id,
            'inventory_source_id' => $agent->outlet->inventory_source_id,
        ];

        $products = $this->outletProductRepository->getLowStockProducts($params);

        $products->map(function ($product) {
            $product->price_html = $product->getTypeInstance()->getPriceHtml();
        });

        return $products;
    }

    /**
     * Get the requested products
     */
    public function getRequestedProducts($rootValue, array $args, GraphQLContext $context)
    {
        $query = $this->productRequestRepository->getModel()
            ->leftJoin('product_flat', 'product_flat.product_id', '=', 'pos_product_request.product_id')
            ->select('pos_product_request.*')
            ->addSelect('product_flat.name')
            ->where('product_flat.locale', core()->getRequestedLocaleCode())
            ->where('pos_product_request.user_id', $context->user()->id)
            ->when(! empty($args['query']), function ($qb) use ($args) {
                $qb->where('product_flat.name', 'like', '%'.$args['query'].'%');
            })
            ->orderBy('pos_product_request.created_at', 'desc');

        $paginated = $query->paginate($args['first'], ['*'], 'page', $args['page']);

        return [
            'data'          => $paginated->items(),
            'paginatorInfo' => [
                'count'       => $paginated->count(),
                'currentPage' => $paginated->currentPage(),
                'lastPage'    => $paginated->lastPage(),
                'total'       => $paginated->total(),
            ],
        ];
    }
}
