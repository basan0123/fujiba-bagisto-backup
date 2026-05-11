<?php

namespace Webkul\Pos\Mutations\Outlet;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Helpers\RoleGate;

/**
 * Staff (pos_users) CRUD for the merchant-management surface. Owner-only
 * across the board per sprint defaults.
 *
 * - PIN is a separate field from password. Both bcrypt-hashed.
 * - Role enum is enforced by pos_users.role (cashier/manager/owner).
 * - Self-protection: owner cannot demote themselves nor deactivate
 *   themselves — the merchant would lock themselves out.
 */
class StaffMutation
{
    private const ROLES = [RoleGate::CASHIER, RoleGate::MANAGER, RoleGate::OWNER];

    public function create($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireOwner($context->user());

        $validator = Validator::make($args, [
            'email'     => 'required|email|max:190',
            'password'  => 'required|string|min:8|max:200',
            'firstname' => 'required|string|max:100',
            'lastname'  => 'nullable|string|max:100',
            'role'      => 'required|string|in:' . implode(',', self::ROLES),
            'pin'       => 'nullable|string|size:4|regex:/^[0-9]{4}$/',
            'status'    => 'nullable|boolean',
            'outletId'  => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $email = strtolower(trim($args['email']));
        if (DB::table('pos_users')->where('email', $email)->exists()) {
            throw new CustomException(trans('pos::app.outlet.staff.email-taken'));
        }

        $outletId = $args['outletId'] ?? $context->user()->outlet_id;

        $id = DB::table('pos_users')->insertGetId([
            'outlet_id' => $outletId,
            'email'     => $email,
            'password'  => Hash::make($args['password']),
            'pin'       => ! empty($args['pin']) ? Hash::make($args['pin']) : null,
            'firstname' => $args['firstname'],
            'lastname'  => $args['lastname'] ?? '',
            'image'     => '',
            'role'      => $args['role'],
            'status'    => array_key_exists('status', $args) ? (int) $args['status'] : 1,
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.staff.created'),
            'staff'   => $this->serialize($id),
        ];
    }

    public function update($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireOwner($context->user());

        $validator = Validator::make($args, [
            'id'        => 'required|integer',
            'email'     => 'sometimes|required|email|max:190',
            'password'  => 'sometimes|nullable|string|min:8|max:200',
            'firstname' => 'sometimes|required|string|max:100',
            'lastname'  => 'sometimes|nullable|string|max:100',
            'role'      => 'sometimes|required|string|in:' . implode(',', self::ROLES),
            'pin'       => 'sometimes|nullable|string|size:4|regex:/^[0-9]{4}$/',
            'status'    => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $target = DB::table('pos_users')->where('id', $args['id'])->first();
        if (! $target) {
            throw new CustomException(trans('pos::app.outlet.staff.email-taken'));
        }

        $self = $context->user();
        $isSelf = (int) $target->id === (int) $self->id;

        // Self-protect: owner cannot demote themselves.
        if ($isSelf && array_key_exists('role', $args) && $args['role'] !== RoleGate::OWNER) {
            throw new CustomException(trans('pos::app.outlet.staff.self-demote'));
        }
        // Self-protect: owner cannot deactivate themselves.
        if ($isSelf && array_key_exists('status', $args) && ! $args['status']) {
            throw new CustomException(trans('pos::app.outlet.staff.self-deactivate'));
        }

        // Email uniqueness (when changing)
        if (array_key_exists('email', $args)) {
            $newEmail = strtolower(trim($args['email']));
            if ($newEmail !== $target->email) {
                $taken = DB::table('pos_users')->where('email', $newEmail)->where('id', '!=', $args['id'])->exists();
                if ($taken) {
                    throw new CustomException(trans('pos::app.outlet.staff.email-taken'));
                }
            }
        }

        $update = ['updated_at' => now()];
        foreach (['firstname', 'lastname', 'role'] as $f) {
            if (array_key_exists($f, $args)) $update[$f] = $args[$f];
        }
        if (array_key_exists('email', $args)) {
            $update['email'] = strtolower(trim($args['email']));
        }
        if (array_key_exists('status', $args)) {
            $update['status'] = (int) $args['status'];
        }
        if (array_key_exists('password', $args) && ! empty($args['password'])) {
            $update['password'] = Hash::make($args['password']);
        }
        if (array_key_exists('pin', $args)) {
            $update['pin'] = $args['pin'] ? Hash::make($args['pin']) : null;
        }

        DB::table('pos_users')->where('id', $args['id'])->update($update);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.staff.updated'),
            'staff'   => $this->serialize($args['id']),
        ];
    }

    public function deactivate($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireOwner($context->user());

        $validator = Validator::make($args, [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        if ((int) $args['id'] === (int) $context->user()->id) {
            throw new CustomException(trans('pos::app.outlet.staff.self-deactivate'));
        }

        DB::table('pos_users')->where('id', $args['id'])->update([
            'status'     => 0,
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.staff.deactivated'),
            'staff'   => $this->serialize($args['id']),
        ];
    }

    protected function serialize($id): ?array
    {
        $row = DB::table('pos_users')->where('id', $id)->first();
        if (! $row) return null;
        return [
            'id'         => (int) $row->id,
            'email'      => $row->email,
            'firstname'  => $row->firstname,
            'lastname'   => $row->lastname,
            'role'       => $row->role,
            'status'     => (bool) $row->status,
            'outletId'   => $row->outlet_id,
            'hasPin'     => ! empty($row->pin),
        ];
    }
}
