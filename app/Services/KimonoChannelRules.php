<?php

namespace App\Services;

class KimonoChannelRules
{
    public const CONFIG_PREFIX = 'sales.payment_methods.kimono_channel_rules';

    public const TIME_LOCKED = '9:00-20:00';

    public static function constraintForCart($cart): ?array
    {
        if (! $cart) {
            return null;
        }

        if (! (bool) core()->getConfigData(self::CONFIG_PREFIX.'.enabled')) {
            return null;
        }

        $shipCountry = $cart->shipping_address?->country;
        if ($shipCountry !== null && strtoupper((string) $shipCountry) !== 'JP') {
            return null;
        }

        $minQty = (int) (core()->getConfigData(self::CONFIG_PREFIX.'.min_qty') ?: 1);
        $maxQty = (int) (core()->getConfigData(self::CONFIG_PREFIX.'.max_qty') ?: 3);
        $letterpackThreshold = (int) (core()->getConfigData(self::CONFIG_PREFIX.'.letterpack_qty_threshold') ?: 1);

        $totalQty = (int) ($cart->items_qty ?? 0);
        if ($totalQty === 0 && method_exists($cart, 'items') && $cart->items) {
            $totalQty = (int) $cart->items->sum('quantity');
        }

        if ($totalQty < $minQty || $totalQty > $maxQty) {
            return null;
        }

        $allowed = self::allowedPaymentMethods();
        if (empty($allowed)) {
            return null;
        }

        $isLetterpackBlue = $totalQty <= $letterpackThreshold;

        return [
            'qty'                     => $totalQty,
            'date_selectable'         => true,
            'time_locked'             => self::TIME_LOCKED,
            'allowed_payment_methods' => $allowed,
            'ship_label'              => $isLetterpackBlue ? 'letterpack' : 'letterpack_red',
            'notice_key'              => $isLetterpackBlue
                ? 'shop::app.checkout.onepage.delivery.kimono_letterpack_notice'
                : 'shop::app.checkout.onepage.delivery.kimono_letterpack_red_notice',
        ];
    }

    public static function allowedPaymentMethods(): array
    {
        $raw = core()->getConfigData(self::CONFIG_PREFIX.'.allowed_payment_methods');

        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw), fn ($v) => $v !== ''));
        }

        if (is_string($raw) && $raw !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== ''));
        }

        return ['moneytransfer'];
    }
}
