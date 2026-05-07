<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'פרטי ההתחברות אינם תקינים.',
                'not-activated'       => 'החשבון שלך אינו פעיל.',
                'verify-first'        => 'אנא אשר את כתובת האימייל שלך קודם.',
                'success'             => 'נכנסת בהצלחה.',
            ],

            'logout' => [
                'no-login-agent' => 'אין סוכן מחובר.',
                'success'        => 'התנתקת בהצלחה.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'סיסמת הכניסה שהוזנה אינה נכונה.',
                    'success'          => 'החשבון שלך עודכן בהצלחה.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'הלקוח נוצר בהצלחה!',
            'update-success' => 'הלקוח עודכן בהצלחה!',
            'delete-success' => 'הלקוח נמחק בהצלחה!',
            'delete-failed'  => 'מחיקת הלקוח נכשלה!',
            'pending-orders' => 'ללקוח יש הזמנות ממתינות!',
        ],

        'cart' => [
            'already-applied'     => 'הקופון כבר הוחל!',
            'coupon-applied'      => 'הקופון הוחל בהצלחה!',
            'coupon-removed'      => 'הקופון הוסר בהצלחה!',
            'create-success'      => 'העגלה נוצרה בהצלחה!',
            'invalid-coupon'      => 'קוד קופון לא חוקי!',
            'item-add-success'    => 'המוצר נוסף לעגלה בהצלחה!',
            'item-remove-success' => 'המוצר הוסר מהעגלה בהצלחה!',
            'item-update-success' => 'המוצר עודכן בהצלחה!',
            'not-found'           => 'העגלה לא נמצאה!',
        ],

        'payment' => [
            'title' => 'תשלום Pos',

            'options' => [
                'cash' => [
                    'title'       => 'תשלום במזומן Pos',
                    'description' => 'זהו תשלום במזומן Pos.',
                ],

                'card' => [
                    'title'       => 'תשלום בכרטיס Pos',
                    'description' => 'זהו תשלום בכרטיס Pos.',
                ],

                'split' => [
                    'title'       => 'תשלום מחולק Pos',
                    'description' => 'זהו תשלום מחולק Pos.',
                ],
            ],

            'no-items' => 'אין פריטים בסל כדי להמשיך בתשלום.',
            'success'  => 'התשלום הושלם בהצלחה!',
        ],

        'shipping' => [
            'title'       => 'משלוח Pos',
            'description' => 'זהו משלוח Pos חינם.',
        ],

        'order' => [
            'sync-success' => 'ההזמנה סונכרנה בהצלחה!',
        ],

        'return' => [
            'create-success'              => 'החזר נוצר בהצלחה',
            'creation-error'              => 'אין אפשרות ליצור החזר.',
            'invalid-qty'                 => 'נמצאה כמות לא חוקית לחיוב פריטים.',
            'invalid-refund-amount-error' => 'סכום ההחזר חייב להיות שונה מאפס.',
            'refund-limit-error'          => 'לא ניתן לעבד החזר בסך :amount.',
        ],

        'drawer' => [
            'create-success' => 'מגירת הקופה נפתחה בהצלחה!',
            'not-opened'     => 'המגירה אינה פתוחה.',
            'close-success'  => 'המגירה נסגרה בהצלחה!',
        ],

        'products' => [
            'request-success' => 'בקשת המוצר נשלחה בהצלחה.',
            'create-success'  => 'מוצר נוצר בהצלחה!',
        ],

        'reports' => [
            'orders'                  => 'הזמנות',
            'average-order-value'     => 'ערך ממוצע להזמנה',
            'average-items-per-order' => 'ממוצע פריטים להזמנה',
            'discounted-offers'       => 'הצעות עם הנחה',
            'cash-payments'           => 'תשלומים במזומן',
            'other-payments'          => 'תשלומים אחרים',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'הערת הזמנה',
                    'note-not-available' => 'הערה אינה זמינה!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'תוסף נקודת מכירה (POS) של Bagisto.',
                    'title' => 'נקודת מכירה',

                    'settings' => [
                        'info'  => 'הפעל POS, הגדר את הקונפיגורציה הכללית, מוצר POS וקבלה.',
                        'title' => 'הגדרות',

                        'general' => [
                            'footer-content'       => 'תוכן תחתון',
                            'footer-note'          => 'הערה תחתונה',
                            'frontend-url'         => 'כתובת ה-Frontend',
                            'heading-on-login'     => 'כותרת בעת כניסה',
                            'info'                 => 'ההגדרות הכלליות מאפשרות קונפיגורציה של דף המשתמש של POS, על ידי הוספת לוגו, כותרות, תוכן תחתון, הערות תחתון וכו\'.',
                            'pos-logo'             => 'לוגו POS',
                            'status'               => 'סטטוס',
                            'sub-heading-on-login' => 'כותרת משנה בעת כניסה',
                            'title'                => 'כללי',
                        ],

                        'barcode' => [
                            'height'             => 'גובה',
                            'hide-barcode'       => 'הסתר קוד ברקוד',
                            'info'               => 'הגדרות הברקוד מאפשרות קונפיגורציה ליצירת קודים, גובה קוד הברקוד, רוחב קוד הברקוד, סוג קוד הברקוד וכו\'.',
                            'prefix'             => 'קידומת',
                            'print-product-name' => 'הדפס שם מוצר',
                            'title'              => 'ברקוד',
                            'width'              => 'רוחב',

                            'generate-with' => [
                                'title' => 'צור קוד ברקוד עם',

                                'options' => [
                                    'product-id' => 'מזהה מוצר',
                                    'sku'        => 'SKU מוצר',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'אפשר SKU עבור מוצר מותאם אישית',
                            'info'      => 'הגדרות מוצר מאפשרות תצורה עבור ה-SKU של המוצר.',
                            'title'     => 'מוצרים',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'הקצה מוצרים',
            'banks'            => 'בנקים',
            'barcode-products' => 'מוצרים עם קוד ברקוד',
            'create'           => 'יצירה',
            'delete'           => 'מחיקה',
            'edit'             => 'עריכה',
            'generate-barcode' => 'יצירת ברקוד',
            'orders'           => 'הזמנות',
            'outlets'          => 'חנויות',
            'pos'              => 'נקודת מכירה (POS)',
            'preview'          => 'תצוגה מקדימה',
            'print-barcode'    => 'הדפסת ברקוד',
            'receipts'         => 'קבלות',
            'requests'         => 'בקשות',
            'sales-report'     => 'דוח מכירות',
            'users'            => 'סוכנים',
            'view'             => 'תצוגה',
        ],

        'layouts' => [
            'banks'            => 'בנקים',
            'barcode-products' => 'מוצרים עם קוד QR',
            'orders'           => 'הזמנות',
            'pos'              => 'נקודת מכירה (POS)',
            'receipts'         => 'קבלות',
            'requests'         => 'בקשות',
            'sales-report'     => 'דו"ח מכירות',

            'users' => [
                'agents'   => 'סוכנים',
                'outlets'  => 'חנויות',
                'title'    => 'סוכנים',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'צור סוכנים',
                    'pos-front'  => 'חזית POS',
                    'title'      => 'סוכנים',

                    'datagrid' => [
                        'action'              => 'פעולה',
                        'delete'              => 'למחוק',
                        'edit'                => 'ערוך',
                        'email'               => 'דואר אלקטרוני',
                        'full-name'           => 'שם מלא',
                        'id'                  => 'מזהה',
                        'id-value'            => 'מזהה - :id',
                        'mass-delete-success' => 'הסוכנים שנבחרו נמחקו בהצלחה!',
                        'mass-update-success' => 'הסוכנים שנבחרו עודכנו בהצלחה!',
                        'outlet-name'         => 'שם סניף',
                        'profile-image'       => 'תמונת פרופיל',
                        'update-status'       => 'עדכון סטטוס',
                        'username'            => 'שם משתמש',

                        'status' => [
                            'title' => 'סטטוס',

                            'options' => [
                                'active'  => 'פעיל',
                                'disable' => 'ביטול',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'חזרה',
                    'confirm-password'  => 'אשר סיסמה',
                    'email'             => 'אימייל',
                    'first-name'        => 'שם פרטי',
                    'general'           => 'כללי',
                    'image'             => 'תמונה',
                    'last-name'         => 'שם משפחה',
                    'outlet'            => 'חנות',
                    'outlet-and-status' => 'חנות וסטטוס',
                    'password'          => 'סיסמה',
                    'save-btn'          => 'שמור סוכן',
                    'select-outlet'     => 'בחר חנות',
                    'status'            => 'סטטוס',
                    'title'             => 'הוסף סוכן',
                    'user-image'        => 'העלה תמונת סוכן',
                    'username'          => 'שם משתמש',
                ],

                'edit' => [
                    'back-btn'          => 'חזרה',
                    'confirm-password'  => 'אשר סיסמה',
                    'email'             => 'אימייל',
                    'first-name'        => 'שם פרטי',
                    'general'           => 'כללי',
                    'image'             => 'תמונה',
                    'last-name'         => 'שם משפחה',
                    'outlet'            => 'חנות',
                    'outlet-and-status' => 'חנות וסטטוס',
                    'password'          => 'סיסמה',
                    'save-btn'          => 'שמור סוכן',
                    'select-outlet'     => 'בחר חנות',
                    'status'            => 'סטטוס',
                    'title'             => 'ערוך סוכן',
                    'user-image'        => 'העלה תמונת סוכן',
                    'username'          => 'שם משתמש',
                ],

                'create-success' => 'הסוכן נוצר בהצלחה!',
                'delete-failed'  => 'מחיקת הסוכן נכשלה!',
                'delete-success' => 'הסוכן נמחק בהצלחה!',
                'update-success' => 'הסוכן עודכן בהצלחה!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'צור חנות',
                    'pos-front'  => 'חזית POS',
                    'title'      => 'חנויות',

                    'datagrid' => [
                        'action'              => 'פעולה',
                        'active'              => 'פעיל',
                        'assign'              => 'הקצה מוצר',
                        'delete'              => 'מחק',
                        'edit'                => 'ערוך',
                        'id'                  => 'מזהה',
                        'inactive'            => 'לא פעיל',
                        'inventory-source'    => 'מקור מלאי',
                        'mass-delete-success' => 'החנויות שנבחרו נמחקו בהצלחה!',
                        'mass-update-success' => 'החנויות שנבחרו עודכנו בהצלחה!',
                        'name'                => 'שם',
                        'receipt-title'       => 'כותרת קבלה',
                        'status'              => 'סטטוס',
                        'title'               => 'רשימת חנויות POS',
                        'update-status'       => 'עדכן סטטוס',
                    ],
                ],

                'create' => [
                    'address'                 => 'כתובת',
                    'back-btn'                => 'חזרה',
                    'btn-title'               => 'שמור חנות',
                    'city'                    => 'עיר',
                    'country'                 => 'מדינה',
                    'customer-care-number'    => 'מספר שירות לקוחות',
                    'email'                   => 'אימייל',
                    'general'                 => 'כללי',
                    'gst-number'              => 'מספר GST',
                    'inventory'               => 'מלאי',
                    'inventory-source'        => 'מקור מלאי',
                    'low-stock-qty'           => 'כמות מלאי נמוכה',
                    'name'                    => 'שם חנות',
                    'phone'                   => 'טלפון',
                    'postcode'                => 'מיקוד',
                    'receipt'                 => 'קבלה',
                    'select-country'          => 'בחר מדינה',
                    'select-inventory-source' => 'בחר מקור מלאי',
                    'select-receipt'          => 'בחר קבלה',
                    'state'                   => 'מדינה',
                    'status'                  => 'סטטוס',
                    'store-address'           => 'כתובת חנות',
                    'title'                   => 'הוסף חנות',
                    'website'                 => 'אתר אינטרנט',
                ],

                'edit' => [
                    'address'                 => 'כתובת',
                    'back-btn'                => 'חזרה',
                    'btn-title'               => 'שמור חנות',
                    'city'                    => 'עיר',
                    'country'                 => 'מדינה',
                    'customer-care-number'    => 'מספר שירות לקוחות',
                    'email'                   => 'אימייל',
                    'general'                 => 'כללי',
                    'gst-number'              => 'מספר GST',
                    'inventory'               => 'מלאי',
                    'inventory-source'        => 'מקור מלאי',
                    'low-stock-qty'           => 'כמות מלאי נמוכה',
                    'name'                    => 'שם חנות',
                    'phone'                   => 'טלפון',
                    'postcode'                => 'מיקוד',
                    'receipt'                 => 'קבלה',
                    'select-country'          => 'בחר מדינה',
                    'select-inventory-source' => 'בחר מקור מלאי',
                    'select-receipt'          => 'בחר קבלה',
                    'state'                   => 'מדינה',
                    'status'                  => 'סטטוס',
                    'store-address'           => 'כתובת חנות',
                    'title'                   => 'ערוך חנות',
                    'website'                 => 'אתר אינטרנט',
                ],

                'assign' => [
                    'back-btn' => 'חזרה',
                    'title'    => 'נהל מוצרים בחנות',

                    'datagrid' => [
                        'active'              => 'פעיל',
                        'assign'              => 'הקצה',
                        'disable'             => 'ביטול',
                        'id'                  => 'מזהה',
                        'id-value'            => 'מזהה - :id',
                        'image'               => 'תמונה',
                        'mass-assign-success' => 'ההקצאה למוצר עודכנה בהצלחה!',
                        'name'                => 'שם',
                        'out-of-stock'        => 'לא במלאי',
                        'pos-status'          => 'סטטוס POS',
                        'price'               => 'מחיר',
                        'product-image'       => 'תמונת מוצר',
                        'qty'                 => 'כמות',
                        'qty-value'           => ':qty זמינה',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'סטטוס',
                        'type'                => 'סוג',
                        'unassign'            => 'הסר הקצאה',
                        'update-assign'       => 'עדכן הקצאה',
                    ],
                ],

                'create-success' => 'החנות נוצרה בהצלחה!',
                'delete-failed'  => 'מחיקת החנות נכשלה!',
                'delete-success' => 'החנות נמחקה בהצלחה!',
                'update-success' => 'החנות עודכנה בהצלחה!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'מוצרים עם קוד QR',

                'datagrid' => [
                    'barcode'               => 'ברקוד',
                    'generate-barcode'      => 'צור ברקוד',
                    'print-barcode'         => 'הדפס ברקוד',
                    'id'                    => 'מזהה',
                    'id-value'              => 'מזהה - :id',
                    'image'                 => 'תמונה',
                    'mass-generate-success' => 'ברקוד למוצרים שנבחרו נוצר בהצלחה!',
                    'name'                  => 'שם',
                    'out-of-stock'          => 'לא במלאי',
                    'price'                 => 'מחיר',
                    'product-image'         => 'תמונת מוצר',
                    'qty'                   => 'כמות',
                    'qty-value'             => ':qty זמינה',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'סטטוס',

                        'options' => [
                            'active'  => 'פעיל',
                            'disable' => 'ביטול',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'חזרה',
                'btn-title' => 'הדפס',
                'qty'       => 'כמות',
                'title'     => 'הדפס ברקוד',
            ],

            'generate-failed'  => 'יצירת ברקוד נכשלה!',
            'generate-success' => 'ברקוד נוצר בהצלחה!',
        ],

        'orders' => [
            'index' => [
                'title' => 'הזמנות',

                'datagrid' => [
                    'customer-name' => 'שם לקוח',
                    'grand-total'   => 'סך הכל',
                    'order-date'    => 'תאריך הזמנה',
                    'order-id'      => 'מזהה הזמנה',
                    'order-ref-id'  => 'מזהה הפניה להזמנה',
                    'view'          => 'צפה',

                    'status' => [
                        'title' => 'סטטוס',

                        'options' => [
                            'canceled'        => 'מבוטל',
                            'closed'          => 'סגור',
                            'completed'       => 'הושלם',
                            'fraud'           => 'הונאה',
                            'pending'         => 'ממתין',
                            'pending-payment' => 'ממתין לתשלום',
                            'processing'      => 'בעיבוד',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'בקשות',

                'datagrid' => [
                    'id'                  => 'מזהה',
                    'product-image'       => 'תמונת מוצר',
                    'mass-update-error'   => 'עדכון הבקשה נכשל!',
                    'mass-update-success' => 'הבקשות שנבחרו עודכנו בהצלחה!',
                    'product-name'        => 'שם מוצר',
                    'outlet-name'         => 'שם חנות',
                    'qty-value'           => 'כמות - :qty',
                    'request-date'        => 'תאריך בקשה',
                    'requested-qty'       => 'כמות המבוקשת',
                    'update-status'       => 'עדכן סטטוס',
                    'user-name'           => 'שם משתמש',

                    'status' => [
                        'title' => 'סטטוס',

                        'options' => [
                            'complete' => 'מושלם',
                            'decline'  => 'סירב',
                            'pending'  => 'ממתין',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'חזרה',
                'btn-title' => 'שמור',
                'title'     => 'פרטי מוצר המבוקש #:id',

                'user-info' => [
                    'email'            => 'אימייל',
                    'name'             => 'שם',
                    'outlet-address'   => 'כתובת חנות',
                    'outlet-inventory' => 'מקור מלאי חנות',
                    'outlet-name'      => 'שם חנות',
                    'title'            => 'מידע על המשתמש',
                ],

                'request-info' => [
                    'comment'       => 'הערה',
                    'product-name'  => 'שם מוצר',
                    'qty-value'     => 'כמות - :qty',
                    'request-date'  => 'תאריך בקשה',
                    'requested-qty' => 'כמות מבוקשת',
                    'title'         => 'מידע על הבקשה',

                    'status' => [
                        'title' => 'סטטוס',

                        'options' => [
                            'complete' => 'מושלם',
                            'decline'  => 'סירב',
                            'pending'  => 'ממתין',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'עדכון הבקשה נכשל!',
            'update-success' => 'הבקשה עודכנה בהצלחה!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'צור בנק',
                'title'     => 'בנקים',

                'datagrid' => [
                    'active'              => 'פעיל',
                    'address'             => 'כתובת הבנק',
                    'agent-name'          => 'סוכן',
                    'delete'              => 'מחק',
                    'disable'             => 'השבתה',
                    'id'                  => 'מזהה',
                    'mass-delete-success' => 'הבנקים הנבחרים נמחקו בהצלחה!',
                    'name'                => 'שם הבנק',
                    'status'              => 'סטטוס',
                ],
            ],

            'create' => [
                'back-btn'  => 'חזרה',
                'btn-title' => 'שמור בנק',
                'title'     => 'צור בנק חדש',

                'general' => [
                    'address' => 'כתובת',
                    'email'   => 'אימייל',
                    'name'    => 'שם',
                    'phone'   => 'טלפון',
                    'title'   => 'כללי',
                ],

                'agent-and-status' => [
                    'agent'        => 'קבע סוכן POS',
                    'bank-status'  => 'סטטוס בנק',
                    'select-agent' => 'בחר סוכן',
                    'title'        => 'סוכן POS & סטטוס בנק',
                ],
            ],

            'edit' => [
                'back-btn'  => 'חזרה',
                'btn-title' => 'שמור בנק',
                'title'     => 'ערוך בנק',

                'general' => [
                    'address' => 'כתובת',
                    'email'   => 'אימייל',
                    'name'    => 'שם',
                    'phone'   => 'טלפון',
                    'title'   => 'כללי',
                ],

                'agent-and-status' => [
                    'agent'        => 'קבע סוכן POS',
                    'bank-status'  => 'סטטוס בנק',
                    'select-agent' => 'בחר סוכן',
                    'title'        => 'סוכן POS & סטטוס בנק',
                ],
            ],

            'create-success' => 'הבנק נוצר בהצלחה!',
            'delete-failed'  => 'מחיקת הבנק נכשלה!',
            'delete-success' => 'הבנק נמחק בהצלחה!',
            'update-success' => 'הבנק עודכן בהצלחה!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'דו"ח מכירות',

                'datagrid' => [
                    'bank-name'      => 'שם בנק',
                    'grand-total'    => 'סך הכל',
                    'order-date'     => 'תאריך הזמנה',
                    'order-id'       => 'מזהה הזמנה',
                    'order-id-value' => 'מזהה - :id',
                    'order-note'     => 'הערת הזמנה',
                    'outlet-name'    => 'שם חנות',
                    'payment-method' => 'שיטת תשלום',
                    'view'           => 'צפה',

                    'status' => [
                        'title' => 'סטטוס',

                        'options' => [
                            'canceled'        => 'מבוטל',
                            'closed'          => 'סגור',
                            'completed'       => 'הושלם',
                            'fraud'           => 'הונאה',
                            'pending'         => 'ממתין',
                            'pending-payment' => 'ממתין לתשלום',
                            'processing'      => 'בעיבוד',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'צור קבלה',
                'title'      => 'קבלות',

                'datagrid' => [
                    'delete'              => 'מחק',
                    'edit'                => 'ערוך',
                    'id'                  => 'מזהה',
                    'mass-delete-success' => 'הקבלות שנבחרו נמחקו בהצלחה!',
                    'preview'             => 'תצוגה מקדימה',
                    'title'               => 'כותרת',

                    'status' => [
                        'title' => 'סטטוס',

                        'options' => [
                            'active'   => 'פעיל',
                            'inactive' => 'לא פעיל',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'חזרה',
                'btn-title' => 'שמור קבלה',
                'title'     => 'צור קבלה חדשה',

                'general' => [
                    'cashier-name-label'      => 'תווית שם קופאי',
                    'change-amount-label'     => 'תווית סכום שינוי',
                    'credit-amount-label'     => 'תווית סכום אשראי',
                    'discount-amt-label'      => 'תווית סכום הנחה',
                    'display-cashier-name'    => 'הצג שם קופאי',
                    'display-change-amount'   => 'הצג סכום שינוי',
                    'display-credit-amount'   => 'הצג סכום אשראי',
                    'display-customer-name'   => 'הצג שם לקוח',
                    'display-date'            => 'הצג תאריך',
                    'display-discount-amt'    => 'הצג סכום הנחה',
                    'display-order-id'        => 'הצג מזהה הזמנה',
                    'display-outlet-address'  => 'הצג כתובת חנות',
                    'display-outlet-name'     => 'הצג שם חנות',
                    'display-sub-total'       => 'הצג סך ביניים',
                    'display-tax'             => 'הצג מס',
                    'grand-total-label'       => 'תווית סך הכל',
                    'order-id-label'          => 'תווית מזהה הזמנה',
                    'receipt-title'           => 'כותרת קבלה',
                    'show-order-barcode'      => 'הצג ברקוד הזמנה',
                    'show-print-confirmation' => 'הצג אישור הדפסה',
                    'status'                  => 'סטטוס',
                    'sub-total-label'         => 'תווית סך ביניים',
                    'tax-label'               => 'תווית מס',
                    'title'                   => 'כללי',
                ],

                'logo' => [
                    'display-logo' => 'הצג לוגו',
                    'logo-alt'     => 'טקסט חלופי ללוגו',
                    'logo-height'  => 'גובה לוגו (בפיקסלים)',
                    'logo-width'   => 'רוחב לוגו (בפיקסלים)',
                    'title'        => 'לוגו',
                    'upload-logo'  => 'העלה לוגו',
                ],

                'header' => [
                    'header-content' => 'תוכן כותרת',
                    'title'          => 'כותרת',
                ],

                'footer' => [
                    'footer-content' => 'תוכן כותרת תחתונה',
                    'title'          => 'כותרת תחתונה',
                ],
            ],

            'edit' => [
                'back-btn'  => 'חזרה',
                'btn-title' => 'שמור קבלה',
                'title'     => 'ערוך קבלה',

                'general' => [
                    'cashier-name-label'      => 'תווית שם קופאי',
                    'change-amount-label'     => 'תווית סכום שינוי',
                    'credit-amount-label'     => 'תווית סכום אשראי',
                    'discount-amt-label'      => 'תווית סכום הנחה',
                    'display-cashier-name'    => 'הצג שם קופאי',
                    'display-change-amount'   => 'הצג סכום שינוי',
                    'display-credit-amount'   => 'הצג סכום אשראי',
                    'display-customer-name'   => 'הצג שם לקוח',
                    'display-date'            => 'הצג תאריך',
                    'display-discount-amt'    => 'הצג סכום הנחה',
                    'display-order-id'        => 'הצג מזהה הזמנה',
                    'display-outlet-address'  => 'הצג כתובת חנות',
                    'display-outlet-name'     => 'הצג שם חנות',
                    'display-sub-total'       => 'הצג סך ביניים',
                    'display-tax'             => 'הצג מס',
                    'grand-total-label'       => 'תווית סך הכל',
                    'order-id-label'          => 'תווית מזהה הזמנה',
                    'receipt-title'           => 'כותרת קבלה',
                    'show-order-barcode'      => 'הצג ברקוד הזמנה',
                    'show-print-confirmation' => 'הצג אישור הדפסה',
                    'status'                  => 'סטטוס',
                    'sub-total-label'         => 'תווית סך ביניים',
                    'tax-label'               => 'תווית מס',
                    'title'                   => 'כללי',
                ],

                'logo' => [
                    'display-logo' => 'הצג לוגו',
                    'logo-alt'     => 'טקסט חלופי ללוגו',
                    'logo-height'  => 'גובה לוגו (בפיקסלים)',
                    'logo-width'   => 'רוחב לוגו (בפיקסלים)',
                    'title'        => 'לוגו',
                    'upload-logo'  => 'העלה לוגו',
                ],

                'header' => [
                    'header-content' => 'תוכן כותרת',
                    'title'          => 'כותרת',
                ],

                'footer' => [
                    'footer-content' => 'תוכן כותרת תחתונה',
                    'title'          => 'כותרת תחתונה',
                ],
            ],

            'preview' => [
                'amount'         => 'סכום',
                'cashier'        => 'קופאי',
                'change-amount'  => 'שינוי',
                'customer'       => 'לקוח',
                'customer-email' => 'אימייל לקוח',
                'customer-name'  => 'שם לקוח',
                'customer-phone' => 'טלפון לקוח',
                'date'           => 'תאריך',
                'discount'       => 'הנחה',
                'email'          => 'אימייל',
                'grand-total'    => 'סך הכל',
                'order-id'       => 'מזהה הזמנה',
                'phone'          => 'טלפון',
                'price'          => 'מחיר',
                'product'        => 'מוצר',
                'qty'            => 'כמות',
                'sub-total'      => 'סך ביניים',
                'tax'            => 'מס',
                'title'          => 'תצוגה מקדימה של קבלה',
                'total-qty'      => 'סה"כ כמות',
            ],

            'create-success' => 'הקבלה נוצרה בהצלחה!',
            'delete-failed'  => 'מחיקת הקבלה נכשלה!',
            'delete-success' => 'הקבלה נמחקה בהצלחה!',
            'update-success' => 'הקבלה עודכנה בהצלחה!',
        ],
    ],

    'emails' => [
        'dear'     => 'שלום :name',
        'greeting' => 'ברכות!',

        'registration' => [
            'message' => 'מזל טוב! החשבון שלך נוצר בהצלחה. אנא התחבר לחשבון שלך והתחל להשתמש במערכת ה-POS.',
            'subject' => 'מייל רישום משתמש POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'התחברות',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
