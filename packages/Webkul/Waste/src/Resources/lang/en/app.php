<?php

return [
    'title'              => 'Waste Management',
    'add'                => 'Add Waste',
    'add-record'         => 'Add Waste Record',
    'edit-record'        => 'Edit Waste Record',
    'filter'             => 'Filter',
    'reset'              => 'Reset',
    'records'            => 'records',
    'filtered-total'     => 'Filtered total amount',
    'total-qty'          => 'Total quantity',

    'columns' => [
        'date'    => 'Date',
        'product' => 'Product',
        'sku'     => 'SKU',
        'channel' => 'Channel',
        'qty'     => 'Qty',
        'amount'  => 'Amount',
        'reason'  => 'Reason',
        'notes'   => 'Notes',
        'actions' => 'Actions',
    ],

    'empty'              => 'No waste records found.',

    'actions' => [
        'edit'    => 'Edit',
        'delete'  => 'Delete',
        'save'    => 'Save',
        'cancel'  => 'Cancel',
        'confirm' => 'Confirm',
        'close'   => 'Close',
    ],

    'form' => [
        'product'         => 'Product',
        'search-product'  => 'Search product by name / SKU / barcode',
        'qty'             => 'Quantity',
        'amount'          => 'Amount (¥)',
        'amount-hint'     => 'Auto-calculated from buy price × qty',
        'buy-price'       => 'Buy Price (¥)',
        'buy-price-hint'  => 'Prefilled from product cost; edit to override',
        'reason'          => 'Reason',
        'notes'           => 'Notes',
        'date'            => 'Waste date',
        'channel'         => 'Channel',
        'save-btn'        => 'Save Waste Record',
        'update-btn'      => 'Update Waste Record',
    ],

    'reasons' => [
        'expired' => 'Expired',
        'damaged' => 'Damaged',
        'theft'   => 'Theft',
        'other'   => 'Other',
    ],

    'confirm' => [
        'delete-title' => 'Delete this waste record?',
        'delete-text'  => 'This cannot be undone.',
    ],

    'messages' => [
        'saved'   => 'Waste record saved successfully.',
        'updated' => 'Waste record updated.',
        'deleted' => 'Waste record deleted.',
        'error'   => 'Operation failed.',
    ],

    'menu' => [
        'catalog' => 'Catalog',
        'waste'   => 'Waste',
    ],
];
