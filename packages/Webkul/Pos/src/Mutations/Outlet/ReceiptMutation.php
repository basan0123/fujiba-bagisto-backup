<?php

namespace Webkul\Pos\Mutations\Outlet;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Repositories\OutletRepository;
use Webkul\Pos\Repositories\ReceiptRepository;

/**
 * Receipt-template editor for the POS settings screen. Edits the receipt
 * attached to the authenticated agent's own outlet; if the outlet has no
 * receipt yet (pos_outlets.receipt_id is null) one is created and linked.
 */
class ReceiptMutation extends Controller
{
    public function __construct(
        protected ReceiptRepository $receiptRepository,
        protected OutletRepository $outletRepository
    ) {}

    public function update($root, array $args, GraphQLContext $context): array
    {
        $agent = auth('pos')->user();

        if (! $agent || ! $agent->outlet_id) {
            return ['success' => false, 'message' => 'No outlet bound to this session.', 'receipt' => null];
        }

        $validator = Validator::make($args, [
            'title'                => 'nullable|string|max:255',
            'headerContent'        => 'nullable|string|max:2000',
            'footerContent'        => 'nullable|string|max:2000',
            'logo'                 => 'nullable|string',
            'displayLogo'          => 'nullable|boolean',
            'displayOutletName'    => 'nullable|boolean',
            'displayOutletAddress' => 'nullable|boolean',
            'displayCashierName'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first(), 'receipt' => null];
        }

        // camelCase arg → snake_case column. display_* are int flags → cast bool to 0/1.
        $map = [
            'title'                => ['title', false],
            'headerContent'        => ['header_content', false],
            'footerContent'        => ['footer_content', false],
            'logo'                 => ['logo', false],
            'displayLogo'          => ['display_logo', true],
            'displayOutletName'    => ['display_outlet_name', true],
            'displayOutletAddress' => ['display_outlet_address', true],
            'displayCashierName'   => ['display_cashier_name', true],
        ];

        $data = [];
        foreach ($map as $arg => [$col, $isFlag]) {
            if (array_key_exists($arg, $args)) {
                $data[$col] = $isFlag ? (int) (bool) $args[$arg] : $args[$arg];
            }
        }

        $outlet = $this->outletRepository->find($agent->outlet_id);

        // A cloned tenant can carry a dangling receipt_id pointing at a row
        // that doesn't exist (the receipts table came over empty). Treat that
        // as "no receipt" and create a fresh one rather than updating nothing.
        $existing = $outlet->receipt_id
            ? $this->receiptRepository->find($outlet->receipt_id)
            : null;

        if ($existing) {
            $receipt = $this->receiptRepository->update($data, $existing->id);
        } else {
            // First-time setup: a receipt needs a title (NOT NULL); default it.
            $data['title'] = $data['title'] ?? ($outlet->name.' レシート');
            $data['status'] = 1;
            $receipt = $this->receiptRepository->create($data);
            $this->outletRepository->update(['receipt_id' => $receipt->id], $outlet->id);
        }

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.receipt.update-success', [], null) ?: 'Receipt updated.',
            'receipt' => $receipt,
        ];
    }
}
