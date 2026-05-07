<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'අවලංගු ප්‍රතිපත්ති.',
                'not-activated'       => 'ඔබගේ ගිණුම සක්‍රීය කර නැත.',
                'verify-first'        => 'කරුණාකර පෙර එමෙන් තහවුරු කරන්න.',
                'success'             => 'ඔබ සාර්ථකව පනස්වට ගිණුමට පිවිසියාය.',
            ],

            'logout' => [
                'no-login-agent' => 'කිසිදු නියෝජිතයෙක් පිවිසෙන්නෙ නැත.',
                'success'        => 'ඔබ සාර්ථකව පිටවෙන්නටයි.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'ඔබ ලබාදුන් මුරපදය වැරදි ය.',
                    'success'          => 'ඔබගේ ගිණුම සාර්ථකව යාවත්කාලීන කරන ලදී.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'පාරිභෝගිකයා සාර්ථකව සාදන ලදී!',
            'update-success' => 'පාරිභෝගිකයා සාර්ථකව යාවත්කාලීන කරන ලදී!',
            'delete-success' => 'පාරිභෝගිකයා සාර්ථකව මැකීය!',
            'delete-failed'  => 'පාරිභෝගිකයා මැකීම අසාර්ථක විය!',
            'pending-orders' => 'පාරිභෝගිකයාට ප්‍රතික්ෂේප නියමිත ඇණවුම් තිබේ!',
        ],

        'cart' => [
            'already-applied'     => 'කුපොනය දැනටමත් අයදුම් කර ඇත!',
            'coupon-applied'      => 'කුපොනය සාර්ථකව අයදුම් කෙරිණි!',
            'coupon-removed'      => 'කුපොනය සාර්ථකව ඉවත් කරන ලදී!',
            'create-success'      => 'මාර්ගාව සාර්ථකව නිර්මාණය කරන ලදී!',
            'invalid-coupon'      => 'අවලංගු කුපෝන් කේතය!',
            'item-add-success'    => 'නිෂ්පාදනය සාර්ථකව මාර්ගාවට එකතු කරන ලදී!',
            'item-remove-success' => 'නිෂ්පාදනය සාර්ථකව මාර්ගාවෙන් ඉවත් කරන ලදී!',
            'item-update-success' => 'නිෂ්පාදනය සාර්ථකව යාවත්කාලීන කරන ලදී!',
            'not-found'           => 'මාර්ගාව සොයා ගැනීමට නොහැකි විය!',
        ],

        'payment' => [
            'title' => 'Pos ගෙවීම',

            'options' => [
                'cash' => [
                    'title'       => 'Pos මුදල් ගෙවීම',
                    'description' => 'මෙය Pos මුදල් ගෙවීමයි.',
                ],

                'card' => [
                    'title'       => 'Pos කාඩ්පතේ ගෙවීම',
                    'description' => 'මෙය Pos කාඩ්පතේ ගෙවීමයි.',
                ],

                'split' => [
                    'title'       => 'Pos බෙදා ගෙවීම',
                    'description' => 'මෙය Pos බෙදා ගෙවීමයි.',
                ],
            ],

            'no-items' => 'ගෙවීම අත්හිටුවීමට වාහනේ කිසිදු භාණ්ඩයක් නැත.',
            'success'  => 'ගෙවීම සාර්ථකව සම්පූර්ණ විය!',
        ],

        'shipping' => [
            'title'       => 'Pos නැව්ගත කිරීම',
            'description' => 'මෙය නොමිලේ Pos නැව්ගත කිරීමයි.',
        ],

        'order' => [
            'sync-success' => 'ඇණවුම සාර්ථකව සමුපකරණය කරන ලදී!',
        ],

        'return' => [
            'create-success'              => 'ප්‍රතිලාභය සාර්ථකව නිර්මාණය කරන ලදී',
            'creation-error'              => 'ප්‍රතිලාභ නිර්මාණය කිරීමට ඉඩ නොදෙයි.',
            'invalid-qty'                 => 'අයිතම බිල් කිරීම සඳහා වලංගු නොවන ප්‍රමාණයක් හමු විය.',
            'invalid-refund-amount-error' => 'ප්‍රතිලාභ මුදල ශූන්‍ය නොවිය යුතුය.',
            'refund-limit-error'          => 'ප්‍රතිලාභ මුදල :amount ක් සැකසිය නොහැක.',
        ],

        'drawer' => [
            'create-success' => 'අංශය සාර්ථකව විවෘත කරන ලදී!',
            'not-opened'     => 'අංශය විවෘත කර නැත.',
            'close-success'  => 'අංශය සාර්ථකව වසා ඇත!',
        ],

        'products' => [
            'request-success' => 'නිෂ්පාදන අයදුම්පත සාර්ථකව ඉදිරිපත් කරන ලදී.',
            'create-success'  => 'නිෂ්පාදනය සාර්ථකව සාදන ලදී!',
        ],

        'reports' => [
            'orders'                  => 'ඇණවුම්',
            'average-order-value'     => 'මධ්‍යම ඇණවුම් වටිනාකම',
            'average-items-per-order' => 'ඇණවුමකට මධ්‍යම භාණ්ඩ ගණන',
            'discounted-offers'       => 'වට්ටම් ලබාදුන් ඇණවුම්',
            'cash-payments'           => 'මුදල් ගෙවීම්',
            'other-payments'          => 'ඉතිරියෙන් ගෙවීම්',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'ඇණවුම් සටහන',
                    'note-not-available' => 'සටහන ලබා ගත නොහැක!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'Bagisto Point of Sale (POS) විශේෂාංගය.',
                    'title' => 'ප්‍රේක්ෂක මට්ටම',

                    'settings' => [
                        'info'  => 'POS සක්‍රීය කරන්න, සාමාන්‍ය වින්‍යාසය, POS නිෂ්පාදන සහ බිල් මුද්‍රණය සැකසීම.',
                        'title' => 'සැකසුම්',

                        'general' => [
                            'footer-content'       => 'අන්තිම අන්තර්ගතය',
                            'footer-note'          => 'අන්තිම සටහන',
                            'frontend-url'         => 'ෆ්රන්ට්ඕන්ඩ් URL',
                            'heading-on-login'     => 'ඉන්ලොග්හි මාතෘකාව',
                            'info'                 => 'සාමාන්‍ය සැකසුම් POS පරිශීලක පිටුව සඳහා වින්‍යාස සලසයි, ලාංඡන, මාතෘකාවන්, අන්තිම අන්තර්ගතය, අන්තිම සටහන ආදී එක් කිරීමට.',
                            'pos-logo'             => 'POS ලාංඡනය',
                            'status'               => 'ස්ථාවරය',
                            'sub-heading-on-login' => 'ඉන්ලොග්හි උප මාතෘකාව',
                            'title'                => 'සාමාන්‍ය',
                        ],

                        'barcode' => [
                            'height'             => 'උස',
                            'hide-barcode'       => 'බාකෝඩ් සඟවා ගැනීම',
                            'info'               => 'බාකෝඩ් සැකසුම් බාකෝඩ් ජනන, බාකෝඩ් උස, බාකෝඩ් පළල, බාකෝඩ් වර්ගය ආදී වින්‍යාස සලසයි.',
                            'prefix'             => 'පූර්වගාමී',
                            'print-product-name' => 'නිෂ්පාදන නාමය මුද්‍රණය කරන්න',
                            'title'              => 'බාකෝඩ්',
                            'width'              => 'පළල',

                            'generate-with' => [
                                'title' => 'බාකෝඩ් නිෂ්පාදනය',

                                'options' => [
                                    'product-id' => 'නිෂ්පාදන හැඳුනුම්පත',
                                    'sku'        => 'නිෂ්පාදන SKU',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'පාරිභෝගික නිෂ්පාදනය සඳහා SKU අවසර දෙන්න',
                            'info'      => 'නිෂ්පාදන සැකසුම් SKU සඳහා වින්‍යාස කිරීමට ඉඩ දෙයි.',
                            'title'     => 'නිෂ්පාදන',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'නිෂ්පාදන පවරන්න',
            'banks'            => 'බැංකු',
            'barcode-products' => 'බාර්කෝඩ් නිෂ්පාදන',
            'create'           => 'සාදන්න',
            'delete'           => 'මකන්න',
            'edit'             => 'සංස්කරණය',
            'generate-barcode' => 'බාර්කෝඩ් ජනනය කරන්න',
            'orders'           => 'ඇණවුම්',
            'outlets'          => 'අවකාශ',
            'pos'              => 'අලෙවිකරණ ස්ථානය (POS)',
            'preview'          => 'පෙරදසුන',
            'print-barcode'    => 'බාර්කෝඩ් මුද්‍රණය කරන්න',
            'receipts'         => 'අපිත්',
            'requests'         => 'ඉල්ලීම්',
            'sales-report'     => 'විකුණුම් වාර්තාව',
            'users'            => 'ආදේශක',
            'view'             => 'දර්ශනය',
        ],

        'layouts' => [
            'banks'            => 'බැංකු',
            'barcode-products' => 'බාර්කෝඩ් නිෂ්පාදන',
            'orders'           => 'ඇණවුම්',
            'pos'              => 'අලෙවිකරණ ස්ථානය (POS)',
            'receipts'         => 'අපිත්',
            'requests'         => 'ඉල්ලීම්',
            'sales-report'     => 'විකුණුම් වාර්තාව',

            'users' => [
                'agents'   => 'ආදේශක',
                'outlets'  => 'අවශ්‍යතා',
                'title'    => 'ආදේශක',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'නියෝජිතයන් තනන්න',
                    'pos-front'  => 'POS ෆ්රන්ට්',
                    'title'      => 'නියෝජිතයන්',

                    'datagrid' => [
                        'action'              => 'ක්‍රියා',
                        'delete'              => 'මැකීම',
                        'edit'                => 'සංස්කරණය',
                        'email'               => 'විද්‍යුත් තැපෑල',
                        'full-name'           => 'සම්පූර්ණ නාමය',
                        'id'                  => 'හැඳුනුම්පත',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'තෝරාගත් නියෝජිතයින් සාර්ථකව මකා ඇත!',
                        'mass-update-success' => 'තෝරාගත් නියෝජිතයින් සාර්ථකව යාවත්කාලීන කර ඇත!',
                        'outlet-name'         => 'අවකාශයේ නම',
                        'profile-image'       => 'පැතිකඩ රූපය',
                        'update-status'       => 'අවස්ථාව යාවත්කාලීන කරන්න',
                        'username'            => 'පරිශීලක නාමය',

                        'status' => [
                            'title' => 'ස්ථාවරය',

                            'options' => [
                                'active'  => 'සක්‍රීය',
                                'disable' => 'අක්‍රීය',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'ආපසු',
                    'confirm-password'  => 'මුරපදය තහවුරු කරන්න',
                    'email'             => 'විද්‍යුත් තැපෑල',
                    'first-name'        => 'මුල් නම',
                    'general'           => 'සාමාන්‍ය',
                    'image'             => 'රූපය',
                    'last-name'         => 'අවසාන නම',
                    'outlet'            => 'අවකාශ',
                    'outlet-and-status' => 'අවකාශය සහ ස්ථාවරය',
                    'password'          => 'මුරපදය',
                    'save-btn'          => 'නියෝජිතය සුරැකුම් කරන්න',
                    'select-outlet'     => 'අවකාශය තෝරන්න',
                    'status'            => 'ස්ථාවරය',
                    'title'             => 'නියෝජිතය එක් කරන්න',
                    'user-image'        => 'නියෝජිත රූපය උඩුගත කරන්න',
                    'username'          => 'පරිශීලක නාමය',
                ],

                'edit' => [
                    'back-btn'          => 'ආපසු',
                    'confirm-password'  => 'මුරපදය තහවුරු කරන්න',
                    'email'             => 'විද්‍යුත් තැපෑල',
                    'first-name'        => 'මුල් නම',
                    'general'           => 'සාමාන්‍ය',
                    'image'             => 'රූපය',
                    'last-name'         => 'අවසාන නම',
                    'outlet'            => 'අවකාශ',
                    'outlet-and-status' => 'අවකාශය සහ ස්ථාවරය',
                    'password'          => 'මුරපදය',
                    'save-btn'          => 'නියෝජිතය සුරැකුම් කරන්න',
                    'select-outlet'     => 'අවකාශය තෝරන්න',
                    'status'            => 'ස්ථාවරය',
                    'title'             => 'නියෝජිතය සංස්කරණය කරන්න',
                    'user-image'        => 'නියෝජිත රූපය උඩුගත කරන්න',
                    'username'          => 'පරිශීලක නාමය',
                ],

                'create-success' => 'නියෝජිතය සාර්ථකව තනන ලදි!',
                'delete-failed'  => 'නියෝජිතය මැකීම අපට සාර්ථක නොවීය!',
                'delete-success' => 'නියෝජිතය සාර්ථකව මැකීය!',
                'update-success' => 'නියෝජිතය සාර්ථකව යාවත්කාලීන කරන ලදි!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'අවකාශ තනන්න',
                    'pos-front'  => 'POS ෆ්රන්ට්',
                    'title'      => 'අවකාශ',

                    'datagrid' => [
                        'action'              => 'ක්‍රියා',
                        'active'              => 'සක්‍රීය',
                        'assign'              => 'නිෂ්පාදන පවරන්න',
                        'delete'              => 'මැකීම',
                        'edit'                => 'සංස්කරණය',
                        'id'                  => 'හැඳුනුම්පත',
                        'inactive'            => 'අක්‍රීය',
                        'inventory-source'    => 'ආරක්ෂණ මූලාශ්‍රය',
                        'mass-delete-success' => 'තෝරාගත් ආයතන මැකීම සාර්ථකයි!',
                        'mass-update-success' => 'තෝරාගත් ආයතන යාවත්කාලීන කිරීම සාර්ථකයි!',
                        'name'                => 'නම',
                        'receipt-title'       => 'බිල් මාතෘකාව',
                        'status'              => 'ස්ථාවරය',
                        'title'               => 'POS දෘශ්‍යය ලැයිස්තු',
                        'update-status'       => 'ස්ථාවරය යාවත්කාලීන කරන්න',
                    ],
                ],

                'create' => [
                    'address'                 => 'ලිපිනය',
                    'back-btn'                => 'ආපසු',
                    'btn-title'               => 'අවකාශය සුරකින්න',
                    'city'                    => 'නගරය',
                    'country'                 => 'රට',
                    'customer-care-number'    => 'පාරිභෝගික සේවා අංකය',
                    'email'                   => 'විද්‍යුත් තැපෑල',
                    'general'                 => 'සාමාන්‍ය',
                    'gst-number'              => 'GST අංකය',
                    'inventory'               => 'ඉන්වෙන්ටරි',
                    'inventory-source'        => 'ඉන්වෙන්ටරි මූලාශ්‍රය',
                    'low-stock-qty'           => 'අඩු තොග ප්‍රමාණය',
                    'name'                    => 'අවකාශයේ නම',
                    'phone'                   => 'දුරකථන',
                    'postcode'                => 'තැපැල් කේතය',
                    'receipt'                 => 'බිල්පත',
                    'select-country'          => 'රට තෝරන්න',
                    'select-inventory-source' => 'ඉන්වෙන්ටරි මූලාශ්‍රය තෝරන්න',
                    'select-receipt'          => 'බිල්පත තෝරන්න',
                    'state'                   => 'පළාත',
                    'status'                  => 'ස්ථාවරය',
                    'store-address'           => 'අවකාශ ලිපිනය',
                    'title'                   => 'අවකාශය එක් කරන්න',
                    'website'                 => 'වෙබ් අඩවිය',
                ],

                'edit' => [
                    'address'                 => 'ලිපිනය',
                    'back-btn'                => 'ආපසු',
                    'btn-title'               => 'අවකාශය සුරකින්න',
                    'city'                    => 'නගරය',
                    'country'                 => 'රට',
                    'customer-care-number'    => 'පාරිභෝගික සේවා අංකය',
                    'email'                   => 'විද්‍යුත් තැපෑල',
                    'general'                 => 'සාමාන්‍ය',
                    'gst-number'              => 'GST අංකය',
                    'inventory'               => 'ඉන්වෙන්ටරි',
                    'inventory-source'        => 'ඉන්වෙන්ටරි මූලාශ්‍රය',
                    'low-stock-qty'           => 'අඩු තොග ප්‍රමාණය',
                    'name'                    => 'අවකාශයේ නම',
                    'phone'                   => 'දුරකථන',
                    'postcode'                => 'තැපැල් කේතය',
                    'receipt'                 => 'බිල්පත',
                    'select-country'          => 'රට තෝරන්න',
                    'select-inventory-source' => 'ඉන්වෙන්ටරි මූලාශ්‍රය තෝරන්න',
                    'select-receipt'          => 'බිල්පත තෝරන්න',
                    'state'                   => 'පළාත',
                    'status'                  => 'ස්ථාවරය',
                    'store-address'           => 'අවකාශ ලිපිනය',
                    'title'                   => 'අවකාශය සංස්කරණය කරන්න',
                    'website'                 => 'වෙබ් අඩවිය',
                ],

                'assign' => [
                    'back-btn' => 'ආපසු',
                    'title'    => 'අවකාශ නිෂ්පාදන කළමනාකරණය',

                    'datagrid' => [
                        'active'              => 'සක්‍රීය',
                        'assign'              => 'පවරන්න',
                        'disable'             => 'අක්‍රීය කරන්න',
                        'id'                  => 'හැඳුනුම්පත',
                        'id-value'            => 'Id - :id',
                        'image'               => 'රූපය',
                        'mass-assign-success' => 'නිෂ්පාදන පවරන යාවත්කාලීන කිරීම සාර්ථකයි!',
                        'name'                => 'නම',
                        'out-of-stock'        => 'තොගය අඩු',
                        'pos-status'          => 'POS ස්ථාවරය',
                        'price'               => 'මිල',
                        'product-image'       => 'නිෂ්පාදන රූපය',
                        'qty'                 => 'ප්‍රමාණය',
                        'qty-value'           => ':qty ලබා ගත හැකිය',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'ස්ථාවරය',
                        'type'                => 'වර්ගය',
                        'unassign'            => 'අකිරීම',
                        'update-assign'       => 'පවරන යාවත්කාලීන කිරීම',
                    ],
                ],

                'create-success' => 'අවකාශය සාර්ථකව තනන ලදි!',
                'delete-failed'  => 'අවකාශය මැකීම අපට සාර්ථක නොවීය!',
                'delete-success' => 'අවකාශය සාර්ථකව මැකීය!',
                'update-success' => 'අවකාශය සාර්ථකව යාවත්කාලීන කරන ලදි!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Productos con Código de Barras',

                'datagrid' => [
                    'barcode'               => 'බාර්කෝඩ්',
                    'generate-barcode'      => 'බාර්කෝඩ් ජනනය කරන්න',
                    'print-barcode'         => 'බාර්කෝඩ් මුද්‍රණය කරන්න',
                    'id'                    => 'හැඳුනුම්පත',
                    'id-value'              => 'Id - :id',
                    'image'                 => 'රූපය',
                    'mass-generate-success' => 'තෝරාගත් නිෂ්පාදන බාර්කෝඩ් සාර්ථකව ජනනය කරන ලදි!',
                    'name'                  => 'නම',
                    'out-of-stock'          => 'තොගයෙන් පිටවී ඇත',
                    'price'                 => 'මිල',
                    'product-image'         => 'නිෂ්පාදන රූපය',
                    'qty'                   => 'ප්‍රමාණය',
                    'qty-value'             => ':qty ලබා ගත හැකිය',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'ස්ථාවරය',

                        'options' => [
                            'active'  => 'සක්‍රීය',
                            'disable' => 'අක්‍රීය',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'ආපසු',
                'btn-title' => 'මුද්‍රණය',
                'qty'       => 'ප්‍රමාණය',
                'title'     => 'බාර්කෝඩ් මුද්‍රණය',
            ],

            'generate-failed'  => 'බාර්කෝඩ් ජනනය කිරීම අසාර්ථකයි!',
            'generate-success' => 'බාර්කෝඩ් සාර්ථකව ජනනය කරන ලදි!',
        ],

        'orders' => [
            'index' => [
                'title' => 'ඇණවුම්',

                'datagrid' => [
                    'customer-name' => 'ගනුදෙනුකරුගේ නම',
                    'grand-total'   => 'මුළු එකතු කිරීම',
                    'order-date'    => 'ඇණවුම් දිනය',
                    'order-id'      => 'ඇණවුම් ID',
                    'order-ref-id'  => 'ඇණවුම් යාවත්කාලීන ID',
                    'view'          => 'දැක්ම',

                    'status' => [
                        'title' => 'ස්ථාවරය',

                        'options' => [
                            'canceled'        => 'අවලංගු',
                            'closed'          => 'සැමවිට',
                            'completed'       => 'සම්පූර්ණ',
                            'fraud'           => 'වංචන',
                            'pending'         => 'නොමැත',
                            'pending-payment' => 'ගෙවීමේදී',
                            'processing'      => 'සංස්කරණය',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'අයැදුම්',

                'datagrid' => [
                    'id'                  => 'හැඳුනුම්පත',
                    'product-image'       => 'නිෂ්පාදන රූපය',
                    'mass-update-error'   => 'අයැදුම් යාවත්කාලීන කිරීම අසාර්ථකයි!',
                    'mass-update-success' => 'තෝරාගත් අයැදුම් සාර්ථකව යාවත්කාලීන කරන ලදි!',
                    'product-name'        => 'නිෂ්පාදන නම',
                    'outlet-name'         => 'අවසාන නම',
                    'qty-value'           => 'ප්‍රමාණය - :qty',
                    'request-date'        => 'අයැදුම් දිනය',
                    'requested-qty'       => 'අයැදුම් කරන ලද ප්‍රමාණය',
                    'update-status'       => 'ස්ථාවරය යාවත්කාලීන කරන්න',
                    'user-name'           => 'පරිශීලක නම',

                    'status' => [
                        'title' => 'ස්ථාවරය',

                        'options' => [
                            'complete' => 'සම්පූර්ණ',
                            'decline'  => 'ප්‍රතික්ෂේප කරන්න',
                            'pending'  => 'නොමැත',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'ආපසු',
                'btn-title' => 'සුරකින්න',
                'title'     => 'අයැදුම් කරන ලද නිෂ්පාදන විස්තර #:id',

                'user-info' => [
                    'email'            => 'ඊ-තැපැල්',
                    'name'             => 'නම',
                    'outlet-address'   => 'අවසාන ලිපිනය',
                    'outlet-inventory' => 'අවසාන තොග මූලය',
                    'outlet-name'      => 'අවසාන නම',
                    'title'            => 'පරිශීලක තොරතුරු',
                ],

                'request-info' => [
                    'comment'       => 'අනුරෝධය',
                    'product-name'  => 'නිෂ්පාදන නම',
                    'qty-value'     => 'ප්‍රමාණය - :qty',
                    'request-date'  => 'අයැදුම් දිනය',
                    'requested-qty' => 'අයැදුම් කරන ලද ප්‍රමාණය',
                    'title'         => 'අයැදුම් තොරතුරු',

                    'status' => [
                        'title' => 'ස්ථාවරය',

                        'options' => [
                            'complete' => 'සම්පූර්ණ',
                            'decline'  => 'ප්‍රතික්ෂේප කරන්න',
                            'pending'  => 'නොමැත',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'අයැදුම් යාවත්කාලීන කිරීම අසාර්ථකයි!',
            'update-success' => 'අයැදුම් සාර්ථකව යාවත්කාලීන කරන ලදි!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'බැංකුවක් සාදන්න',
                'title'     => 'බැංකු',

                'datagrid' => [
                    'active'              => 'සක්‍රීය',
                    'address'             => 'බැංකු ලිපිනය',
                    'agent-name'          => 'ආයුක්ත',
                    'delete'              => 'ඉවත් කරන්න',
                    'disable'             => 'සක්‍රීය නොකරන්න',
                    'id'                  => 'අයිඩි',
                    'mass-delete-success' => 'තෝරාගත් බැංකු මැකීම සාර්ථකයි!',
                    'name'                => 'බැංකු නාමය',
                    'status'              => 'තත්ත්වය',
                ],
            ],

            'create' => [
                'back-btn'  => 'ආපසු',
                'btn-title' => 'බැංකුව සුරකින්න',
                'title'     => 'නව බැංකුවක් සාදන්න',

                'general' => [
                    'address' => 'ලිපිනය',
                    'email'   => 'ඊ-තැපැල්',
                    'name'    => 'නම',
                    'phone'   => 'දුරකථන',
                    'title'   => 'සාමාන්‍ය',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS උපදේශකය පිවිසෙන්න',
                    'bank-status'  => 'බැංකු ස්ථාවරය',
                    'select-agent' => 'උපදේශකය තෝරන්න',
                    'title'        => 'POS උපදේශකයා සහ බැංකු ස්ථාවරය',
                ],
            ],

            'edit' => [
                'back-btn'  => 'ආපසු',
                'btn-title' => 'බැංකුව සුරකින්න',
                'title'     => 'බැංකු සංස්කරණය',

                'general' => [
                    'address' => 'ලිපිනය',
                    'email'   => 'ඊ-තැපැල්',
                    'name'    => 'නම',
                    'phone'   => 'දුරකථන',
                    'title'   => 'සාමාන්‍ය',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS උපදේශකය පිවිසෙන්න',
                    'bank-status'  => 'බැංකු ස්ථාවරය',
                    'select-agent' => 'උපදේශකය තෝරන්න',
                    'title'        => 'POS උපදේශකයා සහ බැංකු ස්ථාවරය',
                ],
            ],

            'create-success' => 'බැංකුව සාර්ථකව සාදන ලදි!',
            'delete-failed'  => 'බැංකු මැකීම අසාර්ථකයි!',
            'delete-success' => 'බැංකුව සාර්ථකව මැකීය!',
            'update-success' => 'බැංකුව සාර්ථකව යාවත්කාලීන කරන ලදි!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'විකුණුම් වාර්තාව',

                'datagrid' => [
                    'bank-name'      => 'බැංකු නම',
                    'grand-total'    => 'මුළු එකතු',
                    'order-date'     => 'ඇණවුම් දිනය',
                    'order-id'       => 'ඇණවුම් හැඳුනුම්පත',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'ඇණවුම් සටහන',
                    'outlet-name'    => 'අවසාන නම',
                    'payment-method' => 'ගෙවීම් ක්‍රමය',
                    'view'           => 'දැක්කාම',

                    'status' => [
                        'title' => 'ස්ථාවරය',

                        'options' => [
                            'canceled'        => 'අවලංගු',
                            'closed'          => 'වසා දැමූ',
                            'completed'       => 'සම්පූර්ණ',
                            'fraud'           => 'වංචන',
                            'pending'         => 'පැමිනිම්',
                            'pending-payment' => 'ගෙවීම් නොකළ',
                            'processing'      => 'සංස්කරණය කිරීම',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'අප්‍රාප්ත සාදන්න',
                'title'      => 'අප්‍රාප්ත',

                'datagrid' => [
                    'delete'              => 'මැකීම',
                    'edit'                => 'සංස්කරණය',
                    'id'                  => 'හැඳුනුම්පත',
                    'mass-delete-success' => 'තෝරාගත් අප්‍රාප්ත සාර්ථකව මැකීය!',
                    'preview'             => 'පෙරදැක්වීම',
                    'title'               => 'ශීර්ෂය',

                    'status' => [
                        'title' => 'ස්ථාවරය',

                        'options' => [
                            'active'   => 'සක්‍රීය',
                            'inactive' => 'අක්‍රීය',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'ආපසු',
                'btn-title' => 'අප්‍රාප්ත සුරකින්න',
                'title'     => 'නව අප්‍රාප්ත සාදන්න',

                'general' => [
                    'cashier-name-label'      => 'වැසිලිකරු නම ලේබලය',
                    'change-amount-label'     => 'වෙනස් කිරීම ප්‍රමාණය ලේබලය',
                    'credit-amount-label'     => 'ක්‍රෙඩිට් ප්‍රමාණය ලේබලය',
                    'discount-amt-label'      => 'අවස්ථාව ලේබලය',
                    'display-cashier-name'    => 'වැසිලිකරු නම පෙන්වන්න',
                    'display-change-amount'   => 'වෙනස් කිරීම ප්‍රමාණය පෙන්වන්න',
                    'display-credit-amount'   => 'ක්‍රෙඩිට් ප්‍රමාණය පෙන්වන්න',
                    'display-customer-name'   => 'පාරිභෝගික නම පෙන්වන්න',
                    'display-date'            => 'දිනය පෙන්වන්න',
                    'display-discount-amt'    => 'අවස්ථාව පෙන්වන්න',
                    'display-order-id'        => 'ඇණවුම් හැඳුනුම්පත පෙන්වන්න',
                    'display-outlet-address'  => 'අවසාන ලිපිනය පෙන්වන්න',
                    'display-outlet-name'     => 'අවසාන නම පෙන්වන්න',
                    'display-sub-total'       => 'අඩු මුළු මුදල පෙන්වන්න',
                    'display-tax'             => 'අදාළ වියදම් පෙන්වන්න',
                    'grand-total-label'       => 'මුළු එකතු ලේබලය',
                    'order-id-label'          => 'ඇණවුම් හැඳුනුම්පත ලේබලය',
                    'receipt-title'           => 'අප්‍රාප්ත ශීර්ෂය',
                    'show-order-barcode'      => 'ඇණවුම් තීරු කේතය පෙන්වන්න',
                    'show-print-confirmation' => 'මුද්‍රණ තහවුරු කිරීම පෙන්වන්න',
                    'status'                  => 'ස්ථාවරය',
                    'sub-total-label'         => 'අඩු මුළු මුදල ලේබලය',
                    'tax-label'               => 'අදාළ වියදම් ලේබලය',
                    'title'                   => 'සාමාන්‍ය',
                ],

                'logo' => [
                    'display-logo' => 'ලෝගෝ පෙන්වන්න',
                    'logo-alt'     => 'ලෝගෝ විශේෂණ',
                    'logo-height'  => 'ලෝගෝ උස (px වලින්)',
                    'logo-width'   => 'ලෝගෝ පළල (px වලින්)',
                    'title'        => 'ලෝගෝ',
                    'upload-logo'  => 'ලෝගෝ උඩුගත කරන්න',
                ],

                'header' => [
                    'header-content' => 'ශීර්ෂ අන්තර්ගතය',
                    'title'          => 'ශීර්ෂ',
                ],

                'footer' => [
                    'footer-content' => 'අවසන් අන්තර්ගතය',
                    'title'          => 'අවසන්',
                ],
            ],

            'edit' => [
                'back-btn'  => 'ආපසු',
                'btn-title' => 'අප්‍රාප්ත සුරකින්න',
                'title'     => 'අප්‍රාප්ත සංස්කරණය',

                'general' => [
                    'cashier-name-label'      => 'වැසිලිකරු නම ලේබලය',
                    'change-amount-label'     => 'වෙනස් කිරීම ප්‍රමාණය ලේබලය',
                    'credit-amount-label'     => 'ක්‍රෙඩිට් ප්‍රමාණය ලේබලය',
                    'discount-amt-label'      => 'අවස්ථාව ලේබලය',
                    'display-cashier-name'    => 'වැසිලිකරු නම පෙන්වන්න',
                    'display-change-amount'   => 'වෙනස් කිරීම ප්‍රමාණය පෙන්වන්න',
                    'display-credit-amount'   => 'ක්‍රෙඩිට් ප්‍රමාණය පෙන්වන්න',
                    'display-customer-name'   => 'පාරිභෝගික නම පෙන්වන්න',
                    'display-date'            => 'දිනය පෙන්වන්න',
                    'display-discount-amt'    => 'අවස්ථාව පෙන්වන්න',
                    'display-order-id'        => 'ඇණවුම් හැඳුනුම්පත පෙන්වන්න',
                    'display-outlet-address'  => 'අවසාන ලිපිනය පෙන්වන්න',
                    'display-outlet-name'     => 'අවසාන නම පෙන්වන්න',
                    'display-sub-total'       => 'අඩු මුළු මුදල පෙන්වන්න',
                    'display-tax'             => 'අදාළ වියදම් පෙන්වන්න',
                    'grand-total-label'       => 'මුළු එකතු ලේබලය',
                    'order-id-label'          => 'ඇණවුම් හැඳුනුම්පත ලේබලය',
                    'receipt-title'           => 'අප්‍රාප්ත ශීර්ෂය',
                    'show-order-barcode'      => 'ඇණවුම් තීරු කේතය පෙන්වන්න',
                    'show-print-confirmation' => 'මුද්‍රණ තහවුරු කිරීම පෙන්වන්න',
                    'status'                  => 'ස්ථාවරය',
                    'sub-total-label'         => 'අඩු මුළු මුදල ලේබලය',
                    'tax-label'               => 'අදාළ වියදම් ලේබලය',
                    'title'                   => 'සාමාන්‍ය',
                ],

                'logo' => [
                    'display-logo' => 'ලෝගෝ පෙන්වන්න',
                    'logo-alt'     => 'ලෝගෝ විශේෂණ',
                    'logo-height'  => 'ලෝගෝ උස (px වලින්)',
                    'logo-width'   => 'ලෝගෝ පළල (px වලින්)',
                    'title'        => 'ලෝගෝ',
                    'upload-logo'  => 'ලෝගෝ උඩුගත කරන්න',
                ],

                'header' => [
                    'header-content' => 'ශීර්ෂ අන්තර්ගතය',
                    'title'          => 'ශීර්ෂ',
                ],

                'footer' => [
                    'footer-content' => 'අවසන් අන්තර්ගතය',
                    'title'          => 'අවසන්',
                ],
            ],

            'preview' => [
                'amount'         => 'ප්‍රමාණය',
                'cashier'        => 'වැසිලිකරු',
                'change-amount'  => 'වෙනස්',
                'customer'       => 'පාරිභෝගිකයා',
                'customer-email' => 'පාරිභෝගික ඊමේල්',
                'customer-name'  => 'පාරිභෝගික නම',
                'customer-phone' => 'පාරිභෝගික දුරකථන',
                'date'           => 'දිනය',
                'discount'       => 'අවස්ථාව',
                'email'          => 'ඊමේල්',
                'grand-total'    => 'මුළු එකතු',
                'order-id'       => 'ඇණවුම් හැඳුනුම්පත',
                'phone'          => 'දුරකථන',
                'price'          => 'මිල',
                'product'        => 'නිෂ්පාදනය',
                'qty'            => 'ප්‍රමාණය',
                'sub-total'      => 'අඩු මුළු',
                'tax'            => 'අදාළ වියදම්',
                'title'          => 'අප්‍රාප්ත පෙරදැක්වීම',
                'total-qty'      => 'මුළු ප්‍රමාණය',
            ],

            'create-success' => 'අප්‍රාප්ත සාර්ථකව සාදන ලදි!',
            'delete-failed'  => 'අප්‍රාප්ත මැකීම අසාර්ථකයි!',
            'delete-success' => 'අප්‍රාප්ත සාර්ථකව මැකීය!',
            'update-success' => 'අප්‍රාප්ත සාර්ථකව යාවත්කාලීන කරන ලදි!',
        ],
    ],

    'emails' => [
        'dear'     => 'ප්‍රිය :name',
        'greeting' => 'ආයුබෝවන්!',

        'registration' => [
            'message' => 'සුබ පැතුම්! ඔබේ ගිණුම සාර්ථකව සාදන ලදී. කරුණාකර ඔබගේ ගිණුමට පිවිසෙන්න සහ POS පද්ධතිය භාවිතා කරන්න ආරම්භ කරන්න.',
            'subject' => 'POS පරිශීලක ලියාපදිංචි ඊමේල්',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'ඇතුල්වන්න',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
