<?php

namespace Webkul\Checkout\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Checkout Rules Service
 * 
 * Manages shipping methods and COD fees based on configurable rules.
 * Supports multi-channel setups with dynamic rule application.
 */
class CheckoutRulesService
{
    protected $channelCode;
    protected $rules;

    /**
     * Read-only access for collaborator services (CartTotalsCollector, etc.).
     * The property itself stays protected so internal mutation paths are clear.
     */
    public function getRules(): array
    {
        return is_array($this->rules) ? $this->rules : [];
    }

    /**
     * PHP magic getter — preserves the long-standing `$svc->rules` reads in
     * the codebase without exposing the property directly.
     */
    public function __get(string $name)
    {
        if ($name === 'rules') {
            return $this->getRules();
        }
        return null;
    }

    /**
     * Initialize with channel code
     */
    public function __construct($channelCode = null)
    {
        $this->channelCode = $channelCode ?? core()->getCurrentChannelCode();
        $this->rules = $this->loadRules();
    }

    /**
     * Load rules for current channel
     */
    protected function loadRules()
    {
        $cacheKey = "checkout_rules_{$this->channelCode}";

        if (config('checkout-rules.global.cache_rules')) {
            return Cache::remember($cacheKey, config('checkout-rules.global.cache_duration'), function () {
                return config("checkout-rules.channels.{$this->channelCode}", 
                    config('checkout-rules.channels.default'));
            });
        }

        return config("checkout-rules.channels.{$this->channelCode}", 
            config('checkout-rules.channels.default'));
    }

    /**
     * Get valid shipping methods for cart total
     * 
     * Returns only the ONE method that matches the current cart total
     */
    public function getValidShippingMethods($cartTotal)
    {
        if (!isset($this->rules['shipping'])) {
            return [];
        }

        $methods = $this->rules['shipping']['methods'] ?? [];
        $validMethods = [];

        foreach ($methods as $key => $method) {
            $minCart = $method['min_cart'] ?? 0;
            $maxCart = $method['max_cart'] ?? PHP_INT_MAX;

            if ($cartTotal >= $minCart && $cartTotal <= $maxCart) {
                $validMethods[$key] = $method;
            }
        }

        return $validMethods;
    }

    /**
     * Get the single best matching shipping method
     * Returns null if multiple or no methods match
     */
    public function getAutoSelectShippingMethod($cartTotal)
    {
        $valid = $this->getValidShippingMethods($cartTotal);

        if (count($valid) === 1) {
            return array_values($valid)[0];
        }

        // If multiple methods valid, return first (or implement priority)
        if (count($valid) > 1) {
            return array_values($valid)[0];
        }

        return null;
    }

    /**
     * Get shipping price for cart total
     */
    public function getShippingPrice($cartTotal)
    {
        $method = $this->getAutoSelectShippingMethod($cartTotal);
        return $method['price'] ?? 0;
    }

    /**
     * Calculate COD fee based on cart total and payment method
     */
    public function getCODFee($cartTotal, $paymentMethod = null)
    {
        // Return 0 if COD not enabled
        if (!$this->rules['cod']['enabled'] ?? false) {
            return 0;
        }

        // Return 0 if payment method is not COD
        $codMethod = $this->rules['cod']['payment_method'] ?? 'cashondelivery';
        if ($paymentMethod && $paymentMethod !== $codMethod) {
            return 0;
        }

        $fees = $this->rules['cod']['fees'] ?? [];

        foreach ($fees as $feeRule) {
            $minCart = $feeRule['min_cart'] ?? 0;
            $maxCart = $feeRule['max_cart'] ?? PHP_INT_MAX;

            if ($cartTotal >= $minCart && $cartTotal <= $maxCart) {
                return $feeRule['fee'] ?? 0;
            }
        }

        return 0;
    }

    /**
     * Get all shipping methods (for admin or info)
     */
    public function getAllShippingMethods()
    {
        return $this->rules['shipping']['methods'] ?? [];
    }

    /**
     * Check if auto-select is enabled
     */
    public function isAutoSelectEnabled()
    {
        return $this->rules['shipping']['auto_select'] ?? true;
    }

    /**
     * Check if multiple shipping methods allowed
     */
    public function isMultipleMethodsAllowed()
    {
        return $this->rules['shipping']['allow_multiple'] ?? false;
    }

    /**
     * Get complete checkout state for frontend
     */
    public function getCheckoutState($cartTotal, $paymentMethod = null)
    {
        $shippingMethods = $this->getValidShippingMethods($cartTotal);
        $autoMethod = $this->getAutoSelectShippingMethod($cartTotal);
        $codFee = $this->getCODFee($cartTotal, $paymentMethod);

        return [
            'cart_total' => (float) $cartTotal,
            'shipping_methods' => $shippingMethods,
            'auto_selected_method' => $autoMethod,
            'shipping_price' => $autoMethod['price'] ?? 0,
            'shipping_method_code' => $autoMethod['code'] ?? null,
            'payment_method' => $paymentMethod,
            'cod_fee' => (float) $codFee,
            'total_with_shipping_and_cod' => (float) ($cartTotal + ($autoMethod['price'] ?? 0) + $codFee),
            'rules' => [
                'auto_select_enabled' => $this->isAutoSelectEnabled(),
                'multiple_methods_allowed' => $this->isMultipleMethodsAllowed(),
                'realtime_update' => $this->rules['cod']['realtime_update'] ?? true,
            ],
        ];
    }

    /**
     * Invalidate cache (call after rule changes)
     */
    public function invalidateCache()
    {
        Cache::forget("checkout_rules_{$this->channelCode}");
    }
}
