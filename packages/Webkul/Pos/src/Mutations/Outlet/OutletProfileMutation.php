<?php

namespace Webkul\Pos\Mutations\Outlet;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Repositories\OutletRepository;

/**
 * Store-profile editor for the POS app's StoreProfileScreen. The cashier
 * can only ever edit their own outlet (resolved from the auth token), so
 * there is no outletId argument — preventing one outlet from editing
 * another within the same tenant.
 */
class OutletProfileMutation extends Controller
{
    public function __construct(
        protected OutletRepository $outletRepository
    ) {}

    public function update($root, array $args, GraphQLContext $context): array
    {
        $agent = auth('pos')->user();

        if (! $agent || ! $agent->outlet_id) {
            return ['success' => false, 'message' => 'No outlet bound to this session.', 'outlet' => null];
        }

        $validator = Validator::make($args, [
            'name'          => 'sometimes|required|string|max:255',
            'tagline'       => 'nullable|string|max:255',
            'brandColor'    => 'nullable|string|max:32',
            'phone'         => 'nullable|string|max:50',
            'email'         => 'nullable|email|max:255',
            'address'       => 'nullable|string|max:500',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'postcode'      => 'nullable|string|max:20',
            // 適格請求書発行事業者番号: T + 13 digits, or empty if unregistered.
            'gstNumber'     => 'nullable|string|regex:/^T?[0-9]{0,13}$/',
            // JSON-encoded weekday→hours map; validated as decodable below.
            'businessHours' => 'nullable|string',
            'taxMode'       => 'nullable|in:inclusive,exclusive',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()->first(),
                'outlet'  => null,
            ];
        }

        if (! empty($args['businessHours'])) {
            json_decode($args['businessHours']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'message' => 'businessHours must be valid JSON.', 'outlet' => null];
            }
        }

        // Map camelCase args → snake_case columns; only set keys the caller sent.
        $map = [
            'name' => 'name', 'tagline' => 'tagline', 'brandColor' => 'brand_color',
            'phone' => 'phone', 'email' => 'email',
            'address' => 'address', 'city' => 'city', 'state' => 'state',
            'country' => 'country', 'postcode' => 'postcode',
            'gstNumber' => 'gst_number', 'businessHours' => 'business_hours',
            'taxMode' => 'tax_mode',
        ];

        $data = [];
        foreach ($map as $arg => $col) {
            if (array_key_exists($arg, $args)) {
                $data[$col] = $args[$arg];
            }
        }

        $outlet = $this->outletRepository->update($data, $agent->outlet_id);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.profile.update-success', [], null) ?: 'Store profile updated.',
            'outlet'  => $outlet,
        ];
    }
}
