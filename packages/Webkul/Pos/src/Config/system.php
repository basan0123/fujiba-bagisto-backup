<?php

return [
    [
        'key'  => 'pos',
        'name' => 'pos::app.admin.configuration.index.pos.title',
        'info' => 'pos::app.admin.configuration.index.pos.info',
        'sort' => 1,
    ], [
        'key'  => 'pos.settings',
        'name' => 'pos::app.admin.configuration.index.pos.settings.title',
        'info' => 'pos::app.admin.configuration.index.pos.settings.info',
        'icon' => 'settings/store.svg',
        'sort' => 1,
    ], [
        'key'    => 'pos.settings.general',
        'name'   => 'pos::app.admin.configuration.index.pos.settings.general.title',
        'info'   => 'pos::app.admin.configuration.index.pos.settings.general.info',
        'sort'   => 1,
        'fields' => [
            [
                'name'  => 'status',
                'title' => 'pos::app.admin.configuration.index.pos.settings.general.status',
                'type'  => 'boolean',
            ], [
                'name'       => 'pos_logo',
                'title'      => 'pos::app.admin.configuration.index.pos.settings.general.pos-logo',
                'type'       => 'image',
                'validation' => 'mimes:bmp,jpeg,jpg,png,webp,gif,tiff,tif,heic,heif,avif,svg,ico',
            ], [
                'name'         => 'heading_on_login',
                'title'        => 'pos::app.admin.configuration.index.pos.settings.general.heading-on-login',
                'type'         => 'text',
                'validation'   => 'required|max:50',
                'locale_based' => true,
            ], [
                'name'         => 'sub_heading_on_login',
                'title'        => 'pos::app.admin.configuration.index.pos.settings.general.sub-heading-on-login',
                'type'         => 'text',
                'validation'   => 'required|max:50',
                'locale_based' => true,
            ], [
                'name'         => 'footer_content',
                'title'        => 'pos::app.admin.configuration.index.pos.settings.general.footer-content',
                'type'         => 'textarea',
                'validation'   => 'required|max:250',
                'locale_based' => true,
            ], [
                'name'         => 'footer_note',
                'title'        => 'pos::app.admin.configuration.index.pos.settings.general.footer-note',
                'type'         => 'text',
                'validation'   => 'required|max:100',
                'locale_based' => true,
            ],
        ],
    ], [
        'key'    => 'pos.settings.barcode',
        'name'   => 'pos::app.admin.configuration.index.pos.settings.barcode.title',
        'info'   => 'pos::app.admin.configuration.index.pos.settings.barcode.info',
        'sort'   => 1,
        'fields' => [
            [
                'name'  => 'print_product_name',
                'title' => 'pos::app.admin.configuration.index.pos.settings.barcode.print-product-name',
                'type'  => 'boolean',
            ], [
                'name'       => 'width',
                'title'      => 'pos::app.admin.configuration.index.pos.settings.barcode.width',
                'type'       => 'text',
                'validation' => 'numeric',
            ], [
                'name'       => 'height',
                'title'      => 'pos::app.admin.configuration.index.pos.settings.barcode.height',
                'type'       => 'text',
                'validation' => 'numeric',
            ], [
                'name'    => 'generate_with',
                'title'   => 'pos::app.admin.configuration.index.pos.settings.barcode.generate-with.title',
                'type'    => 'select',
                'options' => [
                    [
                        'title' => 'pos::app.admin.configuration.index.pos.settings.barcode.generate-with.options.product-id',
                        'value' => 'id',
                    ], [
                        'title' => 'pos::app.admin.configuration.index.pos.settings.barcode.generate-with.options.sku',
                        'value' => 'sku',
                    ], [
                        'title' => 'pos::app.admin.configuration.index.pos.settings.barcode.generate-with.options.gtin',
                        'value' => 'gtin',
                    ],
                ],
            ], [
                'name'         => 'prefix',
                'title'        => 'pos::app.admin.configuration.index.pos.settings.barcode.prefix',
                'type'         => 'text',
                'validation'   => 'max:6',
                'locale_based' => true,
            ], [
                'name'  => 'hide',
                'title' => 'pos::app.admin.configuration.index.pos.settings.barcode.hide-barcode',
                'type'  => 'boolean',
            ],
        ],
    ], [
        'key'    => 'pos.settings.product',
        'name'   => 'pos::app.admin.configuration.index.pos.settings.products.title',
        'info'   => 'pos::app.admin.configuration.index.pos.settings.products.info',
        'sort'   => 1,
        'fields' => [
            [
                'name'  => 'allow_sku',
                'title' => 'pos::app.admin.configuration.index.pos.settings.products.allow-sku',
                'type'  => 'boolean',
            ],
        ],
    ],
];
