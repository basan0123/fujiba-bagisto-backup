<?php

namespace Webkul\Pos\Queries\Outlet;

use Illuminate\Database\Eloquent\Builder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Customer\Models\Customer;

class CustomerQuery
{
    /**
     * Builder used by the @paginate(builder:) directive on getCustomers.
     *
     * Skips rows whose `email` is NULL or empty. Some Fujiba tenants have
     * thousands of legacy import rows in this shape; because the shared
     * Customer GraphQL type declares email as String!, a single such row
     * in the page returned an InvariantViolation and the entire native
     * POS customer list failed to load. These rows are also unusable for
     * POS sales (CustomerMutation requires an email), so dropping them
     * from the list view loses no functionality.
     */
    public function listForPos($root, array $args, GraphQLContext $context, $resolveInfo): Builder
    {
        return Customer::query()
            ->whereNotNull('email')
            ->where('email', '!=', '');
    }
}
