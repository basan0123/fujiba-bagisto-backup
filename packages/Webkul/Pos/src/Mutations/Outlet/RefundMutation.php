<?php

namespace Webkul\Pos\Mutations\Outlet;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Helpers\RoleGate;

/**
 * Refund + void mutations for the merchant-management surface.
 *
 * - `refundOrder(orderId, amount, target, reason, items?, note?, idempotencyKey?)`
 *   cashier+ for amount ≤ ¥3,000, manager+ above that. Records to
 *   pos_refund_log + creates a refund row inside the same DB::transaction.
 *
 * - `voidOrder(orderId, reason)` cashier+, **same shift only** — checks the
 *   order's pos_order.created_at is within the caller's last open drawer
 *   session. Beyond that window only manager+ can void via the refund flow.
 *
 * Reason enum: customer_request / wrong_item / defective / mistake / other.
 * Target enum: cash / credit / original.
 */
class RefundMutation
{
    private const REFUND_REASONS = ['customer_request', 'wrong_item', 'defective', 'mistake', 'other'];
    private const REFUND_TARGETS = ['cash', 'credit', 'original'];
    private const MANAGER_THRESHOLD_YEN = 3000;

    public function refund($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'orderId'        => 'required|integer',
            'amount'         => 'required|numeric|min:0.0001',
            'target'         => 'required|string|in:' . implode(',', self::REFUND_TARGETS),
            'reason'         => 'required|string|in:' . implode(',', self::REFUND_REASONS),
            'note'           => 'nullable|string|max:500',
            'items'          => 'nullable|array',
            'items.*.itemId' => 'required_with:items|integer',
            'items.*.qty'    => 'required_with:items|integer|min:1',
            'idempotencyKey' => 'nullable|string|min:16|max:64|regex:/^[A-Za-z0-9_-]+$/',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $user = $context->user();

        // Tier gate
        if ($args['amount'] > self::MANAGER_THRESHOLD_YEN && ! RoleGate::atLeast($user, RoleGate::MANAGER)) {
            throw new CustomException(trans('pos::app.outlet.orders.manager-required-large'));
        }

        if ($replay = $this->idempotencyReplay($user->id, $args['idempotencyKey'] ?? null)) {
            return $replay;
        }

        $order = DB::table('orders')->where('id', $args['orderId'])->first();
        if (! $order) {
            throw new CustomException(trans('pos::app.outlet.orders.not-found'));
        }

        return DB::transaction(function () use ($user, $args) {
            $logId = DB::table('pos_refund_log')->insertGetId([
                'pos_user_id'     => $user->id,
                'order_id'        => $args['orderId'],
                'action'          => 'refund',
                'target'          => $args['target'],
                'amount'          => $args['amount'],
                'reason'          => $args['reason'],
                'note'            => $args['note'] ?? null,
                'items'           => isset($args['items']) ? json_encode($args['items']) : null,
                'idempotency_key' => $args['idempotencyKey'] ?? null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return [
                'success'    => true,
                'message'    => trans('pos::app.outlet.orders.refund-success'),
                'auditRowId' => $logId,
                'amount'     => (float) $args['amount'],
                'target'     => $args['target'],
            ];
        });
    }

    public function void($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'orderId'        => 'required|integer',
            'reason'         => 'required|string|in:' . implode(',', self::REFUND_REASONS),
            'note'           => 'nullable|string|max:500',
            'idempotencyKey' => 'nullable|string|min:16|max:64|regex:/^[A-Za-z0-9_-]+$/',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $user = $context->user();

        if ($replay = $this->idempotencyReplay($user->id, $args['idempotencyKey'] ?? null)) {
            return $replay;
        }

        // Same-shift check via pos_order.user_id + pos_order.created_at within
        // the caller's most recent (still-open) drawer session.
        $posOrder = DB::table('pos_order')->where('order_id', $args['orderId'])->first();
        if (! $posOrder) {
            throw new CustomException(trans('pos::app.outlet.orders.not-found'));
        }

        // Find the caller's most recent open drawer session if any.
        $openSession = DB::table('user_drawer')
            ->where('user_id', $user->id)
            ->whereNull('closed_at')
            ->orderBy('id', 'desc')
            ->first();

        $voidAllowed = false;
        if ($openSession) {
            $voidAllowed = strtotime($posOrder->created_at) >= strtotime($openSession->opened_at);
        }

        // Manager+owner can void any time
        if (! $voidAllowed && ! RoleGate::atLeast($user, RoleGate::MANAGER)) {
            throw new CustomException(trans('pos::app.outlet.orders.void-too-old'));
        }

        return DB::transaction(function () use ($user, $args) {
            $logId = DB::table('pos_refund_log')->insertGetId([
                'pos_user_id'     => $user->id,
                'order_id'        => $args['orderId'],
                'action'          => 'void',
                'target'          => 'original',
                'amount'          => 0,
                'reason'          => $args['reason'],
                'note'            => $args['note'] ?? null,
                'idempotency_key' => $args['idempotencyKey'] ?? null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Mark order canceled via Bagisto core column if available.
            DB::table('orders')
                ->where('id', $args['orderId'])
                ->update(['status' => 'canceled', 'updated_at' => now()]);

            return [
                'success'    => true,
                'message'    => trans('pos::app.outlet.orders.void-success'),
                'auditRowId' => $logId,
            ];
        });
    }

    protected function idempotencyReplay(int $userId, ?string $key): ?array
    {
        if (! $key) return null;
        $existing = DB::table('pos_refund_log')
            ->where('pos_user_id', $userId)
            ->where('idempotency_key', $key)
            ->first();
        if (! $existing) return null;
        return [
            'success'    => true,
            'message'    => $existing->action === 'refund'
                ? trans('pos::app.outlet.orders.refund-success')
                : trans('pos::app.outlet.orders.void-success'),
            'auditRowId' => (int) $existing->id,
            'amount'     => (float) $existing->amount,
            'target'     => $existing->target,
        ];
    }
}
