<?php

namespace Webkul\Pos\Queries\Cashier;

use App\Http\Controllers\Controller;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Repositories\WasteEntryRepository;

class WasteQuery extends Controller
{
    /**
     * Create a new instance.
     */
    public function __construct(protected WasteEntryRepository $wasteEntryRepository) {}

    /**
     * Return waste entries submitted by the current agent.
     */
    public function getWasteEntries(mixed $rootValue, array $args, GraphQLContext $context): array
    {
        $agent = $context->user();

        return $this->wasteEntryRepository
            ->getModel()
            ->where('user_id', $agent->id)
            ->orderByDesc('wasted_at')
            ->limit(50)
            ->get()
            ->toArray();
    }
}
