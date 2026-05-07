<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pos Users
    |--------------------------------------------------------------------------
    |
    | All ACLs related to Pos Users will be placed here.
    |
    */

    [
        'key'   => 'pos',
        'name'  => 'pos::app.admin.acl.pos',
        'route' => 'admin.pos.users.index',
        'sort'  => 1,
    ], [
        'key'   => 'pos.users',
        'name'  => 'pos::app.admin.acl.users',
        'route' => 'admin.pos.users.index',
        'sort'  => 1,
    ], [
        'key'   => 'pos.users.users',
        'name'  => 'pos::app.admin.acl.users',
        'route' => 'admin.pos.users.index',
        'sort'  => 1,
    ], [
        'key'   => 'pos.users.users.create',
        'name'  => 'pos::app.admin.acl.create',
        'route' => 'admin.pos.users.create',
        'sort'  => 1,
    ], [
        'key'   => 'pos.users.users.edit',
        'name'  => 'pos::app.admin.acl.edit',
        'route' => 'admin.pos.users.edit',
        'sort'  => 2,
    ], [
        'key'   => 'pos.users.users.delete',
        'name'  => 'pos::app.admin.acl.delete',
        'route' => 'admin.pos.users.delete',
        'sort'  => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pos Outlets
    |--------------------------------------------------------------------------
    |
    | All ACLs related to Pos Outlets will be placed here.
    |
    */

    [
        'key'   => 'pos.users.outlets',
        'name'  => 'pos::app.admin.acl.outlets',
        'route' => 'admin.pos.outlets.index',
        'sort'  => 2,
    ], [
        'key'   => 'pos.users.outlets.create',
        'name'  => 'pos::app.admin.acl.create',
        'route' => 'admin.pos.outlets.create',
        'sort'  => 1,
    ], [
        'key'   => 'pos.users.outlets.edit',
        'name'  => 'pos::app.admin.acl.edit',
        'route' => 'admin.pos.outlets.edit',
        'sort'  => 2,
    ], [
        'key'   => 'pos.users.outlets.delete',
        'name'  => 'pos::app.admin.acl.delete',
        'route' => 'admin.pos.outlets.delete',
        'sort'  => 3,
    ], [
        'key'   => 'pos.users.outlets.assign_products',
        'name'  => 'pos::app.admin.acl.assign-products',
        'route' => 'admin.pos.outlets.assign_products',
        'sort'  => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pos Barcode Products
    |--------------------------------------------------------------------------
    |
    | All ACLs related to Pos Barcode Products will be placed here.
    |
    */

    [
        'key'   => 'pos.barcode_products',
        'name'  => 'pos::app.admin.acl.barcode-products',
        'route' => 'admin.pos.barcode_products.index',
        'sort'  => 2,
    ], [
        'key'   => 'pos.barcode_products.generate_barcode',
        'name'  => 'pos::app.admin.acl.generate-barcode',
        'route' => 'admin.pos.barcode_products.generate_barcode',
        'sort'  => 1,
    ], [
        'key'   => 'pos.barcode_products.print_barcode',
        'name'  => 'pos::app.admin.acl.print-barcode',
        'route' => 'admin.pos.barcode_products.print_barcode',
        'sort'  => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pos Orders
    |--------------------------------------------------------------------------
    |
    | All ACLs related to Pos Orders will be placed here.
    |
    */

    [
        'key'   => 'pos.orders',
        'name'  => 'pos::app.admin.acl.orders',
        'route' => 'admin.pos.orders.index',
        'sort'  => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pos Requested Products
    |--------------------------------------------------------------------------
    |
    | All ACLs related to Pos Requested Products will be placed here.
    |
    */

    [
        'key'   => 'pos.requests',
        'name'  => 'pos::app.admin.acl.requests',
        'route' => 'admin.pos.requests.index',
        'sort'  => 4,
    ], [
        'key'   => 'pos.requests.view',
        'name'  => 'pos::app.admin.acl.view',
        'route' => 'admin.pos.requests.view',
        'sort'  => 1,
    ], [
        'key'   => 'pos.requests.edit',
        'name'  => 'pos::app.admin.acl.edit',
        'route' => 'admin.pos.requests.mass_update',
        'sort'  => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pos Bank Details
    |--------------------------------------------------------------------------
    |
    | All ACLs related to Bank Details will be placed here.
    |
    */

    [
        'key'   => 'pos.banks',
        'name'  => 'pos::app.admin.acl.banks',
        'route' => 'admin.pos.banks.index',
        'sort'  => 5,
    ], [
        'key'   => 'pos.banks.create',
        'name'  => 'pos::app.admin.acl.create',
        'route' => 'admin.pos.banks.create',
        'sort'  => 1,
    ], [
        'key'   => 'pos.banks.edit',
        'name'  => 'pos::app.admin.acl.edit',
        'route' => 'admin.pos.banks.edit',
        'sort'  => 2,
    ], [
        'key'   => 'pos.banks.delete',
        'name'  => 'pos::app.admin.acl.delete',
        'route' => 'admin.pos.banks.delete',
        'sort'  => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pos Sales Report
    |--------------------------------------------------------------------------
    |
    | All ACLs related to Pos Sales Report will be placed here.
    |
    */

    [
        'key'   => 'pos.sales_report',
        'name'  => 'pos::app.admin.acl.sales-report',
        'route' => 'admin.pos.sales_report.index',
        'sort'  => 6,
    ], [
        'key'   => 'pos.sales_report.view',
        'name'  => 'pos::app.admin.acl.view',
        'route' => 'admin.sales.orders.view',
        'sort'  => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pos Receipt Details
    |--------------------------------------------------------------------------
    |
    | All ACLs related to Receipt Details will be placed here.
    |
    */

    [
        'key'   => 'pos.receipts',
        'name'  => 'pos::app.admin.acl.receipts',
        'route' => 'admin.pos.receipts.index',
        'sort'  => 7,
    ], [
        'key'   => 'pos.receipts.create',
        'name'  => 'pos::app.admin.acl.create',
        'route' => 'admin.pos.receipts.create',
        'sort'  => 1,
    ], [
        'key'   => 'pos.receipts.edit',
        'name'  => 'pos::app.admin.acl.edit',
        'route' => 'admin.pos.receipts.edit',
        'sort'  => 2,
    ], [
        'key'   => 'pos.receipts.preview',
        'name'  => 'pos::app.admin.acl.preview',
        'route' => 'admin.pos.receipts.show',
        'sort'  => 3,
    ], [
        'key'   => 'pos.receipts.delete',
        'name'  => 'pos::app.admin.acl.delete',
        'route' => 'admin.pos.banks.delete',
        'sort'  => 4,
    ],
];
