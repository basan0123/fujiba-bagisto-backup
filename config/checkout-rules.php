<?php

/**
 * Bagisto Checkout Rules Configuration
 * 
 * Defines shipping methods and COD fee logic per channel.
 * Supports multi-tenant, multi-channel setups without hardcoding.
 * 
 * Usage:
 *   $rules = config('checkout-rules.channels.' . $channelCode);
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Channel-Based Checkout Rules
    |--------------------------------------------------------------------------
    |
    | Define shipping methods and COD fees per channel.
    | Each channel can have different rules.
    */

    'channels' => [
        'mmfood' => [
            'enabled' => true,
            'currency' => 'JPY',
            
            'shipping' => [
                'threshold' => 8000,  // Cart total threshold
                
                'methods' => [
                    'free' => [
                        'code' => 'free-shipping',
                        'label' => '送料無料',
                        'price' => 0,
                        'min_cart' => 8000,  // Show when cart >= 8000
                        'max_cart' => null,  // No upper limit
                    ],
                    'flat' => [
                        'code' => 'flat-rate',
                        'label' => '宅配便',
                        'price' => 650,
                        'min_cart' => 0,     // Show when cart < 8000
                        'max_cart' => 7999,
                    ],
                ],
                
                'auto_select' => true,  // Auto-select valid method
                'allow_multiple' => false,  // Only one method visible at a time
            ],

            'cod' => [
                'enabled' => true,
                'payment_method' => 'cashondelivery',  // Payment method code
                
                'fees' => [
                    [
                        'min_cart' => 10000,
                        'max_cart' => null,
                        'fee' => 440,
                        'label' => 'COD手数料',
                    ],
                    [
                        'min_cart' => 0,
                        'max_cart' => 9999,
                        'fee' => 330,
                        'label' => 'COD手数料',
                    ],
                ],
                
                'realtime_update' => true,  // Update on cart change
            ],

            'totals' => [
                'include_shipping' => true,
                'include_cod_fee' => true,
                'tax_on_shipping' => false,  // Tax on shipping cost
                'tax_on_cod' => false,  // Tax on COD fee
                'tax_rate' => 0.10,          // Japan consumption tax (10%)
            ],
        ],

        // Add more channels as needed
        'default' => [
            'enabled' => true,
            'currency' => 'JPY',
            
            'shipping' => [
                'threshold' => 5000,
                'methods' => [
                    'free' => [
                        'code' => 'free-shipping',
                        'label' => 'Free Shipping',
                        'price' => 0,
                        'min_cart' => 5000,
                        'max_cart' => null,
                    ],
                    'flat' => [
                        'code' => 'flat-rate',
                        'label' => 'Flat Rate',
                        'price' => 500,
                        'min_cart' => 0,
                        'max_cart' => 4999,
                    ],
                ],
                'auto_select' => true,
                'allow_multiple' => false,
            ],

            'cod' => [
                'enabled' => true,
                'payment_method' => 'cashondelivery',
                'fees' => [
                    [
                        'min_cart' => 5000,
                        'max_cart' => null,
                        'fee' => 300,
                        'label' => 'COD Fee',
                    ],
                    [
                        'min_cart' => 0,
                        'max_cart' => 4999,
                        'fee' => 200,
                        'label' => 'COD Fee',
                    ],
                ],
                'realtime_update' => true,
            ],

            'totals' => [
                'include_shipping' => true,
                'include_cod_fee' => true,
                'tax_on_shipping' => false,
                'tax_on_cod' => false,
                'tax_rate' => 0.10,          // Japan consumption tax (10%)
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Settings
    |--------------------------------------------------------------------------
    */

    'global' => [
        'enable_checkout_rules' => true,
        'cache_rules' => true,
        'cache_duration' => 3600,  // seconds
    ],
];
