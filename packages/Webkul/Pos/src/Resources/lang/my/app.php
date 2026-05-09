<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'အကောင့်ဝင်အချက်အလက်များ မှားယွင်းနေပါသည်။',
                'not-activated'       => 'သင်၏အကောင့်ကို အသက်မသွင်းရသေးပါ။',
                'verify-first'        => 'ကျေးဇူးပြု၍ သင်၏အီးမေးလ်ကို အရင်စစ်ဆေးပေးပါ။',
                'success'             => 'အကောင့်ဝင်ခြင်း အောင်မြင်ပါသည်။',
            ],

            'logout' => [
                'no-login-agent' => 'ဝင်ရောက်ထားသော အရောင်းဝန်ထမ်း မရှိပါ။',
                'success'        => 'အကောင့်ထွက်ခြင်း အောင်မြင်ပါသည်။',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'သင်ရိုက်ထည့်လိုက်သော စကားဝှက် မှားယွင်းနေပါသည်။',
                    'success'          => 'သင်၏အကောင့်ကို အောင်မြင်စွာ အပ်ဒိတ်လုပ်ပြီးပါပြီ။',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'ဝယ်ယူသူအသစ်ကို အောင်မြင်စွာ ထည့်သွင်းပြီးပါပြီ။',
            'update-success' => 'ဝယ်ယူသူအချက်အလက်ကို အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။',
            'delete-success' => 'ဝယ်ယူသူကို အောင်မြင်စွာ ဖျက်သိမ်းပြီးပါပြီ။',
            'delete-failed'  => 'ဝယ်ယူသူကို ဖျက်သိမ်း၍ မရနိုင်ပါ။',
            'pending-orders' => 'ဝယ်ယူသူတွင် လုပ်ဆောင်ဆဲ အော်ဒါများ ရှိနေပါသည်။',
        ],

        'credit' => [
            'adjust-success'    => 'ဝယ်ယူသူအကြွေးအား အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။',
            'manager-required'  => 'မန်နေဂျာအဆင့် ခွင့်ပြုချက် လိုအပ်ပါသည်။',
            'insufficient'      => 'လက်ကျန်ငွေ မလုံလောက်ပါ။ လက်ရှိ လက်ကျန်မှာ :balance ဖြစ်သည်။',
            'customer-missing'  => 'ဝယ်ယူသူ မတွေ့ရှိပါ။',
        ],

        'cart' => [
            'already-applied'     => 'ကူပွန်ကို အသုံးပြုပြီးသား ဖြစ်သည်။',
            'coupon-applied'      => 'ကူပွန်ကို အောင်မြင်စွာ အသုံးပြုပြီးပါပြီ။',
            'coupon-removed'      => 'ကူပွန်ကို အောင်မြင်စွာ ဖယ်ရှားပြီးပါပြီ။',
            'create-success'      => 'စျေးဝယ်လှည်းကို အောင်မြင်စွာ ဖန်တီးပြီးပါပြီ။',
            'invalid-coupon'      => 'ကူပွန်ကုဒ် မမှန်ကန်ပါ။',
            'item-add-success'    => 'ကုန်ပစ္စည်းကို စျေးဝယ်လှည်းထဲသို့ အောင်မြင်စွာ ထည့်ပြီးပါပြီ။',
            'item-remove-success' => 'ကုန်ပစ္စည်းကို စျေးဝယ်လှည်းထဲမှ အောင်မြင်စွာ ဖယ်ရှားပြီးပါပြီ။',
            'item-update-success' => 'ကုန်ပစ္စည်းကို အောင်မြင်စွာ အပ်ဒိတ်လုပ်ပြီးပါပြီ။',
            'not-found'           => 'စျေးဝယ်လှည်းကို ရှာမတွေ့ပါ။',
        ],

        'payment' => [
            'title' => 'POS ငွေပေးချေမှု',

            'options' => [
                'cash' => [
                    'title'       => 'POS လက်ငင်းငွေသားဖြင့် ပေးချေမှု',
                    'description' => '၎င်းသည် POS လက်ငင်းငွေသားဖြင့် ပေးချေမှုဖြစ်သည်။',
                ],

                'card' => [
                    'title'       => 'POS ကတ်ဖြင့် ပေးချေမှု',
                    'description' => '၎င်းသည် POS ကတ်ဖြင့် ပေးချေမှုဖြစ်သည်။',
                ],

                'split' => [
                    'title'       => 'POS ခွဲ၍ ငွေပေးချေမှု',
                    'description' => '၎င်းသည် POS ခွဲ၍ ငွေပေးချေမှုဖြစ်သည်။',
                ],
            ],

            'no-items' => 'ငွေပေးချေရန် စျေးဝယ်လှည်းထဲတွင် ပစ္စည်းမရှိပါ။',
            'success'  => 'ငွေပေးချေမှု အောင်မြင်စွာ ပြီးဆုံးပါပြီ။',
        ],

        'shipping' => [
            'title'       => 'POS ပို့ဆောင်မှု',
            'description' => '၎င်းသည် အခမဲ့ POS ပို့ဆောင်မှုဖြစ်သည်။',
        ],

        'order' => [
            'sync-success' => 'အော်ဒါကို အောင်မြင်စွာ ချိတ်ဆက် (Sync) ပြီးပါပြီ။',
        ],

        'return' => [
            'create-success'              => 'ငွေပြန်အမ်းခြင်း အောင်မြင်စွာ ပြုလုပ်ပြီးပါပြီ',
            'creation-error'              => 'ငွေပြန်အမ်းခြင်းကို ခွင့်မပြုပါ။',
            'invalid-qty'                 => 'ဘောက်ချာထုတ်ရန် ပစ္စည်းအရေအတွက် မမှန်ကန်ပါ။',
            'invalid-refund-amount-error' => 'ပြန်အမ်းငွေပမာဏသည် သုည မဖြစ်ရပါ။',
            'refund-limit-error'          => 'ပြန်အမ်းငွေပမာဏ :amount ကို ဆောင်ရွက်၍ မရပါ။',
        ],

        'drawer' => [
            'create-success' => 'ငွေအံဆွဲကို အောင်မြင်စွာ ဖွင့်ပြီးပါပြီ။',
            'not-opened'     => 'ငွေအံဆွဲကို ဖွင့်မထားပါ။',
            'close-success'  => 'ငွေအံဆွဲကို အောင်မြင်စွာ ပိတ်ပြီးပါပြီ။',
        ],

        'products' => [
            'request-success'     => 'ကုန်ပစ္စည်း တောင်းဆိုမှုကို အောင်မြင်စွာ ပေးပို့ပြီးပါပြီ။',
            'create-success'      => 'ကုန်ပစ္စည်းကို အောင်မြင်စွာ ဖန်တီးပြီးပါပြီ။',
            'update-success'      => 'ကုန်ပစ္စည်းများကို အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။',
            'barcode-linked'      => 'ဘားကုဒ်ကို ထုတ်ကုန်နှင့် ချိတ်ဆက်ပြီးပါပြီ။',
            'not-in-outlet'       => 'ဤကုန်ပစ္စည်းကို သင့်ဆိုင်တွင် မသတ်မှတ်ထားပါ။',
            'image-updated'       => 'ကုန်ပစ္စည်းပုံ ပြောင်းပြီး။',
            'image-removed'       => 'ကုန်ပစ္စည်းပုံ ဖျက်ပြီး။',
            'image-upload-failed' => 'ပုံတင်ခြင်း မအောင်မြင်ပါ။ ပုံအရွယ်ငယ်ငယ် စမ်းပါ။',
        ],

        'reports' => [
            'orders'                  => 'အော်ဒါများ',
            'average-order-value'     => 'ပျမ်းမျှ အော်ဒါတန်ဖိုး',
            'average-items-per-order' => 'အော်ဒါတစ်ခုလျှင် ပျမ်းမျှပစ္စည်းအရေအတွက်',
            'discounted-offers'       => 'လျှော့စျေးကမ်းလှမ်းမှုများ',
            'cash-payments'           => 'ငွေသားပေးချေမှုများ',
            'other-payments'          => 'အခြားပေးချေမှုများ',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'အော်ဒါမှတ်ချက်',
                    'note-not-available' => 'မှတ်ချက် မရှိပါ။',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'Bagisto Point of Sale (POS) Extension ဖြစ်သည်။',
                    'title' => 'အရောင်းစက် (POS)',

                    'settings' => [
                        'info'  => 'POS ကို ဖွင့်ရန်၊ အထွေထွေပြင်ဆင်မှုများ၊ POS ကုန်ပစ္စည်းနှင့် ငွေပြေစာတို့ကို သတ်မှတ်ရန်။',
                        'title' => 'ဆက်တင်များ',

                        'general' => [
                            'footer-content'       => 'အောက်ခြေပါဝင်သည့်အကြောင်းအရာ',
                            'footer-note'          => 'အောက်ခြေမှတ်ချက်',
                            'frontend-url'         => 'Frontend URL',
                            'heading-on-login'     => 'အကောင့်ဝင်ရန် ခေါင်းစဉ်',
                            'info'                 => 'အထွေထွေဆက်တင်များမှတစ်ဆင့် POS အသုံးပြုသူစာမျက်နှာအတွက် Logo၊ ခေါင်းစဉ်များ၊ အောက်ခြေမှတ်ချက်များ စသည်တို့ကို ပြင်ဆင်နိုင်သည်။',
                            'pos-logo'             => 'POS Logo',
                            'status'               => 'အခြေအနေ',
                            'sub-heading-on-login' => 'အကောင့်ဝင်ရန် ခေါင်းစဉ်ငယ်',
                            'title'                => 'အထွေထွေ',
                        ],

                        'barcode' => [
                            'height'             => 'အမြင့်',
                            'hide-barcode'       => 'ဘားကုဒ်ကို ဝှက်ထားရန်',
                            'info'               => 'ဘားကုဒ်ဆက်တင်များမှတစ်ဆင့် ဘားကုဒ်ထုတ်လုပ်ခြင်း၊ အမြင့်၊ အကျယ်နှင့် အမျိုးအစားတို့ကို သတ်မှတ်နိုင်သည်။',
                            'prefix'             => 'ရှေ့ဆက်ကုဒ် (Prefix)',
                            'print-product-name' => 'ကုန်ပစ္စည်းအမည်ကို ရိုက်နှိပ်ရန်',
                            'title'              => 'ဘားကုဒ်',
                            'width'              => 'အကျယ်',

                            'generate-with' => [
                                'title' => 'ဘားကုဒ် ထုတ်လုပ်မည့်အခြေခံ',

                                'options' => [
                                    'product-id' => 'ကုန်ပစ္စည်း Id',
                                    'sku'        => 'ကုန်ပစ္စည်း SKU',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'စိတ်ကြိုက်ကုန်ပစ္စည်းအတွက် SKU ခွင့်ပြုရန်',
                            'info'      => 'ကုန်ပစ္စည်းဆက်တင်များမှတစ်ဆင့် ကုန်ပစ္စည်း SKU ကို ပြင်ဆင်နိုင်သည်။',
                            'title'     => 'ကုန်ပစ္စည်းများ',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'ကုန်ပစ္စည်းများ သတ်မှတ်ရန်',
            'banks'            => 'ဘဏ်များ',
            'barcode-products' => 'ဘားကုဒ်ပါသောကုန်ပစ္စည်းများ',
            'create'           => 'အသစ်ဖန်တီးရန်',
            'delete'           => 'ဖျက်ရန်',
            'edit'             => 'ပြင်ဆင်ရန်',
            'generate-barcode' => 'ဘားကုဒ်ထုတ်ရန်',
            'orders'           => 'အော်ဒါများ',
            'outlets'          => 'အရောင်းဆိုင်များ',
            'pos'              => 'အရောင်းစက် (POS)',
            'preview'          => 'နမူနာကြည့်ရန်',
            'print-barcode'    => 'ဘားကုဒ်ထုတ်ရန်',
            'receipts'         => 'ပြေစာများ',
            'requests'         => 'တောင်းဆိုချက်များ',
            'sales-report'     => 'အရောင်းအစီရင်ခံစာ',
            'users'            => 'အရောင်းဝန်ထမ်းများ',
            'view'             => 'ကြည့်ရှုရန်',
        ],

        'layouts' => [
            'banks'            => 'ဘဏ်များ',
            'barcode-products' => 'ဘားကုဒ်ပါသောကုန်ပစ္စည်းများ',
            'orders'           => 'အော်ဒါများ',
            'pos'              => 'အရောင်းစက် (POS)',
            'receipts'         => 'ပြေစာများ',
            'requests'         => 'တောင်းဆိုချက်များ',
            'sales-report'     => 'အရောင်းအစီရင်ခံစာ',

            'users' => [
                'agents'   => 'အရောင်းဝန်ထမ်းများ',
                'outlets'  => 'အရောင်းဆိုင်များ',
                'title'    => 'အရောင်းဝန်ထမ်းများ',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'ဝန်ထမ်းအသစ်ခန့်ရန်',
                    'pos-front'  => 'POS ရှေ့မျက်နှာပြင်',
                    'title'      => 'အရောင်းဝန်ထမ်းများ',

                    'datagrid' => [
                        'action'              => 'လုပ်ဆောင်ချက်',
                        'delete'              => 'ဖျက်ရန်',
                        'edit'                => 'ပြင်ရန်',
                        'email'               => 'အီးမေးလ်',
                        'full-name'           => 'နာမည်အပြည့်အစုံ',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'ရွေးချယ်ထားသော ဝန်ထမ်းများကို အောင်မြင်စွာ ဖျက်ပြီးပါပြီ။',
                        'mass-update-success' => 'ရွေးချယ်ထားသော ဝန်ထမ်းများကို အောင်မြင်စွာ အပ်ဒိတ်လုပ်ပြီးပါပြီ။',
                        'outlet-name'         => 'ဆိုင်ခွဲအမည်',
                        'profile-image'       => 'ပရိုဖိုင်ပုံ',
                        'update-status'       => 'အခြေအနေကို အပ်ဒိတ်လုပ်ရန်',
                        'username'            => 'အသုံးပြုသူအမည်',

                        'status' => [
                            'title' => 'အခြေအနေ',

                            'options' => [
                                'active'  => 'အသုံးပြုနေသည်',
                                'disable' => 'ရပ်ဆိုင်းထားသည်',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'နောက်သို့',
                    'confirm-password'  => 'စကားဝှက်ကို အတည်ပြုပါ',
                    'email'             => 'အီးမေးလ်',
                    'first-name'        => 'အမည်',
                    'general'           => 'အထွေထွေ',
                    'image'             => 'ပုံ',
                    'last-name'         => 'မျိုးရိုးအမည်',
                    'outlet'            => 'ဆိုင်ခွဲ',
                    'outlet-and-status' => 'ဆိုင်ခွဲနှင့် အခြေအနေ',
                    'password'          => 'စကားဝှက်',
                    'save-btn'          => 'ဝန်ထမ်းအချက်အလက် သိမ်းဆည်းရန်',
                    'select-outlet'     => 'ဆိုင်ခွဲရွေးချယ်ပါ',
                    'status'            => 'အခြေအနေ',
                    'title'             => 'ဝန်ထမ်းအသစ်ထည့်ရန်',
                    'user-image'        => 'ဝန်ထမ်းပုံတင်ရန်',
                    'username'          => 'အသုံးပြုသူအမည်',
                ],

                'edit' => [
                    'back-btn'          => 'နောက်သို့',
                    'confirm-password'  => 'စကားဝှက်ကို အတည်ပြုပါ',
                    'email'             => 'အီးမေးလ်',
                    'first-name'        => 'အမည်',
                    'general'           => 'အထွေထွေ',
                    'image'             => 'ပုံ',
                    'last-name'         => 'မျိုးရိုးအမည်',
                    'outlet'            => 'ဆိုင်ခွဲ',
                    'outlet-and-status' => 'ဆိုင်ခွဲနှင့် အခြေအနေ',
                    'password'          => 'စကားဝှက်',
                    'save-btn'          => 'ဝန်ထမ်းအချက်အလက် သိမ်းဆည်းရန်',
                    'select-outlet'     => 'ဆိုင်ခွဲရွေးချယ်ပါ',
                    'status'            => 'အခြေအနေ',
                    'title'             => 'ဝန်ထမ်းအချက်အလက်ပြင်ရန်',
                    'user-image'        => 'ဝန်ထမ်းပုံတင်ရန်',
                    'username'          => 'အသုံးပြုသူအမည်',
                ],

                'create-success' => 'အရောင်းဝန်ထမ်းအသစ်ကို အောင်မြင်စွာ ဖန်တီးပြီးပါပြီ။',
                'delete-failed'  => 'အရောင်းဝန်ထမ်းကို ဖျက်၍မရပါ။',
                'delete-success' => 'အရောင်းဝန်ထမ်းကို အောင်မြင်စွာ ဖျက်ပြီးပါပြီ။',
                'update-success' => 'အရောင်းဝန်ထမ်းအချက်အလက်ကို အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။',
            ],

           'outlets' => [
                'index' => [
                    'create-btn' => 'ဆိုင်ခွဲအသစ်ဖွင့်ရန်',
                    'pos-front'  => 'POS ရှေ့မျက်နှာပြင်',
                    'title'      => 'ဆိုင်ခွဲများ',

                    'datagrid' => [
                        'action'              => 'လုပ်ဆောင်ချက်',
                        'active'              => 'အသုံးပြုနေသည်',
                        'assign'              => 'ကုန်ပစ္စည်းသတ်မှတ်ရန်',
                        'delete'              => 'ဖျက်ရန်',
                        'edit'                => 'ပြင်ရန်',
                        'id'                  => 'Id',
                        'inactive'            => 'အသုံးမပြုပါ',
                        'inventory-source'    => 'ကုန်ပစ္စည်းအရင်းအမြစ် (Inventory Source)',
                        'mass-delete-success' => 'ရွေးချယ်ထားသော ဆိုင်ခွဲများကို အောင်မြင်စွာ ဖျက်ပြီးပါပြီ။',
                        'mass-update-success' => 'ရွေးချယ်ထားသော ဆိုင်ခွဲများကို အောင်မြင်စွာ အပ်ဒိတ်လုပ်ပြီးပါပြီ။',
                        'name'                => 'အမည်',
                        'receipt-title'       => 'ပြေစာခေါင်းစဉ်',
                        'status'              => 'အခြေအနေ',
                        'title'               => 'POS ဆိုင်စာရင်း',
                        'update-status'       => 'အခြေအနေ အပ်ဒိတ်လုပ်ရန်',
                    ],
                ],

                'create' => [
                    'address'                 => 'လိပ်စာ',
                    'back-btn'                => 'နောက်သို့',
                    'btn-title'               => 'ဆိုင်ခွဲအချက်အလက် သိမ်းဆည်းရန်',
                    'city'                    => 'မြို့',
                    'country'                 => 'နိုင်ငံ',
                    'customer-care-number'    => 'ဖောက်သည်ဝန်ဆောင်မှုဖုန်း',
                    'email'                   => 'အီးမေးလ်',
                    'general'                 => 'အထွေထွေ',
                    'gst-number'              => 'အခွန်နံပါတ် (GST)',
                    'inventory'               => 'ကုန်ပစ္စည်းလက်ကျန် (Inventory)',
                    'inventory-source'        => 'ကုန်ပစ္စည်းအရင်းအမြစ်',
                    'low-stock-qty'           => 'လက်ကျန်နည်းပါးသည့် အရေအတွက်',
                    'name'                    => 'ဆိုင်ခွဲအမည်',
                    'phone'                   => 'ဖုန်း',
                    'postcode'                => 'စာတိုက်နံပါတ် (Postcode)',
                    'receipt'                 => 'ပြေစာ',
                    'select-country'          => 'နိုင်ငံရွေးချယ်ပါ',
                    'select-inventory-source' => 'ကုန်ပစ္စည်းအရင်းအမြစ် ရွေးချယ်ပါ',
                    'select-receipt'          => 'ပြေစာပုံစံ ရွေးချယ်ပါ',
                    'state'                   => 'ပြည်နယ်/တိုင်း',
                    'status'                  => 'အခြေအနေ',
                    'store-address'           => 'ဆိုင်ခွဲလိပ်စာ',
                    'title'                   => 'ဆိုင်ခွဲအသစ်ထည့်ရန်',
                    'website'                 => 'ဝဘ်ဆိုဒ်',
                ],

                'edit' => [
                    'address'                 => 'လိပ်စာ',
                    'back-btn'                => 'နောက်သို့',
                    'btn-title'               => 'ဆိုင်ခွဲအချက်အလက် သိမ်းဆည်းရန်',
                    'city'                    => 'မြို့',
                    'country'                 => 'နိုင်ငံ',
                    'customer-care-number'    => 'ဖောက်သည်ဝန်ဆောင်မှုဖုန်း',
                    'email'                   => 'အီးမေးလ်',
                    'general'                 => 'အထွေထွေ',
                    'gst-number'              => 'အခွန်နံပါတ် (GST)',
                    'inventory'               => 'ကုန်ပစ္စည်းလက်ကျန် (Inventory)',
                    'inventory-source'        => 'ကုန်ပစ္စည်းအရင်းအမြစ်',
                    'low-stock-qty'           => 'လက်ကျန်နည်းပါးသည့် အရေအတွက်',
                    'name'                    => 'ဆိုင်ခွဲအမည်',
                    'phone'                   => 'ဖုန်း',
                    'postcode'                => 'စာတိုက်နံပါတ် (Postcode)',
                    'receipt'                 => 'ပြေစာ',
                    'select-country'          => 'နိုင်ငံရွေးချယ်ပါ',
                    'select-inventory-source' => 'ကုန်ပစ္စည်းအရင်းအမြစ် ရွေးချယ်ပါ',
                    'select-receipt'          => 'ပြေစာပုံစံ ရွေးချယ်ပါ',
                    'state'                   => 'ပြည်နယ်/တိုင်း',
                    'status'                  => 'အခြေအနေ',
                    'store-address'           => 'ဆိုင်ခွဲလိပ်စာ',
                    'title'                   => 'ဆိုင်ခွဲအချက်အလက် ပြင်ဆင်ရန်',
                    'website'                 => 'ဝဘ်ဆိုဒ်',
                ],

                'assign' => [
                    'back-btn' => 'နောက်သို့',
                    'title'    => 'ဆိုင်ခွဲကုန်ပစ္စည်းများကို စီမံရန်',

                    'datagrid' => [
                        'active'              => 'အသုံးပြုနေသည်',
                        'assign'              => 'သတ်မှတ်ရန်',
                        'disable'             => 'ရပ်ဆိုင်းရန်',
                        'id'                  => 'Id',
                        'id-value'            => 'Id - :id',
                        'image'               => 'ပုံ',
                        'mass-assign-success' => 'ကုန်ပစ္စည်းသတ်မှတ်ခြင်းကို အောင်မြင်စွာ အပ်ဒိတ်လုပ်ပြီးပါပြီ။',
                        'name'                => 'အမည်',
                        'out-of-stock'        => 'ပစ္စည်းပြတ်နေသည်',
                        'pos-status'          => 'POS အခြေအနေ',
                        'price'               => 'စျေးနှုန်း',
                        'product-image'       => 'ကုန်ပစ္စည်းပုံ',
                        'qty'                 => 'အရေအတွက်',
                        'qty-value'           => ':qty ရရှိနိုင်သည်',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'အခြေအနေ',
                        'type'                => 'အမျိုးအစား',
                        'unassign'            => 'ဖယ်ရှားရန်',
                        'update-assign'       => 'သတ်မှတ်ချက်ကို အပ်ဒိတ်လုပ်ရန်',
                    ],
                ],

                'create-success' => 'ဆိုင်ခွဲအသစ်ကို အောင်မြင်စွာ ဖန်တီးပြီးပါပြီ။',
                'delete-failed'  => 'ဆိုင်ခွဲကို ဖျက်၍မရပါ။',
                'delete-success' => 'ဆိုင်ခွဲကို အောင်မြင်စွာ ဖျက်ပြီးပါပြီ။',
                'update-success' => 'ဆိုင်ခွဲအချက်အလက်ကို အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'ဘားကုဒ်ကုန်ပစ္စည်းများ',

                'datagrid' => [
                    'barcode'               => 'ဘားကုဒ်',
                    'generate-barcode'      => 'ဘားကုဒ်ထုတ်ရန်',
                    'print-barcode'         => 'ဘားကုဒ်ရိုက်နှိပ်ရန်',
                    'id'                    => 'Id',
                    'id-value'              => 'Id - :id',
                    'image'                 => 'ပုံ',
                    'mass-generate-success' => 'ရွေးချယ်ထားသော ကုန်ပစ္စည်းများအတွက် ဘားကုဒ်ကို အောင်မြင်စွာ ထုတ်ပြီးပါပြီ။',
                    'name'                  => 'အမည်',
                    'out-of-stock'          => 'ပစ္စည်းပြတ်နေသည်',
                    'price'                 => 'စျေးနှုန်း',
                    'product-image'         => 'ကုန်ပစ္စည်းပုံ',
                    'qty'                   => 'အရေအတွက်',
                    'qty-value'             => ':qty ရရှိနိုင်သည်',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'အခြေအနေ',

                        'options' => [
                            'active'  => 'အသုံးပြုနေသည်',
                            'disable' => 'ရပ်ဆိုင်းထားသည်',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'   => 'နောက်သို့',
                'btn-title'  => 'ရိုက်နှိပ်ရန်',
                'qty'        => 'အရေအတွက်',
                'title'      => 'ဘားကုဒ်ရိုက်နှိပ်ရန်',
            ],

            'generate-failed'  => 'ဘားကုဒ်ထုတ်လုပ်ခြင်း မအောင်မြင်ပါ။',
            'generate-success' => 'ဘားကုဒ်ကို အောင်မြင်စွာ ထုတ်ပြီးပါပြီ။',
        ],

        'orders' => [
            'index' => [
                'title' => 'အော်ဒါများ',

                'datagrid' => [
                    'customer-name' => 'ဝယ်ယူသူအမည်',
                    'grand-total'   => 'စုစုပေါင်းတန်ဖိုး',
                    'order-date'    => 'အော်ဒါရက်စွဲ',
                    'order-id'      => 'အော်ဒါ ID',
                    'order-ref-id'  => 'အော်ဒါရည်ညွှန်း ID',
                    'view'          => 'ကြည့်ရှုရန်',

                    'status' => [
                        'title' => 'အခြေအနေ',

                        'options' => [
                            'canceled'        => 'ပယ်ဖျက်ပြီး',
                            'closed'          => 'ပိတ်ပြီး',
                            'completed'       => 'ပြီးမြောက်သည်',
                            'fraud'           => 'မသင်္ကာဖွယ်ရာ (Fraud)',
                            'pending'         => 'ဆိုင်းငံ့ထားသည်',
                            'pending-payment' => 'ငွေပေးချေရန် ဆိုင်းငံ့နေသည်',
                            'processing'      => 'လုပ်ဆောင်နေသည်',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'ကုန်ပစ္စည်းတောင်းဆိုချက်များ',

                'datagrid' => [
                    'id'                  => 'Id',
                    'product-image'       => 'ကုန်ပစ္စည်းပုံ',
                    'mass-update-error'   => 'တောင်းဆိုချက် အပ်ဒိတ်လုပ်ခြင်း မအောင်မြင်ပါ။',
                    'mass-update-success' => 'ရွေးချယ်ထားသော တောင်းဆိုချက်များကို အောင်မြင်စွာ အပ်ဒိတ်လုပ်ပြီးပါပြီ။',
                    'product-name'        => 'ကုန်ပစ္စည်းအမည်',
                    'outlet-name'         => 'ဆိုင်ခွဲအမည်',
                    'qty-value'           => 'အရေအတွက် - :qty',
                    'request-date'        => 'တောင်းဆိုသည့်ရက်စွဲ',
                    'requested-qty'       => 'တောင်းဆိုသည့်အရေအတွက်',
                    'update-status'       => 'အခြေအနေ အပ်ဒိတ်လုပ်ရန်',
                    'user-name'           => 'အသုံးပြုသူအမည်',

                    'status' => [
                        'title' => 'အခြေအနေ',

                        'options' => [
                            'complete' => 'ပြီးမြောက်သည်',
                            'decline'  => 'ငြင်းပယ်သည်',
                            'pending'  => 'ဆိုင်းငံ့ထားသည်',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'နောက်သို့',
                'btn-title' => 'သိမ်းဆည်းရန်',
                'title'     => 'တောင်းဆိုထားသော ကုန်ပစ္စည်းအသေးစိတ် #:id',

                'user-info' => [
                    'email'            => 'အီးမေးလ်',
                    'name'             => 'အမည်',
                    'outlet-address'   => 'ဆိုင်ခွဲလိပ်စာ',
                    'outlet-inventory' => 'ဆိုင်ခွဲကုန်ပစ္စည်းအရင်းအမြစ်',
                    'outlet-name'      => 'ဆိုင်ခွဲအမည်',
                    'title'            => 'အသုံးပြုသူအချက်အလက်',
                ],

                'request-info' => [
                    'comment'       => 'မှတ်ချက်',
                    'product-name'  => 'ကုန်ပစ္စည်းအမည်',
                    'qty-value'     => 'အရေအတွက် - :qty',
                    'request-date'  => 'တောင်းဆိုသည့်ရက်စွဲ',
                    'requested-qty' => 'တောင်းဆိုသည့်အရေအတွက်',
                    'title'         => 'တောင်းဆိုချက်အချက်အလက်',

                    'status' => [
                        'title' => 'အခြေအနေ',

                        'options' => [
                            'complete' => 'ပြီးမြောက်သည်',
                            'decline'  => 'ငြင်းပယ်သည်',
                            'pending'  => 'ဆိုင်းငံ့ထားသည်',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'တောင်းဆိုချက် အပ်ဒိတ်လုပ်ခြင်း မအောင်မြင်ပါ။',
            'update-success' => 'တောင်းဆိုချက်ကို အောင်မြင်စွာ အပ်ဒိတ်လုပ်ပြီးပါပြီ။',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'ဘဏ်အသစ်ထည့်ရန်',
                'title'     => 'ဘဏ်များ',

                'datagrid' => [
                    'active'              => 'အသုံးပြုနေသည်',
                    'address'             => 'ဘဏ်လိပ်စာ',
                    'agent-name'          => 'အရောင်းဝန်ထမ်း',
                    'delete'              => 'ဖျက်ရန်',
                    'disable'             => 'ရပ်ဆိုင်းရန်',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'ရွေးချယ်ထားသော ဘဏ်များကို အောင်မြင်စွာ ဖျက်ပြီးပါပြီ။',
                    'name'                => 'ဘဏ်အမည်',
                    'status'              => 'အခြေအနေ',
                ],
            ],

            'create' => [
                'back-btn'  => 'နောက်သို့',
                'btn-title' => 'ဘဏ်အချက်အလက် သိမ်းဆည်းရန်',
                'title'     => 'ဘဏ်အသစ်ဖန်တီးရန်',

                'general' => [
                    'address' => 'လိပ်စာ',
                    'email'   => 'အီးမေးလ်',
                    'name'    => 'အမည်',
                    'phone'   => 'ဖုန်း',
                    'title'   => 'အထွေထွေ',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS ဝန်ထမ်း သတ်မှတ်ရန်',
                    'bank-status'  => 'ဘဏ်အခြေအနေ',
                    'select-agent' => 'ဝန်ထမ်းရွေးချယ်ပါ',
                    'title'        => 'POS ဝန်ထမ်းနှင့် ဘဏ်အခြေအနေ',
                ],
            ],

            'edit' => [
                'back-btn'  => 'နောက်သို့',
                'btn-title' => 'ဘဏ်အချက်အလက် သိမ်းဆည်းရန်',
                'title'     => 'ဘဏ်အချက်အလက် ပြင်ဆင်ရန်',

                'general' => [
                    'address' => 'လိပ်စာ',
                    'email'   => 'အီးမေးလ်',
                    'name'    => 'အမည်',
                    'phone'   => 'ဖုန်း',
                    'title'   => 'အထွေထွေ',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS ဝန်ထမ်း သတ်မှတ်ရန်',
                    'bank-status'  => 'ဘဏ်အခြေအနေ',
                    'select-agent' => 'ဝန်ထမ်းရွေးချယ်ပါ',
                    'title'        => 'POS ဝန်ထမ်းနှင့် ဘဏ်အခြေအနေ',
                ],
            ],

            'create-success' => 'ဘဏ်အသစ်ကို အောင်မြင်စွာ ဖန်တီးပြီးပါပြီ။',
            'delete-failed'  => 'ဘဏ်ကို ဖျက်၍မရပါ။',
            'delete-success' => 'ဘဏ်ကို အောင်မြင်စွာ ဖျက်ပြီးပါပြီ။',
            'update-success' => 'ဘဏ်အချက်အလက်ကို အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'အရောင်းအစီရင်ခံစာများ',

                'datagrid' => [
                    'bank-name'      => 'ဘဏ်အမည်',
                    'grand-total'    => 'စုစုပေါင်းတန်ဖိုး',
                    'order-date'     => 'အော်ဒါရက်စွဲ',
                    'order-id'       => 'အော်ဒါ ID',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'အော်ဒါမှတ်ချက်',
                    'outlet-name'    => 'ဆိုင်ခွဲအမည်',
                    'payment-method' => 'ငွေပေးချေမှုပုံစံ',
                    'view'           => 'ကြည့်ရှုရန်',

                    'status' => [
                        'title' => 'အခြေအနေ',

                        'options' => [
                            'canceled'        => 'ပယ်ဖျက်ပြီး',
                            'closed'          => 'ပိတ်ပြီး',
                            'completed'       => 'ပြီးမြောက်သည်',
                            'fraud'           => 'မသင်္ကာဖွယ်ရာ',
                            'pending'         => 'ဆိုင်းငံ့ထားသည်',
                            'pending-payment' => 'ငွေပေးချေရန် ဆိုင်းငံ့နေသည်',
                            'processing'      => 'လုပ်ဆောင်နေသည်',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'ပြေစာပုံစံသစ် ဖန်တီးရန်',
                'title'      => 'ပြေစာများ',

                'datagrid' => [
                    'delete'              => 'ဖျက်ရန်',
                    'edit'                => 'ပြင်ရန်',
                    'id'                  => 'Id',
                    'mass-delete-success' => 'ရွေးချယ်ထားသော ပြေစာများကို အောင်မြင်စွာ ဖျက်ပြီးပါပြီ။',
                    'preview'             => 'နမူနာကြည့်ရန်',
                    'title'               => 'ခေါင်းစဉ်',

                    'status' => [
                        'title' => 'အခြေအနေ',

                        'options' => [
                            'active'   => 'အသုံးပြုနေသည်',
                            'inactive' => 'အသုံးမပြုပါ',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'နောက်သို့',
                'btn-title' => 'ပြေစာပုံစံ သိမ်းဆည်းရန်',
                'title'     => 'ပြေစာပုံစံသစ် ဖန်တီးရန်',

                'general' => [
                    'cashier-name-label'      => 'ငွေကိုင်အမည်တံဆိပ် (Label)',
                    'change-amount-label'     => 'ပြန်အမ်းငွေတံဆိပ်',
                    'credit-amount-label'     => 'အကြွေးငွေတံဆိပ်',
                    'discount-amt-label'      => 'လျှော့စျေးတံဆိပ်',
                    'display-cashier-name'    => 'ငွေကိုင်အမည် ပြသရန်',
                    'display-change-amount'   => 'ပြန်အမ်းငွေ ပြသရန်',
                    'display-credit-amount'   => 'အကြွေးငွေ ပြသရန်',
                    'display-customer-name'   => 'ဝယ်ယူသူအမည် ပြသရန်',
                    'display-date'            => 'ရက်စွဲ ပြသရန်',
                    'display-discount-amt'    => 'လျှော့စျေးပမာဏ ပြသရန်',
                    'display-order-id'        => 'အော်ဒါ ID ပြသရန်',
                    'display-outlet-address'  => 'ဆိုင်ခွဲလိပ်စာ ပြသရန်',
                    'display-outlet-name'     => 'ဆိုင်ခွဲအမည် ပြသရန်',
                    'display-sub-total'       => 'ပစ္စည်းစုစုပေါင်းတန်ဖိုး (Sub Total) ပြသရန်',
                    'display-tax'             => 'အခွန် (Tax) ပြသရန်',
                    'grand-total-label'       => 'စုစုပေါင်းကုန်ကျငွေတံဆိပ်',
                    'order-id-label'          => 'အော်ဒါ ID တံဆိပ်',
                    'receipt-title'           => 'ပြေစာခေါင်းစဉ်',
                    'show-order-barcode'      => 'အော်ဒါဘားကုဒ် ပြသရန်',
                    'show-print-confirmation' => 'Print ထုတ်ရန် အတည်ပြုချက်ပြတင်းပေါက် ပြသရန်',
                    'status'                  => 'အခြေအနေ',
                    'sub-total-label'         => 'Sub Total တံဆိပ်',
                    'tax-label'               => 'Tax တံဆိပ်',
                    'title'                   => 'အထွေထွေ',
                ],

                'logo' => [
                    'display-logo' => 'Logo ပြသရန်',
                    'logo-alt'     => 'Logo Alt Text',
                    'logo-height'  => 'Logo အမြင့် (px)',
                    'logo-width'   => 'Logo အကျယ် (px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Logo တင်ရန်',
                ],

                'header' => [
                    'header-content' => 'အပေါ်ပိုင်းပါဝင်သည့်အကြောင်းအရာ (Header)',
                    'title'          => 'Header',
                ],

                'footer' => [
                    'footer-content' => 'အောက်ခြေပါဝင်သည့်အကြောင်းအရာ (Footer)',
                    'title'          => 'Footer',
                ],
            ],

            'edit' => [
                'back-btn'  => 'နောက်သို့',
                'btn-title' => 'ပြေစာပုံစံ သိမ်းဆည်းရန်',
                'title'     => 'ပြေစာပုံစံ ပြင်ဆင်ရန်',

                'general' => [
                    'cashier-name-label'      => 'ငွေကိုင်အမည်တံဆိပ်',
                    'change-amount-label'     => 'ပြန်အမ်းငွေတံဆိပ်',
                    'credit-amount-label'     => 'အကြွေးငွေတံဆိပ်',
                    'discount-amt-label'      => 'လျှော့စျေးတံဆိပ်',
                    'display-cashier-name'    => 'ငွေကိုင်အမည် ပြသရန်',
                    'display-change-amount'   => 'ပြန်အမ်းငွေ ပြသရန်',
                    'display-credit-amount'   => 'အကြွေးငွေ ပြသရန်',
                    'display-customer-name'   => 'ဝယ်ယူသူအမည် ပြသရန်',
                    'display-date'            => 'ရက်စွဲ ပြသရန်',
                    'display-discount-amt'    => 'လျှော့စျေးပမာဏ ပြသရန်',
                    'display-order-id'        => 'အော်ဒါ ID ပြသရန်',
                    'display-outlet-address'  => 'ဆိုင်ခွဲလိပ်စာ ပြသရန်',
                    'display-outlet-name'     => 'ဆိုင်ခွဲအမည် ပြသရန်',
                    'display-sub-total'       => 'Sub Total ပြသရန်',
                    'display-tax'             => 'အခွန် ပြသရန်',
                    'grand-total-label'       => 'စုစုပေါင်းကုန်ကျငွေတံဆိပ်',
                    'order-id-label'          => 'အော်ဒါ ID တံဆိပ်',
                    'receipt-title'           => 'ပြေစာခေါင်းစဉ်',
                    'show-order-barcode'      => 'အော်ဒါဘားကုဒ် ပြသရန်',
                    'show-print-confirmation' => 'Print ထုတ်ရန် အတည်ပြုချက် ပြသရန်',
                    'status'                  => 'အခြေအနေ',
                    'sub-total-label'         => 'Sub Total တံဆိပ်',
                    'tax-label'               => 'Tax တံဆိပ်',
                    'title'                   => 'အထွေထွေ',
                ],

                'logo' => [
                    'display-logo' => 'Logo ပြသရန်',
                    'logo-alt'     => 'Logo Alt Text',
                    'logo-height'  => 'Logo အမြင့် (px)',
                    'logo-width'   => 'Logo အကျယ် (px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Logo တင်ရန်',
                ],

                'header' => [
                    'header-content' => 'Header အကြောင်းအရာ',
                    'title'          => 'Header',
                ],

                'footer' => [
                    'footer-content' => 'Footer အကြောင်းအရာ',
                    'title'          => 'Footer',
                ],
            ],

            'preview' => [
                'amount'         => 'ပမာဏ',
                'cashier'        => 'ငွေကိုင်',
                'change-amount'  => 'ပြန်အမ်းငွေ',
                'customer'       => 'ဝယ်ယူသူ',
                'customer-email' => 'ဝယ်ယူသူအီးမေးလ်',
                'customer-name'  => 'ဝယ်ယူသူအမည်',
                'customer-phone' => 'ဝယ်ယူသူဖုန်း',
                'date'           => 'ရက်စွဲ',
                'discount'       => 'လျှော့စျေး',
                'email'          => 'အီးမေးလ်',
                'grand-total'    => 'စုစုပေါင်း',
                'order-id'       => 'အော်ဒါ ID',
                'phone'          => 'ဖုန်း',
                'price'          => 'စျေးနှုန်း',
                'product'        => 'ကုန်ပစ္စည်း',
                'qty'            => 'အရေအတွက်',
                'sub-total'      => 'စုစုပေါင်းတန်ဖိုး',
                'tax'            => 'အခွန်',
                'title'          => 'ပြေစာနမူနာကြည့်ရန်',
                'total-qty'      => 'စုစုပေါင်းအရေအတွက်',
            ],

            'create-success' => 'ပြေစာပုံစံကို အောင်မြင်စွာ ဖန်တီးပြီးပါပြီ။',
            'delete-failed'  => 'ပြေစာပုံစံကို ဖျက်၍မရပါ။',
            'delete-success' => 'ပြေစာပုံစံကို အောင်မြင်စွာ ဖျက်ပြီးပါပြီ။',
            'update-success' => 'ပြေစာပုံစံကို အောင်မြင်စွာ အပ်ဒိတ်လုပ်ပြီးပါပြီ။',
        ],
    ],

    'emails' => [
        'dear'     => 'လူကြီးမင်း :name ခင်ဗျာ/ရှင်',
        'greeting' => 'မင်္ဂလာပါ!',

        'registration' => [
            'message' => 'ဂုဏ်ယူပါသည်။ လူကြီးမင်း၏အကောင့်ကို အောင်မြင်စွာ ဖွင့်လှစ်ပြီးပါပြီ။ လူကြီးမင်း၏အကောင့်သို့ ဝင်ရောက်၍ POS စနစ်ကို စတင်အသုံးပြုနိုင်ပါပြီ။',
            'subject' => 'POS အသုံးပြုသူအဖြစ် မှတ်ပုံတင်ခြင်းဆိုင်ရာ အီးမေးလ်',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'အကောင့်ဝင်ရန်',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© ၂၀၂၆ Fujiba။ မူပိုင်ခွင့်များကို ကာကွယ်ထားပါသည်။',
        ],
    ],
];