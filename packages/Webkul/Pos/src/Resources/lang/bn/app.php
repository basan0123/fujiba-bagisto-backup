<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'অবৈধ শংসাপত্র।',
                'not-activated'       => 'আপনার অ্যাকাউন্টটি সক্রিয় করা হয়নি।',
                'verify-first'        => 'দয়া করে প্রথমে আপনার ইমেল যাচাই করুন।',
                'success'             => 'আপনি সফলভাবে লগ ইন করেছেন।',
            ],

            'logout' => [
                'no-login-agent' => 'কোনও এজেন্ট লগ ইন করেনি।',
                'success'        => 'আপনি সফলভাবে লগ আউট করেছেন।',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'আপনি যে পাসওয়ার্ডটি দিয়েছেন তা ভুল।',
                    'success'          => 'আপনার অ্যাকাউন্ট সফলভাবে আপডেট করা হয়েছে।',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'গ্রাহক সফলভাবে তৈরি হয়েছে!',
            'update-success' => 'গ্রাহক সফলভাবে আপডেট হয়েছে!',
            'delete-success' => 'গ্রাহক সফলভাবে মুছে ফেলা হয়েছে!',
            'delete-failed'  => 'গ্রাহক মুছে ফেলায় ব্যর্থ!',
            'pending-orders' => 'গ্রাহকের কিছু পেন্ডিং অর্ডার আছে!',
        ],

        'cart' => [
            'already-applied'     => 'কুপন ইতিমধ্যেই প্রয়োগ করা হয়েছে!',
            'coupon-applied'      => 'কুপন সফলভাবে প্রয়োগ হয়েছে!',
            'coupon-removed'      => 'কুপন সফলভাবে সরানো হয়েছে!',
            'create-success'      => 'কার্ট সফলভাবে তৈরি হয়েছে!',
            'invalid-coupon'      => 'অবৈধ কুপন কোড!',
            'item-add-success'    => 'পণ্যটি সফলভাবে কার্টে যুক্ত হয়েছে!',
            'item-remove-success' => 'পণ্যটি সফলভাবে কার্ট থেকে সরানো হয়েছে!',
            'item-update-success' => 'পণ্যটি সফলভাবে আপডেট হয়েছে!',
            'not-found'           => 'কার্ট পাওয়া যায়নি!',
        ],

        'payment' => [
            'title' => 'পস পেমেন্ট',

            'options' => [
                'cash' => [
                    'title'       => 'পস ক্যাশ পেমেন্ট',
                    'description' => 'এটি পস ক্যাশ পেমেন্ট।',
                ],

                'card' => [
                    'title'       => 'পস কার্ড পেমেন্ট',
                    'description' => 'এটি পস কার্ড পেমেন্ট।',
                ],

                'split' => [
                    'title'       => 'পস স্প্লিট পেমেন্ট',
                    'description' => 'এটি পস স্প্লিট পেমেন্ট।',
                ],
            ],

            'no-items' => 'পেমেন্ট চালিয়ে যেতে কার্টে কোনো আইটেম নেই।',
            'success'  => 'পেমেন্ট সফলভাবে সম্পন্ন হয়েছে!',
        ],

        'shipping' => [
            'title'       => 'পস শিপিং',
            'description' => 'এটি ফ্রি পস শিপিং।',
        ],

        'order' => [
            'sync-success' => 'অর্ডার সফলভাবে সিঙ্ক হয়েছে!',
        ],

        'return' => [
            'create-success'              => 'রিফান্ড সফলভাবে তৈরি হয়েছে',
            'creation-error'              => 'রিফান্ড তৈরি অনুমোদিত নয়।',
            'invalid-qty'                 => 'আমরা একটি অবৈধ পরিমাণ পেয়েছি ইনভয়েস আইটেমের জন্য।',
            'invalid-refund-amount-error' => 'রিফান্ডের পরিমাণ শূন্যের বেশি হওয়া উচিত।',
            'refund-limit-error'          => 'রিফান্ডের পরিমাণ :amount প্রক্রিয়াজাত করা যায় না।',
        ],

        'drawer' => [
            'create-success' => 'ড্রয়ার সফলভাবে খোলা হয়েছে!',
            'not-opened'     => 'ড্রয়ার খোলা হয়নি।',
            'close-success'  => 'ড্রয়ার সফলভাবে বন্ধ করা হয়েছে!',
        ],

        'products' => [
            'request-success' => 'পণ্যের অনুরোধ সফলভাবে জমা হয়েছে।',
            'create-success'  => 'পণ্য সফলভাবে তৈরি হয়েছে!',
        ],

        'reports' => [
            'orders'                  => 'অর্ডার',
            'average-order-value'     => 'গড় অর্ডার মান',
            'average-items-per-order' => 'প্রতি অর্ডারে গড় আইটেম',
            'discounted-offers'       => 'ছাড়যুক্ত অফার',
            'cash-payments'           => 'নগদ পেমেন্ট',
            'other-payments'          => 'অন্যান্য পেমেন্ট',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'অর্ডার নোট',
                    'note-not-available' => 'নোট উপলব্ধ নয়!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'ব্যাগিস্টো পয়েন্ট অফ সেল (পস) এক্সটেনশন।',
                    'title' => 'পয়েন্ট অফ সেল',

                    'settings' => [
                        'info'  => 'পস সক্ষম করুন, সাধারণ কনফিগারেশন সেট করুন, পস পণ্য এবং বিল রসিদ।',
                        'title' => 'সেটিংস',

                        'general' => [
                            'footer-content'       => 'ফুটার কনটেন্ট',
                            'footer-note'          => 'ফুটার নোট',
                            'frontend-url'         => 'ফ্রন্টএন্ড ইউআরএল',
                            'heading-on-login'     => 'লগইনে শিরোনাম',
                            'info'                 => 'সাধারণ সেটিংস পস ব্যবহারকারী পৃষ্ঠার জন্য কনফিগারেশন অনুমতি দেয়, লোগো, শিরোনাম, ফুটার কনটেন্ট, ফুটার নোট ইত্যাদি যোগ করে।',
                            'pos-logo'             => 'পস লোগো',
                            'status'               => 'স্থিতি',
                            'sub-heading-on-login' => 'লগইনে সাব-শিরোনাম',
                            'title'                => 'সাধারণ',
                        ],

                        'barcode' => [
                            'height'             => 'উচ্চতা',
                            'hide-barcode'       => 'বারকোড লুকান',
                            'info'               => 'বারকোড সেটিংস বারকোড জেনারেশন, বারকোড উচ্চতা, বারকোড প্রস্থ, বারকোড প্রকার ইত্যাদির জন্য কনফিগারেশন অনুমতি দেয়।',
                            'prefix'             => 'প্রিফিক্স',
                            'print-product-name' => 'পণ্যের নাম মুদ্রণ করুন',
                            'title'              => 'বারকোড',
                            'width'              => 'প্রস্থ',

                            'generate-with' => [
                                'title' => 'বারকোড তৈরি করুন',

                                'options' => [
                                    'product-id' => 'পণ্য আইডি',
                                    'sku'        => 'পণ্য এসকিউ',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'কাস্টম পণ্যের জন্য SKU অনুমতি দিন',
                            'info'      => 'পণ্য সেটিংস পণ্যের SKU-এর জন্য কনফিগারেশনগুলি অনুমোদন করে।',
                            'title'     => 'পণ্য',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'পণ্য বরাদ্দ করুন',
            'banks'            => 'ব্যাংক',
            'barcode-products' => 'বারকোড পণ্য',
            'create'           => 'তৈরি করুন',
            'delete'           => 'মুছে ফেলুন',
            'edit'             => 'সম্পাদনা করুন',
            'generate-barcode' => 'বারকোড তৈরি করুন',
            'orders'           => 'অর্ডার',
            'outlets'          => 'আউটলেট',
            'pos'              => 'পয়েন্ট অফ সেল (পিওএস)',
            'preview'          => 'পূর্বরূপ',
            'print-barcode'    => 'বারকোড প্রিন্ট করুন',
            'receipts'         => 'রসিদ',
            'requests'         => 'অনুরোধ',
            'sales-report'     => 'বিক্রয় প্রতিবেদন',
            'users'            => 'এজেন্ট',
            'view'             => 'দেখুন',
        ],

        'layouts' => [
            'banks'            => 'ব্যাংক',
            'barcode-products' => 'বারকোড পণ্য',
            'orders'           => 'অর্ডার',
            'pos'              => 'পয়েন্ট অফ সেল (পিওএস)',
            'receipts'         => 'রসিদ',
            'requests'         => 'অনুরোধ',
            'sales-report'     => 'বিক্রয় প্রতিবেদন',

            'users' => [
                'agents'   => 'এজেন্ট',
                'outlets'  => 'আউটলেট',
                'title'    => 'এজেন্ট',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'এজেন্ট তৈরি করুন',
                    'pos-front'  => 'পিওএস ফ্রন্ট',
                    'title'      => 'এজেন্ট',

                    'datagrid' => [
                        'action'              => 'অ্যাকশন',
                        'delete'              => 'মুছে ফেলুন',
                        'edit'                => 'সম্পাদনা',
                        'email'               => 'ইমেল',
                        'full-name'           => 'পূর্ণ নাম',
                        'id'                  => 'আইডি',
                        'id-value'            => 'আইডি - :id',
                        'mass-delete-success' => 'নির্বাচিত এজেন্ট সফলভাবে মুছে ফেলা হয়েছে!',
                        'mass-update-success' => 'নির্বাচিত এজেন্ট সফলভাবে আপডেট করা হয়েছে!',
                        'outlet-name'         => 'আউটলেটের নাম',
                        'profile-image'       => 'প্রোফাইল ছবি',
                        'update-status'       => 'অবস্থা আপডেট করুন',
                        'username'            => 'ইউজারনেম',

                        'status' => [
                            'title' => 'স্ট্যাটাস',

                            'options' => [
                                'active'  => 'সক্রিয়',
                                'disable' => 'অক্রিয়',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'পিছনে',
                    'confirm-password'  => 'পাসওয়ার্ড নিশ্চিত করুন',
                    'email'             => 'ইমেল',
                    'first-name'        => 'প্রথম নাম',
                    'general'           => 'সাধারণ',
                    'image'             => 'ছবি',
                    'last-name'         => 'শেষ নাম',
                    'outlet'            => 'আউটলেট',
                    'outlet-and-status' => 'আউটলেট এবং স্ট্যাটাস',
                    'password'          => 'পাসওয়ার্ড',
                    'save-btn'          => 'এজেন্ট সংরক্ষণ করুন',
                    'select-outlet'     => 'আউটলেট নির্বাচন করুন',
                    'status'            => 'স্ট্যাটাস',
                    'title'             => 'এজেন্ট যোগ করুন',
                    'user-image'        => 'এজেন্টের ছবি আপলোড করুন',
                    'username'          => 'ইউজারনেম',
                ],

                'edit' => [
                    'back-btn'          => 'পিছনে',
                    'confirm-password'  => 'পাসওয়ার্ড নিশ্চিত করুন',
                    'email'             => 'ইমেল',
                    'first-name'        => 'প্রথম নাম',
                    'general'           => 'সাধারণ',
                    'image'             => 'ছবি',
                    'last-name'         => 'শেষ নাম',
                    'outlet'            => 'আউটলেট',
                    'outlet-and-status' => 'আউটলেট এবং স্ট্যাটাস',
                    'password'          => 'পাসওয়ার্ড',
                    'save-btn'          => 'এজেন্ট সংরক্ষণ করুন',
                    'select-outlet'     => 'আউটলেট নির্বাচন করুন',
                    'status'            => 'স্ট্যাটাস',
                    'title'             => 'এজেন্ট সম্পাদনা করুন',
                    'user-image'        => 'এজেন্টের ছবি আপলোড করুন',
                    'username'          => 'ইউজারনেম',
                ],

                'create-success' => 'এজেন্ট সফলভাবে তৈরি হয়েছে!',
                'delete-failed'  => 'এজেন্ট মুছে ফেলার চেষ্টা ব্যর্থ হয়েছে!',
                'delete-success' => 'এজেন্ট সফলভাবে মুছে ফেলা হয়েছে!',
                'update-success' => 'এজেন্ট সফলভাবে আপডেট হয়েছে!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'আউটলেট তৈরি করুন',
                    'pos-front'  => 'পিওএস ফ্রন্ট',
                    'title'      => 'আউটলেট',

                    'datagrid' => [
                        'action'              => 'অ্যাকশন',
                        'active'              => 'সক্রিয়',
                        'assign'              => 'পণ্য বরাদ্দ করুন',
                        'delete'              => 'মুছে ফেলুন',
                        'edit'                => 'সম্পাদনা করুন',
                        'id'                  => 'আইডি',
                        'inactive'            => 'অক্রিয়',
                        'inventory-source'    => 'ইনভেন্টরি সোর্স',
                        'mass-delete-success' => 'নির্বাচিত দোকানগুলি সফলভাবে মুছে ফেলা হয়েছে!',
                        'mass-update-success' => 'নির্বাচিত দোকানগুলি সফলভাবে আপডেট হয়েছে!',
                        'name'                => 'নাম',
                        'receipt-title'       => 'রসিদের শিরোনাম',
                        'status'              => 'স্ট্যাটাস',
                        'title'               => 'পস স্টোর তালিকা',
                        'update-status'       => 'স্ট্যাটাস আপডেট করুন',
                    ],
                ],

                'create' => [
                    'address'                 => 'ঠিকানা',
                    'back-btn'                => 'পিছনে',
                    'btn-title'               => 'আউটলেট সংরক্ষণ করুন',
                    'city'                    => 'শহর',
                    'country'                 => 'দেশ',
                    'customer-care-number'    => 'গ্রাহক সেবা নম্বর',
                    'email'                   => 'ইমেইল',
                    'general'                 => 'সাধারণ',
                    'gst-number'              => 'GST নম্বর',
                    'inventory'               => 'ইনভেন্টরি',
                    'inventory-source'        => 'ইনভেন্টরি উৎস',
                    'low-stock-qty'           => 'কম মজুদ পরিমাণ',
                    'name'                    => 'আউটলেটের নাম',
                    'phone'                   => 'ফোন',
                    'postcode'                => 'পোস্টকোড',
                    'receipt'                 => 'রশিদ',
                    'select-country'          => 'দেশ নির্বাচন করুন',
                    'select-inventory-source' => 'ইনভেন্টরি উৎস নির্বাচন করুন',
                    'select-receipt'          => 'রশিদ নির্বাচন করুন',
                    'state'                   => 'রাজ্য',
                    'status'                  => 'অবস্থা',
                    'store-address'           => 'আউটলেটের ঠিকানা',
                    'title'                   => 'আউটলেট যোগ করুন',
                    'website'                 => 'ওয়েবসাইট',
                ],

                'edit' => [
                    'address'                 => 'ঠিকানা',
                    'back-btn'                => 'পিছনে',
                    'btn-title'               => 'আউটলেট সংরক্ষণ করুন',
                    'city'                    => 'শহর',
                    'country'                 => 'দেশ',
                    'customer-care-number'    => 'গ্রাহক সেবা নম্বর',
                    'email'                   => 'ইমেইল',
                    'general'                 => 'সাধারণ',
                    'gst-number'              => 'GST নম্বর',
                    'inventory'               => 'ইনভেন্টরি',
                    'inventory-source'        => 'ইনভেন্টরি উৎস',
                    'low-stock-qty'           => 'কম মজুদ পরিমাণ',
                    'name'                    => 'আউটলেটের নাম',
                    'phone'                   => 'ফোন',
                    'postcode'                => 'পোস্টকোড',
                    'receipt'                 => 'রশিদ',
                    'select-country'          => 'দেশ নির্বাচন করুন',
                    'select-inventory-source' => 'ইনভেন্টরি উৎস নির্বাচন করুন',
                    'select-receipt'          => 'রশিদ নির্বাচন করুন',
                    'state'                   => 'রাজ্য',
                    'status'                  => 'অবস্থা',
                    'store-address'           => 'আউটলেটের ঠিকানা',
                    'title'                   => 'আউটলেট সম্পাদনা করুন',
                    'website'                 => 'ওয়েবসাইট',
                ],

                'assign' => [
                    'back-btn' => 'পিছনে',
                    'title'    => 'আউটলেট পণ্য পরিচালনা করুন',

                    'datagrid' => [
                        'active'              => 'সক্রিয়',
                        'assign'              => 'বরাদ্দ করুন',
                        'disable'             => 'অক্রিয়',
                        'id'                  => 'আইডি',
                        'id-value'            => 'আইডি - :id',
                        'image'               => 'ছবি',
                        'mass-assign-success' => 'পণ্য বরাদ্দ সফলভাবে আপডেট হয়েছে!',
                        'name'                => 'নাম',
                        'out-of-stock'        => 'স্টক শেষ',
                        'pos-status'          => 'পস স্ট্যাটাস',
                        'price'               => 'মূল্য',
                        'product-image'       => 'পণ্যের ছবি',
                        'qty'                 => 'পরিমাণ',
                        'qty-value'           => ':qty উপলব্ধ',
                        'sku'                 => 'এসকিউ',
                        'sku-value'           => 'এসকিউ - :sku',
                        'status'              => 'স্ট্যাটাস',
                        'type'                => 'প্রকার',
                        'unassign'            => 'অবরাদ্দ করুন',
                        'update-assign'       => 'বরাদ্দ আপডেট করুন',
                    ],
                ],

                'create-success' => 'আউটলেট সফলভাবে তৈরি হয়েছে!',
                'delete-failed'  => 'আউটলেট মুছে ফেলার চেষ্টা ব্যর্থ হয়েছে!',
                'delete-success' => 'আউটলেট সফলভাবে মুছে ফেলা হয়েছে!',
                'update-success' => 'আউটলেট সফলভাবে আপডেট হয়েছে!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'বারকোড পণ্য',

                'datagrid' => [
                    'barcode'               => 'বারকোড',
                    'generate-barcode'      => 'বারকোড তৈরি করুন',
                    'print-barcode'         => 'বারকোড প্রিন্ট করুন',
                    'id'                    => 'আইডি',
                    'id-value'              => 'আইডি - :id',
                    'image'                 => 'ছবি',
                    'mass-generate-success' => 'নির্বাচিত পণ্যের বারকোড সফলভাবে তৈরি হয়েছে!',
                    'name'                  => 'নাম',
                    'out-of-stock'          => 'স্টক শেষ',
                    'price'                 => 'মূল্য',
                    'product-image'         => 'পণ্যের ছবি',
                    'qty'                   => 'পরিমাণ',
                    'qty-value'             => ':qty উপলব্ধ',
                    'sku'                   => 'এসকিউ',
                    'sku-value'             => 'এসকিউ - :sku',

                    'status' => [
                        'title' => 'স্ট্যাটাস',

                        'options' => [
                            'active'  => 'সক্রিয়',
                            'disable' => 'অক্রিয়',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'পিছনে',
                'btn-title' => 'প্রিন্ট',
                'qty'       => 'পরিমাণ',
                'title'     => 'বারকোড প্রিন্ট করুন',
            ],

            'generate-failed'  => 'বারকোড তৈরি করতে ব্যর্থ!',
            'generate-success' => 'বারকোড সফলভাবে তৈরি হয়েছে!',
        ],

        'orders' => [
            'index' => [
                'title' => 'অর্ডার',

                'datagrid' => [
                    'customer-name' => 'গ্রাহকের নাম',
                    'grand-total'   => 'মোট',
                    'order-date'    => 'অর্ডার তারিখ',
                    'order-id'      => 'অর্ডার আইডি',
                    'order-ref-id'  => 'অর্ডার রেফারেন্স আইডি',
                    'view'          => 'দেখুন',

                    'status' => [
                        'title' => 'স্ট্যাটাস',

                        'options' => [
                            'canceled'        => 'বাতিল',
                            'closed'          => 'বদ্ধ',
                            'completed'       => 'সম্পন্ন',
                            'fraud'           => 'প্রতারণা',
                            'pending'         => 'অপেক্ষমাণ',
                            'pending-payment' => 'পেমেন্টের অপেক্ষায়',
                            'processing'      => 'প্রক্রিয়াধীন',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'অনুরোধ',

                'datagrid' => [
                    'id'                  => 'আইডি',
                    'product-image'       => 'পণ্যের ছবি',
                    'mass-update-error'   => 'অনুরোধ আপডেট ব্যর্থ!',
                    'mass-update-success' => 'নির্বাচিত অনুরোধ সফলভাবে আপডেট হয়েছে!',
                    'product-name'        => 'পণ্যের নাম',
                    'outlet-name'         => 'আউটলেটের নাম',
                    'qty-value'           => 'পরিমাণ - :qty',
                    'request-date'        => 'অনুরোধের তারিখ',
                    'requested-qty'       => 'অনুরোধকৃত পরিমাণ',
                    'update-status'       => 'স্ট্যাটাস আপডেট করুন',
                    'user-name'           => 'ব্যবহারকারীর নাম',

                    'status' => [
                        'title' => 'স্ট্যাটাস',

                        'options' => [
                            'complete' => 'সম্পূর্ণ',
                            'decline'  => 'অস্বীকার',
                            'pending'  => 'অপেক্ষমাণ',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'পিছনে',
                'btn-title' => 'সংরক্ষণ করুন',
                'title'     => 'অনুরোধকৃত পণ্যের বিবরণ #:id',

                'user-info' => [
                    'email'            => 'ইমেইল',
                    'name'             => 'নাম',
                    'outlet-address'   => 'আউটলেটের ঠিকানা',
                    'outlet-inventory' => 'আউটলেট ইনভেন্টরি সোর্স',
                    'outlet-name'      => 'আউটলেটের নাম',
                    'title'            => 'ব্যবহারকারী তথ্য',
                ],

                'request-info' => [
                    'comment'       => 'মন্তব্য',
                    'product-name'  => 'পণ্যের নাম',
                    'qty-value'     => 'পরিমাণ - :qty',
                    'request-date'  => 'অনুরোধের তারিখ',
                    'requested-qty' => 'অনুরোধকৃত পরিমাণ',
                    'title'         => 'অনুরোধের তথ্য',

                    'status' => [
                        'title' => 'স্ট্যাটাস',

                        'options' => [
                            'complete' => 'সম্পূর্ণ',
                            'decline'  => 'অস্বীকার',
                            'pending'  => 'অপেক্ষমাণ',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'অনুরোধ আপডেট করতে ব্যর্থ!',
            'update-success' => 'অনুরোধ সফলভাবে আপডেট হয়েছে!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'ব্যাংক তৈরি করুন',
                'title'     => 'ব্যাংক',

                'datagrid' => [
                    'active'              => 'সক্রিয়',
                    'address'             => 'ব্যাংকের ঠিকানা',
                    'agent-name'          => 'এজেন্ট',
                    'delete'              => 'মুছুন',
                    'disable'             => 'অক্ষম করুন',
                    'id'                  => 'আইডি',
                    'mass-delete-success' => 'নির্বাচিত ব্যাংকগুলি সফলভাবে মুছে ফেলা হয়েছে!',
                    'name'                => 'ব্যাংকের নাম',
                    'status'              => 'স্থিতি',
                ],
            ],

            'create' => [
                'back-btn'  => 'পিছনে',
                'btn-title' => 'ব্যাংক সংরক্ষণ করুন',
                'title'     => 'নতুন ব্যাংক তৈরি করুন',

                'general' => [
                    'address' => 'ঠিকানা',
                    'email'   => 'ইমেইল',
                    'name'    => 'নাম',
                    'phone'   => 'ফোন',
                    'title'   => 'সাধারণ',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS এজেন্ট নিয়োগ করুন',
                    'bank-status'  => 'ব্যাংকের স্ট্যাটাস',
                    'select-agent' => 'এজেন্ট নির্বাচন করুন',
                    'title'        => 'POS এজেন্ট ও ব্যাংকের স্ট্যাটাস',
                ],
            ],

            'edit' => [
                'back-btn'  => 'পিছনে',
                'btn-title' => 'ব্যাংক সংরক্ষণ করুন',
                'title'     => 'ব্যাংক সম্পাদনা করুন',

                'general' => [
                    'address' => 'ঠিকানা',
                    'email'   => 'ইমেইল',
                    'name'    => 'নাম',
                    'phone'   => 'ফোন',
                    'title'   => 'সাধারণ',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS এজেন্ট নিয়োগ করুন',
                    'bank-status'  => 'ব্যাংকের স্ট্যাটাস',
                    'select-agent' => 'এজেন্ট নির্বাচন করুন',
                    'title'        => 'POS এজেন্ট ও ব্যাংকের স্ট্যাটাস',
                ],
            ],

            'create-success' => 'ব্যাংক সফলভাবে তৈরি হয়েছে!',
            'delete-failed'  => 'ব্যাংক মুছে ফেলা ব্যর্থ!',
            'delete-success' => 'ব্যাংক সফলভাবে মুছে ফেলা হয়েছে!',
            'update-success' => 'ব্যাংক সফলভাবে আপডেট হয়েছে!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'বিক্রয় প্রতিবেদন',

                'datagrid' => [
                    'bank-name'      => 'ব্যাংকের নাম',
                    'grand-total'    => 'মোট',
                    'order-date'     => 'অর্ডারের তারিখ',
                    'order-id'       => 'অর্ডার আইডি',
                    'order-id-value' => 'আইডি - :id',
                    'order-note'     => 'অর্ডার নোট',
                    'outlet-name'    => 'আউটলেটের নাম',
                    'payment-method' => 'পেমেন্ট পদ্ধতি',
                    'view'           => 'দেখুন',

                    'status' => [
                        'title' => 'স্ট্যাটাস',

                        'options' => [
                            'canceled'        => 'বাতিল',
                            'closed'          => 'বদ্ধ',
                            'completed'       => 'সম্পন্ন',
                            'fraud'           => 'প্রতারণা',
                            'pending'         => 'অপেক্ষমাণ',
                            'pending-payment' => 'পেমেন্টের অপেক্ষায়',
                            'processing'      => 'প্রক্রিয়াধীন',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'রসিদ তৈরি করুন',
                'title'      => 'রসিদ',

                'datagrid' => [
                    'delete'              => 'মুছুন',
                    'edit'                => 'সম্পাদনা করুন',
                    'id'                  => 'আইডি',
                    'mass-delete-success' => 'নির্বাচিত রসিদ সফলভাবে মুছে ফেলা হয়েছে!',
                    'preview'             => 'পূর্বরূপ',
                    'title'               => 'শিরোনাম',

                    'status' => [
                        'title' => 'স্ট্যাটাস',

                        'options' => [
                            'active'   => 'সক্রিয়',
                            'inactive' => 'নিষ্ক্রিয়',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'পিছনে',
                'btn-title' => 'রসিদ সংরক্ষণ করুন',
                'title'     => 'নতুন রসিদ তৈরি করুন',

                'general' => [
                    'cashier-name-label'      => 'ক্যাশিয়ার নাম লেবেল',
                    'change-amount-label'     => 'পরিবর্তন পরিমাণ লেবেল',
                    'credit-amount-label'     => 'ক্রেডিট পরিমাণ লেবেল',
                    'discount-amt-label'      => 'ডিসকাউন্ট পরিমাণ লেবেল',
                    'display-cashier-name'    => 'ক্যাশিয়ার নাম প্রদর্শন করুন',
                    'display-change-amount'   => 'পরিবর্তন পরিমাণ প্রদর্শন করুন',
                    'display-credit-amount'   => 'ক্রেডিট পরিমাণ প্রদর্শন করুন',
                    'display-customer-name'   => 'গ্রাহকের নাম প্রদর্শন করুন',
                    'display-date'            => 'তারিখ প্রদর্শন করুন',
                    'display-discount-amt'    => 'ডিসকাউন্ট পরিমাণ প্রদর্শন করুন',
                    'display-order-id'        => 'অর্ডার আইডি প্রদর্শন করুন',
                    'display-outlet-address'  => 'আউটলেট ঠিকানা প্রদর্শন করুন',
                    'display-outlet-name'     => 'আউটলেট নাম প্রদর্শন করুন',
                    'display-sub-total'       => 'সাব টোটাল প্রদর্শন করুন',
                    'display-tax'             => 'কর প্রদর্শন করুন',
                    'grand-total-label'       => 'গ্র্যান্ড টোটাল লেবেল',
                    'order-id-label'          => 'অর্ডার আইডি লেবেল',
                    'receipt-title'           => 'রসিদ শিরোনাম',
                    'show-order-barcode'      => 'অর্ডার বারকোড দেখান',
                    'show-print-confirmation' => 'প্রিন্ট নিশ্চিতকরণ দেখান',
                    'status'                  => 'স্ট্যাটাস',
                    'sub-total-label'         => 'সাব টোটাল লেবেল',
                    'tax-label'               => 'কর লেবেল',
                    'title'                   => 'সাধারণ',
                ],

                'logo' => [
                    'display-logo' => 'লোগো প্রদর্শন করুন',
                    'logo-alt'     => 'লোগো বিকল্প',
                    'logo-height'  => 'লোগোর উচ্চতা (পিক্সেলে)',
                    'logo-width'   => 'লোগোর প্রস্থ (পিক্সেলে)',
                    'title'        => 'লোগো',
                    'upload-logo'  => 'লোগো আপলোড করুন',
                ],

                'header' => [
                    'header-content' => 'হেডার কনটেন্ট',
                    'title'          => 'হেডার',
                ],

                'footer' => [
                    'footer-content' => 'ফুটার কনটেন্ট',
                    'title'          => 'ফুটার',
                ],
            ],

            'edit' => [
                'back-btn'  => 'পিছনে',
                'btn-title' => 'রসিদ সংরক্ষণ করুন',
                'title'     => 'রসিদ সম্পাদনা করুন',

                'general' => [
                    'cashier-name-label'      => 'ক্যাশিয়ার নাম লেবেল',
                    'change-amount-label'     => 'পরিবর্তন পরিমাণ লেবেল',
                    'credit-amount-label'     => 'ক্রেডিট পরিমাণ লেবেল',
                    'discount-amt-label'      => 'ডিসকাউন্ট পরিমাণ লেবেল',
                    'display-cashier-name'    => 'ক্যাশিয়ার নাম প্রদর্শন করুন',
                    'display-change-amount'   => 'পরিবর্তন পরিমাণ প্রদর্শন করুন',
                    'display-credit-amount'   => 'ক্রেডিট পরিমাণ প্রদর্শন করুন',
                    'display-customer-name'   => 'গ্রাহকের নাম প্রদর্শন করুন',
                    'display-date'            => 'তারিখ প্রদর্শন করুন',
                    'display-discount-amt'    => 'ডিসকাউন্ট পরিমাণ প্রদর্শন করুন',
                    'display-order-id'        => 'অর্ডার আইডি প্রদর্শন করুন',
                    'display-outlet-address'  => 'আউটলেট ঠিকানা প্রদর্শন করুন',
                    'display-outlet-name'     => 'আউটলেট নাম প্রদর্শন করুন',
                    'display-sub-total'       => 'সাব টোটাল প্রদর্শন করুন',
                    'display-tax'             => 'কর প্রদর্শন করুন',
                    'grand-total-label'       => 'গ্র্যান্ড টোটাল লেবেল',
                    'order-id-label'          => 'অর্ডার আইডি লেবেল',
                    'receipt-title'           => 'রসিদ শিরোনাম',
                    'show-order-barcode'      => 'অর্ডার বারকোড দেখান',
                    'show-print-confirmation' => 'প্রিন্ট নিশ্চিতকরণ দেখান',
                    'status'                  => 'স্ট্যাটাস',
                    'sub-total-label'         => 'সাব টোটাল লেবেল',
                    'tax-label'               => 'কর লেবেল',
                    'title'                   => 'সাধারণ',
                ],

                'logo' => [
                    'display-logo' => 'লোগো প্রদর্শন করুন',
                    'logo-alt'     => 'লোগো বিকল্প',
                    'logo-height'  => 'লোগোর উচ্চতা (পিক্সেলে)',
                    'logo-width'   => 'লোগোর প্রস্থ (পিক্সেলে)',
                    'title'        => 'লোগো',
                    'upload-logo'  => 'লোগো আপলোড করুন',
                ],

                'header' => [
                    'header-content' => 'হেডার কনটেন্ট',
                    'title'          => 'হেডার',
                ],

                'footer' => [
                    'footer-content' => 'ফুটার কনটেন্ট',
                    'title'          => 'ফুটার',
                ],
            ],

            'preview' => [
                'amount'         => 'পরিমাণ',
                'cashier'        => 'ক্যাশিয়ার',
                'change-amount'  => 'পরিবর্তন',
                'customer'       => 'গ্রাহক',
                'customer-email' => 'গ্রাহকের ইমেইল',
                'customer-name'  => 'গ্রাহকের নাম',
                'customer-phone' => 'গ্রাহকের ফোন',
                'date'           => 'তারিখ',
                'discount'       => 'ডিসকাউন্ট',
                'email'          => 'ইমেইল',
                'grand-total'    => 'গ্র্যান্ড টোটাল',
                'order-id'       => 'অর্ডার আইডি',
                'phone'          => 'ফোন',
                'price'          => 'মূল্য',
                'product'        => 'পণ্য',
                'qty'            => 'পরিমাণ',
                'sub-total'      => 'সাব টোটাল',
                'tax'            => 'কর',
                'title'          => 'রসিদ পূর্বরূপ',
                'total-qty'      => 'মোট পরিমাণ',
            ],

            'create-success' => 'রসিদ সফলভাবে তৈরি হয়েছে!',
            'delete-failed'  => 'রসিদ মুছে ফেলা ব্যর্থ!',
            'delete-success' => 'রসিদ সফলভাবে মুছে ফেলা হয়েছে!',
            'update-success' => 'রসিদ সফলভাবে আপডেট হয়েছে!',
        ],
    ],

    'emails' => [
        'dear'     => 'প্রিয় :name',
        'greeting' => 'নমস্কার!',

        'registration' => [
            'message' => 'অভিনন্দন! আপনার অ্যাকাউন্ট সফলভাবে তৈরি করা হয়েছে। দয়া করে আপনার অ্যাকাউন্টে লগইন করুন এবং POS সিস্টেম ব্যবহার শুরু করুন।',
            'subject' => 'POS ব্যবহারকারী রেজিস্ট্রেশন মেইল',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'লগইন করুন',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
