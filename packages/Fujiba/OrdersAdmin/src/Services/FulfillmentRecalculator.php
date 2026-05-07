<?php

namespace Fujiba\OrdersAdmin\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Sales\Models\Order;

class FulfillmentRecalculator
{
    /**
     * Compute the new order totals for a fulfillment-type switch.
     *
     * Pure function: takes an order + a target type, returns the columns
     * the controller should persist. Does not write to the DB itself.
     *
     * Rules (per spec):
     *   pickup        → shipping=0, cod=0, cool=0
     *   cod           → shipping unchanged, cod = config('fujiba.fees.cod'),
     *                   cool re-applied if any item requires cool
     *   bank_transfer → shipping unchanged, cod=0, cool re-applied
     *   delivery      → shipping restored from baseline, cod=0,
     *                   cool re-applied
     *
     * Baseline shipping for delivery is the order's existing shipping_amount
     * unless it was zeroed by a prior pickup switch, in which case we have
     * no record of what it was — caller can pass $deliveryShipping to
     * override. Default behavior leaves the current shipping_amount alone
     * (no-op restore) which matches "switch back to delivery from cod /
     * bank_transfer" cleanly.
     */
    public function compute(Order $order, string $type, ?float $deliveryShipping = null): array
    {
        $coolFee = (float) config('fujiba.fees.cool', 220);

        $needsCool = $this->orderNeedsCoolShipping($order);

        $shipping = (float) $order->shipping_amount;
        $cod      = 0.0;
        $cool     = 0.0;

        switch ($type) {
            case 'pickup':
                $shipping = 0.0;
                $cod      = 0.0;
                $cool     = 0.0;
                break;

            case 'cod':
                // Keep current shipping (caller may pass deliveryShipping
                // if switching from pickup). COD fee is tiered by subtotal:
                // Sagawa charges ¥330 ≤9999, ¥440 ≥10000.
                $shipping = $deliveryShipping ?? $shipping;
                $cod      = $this->resolveCodFee((float) $order->sub_total);
                $cool     = $needsCool ? $coolFee : 0.0;
                break;

            case 'bank_transfer':
                $shipping = $deliveryShipping ?? $shipping;
                $cod      = 0.0;
                $cool     = $needsCool ? $coolFee : 0.0;
                break;

            case 'delivery':
            default:
                $shipping = $deliveryShipping ?? $shipping;
                $cod      = 0.0;
                $cool     = $needsCool ? $coolFee : 0.0;
                break;
        }

        $subTotal       = (float) $order->sub_total;
        $discountAmount = (float) $order->discount_amount;
        $taxAmount      = (float) $order->tax_amount;

        $grandTotal = $subTotal - $discountAmount + $shipping + $cod + $cool + $taxAmount;

        // Bagisto stores order_currency totals + base_currency totals.
        // Same orders are always single-currency for this admin so we use
        // the same numbers for both — matches how cod_fee / base_cod_fee
        // are written by the existing checkout flow.
        return [
            'fulfillment_type'    => $type,
            'shipping_amount'     => $shipping,
            'base_shipping_amount' => $shipping,
            'cod_fee'             => $cod,
            'base_cod_fee'        => $cod,
            'cool_surcharge'      => $cool,
            'base_cool_surcharge' => $cool,
            'grand_total'         => $grandTotal,
            'base_grand_total'    => $grandTotal,
        ];
    }

    /**
     * True if any product in the order has requires_cool_shipping = 1.
     * Reads the boolean attribute via product_boolean_values (Bagisto EAV).
     */
    public function orderNeedsCoolShipping(Order $order): bool
    {
        $attributeId = DB::table('attributes')
            ->where('code', 'requires_cool_shipping')
            ->value('id');

        if (! $attributeId) {
            return false;
        }

        $productIds = $order->items
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($productIds)) {
            return false;
        }

        return DB::table('product_attribute_values')
            ->where('attribute_id', $attributeId)
            ->whereIn('product_id', $productIds)
            ->where('boolean_value', 1)
            ->exists();
    }

    /**
     * Resolve the COD fee for a given subtotal using the tiered config.
     * Tiers: each ['max' => int|null, 'fee' => int]. First tier whose max
     * is null OR whose max ≥ subtotal wins. Falls back to the legacy flat
     * fees.cod value when tiers are absent / malformed.
     */
    public function resolveCodFee(float $subTotal): float
    {
        $tiers = config('fujiba.fees.cod_tiers');

        if (! is_array($tiers) || empty($tiers)) {
            return (float) config('fujiba.fees.cod', 330);
        }

        foreach ($tiers as $tier) {
            $max = $tier['max'] ?? null;
            $fee = $tier['fee'] ?? null;
            if (! is_numeric($fee)) continue;
            if ($max === null || $subTotal <= (float) $max) {
                return (float) $fee;
            }
        }

        return (float) config('fujiba.fees.cod', 330);
    }
}
