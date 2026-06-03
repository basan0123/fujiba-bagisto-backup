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
        $args = $this->normalizeAddressInput($args);

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
        ], array_filter([
            'first_name'    => $args['first_name'],
            'last_name'     => $args['last_name'],
            'phone'         => $args['phone'],
            'email'         => $args['email'],
            'gender'        => $args['gender'] ?? null,
            'date_of_birth' => $args['date_of_birth'] ?? null,
            'password'      => Hash::make(random_int(100000, 10000000)),
        ], fn ($v) => $v !== null && $v !== ''));

        $this->upsertBillingAddress($customer->id, $args);
        $this->upsertShippingAddress($customer->id, $args);

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
        $args = $this->normalizeAddressInput($args);

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

        $customer = $this->customerRepository->update(array_filter(Arr::only($args, [
            'first_name',
            'last_name',
            'email',
            'phone',
            'gender',
            'date_of_birth',
        ]), fn ($v) => $v !== null && $v !== ''), $args['id']);

        $this->upsertBillingAddress($args['id'], $args);
        $this->upsertShippingAddress($args['id'], $args);

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

    /**
     * Accept either the legacy `address: [String]` payload or the new
     * `address1` / `address2` pair, and normalize to both so downstream
     * validation and persistence keep working unchanged.
     */
    protected function normalizeAddressInput(array $args): array
    {
        $hasLegacy = ! empty($args['address']) && is_array($args['address']);
        $line1 = $args['address1'] ?? null;
        $line2 = $args['address2'] ?? null;

        if (! $hasLegacy && ($line1 !== null || $line2 !== null)) {
            $args['address'] = array_values(array_filter([
                $line1,
                $line2,
            ], fn ($v) => $v !== null && $v !== ''));
        }

        return $args;
    }

    /**
     * Create or update the customer's billing address row.
     */
    protected function upsertBillingAddress(int $customerId, array $args): void
    {
        $this->customerAddressRepository->updateOrCreate(
            [
                'customer_id'  => $customerId,
                'address_type' => 'customer',
            ],
            array_merge(Arr::only($args, [
                'first_name',
                'last_name',
                'email',
                'phone',
                'city',
                'postcode',
                'country',
                'state',
                'gender',
                'company_name',
                'vat_id',
            ]), [
                'customer_id'     => $customerId,
                'address_type'    => 'customer',
                'address'         => implode(PHP_EOL, array_filter($args['address'])),
                'default_address' => 1,
            ])
        );
    }

    /**
     * Create or update the shipping address row when the cashier provided
     * one. If `shippingSameAsBilling` is set (or no shipping fields were
     * supplied), the billing row is reused and any prior shipping row is
     * left in place (we don't delete to avoid clobbering past order refs).
     */
    protected function upsertShippingAddress(int $customerId, array $args): void
    {
        if (! empty($args['shippingSameAsBilling'])) {
            return;
        }

        $shippingAddress1 = $args['shippingAddress1'] ?? null;
        $shippingAddress2 = $args['shippingAddress2'] ?? null;

        $hasShipping = ($shippingAddress1 !== null && $shippingAddress1 !== '')
            || ! empty($args['shippingCity'])
            || ! empty($args['shippingPostcode'])
            || ! empty($args['shippingState'])
            || ! empty($args['shippingCountry']);

        if (! $hasShipping) {
            return;
        }

        $lines = array_values(array_filter([$shippingAddress1, $shippingAddress2], fn ($v) => $v !== null && $v !== ''));

        $this->customerAddressRepository->updateOrCreate(
            [
                'customer_id'  => $customerId,
                'address_type' => 'pos_shipping',
            ],
            [
                'customer_id'  => $customerId,
                'address_type' => 'pos_shipping',
                'first_name'   => $args['shippingFirstName']   ?? ($args['first_name'] ?? null),
                'last_name'    => $args['shippingLastName']    ?? ($args['last_name']  ?? null),
                'company_name' => $args['shippingCompanyName'] ?? null,
                'phone'        => $args['shippingPhone']       ?? ($args['phone']      ?? null),
                'email'        => $args['email']               ?? null,
                'address'      => implode(PHP_EOL, $lines),
                'city'         => $args['shippingCity']        ?? null,
                'state'        => $args['shippingState']       ?? null,
                'country'      => $args['shippingCountry']     ?? null,
                'postcode'     => $args['shippingPostcode']    ?? null,
            ]
        );
    }
}
