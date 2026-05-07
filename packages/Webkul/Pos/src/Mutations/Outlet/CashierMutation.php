<?php

namespace Webkul\Pos\Mutations\Outlet;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Repositories\UserDrawerRepository;

class CashierMutation extends Controller
{
    /**
     * Create a new repository instance
     *
     * @return void
     */
    public function __construct(protected UserDrawerRepository $userDrawerRepository) {}

    /**
     * Open a new drawer.
     */
    public function openDrawer($root, array $args, GraphQLContext $context)
    {
        $validator = Validator::make($args, [
            'opening_amount' => 'required|numeric|min:0',
            'remark'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors'  => json_encode($validator->errors()->get('*')),
            ];
        }

        $agent = $context->user();

        $this->userDrawerRepository->create(array_merge($args, [
            'base_currency'    => core()->getBaseCurrencyCode(),
            'opening_currency' => core()->getCurrentCurrencyCode(),
            'outlet_id'        => $agent->outlet_id,
            'user_id'          => $agent->id,
            'status'           => 1,
        ]));

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.drawer.create-success'),
        ];
    }

    /**
     * Close the drawer.
     */
    public function closeDrawer($root, array $args, GraphQLContext $context)
    {
        $validator = Validator::make($args, [
            'opening_amount' => 'required|numeric|min:0',
            'remark'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors'  => json_encode($validator->errors()->get('*')),
            ];
        }

        $posDrawer = $this->userDrawerRepository->getModel()
            ->whereDate('created_at', today())
            ->whereStatus(1)
            ->where('user_id', $context->user()->id)
            ->first();

        if (! $posDrawer) {
            throw new CustomException(trans('pos::app.outlet.drawer.not-opened'));
        }

        $posDrawer->update(array_merge($args, [
            'status' => 0,
        ]));

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.drawer.close-success'),
        ];
    }
}
