<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kimono "+3 times" loyalty rank
    |--------------------------------------------------------------------------
    |
    | A customer is auto-promoted into the `target_group_code` group once they
    | have at least `threshold` distinct COMPLETED orders that each contain at
    | least one product belonging to the kimono category subtree.
    |
    | Rules baked in (see PromoteCustomerRankService):
    |   - Promotion only. Customers are never demoted.
    |   - Customers in any `protected_group_codes` group are NEVER touched
    |     (Wholesale always wins).
    |   - The discount itself is NOT defined here. The admin attaches a
    |     Catalog Rule (Marketing -> Catalog Rules) to the target group.
    |
    */

    'kimono_plus3' => [
        // Group customers get promoted into. Must exist in `customer_groups`.
        'target_group_code' => 'kimono_plus3',

        // Number of distinct completed kimono orders required.
        'threshold' => 3,

        // Only customers currently in one of these groups are eligible for
        // promotion. Anyone outside this list (e.g. wholesale) is left alone.
        'promotable_from_group_codes' => ['general'],

        // Groups that are never modified, even if they qualify. Wholesale wins.
        'protected_group_codes' => ['guest', 'wholesale'],

        // Root kimono category ids. The service expands these to their full
        // nested-set subtrees, so adding/removing kimono sub-categories in
        // admin needs no code change. For this store, "All Kimono" = 16.
        'kimono_root_category_ids' => [16],

        // Order statuses that count toward the threshold.
        'qualifying_order_statuses' => ['completed'],
    ],
];
