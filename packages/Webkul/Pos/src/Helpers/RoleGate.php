<?php

namespace Webkul\Pos\Helpers;

use Webkul\GraphQLAPI\Validators\CustomException;

/**
 * Centralised role-based authorization for POS GraphQL mutations.
 *
 * pos_users.role enum is ['cashier','manager','owner']. Gates use the
 * Bagisto/POS convention of throwing CustomException with a localised
 * message; resolvers stay terse.
 *
 * Usage:
 *   RoleGate::requireManager($context->user());     // manager+owner
 *   RoleGate::requireOwner($context->user());        // owner only
 *   RoleGate::require($context->user(), ['cashier','manager','owner']); // any
 */
class RoleGate
{
    public const CASHIER = 'cashier';
    public const MANAGER = 'manager';
    public const OWNER   = 'owner';

    public const RANK = [
        self::CASHIER => 1,
        self::MANAGER => 2,
        self::OWNER   => 3,
    ];

    public static function require($user, array $allowed): void
    {
        $role = static::roleOf($user);
        if (! in_array($role, $allowed, true)) {
            throw new CustomException(trans('pos::app.outlet.role-gate.denied', [
                'role'    => $role,
                'allowed' => implode(', ', $allowed),
            ]));
        }
    }

    public static function requireManager($user): void
    {
        static::require($user, [self::MANAGER, self::OWNER]);
    }

    public static function requireOwner($user): void
    {
        static::require($user, [self::OWNER]);
    }

    public static function requireCashier($user): void
    {
        static::require($user, [self::CASHIER, self::MANAGER, self::OWNER]);
    }

    /**
     * Helper: rank comparison (cashier < manager < owner).
     */
    public static function atLeast($user, string $minRole): bool
    {
        $role = static::roleOf($user);
        return (static::RANK[$role] ?? 0) >= (static::RANK[$minRole] ?? 99);
    }

    protected static function roleOf($user): string
    {
        return is_object($user) ? ($user->role ?? self::CASHIER) : self::CASHIER;
    }
}
