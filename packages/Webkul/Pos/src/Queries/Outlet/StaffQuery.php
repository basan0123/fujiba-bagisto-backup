<?php

namespace Webkul\Pos\Queries\Outlet;

use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Helpers\RoleGate;

class StaffQuery
{
    public function list($root, array $args, GraphQLContext $context): array
    {
        // List visible to manager+owner; cashiers see only themselves.
        $self = $context->user();
        $rows = DB::table('pos_users')
            ->orderBy('id', 'ASC')
            ->select('id', 'email', 'firstname', 'lastname', 'role', 'status', 'outlet_id', 'pin')
            ->get();

        if (! RoleGate::atLeast($self, RoleGate::MANAGER)) {
            $rows = $rows->where('id', $self->id);
        }

        return $rows->values()->map(fn ($r) => [
            'id'        => (int) $r->id,
            'email'     => $r->email,
            'firstname' => $r->firstname,
            'lastname'  => $r->lastname,
            'role'      => $r->role,
            'status'    => (bool) $r->status,
            'outletId'  => $r->outlet_id,
            'hasPin'    => ! empty($r->pin),
        ])->all();
    }
}
