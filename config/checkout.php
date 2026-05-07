<?php

/**
 * Checkout Configuration for Myanmar Food Store
 * 
 * Shipping Rules:
 * - Cart >= 8000 JPY: Free Shipping (0 JPY)
 * - Cart < 8000 JPY: Flat Rate (650 JPY)
 * 
 * COD Fees (per channel):
 * - (Subtotal + Shipping) >= 10000 JPY: 440 JPY
 * - (Subtotal + Shipping) < 10000 JPY: 330 JPY
 */

return [
    /**
     * Shipping Method Auto-Selection Rules
     * 
     * Rules format:
     * [
     *     'cart_threshold' => 8000,
     *     'methods' => [
     *         'free_shipping' => ['code' => 'free-shipping', 'price' => 0, 'min_cart' => 8000],
     *         'flat_rate' => ['code' => 'flat-rate', 'price' => 650, 'max_cart' => 7999],
     *     ]
     * ]
     */
    'shipping_rules' => [
        // Default channel
        'default' => [
            'cart_threshold' => 8000,
            'methods' => [
                'free_shipping' => [
                    'code' => 'free-shipping',
                    'price' => 0,
                    'min_cart' => 8000,
                    'label' => 'Rule Base (Free Shipping)',
                ],
                'flat_rate' => [
                    'code' => 'flat-rate',
                    'price' => 650,
                    'max_cart' => 7999,
                    'label' => 'Flat Rate',
                ],
            ],
        ],
        // Myanmar Food Store channel
        'mmfood' => [
            'cart_threshold' => 8000,
            'methods' => [
                'free_shipping' => [
                    'code' => 'free-shipping',
                    'price' => 0,
                    'min_cart' => 8000,
                    'label' => 'Rule Base (Free Shipping)',
                ],
                'flat_rate' => [
                    'code' => 'flat-rate',
                    'price' => 650,
                    'max_cart' => 7999,
                    'label' => 'Flat Rate',
                ],
            ],
        ],
    ],

    /**
     * COD Fee Configuration (per channel)
     * 
     * Format:
     * [
     *     'min_amount' => 10000,
     *     'amount' => 440,
     * ]
     * 
     * Applied in descending order of min_amount
     */
    'cod_fees' => [
        // Default channel
        'default' => [
            [
                'min_amount' => 10000,
                'amount' => 440,
                'label' => 'COD Fee (≥ ¥10,000)',
            ],
            [
                'min_amount' => 0,
                'amount' => 330,
                'label' => 'COD Fee (< ¥10,000)',
            ],
        ],
        // Myanmar Food Store channel
        'mmfood' => [
            [
                'min_amount' => 10000,
                'amount' => 440,
                'label' => 'COD Fee (≥ ¥10,000)',
            ],
            [
                'min_amount' => 0,
                'amount' => 330,
                'label' => 'COD Fee (< ¥10,000)',
            ],
        ],
    ],

    /**
     * COD Notice Configuration (Multi-channel, Multi-language)
     * 
     * Shows when customer selects Cash on Delivery payment method
     */
    'cod_notice' => [
        // Default channel
        'default' => [
            'en' => 'Please note: COD fee will be added to your order. Payment will be collected upon delivery.',
            'mm' => 'သတ္တိ: သင့်အမှာစာတွင် အခါတစ်ခါအခြေခံအေပါ်ငွေ ထည့်သွင်းခြင်းခံရမည်။ ခွင့်ပြုချက်ကိုလည်း ပို့ဆောင်ချိန်တွင် စုဆောင်းမည်။',
            'ja' => '注意：代金引換手数料がご注文に追加されます。配送時にお支払いください。',
        ],
        // Myanmar Food Store channel
        'mmfood' => [
            'en' => 'Cash on Delivery: A ¥330-440 fee will be added to your order total. Payment collected at delivery.',
            'mm' => 'အခါတစ်ခါအခြေခံအေပါ်ငွေ: ¥330-440 အခွန်သည် သင့်အမှာစာစုစုပေါင်းတွင် ထည့်သွင်းလိမ့်မည်။ ပို့ဆောင်ချိန်တွင် ငွေပေးချေမည်။',
            'ja' => '代金引換：¥330-440の手数料が注文合計に追加されます。配送時にお支払いください。',
        ],
    ],

    /**
     * Enable/Disable Features
     */
    'features' => [
        'auto_select_shipping' => true,
        'hide_shipping_selection' => true,
        'enable_cod_fee_integration' => true,
        'show_cod_notice' => true,
        'log_shipping_decisions' => true,
    ],

    /**
     * Shipping Display Settings
     */
    'display' => [
        'show_shipping_options' => false,  // Hidden from customer
        'show_auto_selected_message' => true,
        'show_cod_fee_breakdown' => true,
        'show_cod_notice_in_payment' => true,
    ],
];
