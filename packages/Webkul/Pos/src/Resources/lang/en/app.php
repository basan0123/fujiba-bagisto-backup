<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Invalid credentials.',
                'not-activated'       => 'Your account is not activated.',
                'verify-first'        => 'Please verify your email first.',
                'success'             => 'You have successfully logged in.',
            ],

            'logout' => [
                'no-login-agent' => 'No agent is logged in.',
                'success'        => 'You have successfully logged out.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'The password you entered is incorrect.',
                    'success'          => 'Your account has been updated successfully.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Customer created successfully!',
            'update-success' => 'Customer updated successfully!',
            'delete-success' => 'Customer deleted successfully!',
            'delete-failed'  => 'Customer deletion failed!',
            'pending-orders' => 'Customer has pending orders!',
        ],

        'credit' => [
            'adjust-success'    => 'Customer credit adjusted successfully.',
            'manager-required'  => 'Manager-level access is required.',
            'insufficient'      => 'Insufficient balance. Current balance is :balance.',
            'customer-missing'  => 'Customer not found.',
        ],

        'role-gate' => [
            'denied' => 'Your role (:role) is not allowed here. Required: :allowed.',
        ],

        'upload' => [
            'empty'         => 'No image provided.',
            'bad-encoding'  => 'Image base64 is invalid.',
            'too-large'     => 'Image is larger than :limit.',
            'bad-type'      => 'Only JPG and PNG are supported.',
            'decode-failed' => 'Could not decode the image: :msg',
        ],

        'product' => [
            'created'      => 'Product created.',
            'updated'      => 'Product updated.',
            'deleted'      => 'Product deleted.',
            'not-found'    => 'Product not found in this outlet.',
            'has-orders'   => 'Product has historical orders and cannot be hard-deleted.',
        ],

        'category' => [
            'created'   => 'Category created.',
            'updated'   => 'Category updated.',
            'deleted'   => 'Category deleted.',
            'not-found' => 'Category not found.',
            'has-children' => 'Category has child categories.',
        ],

        'stock' => [
            'adjusted'         => 'Stock adjusted.',
            'wasted'           => 'Waste recorded.',
            'transferred'      => 'Stock transferred.',
            'back-orders-saved'=> 'Out-of-stock sale setting saved.',
            'reason-required'  => 'Reason is required.',
            'bad-reason'       => 'Unknown reason :reason.',
            'same-outlet'      => 'Source and destination must differ.',
            'over-deduct'      => 'Cannot deduct more than current quantity (:qty).',
            'no-source-stock'  => 'Source inventory source not found.',
            'no-dest-source'   => 'Destination inventory source not found.',
        ],

        'staff' => [
            'created'     => 'Staff member created.',
            'updated'     => 'Staff member updated.',
            'deactivated' => 'Staff member deactivated.',
            'owner-required' => 'Owner-level access is required.',
            'self-demote' => 'You cannot demote yourself from owner.',
            'self-deactivate' => 'You cannot deactivate yourself.',
            'email-taken' => 'This email is already used by another staff member.',
            'bad-pin'     => 'PIN must be exactly 4 digits.',
            'bad-role'    => 'Unknown role :role.',
        ],

        'orders' => [
            'not-found'      => 'Order not found.',
            'refund-success' => 'Refund recorded.',
            'void-success'   => 'Order voided.',
            'void-too-old'   => 'Voids are limited to the current shift.',
            'bad-reason'     => 'Unknown reason :reason.',
            'amount-required' => 'Refund amount must be positive.',
            'manager-required-large' => 'Refunds over ¥3,000 require manager approval.',
            'address-updated'     => 'Address updated.',
            'payment-updated'     => 'Payment method updated.',
            'fulfillment-updated' => 'Fulfillment updated.',
            'status-updated'      => 'Status updated.',
            'note-saved'          => 'Note saved.',
            'items-updated'       => 'Order updated.',
            'not-editable'        => 'Order cannot be edited in its current status.',
            'stock-check-failed'  => 'Stock check failed.',
        ],

        'shift' => [
            'opened'           => 'Shift opened.',
            'closed'           => 'Shift closed.',
            'already-open'     => 'You already have an open shift.',
            'none-open'        => 'No open shift to close.',
            'variance-reason'  => 'Variance exceeds ¥500; reason is required.',
            'movement-recorded'=> 'Cash movement recorded.',
            'bad-direction'    => 'Direction must be `in` or `out`.',
        ],

        'invoice' => [
            'title'          => 'Invoice',
            'generated'      => 'Invoice generated.',
            'emailed'        => 'Invoice emailed.',
            'order-not-found'=> 'Order not found.',
            'already-exists' => 'Order already has an invoice.',
            'no-customer-email' => 'Customer has no email on file.',
            'draft-created'  => 'Draft invoice created.',
            'not-a-draft'    => 'This invoice is no longer a draft.',
            'payment-recorded' => 'Payment recorded.',
            'payment-exceeds'  => 'Payment exceeds the outstanding balance.',
            'invalid-amount'   => 'Enter a payment amount greater than zero.',
        ],

        'cart' => [
            'already-applied'     => 'Coupon already applied!',
            'coupon-applied'      => 'Coupon applied successfully!',
            'coupon-removed'      => 'Coupon removed successfully!',
            'create-success'      => 'Cart created successfully!',
            'invalid-coupon'      => 'Invalid coupon code!',
            'item-add-success'    => 'Product added to cart successfully!',
            'item-remove-success' => 'Product removed from cart successfully!',
            'item-update-success' => 'Product updated successfully!',
            'not-found'           => 'Cart not found!',
        ],

        'payment' => [
            'title' => 'Pos Payment',

            'options' => [
                'cash' => [
                    'title'       => 'Pos Cash Payment',
                    'description' => 'This is Pos Cash Payment.',
                ],

                'card' => [
                    'title'       => 'Pos Card Payment',
                    'description' => 'This is Pos Card Payment.',
                ],

                'split' => [
                    'title'       => 'Pos Split Payment',
                    'description' => 'This is Pos Split Payment.',
                ],
            ],

            'no-items' => 'No items in the cart to proceed with the payment.',
            'success'  => 'Payment completed successfully!',
        ],

        'shipping' => [
            'title'       => 'Pos Shipping',
            'description' => 'This is Free Pos Shipping.',
        ],

        'order' => [
            'sync-success' => 'Order synced successfully!',
        ],

        'return' => [
            'create-success'              => 'Refund created successfully',
            'creation-error'              => 'Refund creation is not allowed.',
            'invalid-qty'                 => 'We found an invalid quantity to invoice items.',
            'invalid-refund-amount-error' => 'Refund amount should be non zero.',
            'refund-limit-error'          => 'Refund Amount :amount can not proceed.',
        ],

        'drawer' => [
            'create-success' => 'Drawer opened successfully!',
            'not-opened'     => 'Drawer is not opened.',
            'close-success'  => 'Drawer closed successfully!',
        ],

        'products' => [
            'request-success'       => 'Product request submitted successfully.',
            'create-success'        => 'Product created successfully!',
            'update-success'        => 'Products updated successfully!',
            'barcode-linked'        => 'Barcode linked to product successfully.',
            'not-in-outlet'         => 'This product is not assigned to your outlet.',
            'image-updated'         => 'Product image updated.',
            'image-removed'         => 'Product image removed.',
            'image-upload-failed'   => 'Image upload failed. Try a smaller file.',
        ],

        'reports' => [
            'orders'                  => 'Orders',
            'average-order-value'     => 'Average Order Value',
            'average-items-per-order' => 'Average Items Per Order',
            'discounted-offers'       => 'Discounted Offers',
            'cash-payments'           => 'Cash Payments',
            'other-payments'          => 'Other Payment',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Order Note',
                    'note-not-available' => 'Note not available!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'The Bagisto Point of Sale (POS) Extension.',
                    'title' => 'Point Of Sale',

                    'settings' => [
                        'info'  => 'Enable POS, Set General Configuration, Pos Product and Bill Receipt.',
                        'title' => 'Settings',

                        'general' => [
                            'footer-content'       => 'Footer Content',
                            'footer-note'          => 'Footer Note',
                            'frontend-url'         => 'Frontend URL',
                            'heading-on-login'     => 'Heading On Login',
                            'info'                 => 'General settings allows the configurations for POS user page, by adding logo, headings, footer contents, footer note, etc.',
                            'pos-logo'             => 'Pos Logo',
                            'status'               => 'Status',
                            'sub-heading-on-login' => 'Sub-Heading On Login',
                            'title'                => 'General',
                        ],

                        'barcode' => [
                            'height'             => 'Height',
                            'hide-barcode'       => 'Hide Barcode',
                            'info'               => 'Barcode settings allows the configurations for barcode generation, barcode height, barcode width, barcode type, etc.',
                            'prefix'             => 'Prefix',
                            'print-product-name' => 'Print Product Name',
                            'title'              => 'Barcode',
                            'width'              => 'Width',

                            'generate-with' => [
                                'title' => 'Generate Barcode With',

                                'options' => [
                                    'product-id' => 'Product Id',
                                    'sku'        => 'Product Sku',
                                    'gtin'       => 'GTIN / EAN / UPC (per-product)',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'Allow SKU for Custom Product',
                            'info'      => 'Product settings allows the configurations for product sku.',
                            'title'     => 'Products',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Assign Products',
            'banks'            => 'Banks',
            'barcode-products' => 'Barcode Products',
            'create'           => 'Create',
            'delete'           => 'Delete',
            'edit'             => 'Edit',
            'generate-barcode' => 'Generate Barcode',
            'orders'           => 'Orders',
            'outlets'          => 'Outlets',
            'pos'              => 'Point Of Sale (POS)',
            'preview'          => 'Preview',
            'print-barcode'    => 'Print Barcode',
            'receipts'         => 'Receipts',
            'requests'         => 'Requests',
            'sales-report'     => 'Sales Report',
            'users'            => 'Agents',
            'view'             => 'View',
        ],

        'layouts' => [
            'banks'            => 'Banks',
            'barcode-products' => 'Barcode Products',
            'cash-movements'   => 'Cash In/Out (入出金)',
            'fixed-expenses'   => 'Fixed Expenses (固定費)',
            'orders'           => 'Orders',
            'pos'              => 'Point Of Sale (POS)',
            'profit-report'    => 'Profit Report (損益)',
            'receipts'         => 'Receipts',
            'requests'         => 'Requests',
            'sales-report'     => 'Sales Report',
            'waste'            => 'Waste (廃棄)',

            'users' => [
                'agents'   => 'Agents',
                'outlets'  => 'Outlets',
                'title'    => 'Agents',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Create Agent',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Agents',

                    'datagrid' => [
                        'action'              => 'Action',
                        'delete'              => 'Delete',
                        'edit'                => 'Edit',
                        'email'               => 'Email',
                        'full-name'           => 'Full Name',
                        'id'                  => 'Id',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'Selected Agents deleted successfully!',
                        'mass-update-success' => 'Selected Agents updated successfully!',
                        'outlet-name'         => 'Outlet Name',
                        'profile-image'       => 'Profile Image',
                        'update-status'       => 'Update Status',
                        'username'            => 'Username',

                        'status' => [
                            'title' => 'Status',

                            'options' => [
                                'active'  => 'Active',
                                'disable' => 'Disable',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Back',
                    'confirm-password'  => 'Confirm Password',
                    'email'             => 'Email',
                    'first-name'        => 'First Name',
                    'general'           => 'General',
                    'image'             => 'Image',
                    'last-name'         => 'Last Name',
                    'outlet'            => 'Outlet',
                    'outlet-and-status' => 'Outlet & Status',
                    'password'          => 'Password',
                    'save-btn'          => 'Save Agent',
                    'select-outlet'     => 'Select Outlet',
                    'status'            => 'Status',
                    'title'             => 'Add Agent',
                    'user-image'        => 'Upload Agent Image',
                    'username'          => 'Username',
                ],

                'edit' => [
                    'back-btn'          => 'Back',
                    'confirm-password'  => 'Confirm Password',
                    'email'             => 'Email',
                    'first-name'        => 'First Name',
                    'general'           => 'General',
                    'image'             => 'Image',
                    'last-name'         => 'Last Name',
                    'outlet'            => 'Outlet',
                    'outlet-and-status' => 'Outlet & Status',
                    'password'          => 'Password',
                    'save-btn'          => 'Save Agent',
                    'select-outlet'     => 'Select Outlet',
                    'status'            => 'Status',
                    'title'             => 'Edit Agent',
                    'user-image'        => 'Upload Agent Image',
                    'username'          => 'Username',
                ],

                'create-success' => 'Agent created successfully!',
                'delete-failed'  => 'Agent deletion failed!',
                'delete-success' => 'Agent deleted successfully!',
                'update-success' => 'Agent updated successfully!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Create Outlet',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Outlets',

                    'datagrid' => [
                        'action'              => 'Action',
                        'active'              => 'Active',
                        'assign'              => 'Assign Product',
                        'delete'              => 'Delete',
                        'edit'                => 'Edit',
                        'id'                  => 'Id',
                        'inactive'            => 'Inactive',
                        'inventory-source'    => 'Inventory Source',
                        'mass-delete-success' => 'Selected stores deleted successfully!',
                        'mass-update-success' => 'Selected stores updated successfully!',
                        'name'                => 'Name',
                        'receipt-title'       => 'Receipt Title',
                        'status'              => 'Status',
                        'title'               => 'Pos Store List',
                        'update-status'       => 'Update Status',
                    ],
                ],

                'create' => [
                    'address'                 => 'Address',
                    'back-btn'                => 'Back',
                    'btn-title'               => 'Save Outlet',
                    'city'                    => 'City',
                    'country'                 => 'Country',
                    'customer-care-number'    => 'Customer Care Number',
                    'email'                   => 'Email',
                    'general'                 => 'General',
                    'gst-number'              => 'GST Number',
                    'inventory'               => 'Inventory',
                    'inventory-source'        => 'Inventory Source',
                    'low-stock-qty'           => 'Low Stock Quantity',
                    'name'                    => 'Outlet Name',
                    'phone'                   => 'Phone',
                    'postcode'                => 'Postcode',
                    'receipt'                 => 'Receipt',
                    'select-country'          => 'Select Country',
                    'select-inventory-source' => 'Select Inventory Source',
                    'select-receipt'          => 'Select Receipt',
                    'state'                   => 'State',
                    'status'                  => 'Status',
                    'store-address'           => 'Outlet Address',
                    'title'                   => 'Add Outlet',
                    'website'                 => 'Website',
                ],

                'edit' => [
                    'address'                 => 'Address',
                    'back-btn'                => 'Back',
                    'btn-title'               => 'Save Outlet',
                    'city'                    => 'City',
                    'country'                 => 'Country',
                    'customer-care-number'    => 'Customer Care Number',
                    'email'                   => 'Email',
                    'general'                 => 'General',
                    'gst-number'              => 'GST Number',
                    'inventory'               => 'Inventory',
                    'inventory-source'        => 'Inventory Source',
                    'low-stock-qty'           => 'Low Stock Quantity',
                    'name'                    => 'Outlet Name',
                    'phone'                   => 'Phone',
                    'postcode'                => 'Postcode',
                    'receipt'                 => 'Receipt',
                    'select-country'          => 'Select Country',
                    'select-inventory-source' => 'Select Inventory Source',
                    'select-receipt'          => 'Select Receipt',
                    'state'                   => 'State',
                    'status'                  => 'Status',
                    'store-address'           => 'Outlet Address',
                    'title'                   => 'Edit Outlet',
                    'website'                 => 'Website',
                ],

                'assign' => [
                    'back-btn' => 'Back',
                    'title'    => 'Manage Outlet Products',

                    'datagrid' => [
                        'active'              => 'Active',
                        'assign'              => 'Assign',
                        'channel'             => 'Channel',
                        'disable'             => 'Disable',
                        'id'                  => 'Id',
                        'id-value'            => 'Id - :id',
                        'image'               => 'Image',
                        'inventory-source'    => 'Inventory Source',
                        'mass-assign-success' => 'Product assign updated successfully!',
                        'name'                => 'Name',
                        'out-of-stock'        => 'Out of Stock',
                        'pos-status'          => 'POS Status',
                        'price'               => 'Price',
                        'product-image'       => 'Product Image',
                        'qty'                 => 'Quantity',
                        'qty-value'           => ':qty Available',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Status',
                        'type'                => 'Type',
                        'unassign'            => 'Unassign',
                        'update-assign'       => 'Update Assign',
                    ],
                ],

                'create-success' => 'Outlet created successfully!',
                'delete-failed'  => 'Outlet deletion failed!',
                'delete-success' => 'Outlet deleted successfully!',
                'update-success' => 'Outlet updated successfully!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Barcode Products',

                'datagrid' => [
                    'barcode'                        => 'Barcode',
                    'barcode-settings'               => 'Barcode Settings',
                    'gtin'                           => 'GTIN / EAN / UPC',
                    'gtin-placeholder'               => 'e.g. 4006381333931',
                    'gtin-save'                      => 'Save',
                    'scan-type'                      => 'Barcode Type',
                    'scan-type-none'                 => 'None (use global default)',
                    'scan-type-product-id'           => 'Product ID',
                    'scan-type-sku'                  => 'SKU',
                    'scan-type-gtin'                 => 'GTIN / EAN / UPC',
                    'scan-type-product-id-info'      => 'Barcode will encode Product ID: :id',
                    'scan-type-sku-info'             => 'Barcode will encode SKU: :sku',
                    'type-default'          => '— default type —',
                    'generate-barcode'      => 'Generate Barcode',
                    'print-barcode'         => 'Print Barcode',
                    'id'                    => 'Id',
                    'id-value'              => 'Id - :id',
                    'image'                 => 'Image',
                    'mass-generate-success' => 'Selected Products barcode generated successfully!',
                    'name'                  => 'Name',
                    'out-of-stock'          => 'Out of Stock',
                    'price'                 => 'Price',
                    'product-image'         => 'Product Image',
                    'qty'                   => 'Qty',
                    'qty-value'             => ':qty Available',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'active'  => 'Active',
                            'disable' => 'Disable',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'   => 'Back',
                'btn-title'  => 'Print',
                'qty'        => 'Qty',
                'title'      => 'Print Barcode',
            ],

            'generate-failed'  => 'Barcode generation failed!',
            'generate-success' => 'Barcode generated successfully!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Orders',

                'datagrid' => [
                    'customer-name' => 'Customer Name',
                    'grand-total'   => 'Grand Total',
                    'order-date'    => 'Order Date',
                    'order-id'      => 'Order ID',
                    'order-ref-id'  => 'Order Ref. ID',
                    'view'          => 'View',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'canceled'        => 'Canceled',
                            'closed'          => 'Closed',
                            'completed'       => 'Completed',
                            'fraud'           => 'Fraud',
                            'pending'         => 'Pending',
                            'pending-payment' => 'Pending Payment',
                            'processing'      => 'Processing',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Requests',

                'datagrid' => [
                    'id'                  => 'Id',
                    'product-image'       => 'Product Image',
                    'mass-update-error'   => 'Request update failed!',
                    'mass-update-success' => 'Selected requests updated successfully!',
                    'product-name'        => 'Product Name',
                    'outlet-name'         => 'Outlet Name',
                    'qty-value'           => 'QTY - :qty',
                    'request-date'        => 'Request Date',
                    'requested-qty'       => 'Requested QTY',
                    'update-status'       => 'Update Status',
                    'user-name'           => 'User Name',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'complete' => 'Complete',
                            'decline'  => 'Decline',
                            'pending'  => 'Pending',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Back',
                'btn-title' => 'Save',
                'title'     => 'Requested Product Details #:id',

                'user-info' => [
                    'email'            => 'Email',
                    'name'             => 'Name',
                    'outlet-address'   => 'Outlet Address',
                    'outlet-inventory' => 'Outlet Inventory Source',
                    'outlet-name'      => 'Outlet Name',
                    'title'            => 'User Information',
                ],

                'request-info' => [
                    'comment'       => 'Comment',
                    'product-name'  => 'Product Name',
                    'qty-value'     => 'Qty - :qty',
                    'request-date'  => 'Request Date',
                    'requested-qty' => 'Requested Qty',
                    'title'         => 'Request Information',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'complete' => 'Complete',
                            'decline'  => 'Decline',
                            'pending'  => 'Pending',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Request update failed!',
            'update-success' => 'Request updated successfully!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Create Bank',
                'title'     => 'Banks',

                'datagrid' => [
                    'active'              => 'Active',
                    'address'             => 'Bank Address',
                    'agent-name'          => 'Agent',
                    'delete'              => 'Delete',
                    'disable'             => 'Disable',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Selected banks deleted successfully!',
                    'name'                => 'Bank Name',
                    'status'              => 'Status',
                ],
            ],

            'create' => [
                'back-btn'  => 'Back',
                'btn-title' => 'Save Bank',
                'title'     => 'Create New Bank',

                'general' => [
                    'address' => 'Address',
                    'email'   => 'Email',
                    'name'    => 'Name',
                    'phone'   => 'Phone',
                    'title'   => 'General',
                ],

                'agent-and-status' => [
                    'agent'        => 'Assign Pos Agent',
                    'bank-status'  => 'Bank Status',
                    'select-agent' => 'Select Agent',
                    'title'        => 'Pos Agent & Bank Status',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Back',
                'btn-title' => 'Save Bank',
                'title'     => 'Edit Bank',

                'general' => [
                    'address' => 'Address',
                    'email'   => 'Email',
                    'name'    => 'Name',
                    'phone'   => 'Phone',
                    'title'   => 'General',
                ],

                'agent-and-status' => [
                    'agent'        => 'Assign Pos Agent',
                    'bank-status'  => 'Bank Status',
                    'select-agent' => 'Select Agent',
                    'title'        => 'Pos Agent & Bank Status',
                ],
            ],

            'create-success' => 'Bank created successfully!',
            'delete-failed'  => 'Bank deletion failed!',
            'delete-success' => 'Bank deleted successfully!',
            'update-success' => 'Bank updated successfully!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Sales Reports',

                'datagrid' => [
                    'bank-name'      => 'Bank Name',
                    'grand-total'    => 'Grand Total',
                    'order-date'     => 'Order Date',
                    'order-id'       => 'Order ID',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Order Note',
                    'outlet-name'    => 'Outlet Name',
                    'payment-method' => 'Payment Method',
                    'view'           => 'View',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'canceled'        => 'Canceled',
                            'closed'          => 'Closed',
                            'completed'       => 'Completed',
                            'fraud'           => 'Fraud',
                            'pending'         => 'Pending',
                            'pending-payment' => 'Pending Payment',
                            'processing'      => 'Processing',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Create Receipt',
                'title'      => 'Receipts',

                'datagrid' => [
                    'delete'              => 'Delete',
                    'edit'                => 'Edit',
                    'id'                  => 'Id',
                    'mass-delete-success' => 'Selected receipts deleted successfully!',
                    'preview'             => 'Preview',
                    'title'               => 'Title',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'active'   => 'Active',
                            'inactive' => 'Inactive',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Back',
                'btn-title' => 'Save Receipt',
                'title'     => 'Create New Receipt',

                'general' => [
                    'cashier-name-label'      => 'Cashier Name Label',
                    'change-amount-label'     => 'Change Amount Label',
                    'credit-amount-label'     => 'Credit Amount Label',
                    'discount-amt-label'      => 'Discount Amount Label',
                    'display-cashier-name'    => 'Display Cashier Name',
                    'display-change-amount'   => 'Display Change Amount',
                    'display-credit-amount'   => 'Display Credit Amount',
                    'display-customer-name'   => 'Display Customer Name',
                    'display-date'            => 'Display Date',
                    'display-discount-amt'    => 'Display Discount Amount',
                    'display-order-id'        => 'Display Order ID',
                    'display-outlet-address'  => 'Display Outlet Address',
                    'display-outlet-name'     => 'Display Outlet Name',
                    'display-sub-total'       => 'Display Sub Total',
                    'display-tax'             => 'Display Tax',
                    'grand-total-label'       => 'Grand Total Label',
                    'order-id-label'          => 'Order ID Label',
                    'receipt-title'           => 'Receipt Title',
                    'show-order-barcode'      => 'Show Order Barcode',
                    'show-print-confirmation' => 'Show Print Confirmation',
                    'status'                  => 'Status',
                    'sub-total-label'         => 'Sub Total Label',
                    'tax-label'               => 'Tax Label',
                    'title'                   => 'General',
                ],

                'logo' => [
                    'display-logo' => 'Display Logo',
                    'logo-alt'     => 'Logo Alt',
                    'logo-height'  => 'Logo Height (in px)',
                    'logo-width'   => 'Logo Width (in px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Upload Logo',
                ],

                'header' => [
                    'header-content' => 'Header Content',
                    'title'          => 'Header',
                ],

                'footer' => [
                    'footer-content' => 'Footer Content',
                    'title'          => 'Footer',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Back',
                'btn-title' => 'Save Receipt',
                'title'     => 'Edit Receipt',

                'general' => [
                    'cashier-name-label'      => 'Cashier Name Label',
                    'change-amount-label'     => 'Change Amount Label',
                    'credit-amount-label'     => 'Credit Amount Label',
                    'discount-amt-label'      => 'Discount Amount Label',
                    'display-cashier-name'    => 'Display Cashier Name',
                    'display-change-amount'   => 'Display Change Amount',
                    'display-credit-amount'   => 'Display Credit Amount',
                    'display-customer-name'   => 'Display Customer Name',
                    'display-date'            => 'Display Date',
                    'display-discount-amt'    => 'Display Discount Amount',
                    'display-order-id'        => 'Display Order ID',
                    'display-outlet-address'  => 'Display Outlet Address',
                    'display-outlet-name'     => 'Display Outlet Name',
                    'display-sub-total'       => 'Display Sub Total',
                    'display-tax'             => 'Display Tax',
                    'grand-total-label'       => 'Grand Total Label',
                    'order-id-label'          => 'Order ID Label',
                    'receipt-title'           => 'Receipt Title',
                    'show-order-barcode'      => 'Show Order Barcode',
                    'show-print-confirmation' => 'Show Print Confirmation',
                    'status'                  => 'Status',
                    'sub-total-label'         => 'Sub Total Label',
                    'tax-label'               => 'Tax Label',
                    'title'                   => 'General',
                ],

                'logo' => [
                    'display-logo' => 'Display Logo',
                    'logo-alt'     => 'Logo Alt',
                    'logo-height'  => 'Logo Height (in px)',
                    'logo-width'   => 'Logo Width (in px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Upload Logo',
                ],

                'header' => [
                    'header-content' => 'Header Content',
                    'title'          => 'Header',
                ],

                'footer' => [
                    'footer-content' => 'Footer Content',
                    'title'          => 'Footer',
                ],
            ],

            'preview' => [
                'amount'         => 'Amount',
                'cashier'        => 'Cashier',
                'change-amount'  => 'Change',
                'customer'       => 'Customer',
                'customer-email' => 'Customer Email',
                'customer-name'  => 'Customer Name',
                'customer-phone' => 'Customer Phone',
                'date'           => 'Date',
                'discount'       => 'Discount',
                'email'          => 'Email',
                'grand-total'    => 'Grand Total',
                'order-id'       => 'Order ID',
                'phone'          => 'Phone',
                'price'          => 'Price',
                'product'        => 'Product',
                'qty'            => 'Qty',
                'sub-total'      => 'Sub Total',
                'tax'            => 'Tax',
                'title'          => 'Receipt Preview',
                'total-qty'      => 'Total Qty',
            ],

            'create-success' => 'Receipt created successfully!',
            'delete-failed'  => 'Receipt deletion failed!',
            'delete-success' => 'Receipt deleted successfully!',
            'update-success' => 'Receipt updated successfully!',
        ],

        'waste' => [
            'index' => [
                'title'   => 'Waste Entries',
                'add-btn' => 'Add Waste Entry',

                'datagrid' => [
                    'id'         => 'ID',
                    'product'    => 'Product',
                    'sku'        => 'SKU',
                    'qty'        => 'Qty',
                    'total_cost' => 'Total Cost',
                    'reason'     => 'Reason',
                    'status'     => 'Status',
                    'date'       => 'Date',
                    'outlet'     => 'Outlet',
                    'agent'      => 'Agent',
                    'actions'    => 'Actions',
                    'verify'     => 'Verify',
                    'delete'     => 'Delete',
                ],
            ],

            'verify' => [
                'title'   => 'Verify Waste Entry',
                'back'    => 'Back to List',
                'approve' => 'Approve',
                'reject'  => 'Reject',
                'notes'   => 'Notes',
            ],

            'verify-success' => 'Waste entry updated successfully!',
            'delete-success' => 'Waste entry deleted successfully!',
            'delete-failed'  => 'Waste entry deletion failed!',
        ],

        'cash-movements' => [
            'index' => [
                'title'   => 'Cash Movements',
                'add-btn' => 'Add Movement',

                'datagrid' => [
                    'id'          => 'ID',
                    'type'        => 'Type',
                    'category'    => 'Category',
                    'description' => 'Description',
                    'amount'      => 'Amount',
                    'date'        => 'Date',
                    'outlet'      => 'Outlet',
                    'agent'       => 'Agent',
                    'actions'     => 'Actions',
                    'delete'      => 'Delete',
                ],
            ],

            'create' => [
                'title'       => 'Add Cash Movement',
                'back'        => 'Back to List',
                'outlet'      => 'Outlet',
                'type'        => 'Type',
                'income'      => 'Income (入金)',
                'expense'     => 'Expense (出金)',
                'category'    => 'Category',
                'description' => 'Description',
                'amount'      => 'Amount',
                'date'        => 'Date',
                'reference'   => 'Reference No.',
                'save'        => 'Save',
            ],

            'categories' => [
                'rent'        => 'Rent',
                'electricity' => 'Electricity',
                'gas'         => 'Gas',
                'salary'      => 'Salary',
                'supplies'    => 'Supplies',
                'misc'        => 'Misc',
                'sales_other' => 'Sales / Other',
            ],

            'create-success' => 'Cash movement created successfully!',
            'delete-success' => 'Cash movement deleted successfully!',
        ],

        'fixed-expenses' => [
            'index' => [
                'title'           => 'Fixed Expenses',
                'add-btn'         => 'Add Fixed Expense',
                'apply-month-btn' => 'Apply This Month',

                'datagrid' => [
                    'id'          => 'ID',
                    'name'        => 'Name',
                    'category'    => 'Category',
                    'amount'      => 'Amount',
                    'billing_day' => 'Billing Day',
                    'active'      => 'Active',
                    'outlet'      => 'Outlet',
                    'actions'     => 'Actions',
                    'edit'        => 'Edit',
                    'delete'      => 'Delete',
                ],
            ],

            'create' => [
                'title'       => 'Add Fixed Expense',
                'back'        => 'Back to List',
                'outlet'      => 'Outlet',
                'name'        => 'Name',
                'category'    => 'Category',
                'amount'      => 'Amount',
                'billing-day' => 'Billing Day (1–31)',
                'notes'       => 'Notes',
                'active'      => 'Active',
                'save'        => 'Save',
            ],

            'edit' => [
                'title' => 'Edit Fixed Expense',
                'back'  => 'Back to List',
                'save'  => 'Update',
            ],

            'categories' => [
                'rent'        => 'Rent (家賃)',
                'electricity' => 'Electricity (電気)',
                'gas'         => 'Gas (ガス)',
                'water'       => 'Water (水道)',
                'salary'      => 'Salary (給料)',
                'insurance'   => 'Insurance (保険)',
                'lease'       => 'Lease (リース)',
                'other'       => 'Other (その他)',
            ],

            'create-success'      => 'Fixed expense created successfully!',
            'update-success'      => 'Fixed expense updated successfully!',
            'delete-success'      => 'Fixed expense deleted successfully!',
            'apply-month-success' => 'Fixed expenses applied for :month (:count records).',
        ],

        'profit-report' => [
            'index' => [
                'title'           => 'Profit Report (損益計算)',
                'month'           => 'Month',
                'outlet'          => 'Outlet',
                'all-outlets'     => 'All Outlets',
                'search'          => 'Search',
                'gross-sales'     => 'Gross Sales',
                'total-expense'   => 'Total Expense',
                'net-profit'      => 'Net Profit',
                'cash-income'     => 'Cash Income',
                'breakdown'       => 'P&L Breakdown',
                'fixed-breakdown' => 'Fixed Expense Detail',
                'waste-cost'      => 'Waste Cost',
                'cash-expenses'   => 'Cash Expenses',
                'fixed-cost'      => 'Fixed Costs',
                'name'            => 'Name',
                'category'        => 'Category',
                'amount'          => 'Amount',
            ],
        ],

    ],

    'emails' => [
        'dear'     => 'Dear :name',
        'greeting' => 'Greetings!',

        'registration' => [
            'message' => 'Congratulations! Your account has been created successfully. Please login to your account and start using the POS system.',
            'subject' => 'POS User Registration Mail',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Login',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
