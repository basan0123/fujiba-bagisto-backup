<?php

namespace Webkul\Pos\Mutations\Outlet;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Repositories\CashMovementRepository;
use Webkul\Pos\Repositories\UserDrawerRepository;

class CashMovementMutation extends Controller
{
    /**
     * Create a new instance.
     */
    public function __construct(
        protected CashMovementRepository $cashMovementRepository,
        protected UserDrawerRepository $userDrawerRepository
    ) {}

    /**
     * Record a cash movement (income / expense) from the POS.
     */
    public function recordCashMovement($root, array $args, GraphQLContext $context)
    {
        $validator = Validator::make($args, [
            'type'             => 'required|in:income,expense',
            'category'         => 'required|in:rent,electricity,gas,salary,supplies,misc,sales_other',
            'description'      => 'required|string|max:500',
            'amount'           => 'required|numeric|min:0.01',
            'movement_date'    => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return [
                'success'  => false,
                'errors'   => json_encode($validator->errors()->all()),
                'movement' => null,
            ];
        }

        $agent = $context->user();

        // Find the open drawer (if any) for the agent today
        $drawer = $this->userDrawerRepository->getModel()
            ->whereDate('created_at', today())
            ->where('user_id', $agent->id)
            ->where('status', 1)
            ->first();

        $movement = $this->cashMovementRepository->create([
            'outlet_id'        => $agent->outlet_id,
            'user_id'          => $agent->id,
            'drawer_id'        => $drawer?->id,
            'type'             => $args['type'],
            'category'         => $args['category'],
            'description'      => $args['description'],
            'amount'           => $args['amount'],
            'currency'         => core()->getBaseCurrencyCode(),
            'movement_date'    => $args['movement_date'] ?? now()->toDateString(),
            'reference_number' => $args['reference_number'] ?? null,
        ]);

        return [
            'success'  => true,
            'message'  => trans('pos::app.outlet.cash-movement.create-success'),
            'movement' => $movement,
        ];
    }
}
