<?php

namespace Webkul\Pos\Mutations\Outlet;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\GraphQLAPI\Validators\CustomException;

/**
 * Customer credit adjust — admin-only credit add/deduct, with audit
 * log and reject-on-negative-balance.
 *
 * Authorization: pos_users.role IN ('manager', 'owner').
 * Idempotency: optional idempotency_key, deduped per (pos_user_id, key).
 * Negative balance: rejected with a JP-localized exception that
 * surfaces the actual current balance to the cashier.
 */
class CustomerCreditMutation
{
    public function __construct(
        protected CustomerRepository $customerRepository,
    ) {}

    /**
     * Resolver for the customerCreditAdjust mutation.
     */
    public function adjust($root, array $args, GraphQLContext $context)
    {
        $validator = Validator::make($args, [
            'customer_id'     => 'required|integer',
            'amount'          => 'required|numeric|min:0.0001',
            'type'            => 'required|string|in:add,deduct',
            'reason'          => 'required|string|in:manual_add,manual_deduct,order_change,refund_to_credit,expiry,correction',
            'note'            => 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|min:16|max:64|regex:/^[A-Za-z0-9_-]+$/',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        // Authorization gate.
        $user = $context->user();
        $role = $user->role ?? 'cashier';
        if (! in_array($role, ['manager', 'owner'], true)) {
            throw new CustomException(trans('pos::app.outlet.credit.manager-required'));
        }

        // Idempotency: replay returns the original write's result.
        if (! empty($args['idempotency_key'])) {
            $existing = DB::table('pos_customer_credit_log')
                ->where('pos_user_id', $user->id)
                ->where('idempotency_key', $args['idempotency_key'])
                ->first();
            if ($existing) {
                return [
                    'success'       => true,
                    'message'       => trans('pos::app.outlet.credit.adjust-success'),
                    'new_balance'   => (float) $existing->balance_after,
                    'audit_row_id'  => (int) $existing->id,
                ];
            }
        }

        $customer = $this->customerRepository->find($args['customer_id']);
        if (! $customer) {
            throw new CustomException(trans('pos::app.outlet.credit.customer-missing'));
        }

        $delta = $args['type'] === 'add'
            ? (float) $args['amount']
            : -(float) $args['amount'];

        return DB::transaction(function () use ($args, $user, $customer, $delta) {
            // Current balance = sum of every prior log row for the
            // customer. Locks the customer's rows for the txn so
            // concurrent adjusts can't race past zero.
            $currentBalance = (float) DB::table('pos_customer_credit_log')
                ->where('customer_id', $customer->id)
                ->lockForUpdate()
                ->sum('delta');

            $newBalance = $currentBalance + $delta;

            if ($newBalance < 0) {
                throw new CustomException(trans('pos::app.outlet.credit.insufficient', [
                    'balance' => '¥'.number_format($currentBalance, 0),
                ]));
            }

            $now = now();
            $auditRowId = DB::table('pos_customer_credit_log')->insertGetId([
                'customer_id'     => $customer->id,
                'pos_user_id'     => $user->id,
                'delta'           => $delta,
                'balance_after'   => $newBalance,
                'reason'          => $args['reason'],
                'note'            => $args['note'] ?? null,
                'idempotency_key' => $args['idempotency_key'] ?? null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            return [
                'success'       => true,
                'message'       => trans('pos::app.outlet.credit.adjust-success'),
                'new_balance'   => $newBalance,
                'audit_row_id'  => (int) $auditRowId,
            ];
        });
    }
}
