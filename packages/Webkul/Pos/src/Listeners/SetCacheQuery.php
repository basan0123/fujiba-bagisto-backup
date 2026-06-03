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

        // NEVER cache a POS-authenticated request. The GraphQL response cache key
        // (GraphQLCacheService::generateCacheKey) is built only from query +
        // variables + x-locale + x-currency — it does NOT include the POS JWT,
        // agent, or tenant. EVERY POS query (getMyOutlets, fetchCurrentAgent,
        // getOutletProducts, orders, reports, …) is per-agent/per-tenant, so a
        // cached response would be served to a DIFFERENT agent/tenant whose
        // request produced the same key (the "tbt sees FOOD's agent / Fujiba's
        // stores" cross-tenant bleed). Guarding on the `pos` guard covers every
        // current AND future POS query in one place, instead of maintaining an
        // ever-growing skip list that silently misses new queries (getMyOutlets
        // was already missing). Web POS is unaffected: it is also pos-guard
        // authenticated, and its per-agent queries were already in the skip list
        // — they simply stop being cached, which is correct.
        if (auth('pos')->check()) {
            return;
        }

        $queryName = $this->extractQueryName($query);

        // Belt-and-suspenders for any non-pos-guard path: case-INSENSITIVE skip
        // match. extractQueryName() returns the GraphQL OPERATION name
        // (`query <Name>{...}`) and clients differ in casing (web POS lowercase
        // `fetchCurrentAgent` vs RN/Apollo PascalCase `FetchCurrentAgent`), so we
        // lower-case both sides to skip regardless of casing.
        $skippable = array_map('strtolower', $this->getSkippableQueryForPos());
        $isSkippable = $queryName && in_array(strtolower($queryName), $skippable, true);

        if (
            ! $queryName
            || $isSkippable
            || ! GraphQLCacheService::shouldCache($queryName, Auth::guard('api')->check())
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
            // Per-agent identity — MUST NOT be cached. The GraphQL response cache
            // key (GraphQLCacheService::generateCacheKey) is built only from
            // query + variables + x-locale/x-currency — it does NOT include the
            // Authorization token or the agent/tenant. fetchCurrentAgent has an
            // identical query + empty variables for every agent, so caching it
            // served the first agent's record to every later agent on the same
            // worker (the "logged in as tbt but see FOOD/MMFOOD" cross-agent
            // bleed). Skipping it forces a fresh per-request resolve.
            'fetchCurrentAgent',
            'getMyOutlets',
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
