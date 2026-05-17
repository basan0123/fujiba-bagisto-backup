<?php

namespace Webkul\Pos\Mutations\Outlet;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Helpers\RoleGate;
use Webkul\Pos\Queries\Tables\TablesQuery;

class TableMutation
{
    /** Valid status values — kept as a class const so the seed UI
     *  and the mutation share the source of truth. */
    private const STATUSES = ['free', 'occupied', 'reserved', 'dirty'];

    public function create($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $outletId = (int) $context->user()->outlet_id;
        $args['outletId'] = $outletId;

        $v = Validator::make($args, [
            'label'     => 'required|string|max:32',
            'seatCount' => 'nullable|integer|min:1|max:99',
            'positionX' => 'nullable|integer',
            'positionY' => 'nullable|integer',
            'status'    => 'nullable|string|in:' . implode(',', self::STATUSES),
            'note'      => 'nullable|string|max:250',
        ]);
        if ($v->fails()) {
            return ['success' => false, 'message' => $v->errors()->first(), 'table' => null];
        }

        // unique (outlet, label) gate — surface a friendly message
        // instead of letting the unique-index throw raw.
        $dup = DB::table('pos_tables')
            ->where('outlet_id', $outletId)
            ->where('label', $args['label'])
            ->exists();
        if ($dup) {
            return ['success' => false, 'message' => 'Label already exists for this outlet.', 'table' => null];
        }

        $id = DB::table('pos_tables')->insertGetId([
            'outlet_id'   => $outletId,
            'label'       => $args['label'],
            'seat_count'  => (int) ($args['seatCount'] ?? 2),
            'position_x'  => (int) ($args['positionX'] ?? 0),
            'position_y'  => (int) ($args['positionY'] ?? 0),
            'status'      => $args['status'] ?? 'free',
            'note'        => $args['note'] ?? null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Table created.',
            'table'   => TablesQuery::shape(DB::table('pos_tables')->where('id', $id)->first()),
        ];
    }

    public function update($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $outletId = (int) $context->user()->outlet_id;
        $id = (int) $args['id'];

        $row = DB::table('pos_tables')->where('id', $id)->where('outlet_id', $outletId)->first();
        if (! $row) {
            return ['success' => false, 'message' => 'Table not found.', 'table' => null];
        }

        $v = Validator::make($args, [
            'label'     => 'sometimes|string|max:32',
            'seatCount' => 'nullable|integer|min:1|max:99',
            'positionX' => 'nullable|integer',
            'positionY' => 'nullable|integer',
            'status'    => 'nullable|string|in:' . implode(',', self::STATUSES),
            'note'      => 'nullable|string|max:250',
        ]);
        if ($v->fails()) {
            return ['success' => false, 'message' => $v->errors()->first(), 'table' => null];
        }

        // Same outlet+label uniqueness on label change.
        if (! empty($args['label']) && $args['label'] !== $row->label) {
            $dup = DB::table('pos_tables')
                ->where('outlet_id', $outletId)
                ->where('label', $args['label'])
                ->where('id', '!=', $id)
                ->exists();
            if ($dup) {
                return ['success' => false, 'message' => 'Label already exists for this outlet.', 'table' => null];
            }
        }

        $patch = ['updated_at' => now()];
        foreach ([
            'label' => 'label',
            'seatCount' => 'seat_count',
            'positionX' => 'position_x',
            'positionY' => 'position_y',
            'status' => 'status',
            'note' => 'note',
        ] as $gql => $col) {
            if (array_key_exists($gql, $args) && $args[$gql] !== null) {
                $patch[$col] = $args[$gql];
            }
        }

        DB::table('pos_tables')->where('id', $id)->update($patch);

        return [
            'success' => true,
            'message' => 'Table updated.',
            'table'   => TablesQuery::shape(DB::table('pos_tables')->where('id', $id)->first()),
        ];
    }

    public function delete($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $outletId = (int) $context->user()->outlet_id;
        $id = (int) $args['id'];

        $deleted = DB::table('pos_tables')
            ->where('id', $id)
            ->where('outlet_id', $outletId)
            ->delete();

        return [
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'Table deleted.' : 'Table not found.',
            'table'   => null,
        ];
    }

    /** Lightweight setStatus used by tap-on-floor-map flows.
     *  Allowed for cashiers (everyone seats customers). */
    public function setStatus($root, array $args, GraphQLContext $context): array
    {
        $outletId = (int) $context->user()->outlet_id;
        $id = (int) $args['id'];
        $status = (string) $args['status'];

        if (! in_array($status, self::STATUSES, true)) {
            return ['success' => false, 'message' => 'Invalid status.', 'table' => null];
        }

        $updated = DB::table('pos_tables')
            ->where('id', $id)
            ->where('outlet_id', $outletId)
            ->update(['status' => $status, 'updated_at' => now()]);

        if ($updated === 0) {
            return ['success' => false, 'message' => 'Table not found.', 'table' => null];
        }

        return [
            'success' => true,
            'message' => 'Status updated.',
            'table'   => TablesQuery::shape(DB::table('pos_tables')->where('id', $id)->first()),
        ];
    }
}
