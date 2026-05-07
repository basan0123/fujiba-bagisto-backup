<?php

/**
 * Fujiba PwaFrontend admin config registrations.
 * Every field is channel_based so per-tenant (mmfood / kimono / future)
 * settings stay isolated. Customer-facing text is locale_based.
 *
 * Lives at:
 *   /admin/configuration/general/design/{brand_color,topbar,hero,footer_social}
 *   /admin/configuration/pwa/{chat,payments,pickup,messenger,shipping_thresholds}
 */
return [

    // ── Brand colour + secondary logo (already in step 2) ─────────────────
    [
        'key'    => 'general.design.brand_color',
        'name'   => 'Brand Color',
        'info'   => 'Customer-facing brand color (storefront + PWA). Channel-based.',
        'sort'   => 4,
        'fields' => [
            [
                'name'          => 'value',
                'title'         => 'Brand Color',
                'type'          => 'text',
                'channel_based' => true,
                'default'       => '#1e1b4b',
            ],
            [
                'name'          => 'accent_color',
                'title'         => 'Accent Color',
                'type'          => 'text',
                'channel_based' => true,
                'default'       => '#f5a623',
            ],
            [
                'name'          => 'dark_color',
                'title'         => 'Dark / Topbar Color',
                'type'          => 'text',
                'channel_based' => true,
                'default'       => '#1a0800',
            ],
            [
                'name'          => 'logo_secondary',
                'title'         => 'Mobile Logo (Optional)',
                'type'          => 'image',
                'channel_based' => true,
                'validation'    => 'mimes:bmp,jpeg,jpg,png,webp,svg',
            ],
            [
                'name'          => 'btn_radius',
                'title'         => 'Button Radius',
                'type'          => 'text',
                'channel_based' => true,
                'default'       => '12px',
            ],
            [
                'name'          => 'card_radius',
                'title'         => 'Card Radius',
                'type'          => 'text',
                'channel_based' => true,
                'default'       => '18px',
            ],

            // Header / footer / button text colours. Each channel/tenant
            // gets its own set of values; an empty value falls back to a
            // sensible default in the runtime CSS.
            [
                'name'          => 'header_bg',
                'title'         => 'Header Background',
                'type'          => 'text',
                'channel_based' => true,
                'info'          => 'Hex color for the storefront + PWA top header. Empty = white.',
            ],
            [
                'name'          => 'header_text',
                'title'         => 'Header Text Color',
                'type'          => 'text',
                'channel_based' => true,
                'info'          => 'Hex color for header logo text and nav icons.',
            ],
            [
                'name'          => 'footer_bg',
                'title'         => 'Footer Background',
                'type'          => 'text',
                'channel_based' => true,
                'info'          => 'Hex color for the page footer.',
            ],
            [
                'name'          => 'footer_text',
                'title'         => 'Footer Text Color',
                'type'          => 'text',
                'channel_based' => true,
                'info'          => 'Hex color for footer text and links.',
            ],
            [
                'name'          => 'button_text',
                'title'         => 'Button Text Color',
                'type'          => 'text',
                'channel_based' => true,
                'default'       => '#ffffff',
                'info'          => 'Foreground color used on primary brand-coloured buttons (Add to Cart, Checkout, etc.).',
            ],
        ],
    ],

    // ── Topbar ────────────────────────────────────────────────────────────
    [
        'key'    => 'general.design.topbar',
        'name'   => 'Topbar',
        'info'   => 'Top ticker bar on mobile storefront. Channel + locale aware.',
        'sort'   => 5,
        'fields' => [
            [
                'name'          => 'text',
                'title'         => 'Ticker Text',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'free_ship_min',
                'title'         => 'Free Shipping Minimum',
                'type'          => 'text',
                'channel_based' => true,
                'default'       => '3000',
                'validation'    => 'nullable|numeric|min:0',
            ],
            [
                'name'          => 'show_lang_switch',
                'title'         => 'Show Language Switch',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '1',
            ],
            [
                'name'          => 'show_currency_switch',
                'title'         => 'Show Currency Switch',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '1',
            ],
            [
                'name'          => 'show_theme_toggle',
                'title'         => 'Show Theme (light/dark) Toggle',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '1',
            ],
        ],
    ],

    // ── Hero slider (3 slots) ─────────────────────────────────────────────
    [
        'key'    => 'general.design.hero',
        'name'   => 'Hero Slider',
        'info'   => 'Up to 3 hero slides. Empty slots fall back gracefully — slider shows fewer dots.',
        'sort'   => 6,
        'fields' => array_merge(
            ...array_map(fn ($i) => [
                [
                    'name'          => "slot_{$i}_image",
                    'title'         => "Slot {$i} — Image",
                    'type'          => 'image',
                    'channel_based' => true,
                    'validation'    => 'mimes:bmp,jpeg,jpg,png,webp,svg',
                ],
                [
                    'name'          => "slot_{$i}_title",
                    'title'         => "Slot {$i} — Title",
                    'type'          => 'text',
                    'channel_based' => true,
                    'locale_based'  => true,
                ],
                [
                    'name'          => "slot_{$i}_subtitle",
                    'title'         => "Slot {$i} — Subtitle",
                    'type'          => 'text',
                    'channel_based' => true,
                    'locale_based'  => true,
                ],
                [
                    'name'          => "slot_{$i}_badge",
                    'title'         => "Slot {$i} — Badge",
                    'type'          => 'text',
                    'channel_based' => true,
                    'locale_based'  => true,
                ],
                [
                    'name'          => "slot_{$i}_cta_text",
                    'title'         => "Slot {$i} — CTA Text",
                    'type'          => 'text',
                    'channel_based' => true,
                    'locale_based'  => true,
                ],
                [
                    'name'          => "slot_{$i}_cta_url",
                    'title'         => "Slot {$i} — CTA URL",
                    'type'          => 'text',
                    'channel_based' => true,
                ],
            ], [1, 2, 3]),
        ),
    ],

    // ── Footer social links ───────────────────────────────────────────────
    [
        'key'    => 'general.design.footer_social',
        'name'   => 'Footer Social Links',
        'info'   => 'Social icons shown in mobile footer. Empty values hide their icon.',
        'sort'   => 7,
        'fields' => [
            [
                'name'          => 'facebook_url',
                'title'         => 'Facebook URL',
                'type'          => 'text',
                'channel_based' => true,
            ],
            [
                'name'          => 'instagram_url',
                'title'         => 'Instagram URL',
                'type'          => 'text',
                'channel_based' => true,
            ],
            [
                'name'          => 'messenger_url',
                'title'         => 'Messenger URL (m.me/...)',
                'type'          => 'text',
                'channel_based' => true,
            ],
            [
                'name'          => 'tiktok_url',
                'title'         => 'TikTok URL',
                'type'          => 'text',
                'channel_based' => true,
            ],
        ],
    ],

    // ── Chat Checkout messages (existing) ─────────────────────────────────
    [
        'key'    => 'pwa.chat',
        'name'   => 'Chat Checkout',
        'info'   => 'Conversational checkout messages — shown in storefront chat checkout flow.',
        'sort'   => 5,
        'fields' => [
            [
                'name'          => 'enabled',
                'title'         => 'Enable Chat Checkout',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '0',
            ],
            [
                'name'          => 'welcome_message',
                'title'         => 'Welcome Message',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'select_date_message',
                'title'         => 'Select Date Prompt',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'select_time_message',
                'title'         => 'Select Time Prompt',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'confirm_message',
                'title'         => 'Confirm Order Prompt',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'address_done_message',
                'title'         => 'Address Confirmed Message',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'payment_done_message',
                'title'         => 'Payment Confirmed Message',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ],
        ],
    ],

    // ── Payment method toggles (channel-scoped on/off) ────────────────────
    [
        'key'    => 'pwa.payments',
        'name'   => 'Storefront Payment Methods',
        'info'   => 'Per-channel toggles — disabled methods are filtered server-side and never appear in checkout.',
        'sort'   => 6,
        'fields' => [
            [
                'name'          => 'cod_enabled',
                'title'         => 'Cash On Delivery',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '1',
            ],
            [
                'name'          => 'transfer_enabled',
                'title'         => 'Bank / Money Transfer',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '1',
            ],
            [
                'name'          => 'card_enabled',
                'title'         => 'Credit Card',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '0',
            ],
            [
                'name'          => 'paypay_enabled',
                'title'         => 'PayPay',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '0',
            ],
            [
                'name'          => 'komoju_enabled',
                'title'         => 'Komoju',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '0',
            ],
        ],
    ],

    // ── Pickup ────────────────────────────────────────────────────────────
    [
        'key'    => 'pwa.pickup',
        'name'   => 'Store Pickup',
        'info'   => 'Optional store-pickup option in the mobile checkout chat.',
        'sort'   => 7,
        'fields' => [
            [
                'name'          => 'enabled',
                'title'         => 'Enable Store Pickup',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '0',
            ],
            [
                'name'          => 'address',
                'title'         => 'Pickup Address',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'hours',
                'title'         => 'Pickup Hours',
                'type'          => 'text',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'phone',
                'title'         => 'Pickup Contact Phone',
                'type'          => 'text',
                'channel_based' => true,
            ],
        ],
    ],

    // ── Messenger bot subscriber sign-in ──────────────────────────────────
    [
        'key'    => 'pwa.messenger',
        'name'   => 'Messenger Bot Sign-in',
        'info'   => 'Per-channel Messenger bot integration — used to look up subscriber → customer for magic-link sign-in.',
        'sort'   => 8,
        'fields' => [
            [
                'name'          => 'enabled',
                'title'         => 'Enable Messenger Sign-in',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '0',
            ],
            [
                'name'          => 'page_id',
                'title'         => 'Facebook Page ID',
                'type'          => 'text',
                'channel_based' => true,
            ],
            [
                'name'          => 'mme_url',
                'title'         => 'm.me URL',
                'type'          => 'text',
                'channel_based' => true,
            ],
            [
                'name'          => 'login_button_label',
                'title'         => 'Login Button Label',
                'type'          => 'text',
                'channel_based' => true,
                'locale_based'  => true,
            ],
        ],
    ],

    // ── Catalog: filter tabs + sort (drives PWA "All Products" grid) ──────
    [
        'key'    => 'pwa.catalog',
        'name'   => 'Catalog Filters & Sort',
        'info'   => 'Per-channel control of which attribute tabs appear above the PWA "All Products" grid, and the default sort order.',
        'sort'   => 9,
        'fields' => [
            [
                'name'          => 'filter_attributes',
                'title'         => 'Filter Tabs (Comma-Separated Attribute Codes)',
                'type'          => 'textarea',
                'channel_based' => true,
                'info'          => 'Attribute codes to expose as filter tabs, e.g. "category,brand,color,size". Empty = show all filterable attributes. The "category" tab is always available regardless.',
                'default'       => '',
            ],
            [
                'name'          => 'default_sort',
                'title'         => 'Default Sort',
                'type'          => 'select',
                'channel_based' => true,
                'default'       => 'created_at-desc',
                'options'       => [
                    ['title' => 'Featured First',     'value' => 'featured-desc'],
                    ['title' => 'Newest First',       'value' => 'created_at-desc'],
                    ['title' => 'Oldest First',       'value' => 'created_at-asc'],
                    ['title' => 'Price: Low to High', 'value' => 'price-asc'],
                    ['title' => 'Price: High to Low', 'value' => 'price-desc'],
                    ['title' => 'Name A → Z',         'value' => 'name-asc'],
                    ['title' => 'Name Z → A',         'value' => 'name-desc'],
                ],
            ],
        ],
    ],

    // ── Extra address-requirement toggles (Bagisto core only ships
    //     country/state/postcode toggles. We add phone + company_name +
    //     billing_required, default values mirror the previous behavior.
    //     Per-channel so kimono and mmfood can have different rules.) ───
    [
        'key'    => 'customer.address.extra_requirements',
        'name'   => 'Address Requirements (Extra)',
        'info'   => 'Additional address-field rules layered on top of Bagisto\'s built-in country/state/postcode toggles.',
        'sort'   => 3,
        'fields' => [
            [
                'name'          => 'phone',
                'title'         => 'Phone Number Required',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '1',
                'info'          => 'When OFF, customers can leave phone blank at checkout.',
            ],
            [
                'name'          => 'company_name',
                'title'         => 'Company Name Required',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '0',
            ],
            [
                'name'          => 'billing_required',
                'title'         => 'Billing Address Required',
                'type'          => 'boolean',
                'channel_based' => true,
                'default'       => '0',
                'info'          => 'When OFF, billing address is optional and falls back to the shipping address. When ON, customers must enter both.',
            ],
        ],
    ],
];
