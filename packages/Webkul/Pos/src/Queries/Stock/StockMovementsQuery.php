<?php

namespace Webkul\Pos\Queries\Stock;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Helpers\RoleGate;

/**
 * Paginated read over pos_stock_movement_log. The writers were
 * shipped in 2026-05-11 with StockMutation@adjust / transfer +
 * WasteMutation@recordWaste; this query exposes the data they
 * persist.
 *
 * Manager+ sees the whole outlet's movements; cashiers see only
 * their own rows.
 */
class StockMovementsQuery
{
    public function list($root, array $args, GraphQLContext $context): array
    {
        $self = $context->user();
        $isManager = RoleGate::atLeast($self, RoleGate::MANAGER);

        $first = max(1, min(100, (int) ($args['first'] ?? 25)));
        $page  = max(1, (int) ($args['page'] ?? 1));

        $q = DB::table('pos_stock_movement_log as m')
            ->leftJoin('product_flat as pf', function ($j) {
                $j->on('pf.product_id', '=', 'm.product_id');
            })
            ->select(
                'm.*',
                DB::raw('MIN(pf.name) as product_name'),
                DB::raw('MIN(pf.sku)  as product_sku'),
            )
            ->groupBy('m.id')
            ->orderByDesc('m.id');

        if (! $isManager) {
            $q->where('m.pos_user_id', $self->id);
        }
        if (! empty($args['productId'])) {
            $q->where('m.product_id', (int) $args['productId']);
        }
        if (! empty($args['action'])) {
            $action = $args['action'];
            if ($action === 'stock_adjust')   $q->where('m.action', 'adjust');
            elseif ($action === 'waste')      $q->where('m.action', 'waste');
            elseif ($action === 'stock_transfer') $q->whereIn('m.action', ['transfer_out', 'transfer_in']);
            else $q->where('m.action', $action);
        }
        if (! empty($args['dateFrom'])) {
            $q->where('m.created_at', '>=', Carbon::parse($args['dateFrom'])->startOfDay());
        }
        if (! empty($args['dateTo'])) {
            $q->where('m.created_at', '<=', Carbon::parse($args['dateTo'])->endOfDay());
        }

        // count without the SELECT + GROUP BY (those exist only to
        // attach product_flat columns to each row). A separate plain
        // count keeps paginator.total accurate.
        $countQuery = DB::table('pos_stock_movement_log as m');
        if (! $isManager) $countQuery->where('m.pos_user_id', $self->id);
        if (! empty($args['productId'])) $countQuery->where('m.product_id', (int) $args['productId']);
        if (! empty($args['action'])) {
            $action = $args['action'];
            if ($action === 'stock_adjust')   $countQuery->where('m.action', 'adjust');
            elseif ($action === 'waste')      $countQuery->where('m.action', 'waste');
            elseif ($action === 'stock_transfer') $countQuery->whereIn('m.action', ['transfer_out', 'transfer_in']);
            else $countQuery->where('m.action', $action);
        }
        if (! empty($args['dateFrom'])) $countQuery->where('m.created_at', '>=', Carbon::parse($args['dateFrom'])->startOfDay());
        if (! empty($args['dateTo']))   $countQuery->where('m.created_at', '<=', Carbon::parse($args['dateTo'])->endOfDay());
        $total = (int) $countQuery->count('m.id');
        $rows = $q->forPage($page, $first)->get();

        $userIds = $rows->pluck('pos_user_id')->unique()->filter()->all();
        $names = [];
        if (! empty($userIds)) {
            $names = DB::table('pos_users')->whereIn('id', $userIds)
                ->get(['id', 'firstname', 'lastname', 'email'])
                ->mapWithKeys(fn ($u) => [
                    (int) $u->id => trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? '')) ?: ($u->email ?? "user#{$u->id}"),
                ])->all();
        }

        $lastPage = max(1, (int) ceil($total / $first));

        return [
            'paginatorInfo' => [
                'count'        => $rows->count(),
                'currentPage'  => $page,
                'firstItem'    => $total > 0 ? (($page - 1) * $first + 1) : null,
                'hasMorePages' => $page < $lastPage,
                'lastItem'     => $total > 0 ? min($total, $page * $first) : null,
                'lastPage'     => $lastPage,
                'perPage'      => $first,
                'total'        => $total,
            ],
            'data' => $rows->map(fn ($r) => [
                'id'                  => (int) $r->id,
                'posUserId'           => (int) $r->pos_user_id,
                'posUserName'         => $names[(int) $r->pos_user_id] ?? null,
                'productId'           => (int) $r->product_id,
                'productName'         => $r->product_name,
                'productSku'          => $r->product_sku,
                'inventorySourceId'   => (int) $r->inventory_source_id,
                'action'              => $r->action,
                'qtyBefore'           => (float) $r->qty_before,
                'qtyDelta'            => (float) $r->qty_delta,
                'qtyAfter'            => (float) $r->qty_after,
                'reason'              => $r->reason,
                'note'                => $r->note,
                'toInventorySourceId' => $r->to_inventory_source_id !== null ? (int) $r->to_inventory_source_id : null,
                'idempotencyKey'      => $r->idempotency_key,
                'createdAt'           => (string) $r->created_at,
            ])->all(),
        ];
    }
}
