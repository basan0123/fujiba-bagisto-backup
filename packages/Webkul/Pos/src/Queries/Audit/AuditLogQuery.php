<?php

namespace Webkul\Pos\Queries\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Helpers\RoleGate;

/**
 * Unified read across the three per-action audit-style tables that
 * already exist:
 *   pos_refund_log         → refund / void
 *   pos_customer_credit_log → credit add / deduct
 *   pos_stock_movement_log  → stock adjust / waste / transfer
 *
 * No new schema or migration; the resolver merges and paginates the
 * UNION client-side after capping each leg at a sane LIMIT to keep
 * memory bounded for very large datasets.
 *
 * Filters:
 *   posUserId — narrow to one cashier
 *   action    — one of refund|void|credit_add|credit_deduct|stock_adjust|waste|stock_transfer
 *   dateFrom  / dateTo (YYYY-MM-DD inclusive)
 *
 * Authorization: manager+ can see all activity; cashiers see only
 * their own rows.
 */
class AuditLogQuery
{
    private const MAX_PER_LEG = 1000;

    public function list($root, array $args, GraphQLContext $context): array
    {
        $self = $context->user();
        $isManager = RoleGate::atLeast($self, RoleGate::MANAGER);

        $posUserId = $args['posUserId'] ?? null;
        if (! $isManager) {
            $posUserId = (int) $self->id;
        }

        $action  = $args['action']  ?? null;
        $from    = $this->parseDate($args['dateFrom'] ?? null);
        $to      = $this->parseDate($args['dateTo']   ?? null, endOfDay: true);

        $rows = collect();

        if ($action === null || in_array($action, ['refund', 'void'], true)) {
            $rows = $rows->merge($this->fromRefundLog($posUserId, $action, $from, $to));
        }
        if ($action === null || in_array($action, ['credit_add', 'credit_deduct'], true)) {
            $rows = $rows->merge($this->fromCreditLog($posUserId, $action, $from, $to));
        }
        if ($action === null || in_array($action, ['stock_adjust', 'waste', 'stock_transfer'], true)) {
            $rows = $rows->merge($this->fromStockLog($posUserId, $action, $from, $to));
        }

        // Attach pos_user display names in one round-trip.
        $userIds = $rows->pluck('posUserId')->unique()->filter()->all();
        $names = [];
        if (! empty($userIds)) {
            $names = DB::table('pos_users')
                ->whereIn('id', $userIds)
                ->get(['id', 'firstname', 'lastname', 'email'])
                ->mapWithKeys(fn ($u) => [
                    (int) $u->id => trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? '')) ?: ($u->email ?? "user#{$u->id}"),
                ])->all();
        }
        $rows = $rows->map(function ($r) use ($names) {
            $r['posUserName'] = $names[$r['posUserId']] ?? null;
            return $r;
        });

        // Sort newest first.
        $rows = $rows->sortByDesc('createdAt')->values();

        $first = max(1, min(100, (int) ($args['first'] ?? 25)));
        $page  = max(1, (int) ($args['page']  ?? 1));
        $total = $rows->count();
        $sliced = $rows->slice(($page - 1) * $first, $first)->values();
        $lastPage = max(1, (int) ceil($total / $first));

        return [
            'paginatorInfo' => [
                'count'        => $sliced->count(),
                'currentPage'  => $page,
                'firstItem'    => $total > 0 ? (($page - 1) * $first + 1) : null,
                'hasMorePages' => $page < $lastPage,
                'lastItem'     => $total > 0 ? min($total, $page * $first) : null,
                'lastPage'     => $lastPage,
                'perPage'      => $first,
                'total'        => $total,
            ],
            'data' => $sliced->all(),
        ];
    }

    private function parseDate(?string $raw, bool $endOfDay = false): ?Carbon
    {
        if (! $raw) return null;
        try {
            $d = Carbon::parse($raw);
            return $endOfDay ? $d->endOfDay() : $d->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function fromRefundLog(?int $posUserId, ?string $action, ?Carbon $from, ?Carbon $to): array
    {
        $q = DB::table('pos_refund_log')
            ->orderByDesc('id')
            ->limit(self::MAX_PER_LEG);
        if ($posUserId) $q->where('pos_user_id', $posUserId);
        if ($action)    $q->where('action', $action);
        if ($from)      $q->where('created_at', '>=', $from);
        if ($to)        $q->where('created_at', '<=', $to);

        return $q->get()->map(function ($r) {
            return [
                'id'         => 'refund-'.$r->id,
                'entityType' => 'order',
                'entityId'   => (int) $r->order_id,
                'posUserId'  => (int) $r->pos_user_id,
                'action'     => $r->action,
                'amount'     => (float) $r->amount,
                'summary'    => sprintf(
                    '%s ¥%s (target=%s)%s',
                    $r->action,
                    number_format((float) $r->amount, 0),
                    $r->target ?? 'original',
                    $r->reason ? " · {$r->reason}" : '',
                ),
                'createdAt'  => (string) $r->created_at,
            ];
        })->all();
    }

    private function fromCreditLog(?int $posUserId, ?string $action, ?Carbon $from, ?Carbon $to): array
    {
        $q = DB::table('pos_customer_credit_log')
            ->orderByDesc('id')
            ->limit(self::MAX_PER_LEG);
        if ($posUserId) $q->where('pos_user_id', $posUserId);
        if ($from)      $q->where('created_at', '>=', $from);
        if ($to)        $q->where('created_at', '<=', $to);

        if ($action === 'credit_add')    $q->where('delta', '>', 0);
        if ($action === 'credit_deduct') $q->where('delta', '<', 0);

        return $q->get()->map(function ($r) {
            $delta = (float) $r->delta;
            $act = $delta >= 0 ? 'credit_add' : 'credit_deduct';
            return [
                'id'         => 'credit-'.$r->id,
                'entityType' => 'customer',
                'entityId'   => (int) $r->customer_id,
                'posUserId'  => (int) $r->pos_user_id,
                'action'     => $act,
                'amount'     => abs($delta),
                'summary'    => sprintf(
                    '%s ¥%s (balance ¥%s)%s',
                    $delta >= 0 ? '+' : '-',
                    number_format(abs($delta), 0),
                    number_format((float) $r->balance_after, 0),
                    $r->reason ? " · {$r->reason}" : '',
                ),
                'createdAt'  => (string) $r->created_at,
            ];
        })->all();
    }

    private function fromStockLog(?int $posUserId, ?string $action, ?Carbon $from, ?Carbon $to): array
    {
        $q = DB::table('pos_stock_movement_log')
            ->orderByDesc('id')
            ->limit(self::MAX_PER_LEG);
        if ($posUserId) $q->where('pos_user_id', $posUserId);
        if ($from)      $q->where('created_at', '>=', $from);
        if ($to)        $q->where('created_at', '<=', $to);

        // Map external 'stock_adjust' → DB 'adjust', 'stock_transfer' → 'transfer_out|in'
        if ($action === 'stock_adjust')    $q->where('action', 'adjust');
        if ($action === 'waste')           $q->where('action', 'waste');
        if ($action === 'stock_transfer')  $q->whereIn('action', ['transfer_out', 'transfer_in']);

        return $q->get()->map(function ($r) {
            $external = match ($r->action) {
                'adjust'        => 'stock_adjust',
                'waste'         => 'waste',
                'transfer_out',
                'transfer_in'   => 'stock_transfer',
                default         => $r->action,
            };
            $delta = (float) $r->qty_delta;
            return [
                'id'         => 'stock-'.$r->id,
                'entityType' => 'product',
                'entityId'   => (int) $r->product_id,
                'posUserId'  => (int) $r->pos_user_id,
                'action'     => $external,
                'amount'     => $delta,
                'summary'    => sprintf(
                    '%s %s%s (after %s)%s',
                    $external,
                    $delta >= 0 ? '+' : '',
                    rtrim(rtrim(number_format($delta, 4, '.', ''), '0'), '.'),
                    rtrim(rtrim(number_format((float) $r->qty_after, 4, '.', ''), '0'), '.'),
                    $r->reason ? " · {$r->reason}" : '',
                ),
                'createdAt'  => (string) $r->created_at,
            ];
        })->all();
    }
}
