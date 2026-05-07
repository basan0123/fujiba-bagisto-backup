<?php

namespace Webkul\Pos\Mutations\Outlet;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Customer\Repositories\CustomerRepository;

class CustomerMutation extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected CustomerAddressRepository $customerAddressRepository
    ) {}

    /**
     * Create a new customer.
     */
    public function create($root, array $args, GraphQLContext $context): array
    {
        $rules = [
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'phone'      => 'required|string',
            'email'      => 'required|email',
            'address'    => 'required|array',
            'address.*'  => 'required|string',
            'city'       => 'required|string',
            'country'    => 'required|string',
            'state'      => 'required|string',
            'postcode'   => 'required|string',
        ];

        $validator = Validator::make($args, $rules);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors'  => json_encode($validator->errors()->get('*')),
            ];
        }

        $customer = $this->customerRepository->updateOrCreate([
            'email' => $args['email'],
        ], [
            'first_name' => $args['first_name'],
            'last_name'  => $args['last_name'],
            'phone'      => $args['phone'],
            'email'      => $args['email'],
            'password'   => Hash::make(random_int(100000, 10000000)),
        ]);

        $addressData = array_merge(Arr::only($args, [
            'first_name',
            'last_name',
            'email',
            'phone',
            'city',
            'postcode',
            'country',
            'state',
        ]),
            [
                'customer_id' => $customer->id,
                'address'     => implode(PHP_EOL, array_filter($args['address'])),
            ]
        );

        $this->customerAddressRepository->updateOrCreate(
            ['customer_id' => $customer->id],
            $addressData
        );

        return [
            'success'  => true,
            'message'  => $customer->wasRecentlyCreated
                ? trans('pos::app.outlet.customers.create-success')
                : trans('pos::app.outlet.customers.update-success'),
            'customer' => $customer,
        ];
    }

    /**
     * Update a customer.
     */
    public function update($root, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'id'         => 'required|integer',
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'phone'      => 'required|string|unique:customers,phone,'.$args['id'],
            'email'      => 'required|email|unique:customers,email,'.$args['id'],
            'address'    => 'required|array',
            'address.*'  => 'required|string',
            'city'       => 'required|string',
            'country'    => 'required|string',
            'state'      => 'required|string',
            'postcode'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors'  => json_encode($validator->errors()->get('*')),
            ];
        }

        $customer = $this->customerRepository->update(Arr::only($args, [
            'first_name',
            'last_name',
            'email',
            'phone',
        ]), $args['id']);

        $this->customerAddressRepository->updateOrCreate(
            ['customer_id' => $args['id']],
            array_merge(Arr::only($args, [
                'first_name',
                'last_name',
                'email',
                'phone',
                'city',
                'postcode',
                'country',
                'state',
            ]), [
                'customer_id' => $args['id'],
                'address'     => implode(PHP_EOL, array_filter($args['address'])),
            ])
        );

        return [
            'success'  => true,
            'message'  => trans('pos::app.outlet.customers.update-success'),
            'customer' => $customer,
        ];
    }

    /**
     * Delete a customer.
     */
    public function delete($root, array $args, GraphQLContext $context): array
    {
        try {
            $customer = $this->customerRepository->findOrFail($args['id']);

            if (! $this->customerRepository->haveActiveOrders($customer)) {
                $customer->delete();

                return [
                    'success' => true,
                    'message' => trans('pos::app.outlet.customers.delete-success'),
                ];
            }

            return [
                'success' => false,
                'message' => trans('pos::app.outlet.customers.pending-orders'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => trans('pos::app.outlet.customers.delete-failed'),
            ];
        }
    }
}
