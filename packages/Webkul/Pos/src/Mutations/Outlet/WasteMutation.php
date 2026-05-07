<?php

namespace Webkul\Pos\Mutations\Outlet;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Repositories\WasteEntryRepository;

class WasteMutation extends Controller
{
    /**
     * Create a new instance.
     */
    public function __construct(protected WasteEntryRepository $wasteEntryRepository) {}

    /**
     * Record a waste entry from the POS.
     */
    public function recordWaste($root, array $args, GraphQLContext $context)
    {
        $validator = Validator::make($args, [
            'product_name' => 'required|string|max:255',
            'product_sku'  => 'nullable|string|max:100',
            'quantity'     => 'required|integer|min:1',
            'unit_cost'    => 'nullable|numeric|min:0',
            'reason'       => 'required|in:damaged,expired,theft,spoilage,other',
            'notes'        => 'nullable|string',
            'wasted_at'    => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors'  => json_encode($validator->errors()->all()),
                'entry'   => null,
            ];
        }

        $agent     = $context->user();
        $unitCost  = $args['unit_cost'] ?? 0;
        $quantity  = $args['quantity'];
        $totalCost = $unitCost * $quantity;

        $entry = $this->wasteEntryRepository->create([
            'outlet_id'    => $agent->outlet_id,
            'user_id'      => $agent->id,
            'product_id'   => $args['product_id'] ?? null,
            'product_name' => $args['product_name'],
            'product_sku'  => $args['product_sku'] ?? null,
            'quantity'     => $quantity,
            'unit_cost'    => $unitCost,
            'total_cost'   => $totalCost,
            'reason'       => $args['reason'],
            'notes'        => $args['notes'] ?? null,
            'status'       => 'pending',
            'wasted_at'    => $args['wasted_at'] ?? now()->toDateString(),
        ]);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.waste.create-success'),
            'entry'   => $entry,
        ];
    }
}
