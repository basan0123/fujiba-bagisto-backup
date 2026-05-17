<?php

namespace Webkul\Pos\Queries\Agent;

use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * Returns every outlet in the current tenant. Used by the in-app
 * "店舗を切り替え" store picker.
 *
 * Today this is "every outlet in the tenant DB" — pos_users only have
 * a single outlet_id, so there is no per-user filter to apply. When
 * the gateway grows a `pos_user_outlets` bridge table, narrow the
 * `where` clause to outlets the caller's user_id has access to.
 */
class OutletsQuery
{
    public function list($root, array $args, GraphQLContext $context): array
    {
        $rows = DB::table('pos_outlets')
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        return $rows->map(fn ($r) => [
            'id'                  => (int) $r->id,
            'name'                => (string) $r->name,
            'email'               => $r->email,
            'phone'               => $r->phone,
            'website'             => $r->website,
            'customer_care_number' => $r->customer_care_number,
            'gst_number'          => $r->gst_number,
            'low_stock_qty'       => $r->low_stock_qty,
            'address'             => $r->address,
            'country'             => $r->country,
            'state'               => $r->state,
            'city'                => $r->city,
            'postcode'            => $r->postcode,
            'status'              => (int) $r->status,
            'receipt_id'          => $r->receipt_id !== null ? (string) $r->receipt_id : null,
            'inventory_source_id' => $r->inventory_source_id !== null ? (int) $r->inventory_source_id : null,
            'created_at'          => $r->created_at,
            'updated_at'          => $r->updated_at,
        ])->all();
    }
}
