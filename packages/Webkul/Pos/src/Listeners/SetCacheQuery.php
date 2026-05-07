<?php

namespace Webkul\Pos\Listeners;

use Illuminate\Support\Facades\Auth;
use Nuwave\Lighthouse\Events\EndExecution;
use Webkul\GraphQLAPI\Listeners\SetCacheQuery as BaseSetCacheQuery;
use Webkul\GraphQLAPI\Services\GraphQLCacheService;

class SetCacheQuery extends BaseSetCacheQuery
{
    /**
     * Handle the GraphQL execution end event
     */
    public function handle(EndExecution $event): void
    {
        $request = request();

        $query = $request->input('query');

        if (! $this->shouldProcessQuery($query)) {
            return;
        }

        $queryName = $this->extractQueryName($query);

        if (
            ! $queryName
            || ! GraphQLCacheService::shouldCache($queryName, Auth::guard('api')->check())
            || in_array($queryName, $this->getSkippableQueryForPos())
        ) {
            return;
        }

        $this->cacheQueryResult($event, $request, $queryName);
    }

    /**
     * Get the list of queries to skip for POS
     */
    private function getSkippableQueryForPos(): array
    {
        return [
            'getDrawerDetails',
            'getTodaySales',
            'getSaleHistory',
            'fetchGlobalData',
            'getCustomers',
            'getOutletProducts',
            'getOrders',
            'getLowStockProducts',
            'getRequestedProducts',
            'getSaleReport',
        ];
    }
}
