<?php

namespace Webkul\Pos\Mutations\Outlet\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Repositories\UserRepository;

class AccountMutation extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Update the agent profile.
     */
    public function update($rootValue, array $args, GraphQLContext $context): array
    {
        $agent = $context->user();

        $validator = Validator::make($args, [
            'firstname'    => 'required',
            'lastname'     => 'required',
            'email'        => "required|email|unique:pos_users,email,$agent->id",
            'old_password' => 'required_with:new_password',
            'new_password' => 'confirmed|min:6|required_with:old_password|min:6|max:16',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors'  => json_encode($validator->errors()->get('*')),
            ];
        }

        if (
            ! empty($args['old_password'])
            && ! Hash::check($args['old_password'], $agent->password)) {
            return [
                'success' => false,
                'message' => trans('pos::app.outlet.agents.account.update.invalid-password'),
            ];
        } elseif (! empty($args['new_password'])) {
            $args['password'] = $args['new_password'];
        }

        Event::dispatch('pos.outlet.agents.update.before', $agent);

        $agent = $this->userRepository->update(Arr::only($args, [
            'firstname',
            'lastname',
            'email',
            'password',
        ]), $agent->id);

        if (! empty($args['low_stock_qty'])) {
            $agent->outlet->update(['low_stock_qty' => $args['low_stock_qty']]);
        }

        Event::dispatch('pos.outlet.agents.update.after', $agent);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.agents.account.update.success'),
            'agent'   => $agent,
        ];
    }

    /**
     * Toggle the agent's training_mode + training_print_receipt flags.
     *
     * Stored on pos_users so the mode follows the cashier across devices.
     * Read at checkout to decide whether to persist the order.
     */
    public function setTraining($rootValue, array $args, GraphQLContext $context): array
    {
        $agent = $context->user();

        $this->userRepository->update([
            'training_mode'           => (bool) ($args['training_mode'] ?? false),
            'training_print_receipt'  => (bool) ($args['training_print_receipt'] ?? false),
        ], $agent->id);

        $agent = $this->userRepository->find($agent->id);

        return [
            'success'              => true,
            'message'              => trans('pos::app.outlet.agents.account.update.success'),
            'trainingMode'         => (bool) $agent->training_mode,
            'trainingPrintReceipt' => (bool) $agent->training_print_receipt,
        ];
    }
}
