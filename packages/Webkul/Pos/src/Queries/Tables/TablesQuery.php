<?php

namespace Webkul\Pos\Queries\Tables;

use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * Restaurant-mode table list. Scoped to the caller's outlet — there
 * is no cross-outlet visibility because each outlet's floor plan
 * stands on its own.
 *
 * Tables are returned ordered by position_y then position_x then
 * label, so a "scan the floor left-to-right top-to-bottom" loop
 * Just Works without the client having to sort.
 */
class TablesQuery
{
    public function list($root, array $args, GraphQLContext $context): array
    {
        $outletId = (int) $context->user()->outlet_id;

        $rows = DB::table('pos_tables')
            ->where('outlet_id', $outletId)
            ->orderBy('position_y')
            ->orderBy('position_x')
            ->orderBy('label')
            ->get();

        return $rows->map(fn ($r) => $this->shape($r))->all();
    }

    public function get($root, array $args, GraphQLContext $context)
    {
        $outletId = (int) $context->user()->outlet_id;
        $id = (int) $args['id'];

        $row = DB::table('pos_tables')
            ->where('outlet_id', $outletId)
            ->where('id', $id)
            ->first();

        return $row ? $this->shape($row) : null;
    }

    public static function shape(object $r): array
    {
        return [
            'id'         => (int) $r->id,
            'outletId'   => (int) $r->outlet_id,
            'label'      => (string) $r->label,
            'seatCount'  => (int) $r->seat_count,
            'positionX'  => (int) $r->position_x,
            'positionY'  => (int) $r->position_y,
            'status'     => (string) $r->status,
            'note'       => $r->note,
            'createdAt'  => (string) $r->created_at,
            'updatedAt'  => (string) $r->updated_at,
        ];
    }
}
