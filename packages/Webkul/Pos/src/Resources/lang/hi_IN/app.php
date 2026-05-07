<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'अमान्य क्रेडेंशियल्स।',
                'not-activated'       => 'आपका खाता सक्रिय नहीं है।',
                'verify-first'        => 'कृपया पहले अपना ईमेल सत्यापित करें।',
                'success'             => 'आप सफलतापूर्वक लॉगिन हो गए हैं।',
            ],

            'logout' => [
                'no-login-agent' => 'कोई एजेंट लॉगिन नहीं है।',
                'success'        => 'आप सफलतापूर्वक लॉगआउट हो गए हैं।',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'आपके द्वारा दर्ज किया गया पासवर्ड गलत है।',
                    'success'          => 'आपका खाता सफलतापूर्वक अपडेट हो गया है।',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'ग्राहक सफलतापूर्वक बनाया गया!',
            'update-success' => 'ग्राहक सफलतापूर्वक अपडेट किया गया!',
            'delete-success' => 'ग्राहक सफलतापूर्वक हटाया गया!',
            'delete-failed'  => 'ग्राहक को हटाने में विफल रहा!',
            'pending-orders' => 'ग्राहक के पास लंबित आदेश हैं!',
        ],

        'cart' => [
            'already-applied'     => 'कूपन पहले से ही लागू है!',
            'coupon-applied'      => 'कूपन सफलतापूर्वक लागू किया गया!',
            'coupon-removed'      => 'कूपन सफलतापूर्वक हटाया गया!',
            'create-success'      => 'कार्ट सफलतापूर्वक बनाया गया!',
            'invalid-coupon'      => 'अमान्य कूपन कोड!',
            'item-add-success'    => 'उत्पाद को कार्ट में सफलतापूर्वक जोड़ा गया!',
            'item-remove-success' => 'उत्पाद को कार्ट से सफलतापूर्वक हटाया गया!',
            'item-update-success' => 'उत्पाद सफलतापूर्वक अपडेट किया गया!',
            'not-found'           => 'कार्ट नहीं मिला!',
        ],

        'payment' => [
            'title' => 'Pos भुगतान',

            'options' => [
                'cash' => [
                    'title'       => 'Pos नकद भुगतान',
                    'description' => 'यह Pos नकद भुगतान है।',
                ],

                'card' => [
                    'title'       => 'Pos कार्ड भुगतान',
                    'description' => 'यह Pos कार्ड भुगतान है।',
                ],

                'split' => [
                    'title'       => 'Pos विभाजित भुगतान',
                    'description' => 'यह Pos विभाजित भुगतान है।',
                ],
            ],

            'no-items' => 'भुगतान के लिए कार्ट में कोई आइटम नहीं है।',
            'success'  => 'भुगतान सफलतापूर्वक पूरा हुआ!',
        ],

        'shipping' => [
            'title'       => 'Pos शिपिंग',
            'description' => 'यह Pos मुफ्त शिपिंग है।',
        ],

        'order' => [
            'sync-success' => 'ऑर्डर सफलतापूर्वक सिंक हो गया!',
        ],

        'return' => [
            'create-success'              => 'रिफंड सफलतापूर्वक बनाया गया',
            'creation-error'              => 'रिफंड बनाना अनुमत नहीं है।',
            'invalid-qty'                 => 'हमने आइटम को इनवॉइस करने के लिए अमान्य मात्रा पाई है।',
            'invalid-refund-amount-error' => 'रिफंड राशि शून्य से अधिक होनी चाहिए।',
            'refund-limit-error'          => 'रिफंड राशि :amount को प्रोसेस नहीं किया जा सकता।',
        ],

        'drawer' => [
            'create-success' => 'ड्रावर सफलतापूर्वक खोला गया!',
            'not-opened'     => 'ड्रावर नहीं खुला है।',
            'close-success'  => 'ड्रावर सफलतापूर्वक बंद हो गया!',
        ],

        'products' => [
            'request-success' => 'उत्पाद अनुरोध सफलतापूर्वक भेजा गया।',
            'create-success'  => 'उत्पाद सफलतापूर्वक बनाया गया!',
        ],

        'reports' => [
            'orders'                  => 'ऑर्डर',
            'average-order-value'     => 'औसत ऑर्डर मूल्य',
            'average-items-per-order' => 'प्रति ऑर्डर औसत आइटम',
            'discounted-offers'       => 'छूट वाले प्रस्ताव',
            'cash-payments'           => 'नकद भुगतान',
            'other-payments'          => 'अन्य भुगतान',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'ऑर्डर नोट',
                    'note-not-available' => 'नोट उपलब्ध नहीं है!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'Bagisto पॉइंट ऑफ सेल (POS) एक्सटेंशन।',
                    'title' => 'पॉइंट ऑफ सेल',

                    'settings' => [
                        'info'  => 'POS सक्षम करें, सामान्य कॉन्फ़िगरेशन सेट करें, POS उत्पाद और बिल रसीद।',
                        'title' => 'सेटिंग्स',

                        'general' => [
                            'footer-content'       => 'फुटर सामग्री',
                            'footer-note'          => 'फुटर नोट',
                            'frontend-url'         => 'फ्रंटएंड URL',
                            'heading-on-login'     => 'लॉगिन पर शीर्षक',
                            'info'                 => 'सामान्य सेटिंग्स POS उपयोगकर्ता पृष्ठ के लिए कॉन्फ़िगरेशन की अनुमति देती हैं, जिसमें लोगो, शीर्षक, फुटर सामग्री, फुटर नोट आदि जोड़ना शामिल है।',
                            'pos-logo'             => 'POS लोगो',
                            'status'               => 'स्थिति',
                            'sub-heading-on-login' => 'लॉगिन पर उप-शीर्षक',
                            'title'                => 'सामान्य',
                        ],

                        'barcode' => [
                            'height'             => 'ऊँचाई',
                            'hide-barcode'       => 'बारकोड छुपाएं',
                            'info'               => 'बारकोड सेटिंग्स बारकोड निर्माण, बारकोड की ऊँचाई, बारकोड की चौड़ाई, बारकोड प्रकार आदि के लिए कॉन्फ़िगरेशन की अनुमति देती हैं।',
                            'prefix'             => 'प्रारंभिक',
                            'print-product-name' => 'उत्पाद का नाम प्रिंट करें',
                            'title'              => 'बारकोड',
                            'width'              => 'चौड़ाई',

                            'generate-with' => [
                                'title' => 'बारकोड निम्नलिखित के साथ उत्पन्न करें',

                                'options' => [
                                    'product-id' => 'उत्पाद आईडी',
                                    'sku'        => 'उत्पाद SKU',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'कस्टम उत्पाद के लिए SKU की अनुमति दें',
                            'info'      => 'उत्पाद सेटिंग्स SKU के लिए कॉन्फ़िगरेशन की अनुमति देती है।',
                            'title'     => 'उत्पाद',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'उत्पाद सौंपें',
            'banks'            => 'बैंक',
            'barcode-products' => 'बारकोड उत्पाद',
            'create'           => 'बनाएं',
            'delete'           => 'हटाएं',
            'edit'             => 'संपादित करें',
            'generate-barcode' => 'बारकोड जनरेट करें',
            'orders'           => 'आदेश',
            'outlets'          => 'आउटलेट',
            'pos'              => 'पॉइंट ऑफ सेल (POS)',
            'preview'          => 'पूर्वावलोकन',
            'print-barcode'    => 'बारकोड प्रिंट करें',
            'receipts'         => 'रसीदें',
            'requests'         => 'अनुरोध',
            'sales-report'     => 'बिक्री रिपोर्ट',
            'users'            => 'एजेंट',
            'view'             => 'देखें',
        ],

        'layouts' => [
            'banks'            => 'बैंक',
            'barcode-products' => 'बारकोड उत्पाद',
            'orders'           => 'ऑर्डर',
            'pos'              => 'पॉइंट ऑफ सेल (POS)',
            'receipts'         => 'रसीदें',
            'requests'         => 'अनुरोध',
            'sales-report'     => 'बिक्री रिपोर्ट',

            'users' => [
                'agents'   => 'एजेंट',
                'outlets'  => 'आउटलेट',
                'title'    => 'एजेंट',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'एजेंट बनाएं',
                    'pos-front'  => 'POS फ्रंट',
                    'title'      => 'एजेंट',

                    'datagrid' => [
                        'action'              => 'क्रिया',
                        'delete'              => 'हटाएं',
                        'edit'                => 'संपादित करें',
                        'email'               => 'ईमेल',
                        'full-name'           => 'पूरा नाम',
                        'id'                  => 'पहचान',
                        'id-value'            => 'पहचान - :id',
                        'mass-delete-success' => 'चयनित एजेंट सफलतापूर्वक हटाए गए!',
                        'mass-update-success' => 'चयनित एजेंट सफलतापूर्वक अपडेट किए गए!',
                        'outlet-name'         => 'आउटलेट का नाम',
                        'profile-image'       => 'प्रोफ़ाइल छवि',
                        'update-status'       => 'स्थिति अपडेट करें',
                        'username'            => 'उपयोगकर्ता नाम',

                        'status' => [
                            'title' => 'स्थिति',

                            'options' => [
                                'active'  => 'सक्रिय',
                                'disable' => 'निष्क्रिय',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'वापस',
                    'confirm-password'  => 'पासवर्ड की पुष्टि करें',
                    'email'             => 'ईमेल',
                    'first-name'        => 'पहला नाम',
                    'general'           => 'सामान्य',
                    'image'             => 'छवि',
                    'last-name'         => 'अंतिम नाम',
                    'outlet'            => 'आउटलेट',
                    'outlet-and-status' => 'आउटलेट और स्थिति',
                    'password'          => 'पासवर्ड',
                    'save-btn'          => 'एजेंट सहेजें',
                    'select-outlet'     => 'आउटलेट चुनें',
                    'status'            => 'स्थिति',
                    'title'             => 'एजेंट जोड़ें',
                    'user-image'        => 'एजेंट की छवि अपलोड करें',
                    'username'          => 'उपयोगकर्ता नाम',
                ],

                'edit' => [
                    'back-btn'          => 'वापस',
                    'confirm-password'  => 'पासवर्ड की पुष्टि करें',
                    'email'             => 'ईमेल',
                    'first-name'        => 'पहला नाम',
                    'general'           => 'सामान्य',
                    'image'             => 'छवि',
                    'last-name'         => 'अंतिम नाम',
                    'outlet'            => 'आउटलेट',
                    'outlet-and-status' => 'आउटलेट और स्थिति',
                    'password'          => 'पासवर्ड',
                    'save-btn'          => 'एजेंट सहेजें',
                    'select-outlet'     => 'आउटलेट चुनें',
                    'status'            => 'स्थिति',
                    'title'             => 'एजेंट संपादित करें',
                    'user-image'        => 'एजेंट की छवि अपलोड करें',
                    'username'          => 'उपयोगकर्ता नाम',
                ],

                'create-success' => 'एजेंट सफलतापूर्वक बनाया गया!',
                'delete-failed'  => 'एजेंट हटाना विफल!',
                'delete-success' => 'एजेंट सफलतापूर्वक हटा दिया गया!',
                'update-success' => 'एजेंट सफलतापूर्वक अपडेट किया गया!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'आउटलेट बनाएं',
                    'pos-front'  => 'POS फ्रंट',
                    'title'      => 'आउटलेट',

                    'datagrid' => [
                        'action'              => 'कार्य',
                        'active'              => 'सक्रिय',
                        'assign'              => 'उत्पाद सौंपें',
                        'delete'              => 'हटाएं',
                        'edit'                => 'संपादित करें',
                        'id'                  => 'आईडी',
                        'inactive'            => 'निष्क्रिय',
                        'inventory-source'    => 'इन्वेंट्री स्रोत',
                        'mass-delete-success' => 'चयनित स्टोर सफलतापूर्वक हटा दिए गए!',
                        'mass-update-success' => 'चयनित स्टोर सफलतापूर्वक अपडेट किए गए!',
                        'name'                => 'नाम',
                        'receipt-title'       => 'रसीद शीर्षक',
                        'status'              => 'स्थिति',
                        'title'               => 'पीओएस स्टोर सूची',
                        'update-status'       => 'स्थिति अपडेट करें',
                    ],
                ],

                'create' => [
                    'address'                 => 'पता',
                    'back-btn'                => 'वापस',
                    'btn-title'               => 'आउटलेट सहेजें',
                    'city'                    => 'शहर',
                    'country'                 => 'देश',
                    'customer-care-number'    => 'ग्राहक सेवा नंबर',
                    'email'                   => 'ईमेल',
                    'general'                 => 'सामान्य',
                    'gst-number'              => 'जीएसटी नंबर',
                    'inventory'               => 'इन्वेंटरी',
                    'inventory-source'        => 'इन्वेंटरी स्रोत',
                    'low-stock-qty'           => 'कम स्टॉक मात्रा',
                    'name'                    => 'आउटलेट का नाम',
                    'phone'                   => 'फोन',
                    'postcode'                => 'पोस्टकोड',
                    'receipt'                 => 'रसीद',
                    'select-country'          => 'देश चुनें',
                    'select-inventory-source' => 'इन्वेंटरी स्रोत चुनें',
                    'select-receipt'          => 'रसीद चुनें',
                    'state'                   => 'राज्य',
                    'status'                  => 'स्थिति',
                    'store-address'           => 'आउटलेट का पता',
                    'title'                   => 'आउटलेट जोड़ें',
                    'website'                 => 'वेबसाइट',
                ],

                'edit' => [
                    'address'                 => 'पता',
                    'back-btn'                => 'वापस',
                    'btn-title'               => 'आउटलेट सहेजें',
                    'city'                    => 'शहर',
                    'country'                 => 'देश',
                    'customer-care-number'    => 'ग्राहक सेवा नंबर',
                    'email'                   => 'ईमेल',
                    'general'                 => 'सामान्य',
                    'gst-number'              => 'जीएसटी नंबर',
                    'inventory'               => 'इन्वेंटरी',
                    'inventory-source'        => 'इन्वेंटरी स्रोत',
                    'low-stock-qty'           => 'कम स्टॉक मात्रा',
                    'name'                    => 'आउटलेट का नाम',
                    'phone'                   => 'फोन',
                    'postcode'                => 'पोस्टकोड',
                    'receipt'                 => 'रसीद',
                    'select-country'          => 'देश चुनें',
                    'select-inventory-source' => 'इन्वेंटरी स्रोत चुनें',
                    'select-receipt'          => 'रसीद चुनें',
                    'state'                   => 'राज्य',
                    'status'                  => 'स्थिति',
                    'store-address'           => 'आउटलेट का पता',
                    'title'                   => 'आउटलेट संपादित करें',
                    'website'                 => 'वेबसाइट',
                ],

                'assign' => [
                    'back-btn' => 'वापस',
                    'title'    => 'आउटलेट उत्पाद प्रबंधित करें',

                    'datagrid' => [
                        'active'              => 'सक्रिय',
                        'assign'              => 'सौंपें',
                        'disable'             => 'निष्क्रिय करें',
                        'id'                  => 'आईडी',
                        'id-value'            => 'आईडी - :id',
                        'image'               => 'छवि',
                        'mass-assign-success' => 'उत्पाद सौंपने को सफलतापूर्वक अपडेट किया गया!',
                        'name'                => 'नाम',
                        'out-of-stock'        => 'स्टॉक में नहीं',
                        'pos-status'          => 'पीओएस स्थिति',
                        'price'               => 'मूल्य',
                        'product-image'       => 'उत्पाद छवि',
                        'qty'                 => 'मात्रा',
                        'qty-value'           => ':qty उपलब्ध',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'स्थिति',
                        'type'                => 'प्रकार',
                        'unassign'            => 'हटाएं',
                        'update-assign'       => 'सौंपना अपडेट करें',
                    ],
                ],

                'create-success' => 'आउटलेट सफलतापूर्वक बनाया गया!',
                'delete-failed'  => 'आउटलेट हटाना विफल!',
                'delete-success' => 'आउटलेट सफलतापूर्वक हटा दिया गया!',
                'update-success' => 'आउटलेट सफलतापूर्वक अपडेट किया गया!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'बारकोड उत्पाद',

                'datagrid' => [
                    'barcode'               => 'बारकोड',
                    'generate-barcode'      => 'बारकोड जनरेट करें',
                    'print-barcode'         => 'बारकोड प्रिंट करें',
                    'id'                    => 'आईडी',
                    'id-value'              => 'आईडी - :id',
                    'image'                 => 'छवि',
                    'mass-generate-success' => 'चयनित उत्पादों के बारकोड सफलतापूर्वक जनरेट किए गए!',
                    'name'                  => 'नाम',
                    'out-of-stock'          => 'स्टॉक से बाहर',
                    'price'                 => 'मूल्य',
                    'product-image'         => 'उत्पाद छवि',
                    'qty'                   => 'मात्रा',
                    'qty-value'             => ':qty उपलब्ध',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'स्थिति',

                        'options' => [
                            'active'  => 'सक्रिय',
                            'disable' => 'निष्क्रिय',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'वापस',
                'btn-title' => 'प्रिंट करें',
                'qty'       => 'मात्रा',
                'title'     => 'बारकोड प्रिंट करें',
            ],

            'generate-failed'  => 'बारकोड जनरेशन विफल!',
            'generate-success' => 'बारकोड सफलतापूर्वक जनरेट किया गया!',
        ],

        'orders' => [
            'index' => [
                'title' => 'आदेश',

                'datagrid' => [
                    'customer-name' => 'ग्राहक का नाम',
                    'grand-total'   => 'कुल योग',
                    'order-date'    => 'आदेश की तारीख',
                    'order-id'      => 'आदेश आईडी',
                    'order-ref-id'  => 'आदेश संदर्भ आईडी',
                    'view'          => 'देखें',

                    'status' => [
                        'title' => 'स्थिति',

                        'options' => [
                            'canceled'        => 'रद्द किया गया',
                            'closed'          => 'बंद',
                            'completed'       => 'पूर्ण',
                            'fraud'           => 'धोखाधड़ी',
                            'pending'         => 'लंबित',
                            'pending-payment' => 'भुगतान लंबित',
                            'processing'      => 'प्रसंस्करण',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'अनुरोध',

                'datagrid' => [
                    'id'                  => 'आईडी',
                    'product-image'       => 'उत्पाद छवि',
                    'mass-update-error'   => 'अनुरोध अपडेट विफल!',
                    'mass-update-success' => 'चयनित अनुरोधों को सफलतापूर्वक अपडेट किया गया!',
                    'product-name'        => 'उत्पाद का नाम',
                    'outlet-name'         => 'आउटलेट का नाम',
                    'qty-value'           => 'मात्रा - :qty',
                    'request-date'        => 'अनुरोध की तारीख',
                    'requested-qty'       => 'अनुरोधित मात्रा',
                    'update-status'       => 'स्थिति अपडेट करें',
                    'user-name'           => 'उपयोगकर्ता का नाम',

                    'status' => [
                        'title' => 'स्थिति',

                        'options' => [
                            'complete' => 'पूर्ण',
                            'decline'  => 'अस्वीकृत',
                            'pending'  => 'लंबित',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'वापस',
                'btn-title' => 'सहेजें',
                'title'     => 'अनुरोधित उत्पाद विवरण #:id',

                'user-info' => [
                    'email'            => 'ईमेल',
                    'name'             => 'नाम',
                    'outlet-address'   => 'आउटलेट पता',
                    'outlet-inventory' => 'आउटलेट इन्वेंट्री स्रोत',
                    'outlet-name'      => 'आउटलेट का नाम',
                    'title'            => 'उपयोगकर्ता जानकारी',
                ],

                'request-info' => [
                    'comment'       => 'टिप्पणी',
                    'product-name'  => 'उत्पाद का नाम',
                    'qty-value'     => 'मात्रा - :qty',
                    'request-date'  => 'अनुरोध की तारीख',
                    'requested-qty' => 'अनुरोधित मात्रा',
                    'title'         => 'अनुरोध जानकारी',

                    'status' => [
                        'title' => 'स्थिति',

                        'options' => [
                            'complete' => 'पूर्ण',
                            'decline'  => 'अस्वीकृत',
                            'pending'  => 'लंबित',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'अनुरोध अपडेट विफल!',
            'update-success' => 'अनुरोध सफलतापूर्वक अपडेट किया गया!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'बैंक बनाएँ',
                'title'     => 'बैंक',

                'datagrid' => [
                    'active'              => 'सक्रिय',
                    'address'             => 'बैंक का पता',
                    'agent-name'          => 'एजेंट',
                    'delete'              => 'हटाएं',
                    'disable'             => 'अक्षम करें',
                    'id'                  => 'आईडी',
                    'mass-delete-success' => 'चयनित बैंकों को सफलतापूर्वक हटा दिया गया!',
                    'name'                => 'बैंक का नाम',
                    'status'              => 'स्थिति',
                ],
            ],

            'create' => [
                'back-btn'  => 'वापस',
                'btn-title' => 'बैंक सहेजें',
                'title'     => 'नया बैंक बनाएँ',

                'general' => [
                    'address' => 'पता',
                    'email'   => 'ईमेल',
                    'name'    => 'नाम',
                    'phone'   => 'फोन',
                    'title'   => 'सामान्य',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS एजेंट असाइन करें',
                    'bank-status'  => 'बैंक स्थिति',
                    'select-agent' => 'एजेंट चुनें',
                    'title'        => 'POS एजेंट और बैंक स्थिति',
                ],
            ],

            'edit' => [
                'back-btn'  => 'वापस',
                'btn-title' => 'बैंक सहेजें',
                'title'     => 'बैंक संपादित करें',

                'general' => [
                    'address' => 'पता',
                    'email'   => 'ईमेल',
                    'name'    => 'नाम',
                    'phone'   => 'फोन',
                    'title'   => 'सामान्य',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS एजेंट असाइन करें',
                    'bank-status'  => 'बैंक स्थिति',
                    'select-agent' => 'एजेंट चुनें',
                    'title'        => 'POS एजेंट और बैंक स्थिति',
                ],
            ],

            'create-success' => 'बैंक सफलतापूर्वक बनाया गया!',
            'delete-failed'  => 'बैंक हटाना विफल!',
            'delete-success' => 'बैंक सफलतापूर्वक हटाया गया!',
            'update-success' => 'बैंक सफलतापूर्वक अपडेट किया गया!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'बिक्री रिपोर्ट',

                'datagrid' => [
                    'bank-name'      => 'बैंक का नाम',
                    'grand-total'    => 'कुल योग',
                    'order-date'     => 'आदेश की तारीख',
                    'order-id'       => 'आदेश आईडी',
                    'order-id-value' => 'आईडी - :id',
                    'order-note'     => 'आदेश नोट',
                    'outlet-name'    => 'आउटलेट का नाम',
                    'payment-method' => 'भुगतान विधि',
                    'view'           => 'देखें',

                    'status' => [
                        'title' => 'स्थिति',

                        'options' => [
                            'canceled'        => 'रद्द किया गया',
                            'closed'          => 'बंद',
                            'completed'       => 'पूर्ण',
                            'fraud'           => 'धोखाधड़ी',
                            'pending'         => 'लंबित',
                            'pending-payment' => 'भुगतान लंबित',
                            'processing'      => 'प्रसंस्करण',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'रसीद बनाएँ',
                'title'      => 'रसीदें',

                'datagrid' => [
                    'delete'              => 'हटाएँ',
                    'edit'                => 'संपादित करें',
                    'id'                  => 'आईडी',
                    'mass-delete-success' => 'चयनित रसीदें सफलतापूर्वक हटाई गई!',
                    'preview'             => 'पूर्वावलोकन',
                    'title'               => 'शीर्षक',

                    'status' => [
                        'title' => 'स्थिति',

                        'options' => [
                            'active'   => 'सक्रिय',
                            'inactive' => 'निष्क्रिय',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'वापस',
                'btn-title' => 'रसीद सहेजें',
                'title'     => 'नयी रसीद बनाएँ',

                'general' => [
                    'cashier-name-label'      => 'कैशियर नाम लेबल',
                    'change-amount-label'     => 'चेंज अमाउंट लेबल',
                    'credit-amount-label'     => 'क्रेडिट अमाउंट लेबल',
                    'discount-amt-label'      => 'छूट राशि लेबल',
                    'display-cashier-name'    => 'कैशियर नाम दिखाएँ',
                    'display-change-amount'   => 'चेंज अमाउंट दिखाएँ',
                    'display-credit-amount'   => 'क्रेडिट अमाउंट दिखाएँ',
                    'display-customer-name'   => 'ग्राहक नाम दिखाएँ',
                    'display-date'            => 'तारीख दिखाएँ',
                    'display-discount-amt'    => 'छूट राशि दिखाएँ',
                    'display-order-id'        => 'आदेश आईडी दिखाएँ',
                    'display-outlet-address'  => 'आउटलेट पता दिखाएँ',
                    'display-outlet-name'     => 'आउटलेट नाम दिखाएँ',
                    'display-sub-total'       => 'उप-योग दिखाएँ',
                    'display-tax'             => 'कर दिखाएँ',
                    'grand-total-label'       => 'कुल योग लेबल',
                    'order-id-label'          => 'आदेश आईडी लेबल',
                    'receipt-title'           => 'रसीद शीर्षक',
                    'show-order-barcode'      => 'ऑर्डर बारकोड दिखाएं',
                    'show-print-confirmation' => 'प्रिंट पुष्टि दिखाएं',
                    'status'                  => 'स्थिति',
                    'sub-total-label'         => 'उप-योग लेबल',
                    'tax-label'               => 'कर लेबल',
                    'title'                   => 'सामान्य',
                ],

                'logo' => [
                    'display-logo' => 'लोगो दिखाएँ',
                    'logo-alt'     => 'लोगो वैकल्पिक टेक्स्ट',
                    'logo-height'  => 'लोगो की ऊँचाई (पिक्सल में)',
                    'logo-width'   => 'लोगो की चौड़ाई (पिक्सल में)',
                    'title'        => 'लोगो',
                    'upload-logo'  => 'लोगो अपलोड करें',
                ],

                'header' => [
                    'header-content' => 'हेडर सामग्री',
                    'title'          => 'हेडर',
                ],

                'footer' => [
                    'footer-content' => 'फुटर सामग्री',
                    'title'          => 'फुटर',
                ],
            ],

            'edit' => [
                'back-btn'  => 'वापस',
                'btn-title' => 'रसीद सहेजें',
                'title'     => 'रसीद संपादित करें',

                'general' => [
                    'cashier-name-label'      => 'कैशियर नाम लेबल',
                    'change-amount-label'     => 'चेंज अमाउंट लेबल',
                    'credit-amount-label'     => 'क्रेडिट अमाउंट लेबल',
                    'discount-amt-label'      => 'छूट राशि लेबल',
                    'display-cashier-name'    => 'कैशियर नाम दिखाएँ',
                    'display-change-amount'   => 'चेंज अमाउंट दिखाएँ',
                    'display-credit-amount'   => 'क्रेडिट अमाउंट दिखाएँ',
                    'display-customer-name'   => 'ग्राहक नाम दिखाएँ',
                    'display-date'            => 'तारीख दिखाएँ',
                    'display-discount-amt'    => 'छूट राशि दिखाएँ',
                    'display-order-id'        => 'आदेश आईडी दिखाएँ',
                    'display-outlet-address'  => 'आउटलेट पता दिखाएँ',
                    'display-outlet-name'     => 'आउटलेट नाम दिखाएँ',
                    'display-sub-total'       => 'उप-योग दिखाएँ',
                    'display-tax'             => 'कर दिखाएँ',
                    'grand-total-label'       => 'कुल योग लेबल',
                    'order-id-label'          => 'आदेश आईडी लेबल',
                    'receipt-title'           => 'रसीद शीर्षक',
                    'show-order-barcode'      => 'ऑर्डर बारकोड दिखाएं',
                    'show-print-confirmation' => 'प्रिंट पुष्टि दिखाएं',
                    'status'                  => 'स्थिति',
                    'sub-total-label'         => 'उप-योग लेबल',
                    'tax-label'               => 'कर लेबल',
                    'title'                   => 'सामान्य',
                ],

                'logo' => [
                    'display-logo' => 'लोगो दिखाएँ',
                    'logo-alt'     => 'लोगो वैकल्पिक टेक्स्ट',
                    'logo-height'  => 'लोगो की ऊँचाई (पिक्सल में)',
                    'logo-width'   => 'लोगो की चौड़ाई (पिक्सल में)',
                    'title'        => 'लोगो',
                    'upload-logo'  => 'लोगो अपलोड करें',
                ],

                'header' => [
                    'header-content' => 'हेडर सामग्री',
                    'title'          => 'हेडर',
                ],

                'footer' => [
                    'footer-content' => 'फुटर सामग्री',
                    'title'          => 'फुटर',
                ],
            ],

            'preview' => [
                'amount'         => 'राशि',
                'cashier'        => 'कैशियर',
                'change-amount'  => 'चेंज',
                'customer'       => 'ग्राहक',
                'customer-email' => 'ग्राहक ईमेल',
                'customer-name'  => 'ग्राहक नाम',
                'customer-phone' => 'ग्राहक फोन',
                'date'           => 'तारीख',
                'discount'       => 'छूट',
                'email'          => 'ईमेल',
                'grand-total'    => 'कुल योग',
                'order-id'       => 'आदेश आईडी',
                'phone'          => 'फोन',
                'price'          => 'मूल्य',
                'product'        => 'उत्पाद',
                'qty'            => 'मात्रा',
                'sub-total'      => 'उप-योग',
                'tax'            => 'कर',
                'title'          => 'रसीद पूर्वावलोकन',
                'total-qty'      => 'कुल मात्रा',
            ],

            'create-success' => 'रसीद सफलतापूर्वक बनाई गई!',
            'delete-failed'  => 'रसीद हटाना विफल!',
            'delete-success' => 'रसीद सफलतापूर्वक हटाई गई!',
            'update-success' => 'रसीद सफलतापूर्वक अपडेट की गई!',
        ],
    ],

    'emails' => [
        'dear'     => 'प्रिय :name',
        'greeting' => 'नमस्ते!',

        'registration' => [
            'message' => 'बधाई हो! आपका खाता सफलतापूर्वक बनाया गया है। कृपया अपने खाते में लॉगिन करें और POS प्रणाली का उपयोग शुरू करें।',
            'subject' => 'POS उपयोगकर्ता पंजीकरण मेल',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'लॉगिन करें',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
