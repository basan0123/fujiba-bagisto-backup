<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'بيانات الاعتماد غير صحيحة.',
                'not-activated'       => 'حسابك غير مفعل.',
                'verify-first'        => 'يرجى التحقق من بريدك الإلكتروني أولاً.',
                'success'             => 'لقد قمت بتسجيل الدخول بنجاح.',
            ],

            'logout' => [
                'no-login-agent' => 'لا يوجد وكيل مسجل الدخول.',
                'success'        => 'لقد قمت بتسجيل الخروج بنجاح.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'كلمة المرور التي أدخلتها غير صحيحة.',
                    'success'          => 'تم تحديث حسابك بنجاح.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'تم إنشاء العميل بنجاح!',
            'update-success' => 'تم تحديث العميل بنجاح!',
            'delete-success' => 'تم حذف العميل بنجاح!',
            'delete-failed'  => 'فشل حذف العميل!',
            'pending-orders' => 'العميل لديه طلبات معلقة!',
        ],

        'cart' => [
            'already-applied'     => 'تم تطبيق القسيمة بالفعل!',
            'coupon-applied'      => 'تم تطبيق القسيمة بنجاح!',
            'coupon-removed'      => 'تمت إزالة القسيمة بنجاح!',
            'create-success'      => 'تم إنشاء السلة بنجاح!',
            'invalid-coupon'      => 'رمز القسيمة غير صالح!',
            'item-add-success'    => 'تمت إضافة المنتج إلى السلة بنجاح!',
            'item-remove-success' => 'تمت إزالة المنتج من السلة بنجاح!',
            'item-update-success' => 'تم تحديث المنتج بنجاح!',
            'not-found'           => 'لم يتم العثور على السلة!',
        ],

        'payment' => [
            'title' => 'دفع نقاط البيع',

            'options' => [
                'cash' => [
                    'title'       => 'دفع نقدي نقاط البيع',
                    'description' => 'هذا هو دفع نقدي نقاط البيع.',
                ],

                'card' => [
                    'title'       => 'دفع ببطاقة نقاط البيع',
                    'description' => 'هذا هو دفع ببطاقة نقاط البيع.',
                ],

                'split' => [
                    'title'       => 'دفع مقسم نقاط البيع',
                    'description' => 'هذا هو دفع مقسم نقاط البيع.',
                ],
            ],

            'no-items' => 'لا توجد عناصر في السلة للمضي قدمًا في الدفع.',
            'success'  => 'تم إتمام الدفع بنجاح!',
        ],

        'shipping' => [
            'title'       => 'شحن نقاط البيع',
            'description' => 'هذه شحنات مجانية لنقاط البيع.',
        ],

        'order' => [
            'sync-success' => 'تمت مزامنة الطلب بنجاح!',
        ],

        'return' => [
            'create-success'              => 'تم إنشاء المبلغ المسترد بنجاح',
            'creation-error'              => 'غير مسموح بإنشاء المبلغ المسترد.',
            'invalid-qty'                 => 'وجدنا كمية غير صالحة لفوترة العناصر.',
            'invalid-refund-amount-error' => 'يجب أن يكون مبلغ الاسترداد أكبر من صفر.',
            'refund-limit-error'          => 'لا يمكن تنفيذ مبلغ الاسترداد :amount.',
        ],

        'drawer' => [
            'create-success' => 'تم فتح الدرج بنجاح!',
            'not-opened'     => 'الدرج لم يفتح.',
            'close-success'  => 'تم إغلاق الدرج بنجاح!',
        ],

        'products' => [
            'request-success' => 'تم تقديم طلب المنتج بنجاح.',
            'create-success'  => 'تم إنشاء المنتج بنجاح!',
        ],

        'reports' => [
            'orders'                  => 'الطلبات',
            'average-order-value'     => 'متوسط قيمة الطلب',
            'average-items-per-order' => 'متوسط عدد العناصر لكل طلب',
            'discounted-offers'       => 'عروض مخفضة',
            'cash-payments'           => 'المدفوعات النقدية',
            'other-payments'          => 'مدفوعات أخرى',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'ملاحظة الطلب',
                    'note-not-available' => 'الملاحظة غير متوفرة!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'إضافة نقاط البيع الخاصة بـ Bagisto.',
                    'title' => 'نقاط البيع',

                    'settings' => [
                        'info'  => 'تمكين نقاط البيع، تعيين الإعدادات العامة، إعدادات المنتجات والفواتير.',
                        'title' => 'الإعدادات',

                        'general' => [
                            'footer-content'       => 'محتوى التذييل',
                            'footer-note'          => 'ملاحظة التذييل',
                            'frontend-url'         => 'رابط الواجهة الأمامية',
                            'heading-on-login'     => 'العنوان عند تسجيل الدخول',
                            'info'                 => 'تسمح الإعدادات العامة بتكوين صفحة المستخدم لنقاط البيع، من خلال إضافة الشعار والعناوين ومحتويات التذييل والملاحظة، إلخ.',
                            'pos-logo'             => 'شعار نقاط البيع',
                            'status'               => 'الحالة',
                            'sub-heading-on-login' => 'العنوان الفرعي عند تسجيل الدخول',
                            'title'                => 'عام',
                        ],

                        'barcode' => [
                            'height'             => 'الارتفاع',
                            'hide-barcode'       => 'إخفاء الرمز الشريطي',
                            'info'               => 'تسمح إعدادات الرمز الشريطي بتكوين توليد الرموز الشريطية، ارتفاع الرمز الشريطي، عرض الرمز الشريطي، نوع الرمز الشريطي، إلخ.',
                            'prefix'             => 'البادئة',
                            'print-product-name' => 'طباعة اسم المنتج',
                            'title'              => 'الرمز الشريطي',
                            'width'              => 'العرض',

                            'generate-with' => [
                                'title' => 'توليد الرمز الشريطي باستخدام',

                                'options' => [
                                    'product-id' => 'معرف المنتج',
                                    'sku'        => 'رمز المنتج',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'السماح برمز المنتج للمنتجات المخصصة',
                            'info'      => 'إعدادات المنتج تسمح بتكوينات رمز المنتج (SKU).',
                            'title'     => 'المنتجات',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'تعيين المنتجات',
            'banks'            => 'البنوك',
            'barcode-products' => 'منتجات الباركود',
            'create'           => 'إنشاء',
            'delete'           => 'حذف',
            'edit'             => 'تعديل',
            'generate-barcode' => 'إنشاء باركود',
            'orders'           => 'الطلبات',
            'outlets'          => 'الفروع',
            'pos'              => 'نقطة البيع',
            'preview'          => 'معاينة',
            'print-barcode'    => 'طباعة باركود',
            'receipts'         => 'الإيصالات',
            'requests'         => 'الطلبات',
            'sales-report'     => 'تقرير المبيعات',
            'users'            => 'الوكلاء',
            'view'             => 'عرض',
        ],

        'layouts' => [
            'banks'            => 'البنوك',
            'barcode-products' => 'منتجات الباركود',
            'orders'           => 'الطلبات',
            'pos'              => 'نقطة البيع',
            'receipts'         => 'الإيصالات',
            'requests'         => 'الطلبات',
            'sales-report'     => 'تقرير المبيعات',

            'users' => [
                'agents'   => 'الوكلاء',
                'outlets'  => 'المنافذ',
                'title'    => 'الوكلاء',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'إنشاء وكلاء',
                    'pos-front'  => 'واجهة نقاط البيع',
                    'title'      => 'الوكلاء',

                    'datagrid' => [
                        'action'              => 'عمل',
                        'delete'              => 'حذف',
                        'edit'                => 'تعديل',
                        'email'               => 'البريد الإلكتروني',
                        'full-name'           => 'الاسم الكامل',
                        'id'                  => 'معرف',
                        'id-value'            => 'معرف - :id',
                        'mass-delete-success' => 'تم حذف الوكلاء المحددين بنجاح!',
                        'mass-update-success' => 'تم تحديث الوكلاء المحددين بنجاح!',
                        'outlet-name'         => 'اسم المنفذ',
                        'profile-image'       => 'صورة الملف الشخصي',
                        'update-status'       => 'تحديث الحالة',
                        'username'            => 'اسم المستخدم',

                        'status' => [
                            'title' => 'الحالة',

                            'options' => [
                                'active'  => 'نشط',
                                'disable' => 'معطل',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'رجوع',
                    'confirm-password'  => 'تأكيد كلمة المرور',
                    'email'             => 'البريد الإلكتروني',
                    'first-name'        => 'الاسم الأول',
                    'general'           => 'عام',
                    'image'             => 'الصورة',
                    'last-name'         => 'الاسم الأخير',
                    'outlet'            => 'الفرع',
                    'outlet-and-status' => 'الفرع والحالة',
                    'password'          => 'كلمة المرور',
                    'save-btn'          => 'حفظ الوكيل',
                    'select-outlet'     => 'اختر الفرع',
                    'status'            => 'الحالة',
                    'title'             => 'إضافة وكيل',
                    'user-image'        => 'تحميل صورة الوكيل',
                    'username'          => 'اسم المستخدم',
                ],

                'edit' => [
                    'back-btn'          => 'رجوع',
                    'confirm-password'  => 'تأكيد كلمة المرور',
                    'email'             => 'البريد الإلكتروني',
                    'first-name'        => 'الاسم الأول',
                    'general'           => 'عام',
                    'image'             => 'الصورة',
                    'last-name'         => 'الاسم الأخير',
                    'outlet'            => 'الفرع',
                    'outlet-and-status' => 'الفرع والحالة',
                    'password'          => 'كلمة المرور',
                    'save-btn'          => 'حفظ الوكيل',
                    'select-outlet'     => 'اختر الفرع',
                    'status'            => 'الحالة',
                    'title'             => 'تعديل الوكيل',
                    'user-image'        => 'تحميل صورة الوكيل',
                    'username'          => 'اسم المستخدم',
                ],

                'create-success' => 'تم إنشاء الوكيل بنجاح!',
                'delete-failed'  => 'فشل حذف الوكيل!',
                'delete-success' => 'تم حذف الوكيل بنجاح!',
                'update-success' => 'تم تحديث الوكيل بنجاح!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'إنشاء فرع',
                    'pos-front'  => 'واجهة نقاط البيع',
                    'title'      => 'الفروع',

                    'datagrid' => [
                        'action'              => 'الإجراء',
                        'active'              => 'نشط',
                        'assign'              => 'تعيين المنتج',
                        'delete'              => 'حذف',
                        'edit'                => 'تعديل',
                        'id'                  => 'الرقم',
                        'inactive'            => 'غير نشط',
                        'inventory-source'    => 'مصدر المخزون',
                        'mass-delete-success' => 'تم حذف المتاجر المحددة بنجاح!',
                        'mass-update-success' => 'تم تحديث المتاجر المحددة بنجاح!',
                        'name'                => 'الاسم',
                        'receipt-title'       => 'عنوان الفاتورة',
                        'status'              => 'الحالة',
                        'title'               => 'قائمة متاجر نقاط البيع',
                        'update-status'       => 'تحديث الحالة',
                    ],
                ],

                'create' => [
                    'address'                 => 'العنوان',
                    'back-btn'                => 'رجوع',
                    'btn-title'               => 'حفظ الفرع',
                    'city'                    => 'المدينة',
                    'country'                 => 'الدولة',
                    'customer-care-number'    => 'رقم خدمة العملاء',
                    'email'                   => 'البريد الإلكتروني',
                    'general'                 => 'عام',
                    'gst-number'              => 'رقم ضريبة السلع والخدمات',
                    'inventory'               => 'المخزون',
                    'inventory-source'        => 'مصدر المخزون',
                    'low-stock-qty'           => 'كمية المخزون المنخفض',
                    'name'                    => 'اسم الفرع',
                    'phone'                   => 'الهاتف',
                    'postcode'                => 'الرمز البريدي',
                    'receipt'                 => 'الإيصال',
                    'select-country'          => 'اختر الدولة',
                    'select-inventory-source' => 'اختر مصدر المخزون',
                    'select-receipt'          => 'اختر الإيصال',
                    'state'                   => 'الولاية',
                    'status'                  => 'الحالة',
                    'store-address'           => 'عنوان الفرع',
                    'title'                   => 'إضافة فرع',
                    'website'                 => 'الموقع الإلكتروني',
                ],

                'edit' => [
                    'address'                 => 'العنوان',
                    'back-btn'                => 'رجوع',
                    'btn-title'               => 'حفظ الفرع',
                    'city'                    => 'المدينة',
                    'country'                 => 'الدولة',
                    'customer-care-number'    => 'رقم خدمة العملاء',
                    'email'                   => 'البريد الإلكتروني',
                    'general'                 => 'عام',
                    'gst-number'              => 'رقم ضريبة السلع والخدمات',
                    'inventory'               => 'المخزون',
                    'inventory-source'        => 'مصدر المخزون',
                    'low-stock-qty'           => 'كمية المخزون المنخفض',
                    'name'                    => 'اسم الفرع',
                    'phone'                   => 'الهاتف',
                    'postcode'                => 'الرمز البريدي',
                    'receipt'                 => 'الإيصال',
                    'select-country'          => 'اختر الدولة',
                    'select-inventory-source' => 'اختر مصدر المخزون',
                    'select-receipt'          => 'اختر الإيصال',
                    'state'                   => 'الولاية',
                    'status'                  => 'الحالة',
                    'store-address'           => 'عنوان الفرع',
                    'title'                   => 'تعديل الفرع',
                    'website'                 => 'الموقع الإلكتروني',
                ],

                'assign' => [
                    'back-btn' => 'رجوع',
                    'title'    => 'إدارة منتجات الفرع',

                    'datagrid' => [
                        'active'              => 'نشط',
                        'assign'              => 'تعيين',
                        'disable'             => 'تعطيل',
                        'id'                  => 'الرقم',
                        'id-value'            => 'الرقم - :id',
                        'image'               => 'الصورة',
                        'mass-assign-success' => 'تم تحديث تعيين المنتج بنجاح!',
                        'name'                => 'الاسم',
                        'out-of-stock'        => 'نفد من المخزون',
                        'pos-status'          => 'حالة نقاط البيع',
                        'price'               => 'السعر',
                        'product-image'       => 'صورة المنتج',
                        'qty'                 => 'الكمية',
                        'qty-value'           => ':qty متاحة',
                        'sku'                 => 'رمز المنتج',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'الحالة',
                        'type'                => 'النوع',
                        'unassign'            => 'إلغاء التعيين',
                        'update-assign'       => 'تحديث التعيين',
                    ],
                ],

                'create-success' => 'تم إنشاء الفرع بنجاح!',
                'delete-failed'  => 'فشل حذف الفرع!',
                'delete-success' => 'تم حذف الفرع بنجاح!',
                'update-success' => 'تم تحديث الفرع بنجاح!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'منتجات الباركود',

                'datagrid' => [
                    'barcode'               => 'الباركود',
                    'generate-barcode'      => 'إنشاء باركود',
                    'print-barcode'         => 'طباعة باركود',
                    'id'                    => 'الرقم',
                    'id-value'              => 'الرقم - :id',
                    'image'                 => 'الصورة',
                    'mass-generate-success' => 'تم إنشاء باركود المنتجات المحددة بنجاح!',
                    'name'                  => 'الاسم',
                    'out-of-stock'          => 'نفد من المخزون',
                    'price'                 => 'السعر',
                    'product-image'         => 'صورة المنتج',
                    'qty'                   => 'الكمية',
                    'qty-value'             => ':qty متاحة',
                    'sku'                   => 'رمز المنتج',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'الحالة',

                        'options' => [
                            'active'  => 'نشط',
                            'disable' => 'معطل',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'رجوع',
                'btn-title' => 'طباعة',
                'qty'       => 'الكمية',
                'title'     => 'طباعة باركود',
            ],

            'generate-failed'  => 'فشل إنشاء الباركود!',
            'generate-success' => 'تم إنشاء الباركود بنجاح!',
        ],

        'orders' => [
            'index' => [
                'title' => 'الطلبات',

                'datagrid' => [
                    'customer-name' => 'اسم العميل',
                    'grand-total'   => 'الإجمالي',
                    'order-date'    => 'تاريخ الطلب',
                    'order-id'      => 'رقم الطلب',
                    'order-ref-id'  => 'رقم مرجع الطلب',
                    'view'          => 'عرض',

                    'status' => [
                        'title' => 'الحالة',

                        'options' => [
                            'canceled'        => 'ملغى',
                            'closed'          => 'مغلق',
                            'completed'       => 'مكتمل',
                            'fraud'           => 'احتيال',
                            'pending'         => 'قيد الانتظار',
                            'pending-payment' => 'قيد الانتظار للدفع',
                            'processing'      => 'قيد المعالجة',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'الطلبات',

                'datagrid' => [
                    'id'                  => 'الرقم',
                    'product-image'       => 'صورة المنتج',
                    'mass-update-error'   => 'فشل تحديث الطلب!',
                    'mass-update-success' => 'تم تحديث الطلبات المحددة بنجاح!',
                    'product-name'        => 'اسم المنتج',
                    'outlet-name'         => 'اسم الفرع',
                    'qty-value'           => 'الكمية - :qty',
                    'request-date'        => 'تاريخ الطلب',
                    'requested-qty'       => 'الكمية المطلوبة',
                    'update-status'       => 'تحديث الحالة',
                    'user-name'           => 'اسم المستخدم',

                    'status' => [
                        'title' => 'الحالة',

                        'options' => [
                            'complete' => 'مكتمل',
                            'decline'  => 'رفض',
                            'pending'  => 'قيد الانتظار',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'رجوع',
                'btn-title' => 'حفظ',
                'title'     => 'تفاصيل المنتج المطلوب #:id',

                'user-info' => [
                    'email'            => 'البريد الإلكتروني',
                    'name'             => 'الاسم',
                    'outlet-address'   => 'عنوان الفرع',
                    'outlet-inventory' => 'مصدر المخزون للفرع',
                    'outlet-name'      => 'اسم الفرع',
                    'title'            => 'معلومات المستخدم',
                ],

                'request-info' => [
                    'comment'       => 'تعليق',
                    'product-name'  => 'اسم المنتج',
                    'qty-value'     => 'الكمية - :qty',
                    'request-date'  => 'تاريخ الطلب',
                    'requested-qty' => 'الكمية المطلوبة',
                    'title'         => 'معلومات الطلب',

                    'status' => [
                        'title' => 'الحالة',

                        'options' => [
                            'complete' => 'مكتمل',
                            'decline'  => 'رفض',
                            'pending'  => 'قيد الانتظار',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'فشل تحديث الطلب!',
            'update-success' => 'تم تحديث الطلب بنجاح!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'إنشاء بنك',
                'title'     => 'البنوك',

                'datagrid' => [
                    'active'              => 'نشيط',
                    'address'             => 'عنوان البنك',
                    'agent-name'          => 'وكيل',
                    'delete'              => 'حذف',
                    'disable'             => 'تعطيل',
                    'id'                  => 'الرقم التعريفي',
                    'mass-delete-success' => 'تم حذف البنوك المحددة بنجاح!',
                    'name'                => 'اسم البنك',
                    'status'              => 'الحالة',
                ],
            ],

            'create' => [
                'back-btn'  => 'رجوع',
                'btn-title' => 'حفظ البنك',
                'title'     => 'إنشاء بنك جديد',

                'general' => [
                    'address' => 'العنوان',
                    'email'   => 'البريد الإلكتروني',
                    'name'    => 'الاسم',
                    'phone'   => 'الهاتف',
                    'title'   => 'عام',
                ],

                'agent-and-status' => [
                    'agent'        => 'تعيين وكيل POS',
                    'bank-status'  => 'حالة البنك',
                    'select-agent' => 'اختر وكيل',
                    'title'        => 'وكيل POS وحالة البنك',
                ],
            ],

            'edit' => [
                'back-btn'  => 'رجوع',
                'btn-title' => 'حفظ البنك',
                'title'     => 'تعديل البنك',

                'general' => [
                    'address' => 'العنوان',
                    'email'   => 'البريد الإلكتروني',
                    'name'    => 'الاسم',
                    'phone'   => 'الهاتف',
                    'title'   => 'عام',
                ],

                'agent-and-status' => [
                    'agent'        => 'تعيين وكيل POS',
                    'bank-status'  => 'حالة البنك',
                    'select-agent' => 'اختر وكيل',
                    'title'        => 'وكيل POS وحالة البنك',
                ],
            ],

            'create-success' => 'تم إنشاء البنك بنجاح!',
            'delete-failed'  => 'فشل حذف البنك!',
            'delete-success' => 'تم حذف البنك بنجاح!',
            'update-success' => 'تم تحديث البنك بنجاح!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'تقرير المبيعات',

                'datagrid' => [
                    'bank-name'      => 'اسم البنك',
                    'grand-total'    => 'الإجمالي',
                    'order-date'     => 'تاريخ الطلب',
                    'order-id'       => 'رقم الطلب',
                    'order-id-value' => 'الرقم - :id',
                    'order-note'     => 'ملاحظة الطلب',
                    'outlet-name'    => 'اسم الفرع',
                    'payment-method' => 'طريقة الدفع',
                    'view'           => 'عرض',

                    'status' => [
                        'title' => 'الحالة',

                        'options' => [
                            'canceled'        => 'ملغى',
                            'closed'          => 'مغلق',
                            'completed'       => 'مكتمل',
                            'fraud'           => 'احتيال',
                            'pending'         => 'قيد الانتظار',
                            'pending-payment' => 'قيد الانتظار للدفع',
                            'processing'      => 'قيد المعالجة',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'إنشاء إيصال',
                'title'      => 'الإيصالات',

                'datagrid' => [
                    'delete'              => 'حذف',
                    'edit'                => 'تعديل',
                    'id'                  => 'الرقم',
                    'mass-delete-success' => 'تم حذف الإيصالات المحددة بنجاح!',
                    'preview'             => 'معاينة',
                    'title'               => 'العنوان',

                    'status' => [
                        'title' => 'الحالة',

                        'options' => [
                            'active'   => 'نشط',
                            'inactive' => 'غير نشط',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'رجوع',
                'btn-title' => 'حفظ الإيصال',
                'title'     => 'إنشاء إيصال جديد',

                'general' => [
                    'cashier-name-label'      => 'تسمية اسم المحاسب',
                    'change-amount-label'     => 'تسمية مبلغ التغيير',
                    'credit-amount-label'     => 'تسمية مبلغ الائتمان',
                    'discount-amt-label'      => 'تسمية مبلغ الخصم',
                    'display-cashier-name'    => 'عرض اسم المحاسب',
                    'display-change-amount'   => 'عرض مبلغ التغيير',
                    'display-credit-amount'   => 'عرض مبلغ الائتمان',
                    'display-customer-name'   => 'عرض اسم العميل',
                    'display-date'            => 'عرض التاريخ',
                    'display-discount-amt'    => 'عرض مبلغ الخصم',
                    'display-order-id'        => 'عرض رقم الطلب',
                    'display-outlet-address'  => 'عرض عنوان الفرع',
                    'display-outlet-name'     => 'عرض اسم الفرع',
                    'display-sub-total'       => 'عرض المجموع الفرعي',
                    'display-tax'             => 'عرض الضريبة',
                    'grand-total-label'       => 'تسمية الإجمالي',
                    'order-id-label'          => 'تسمية رقم الطلب',
                    'receipt-title'           => 'عنوان الإيصال',
                    'show-order-barcode'      => 'إظهار رمز الباركود الخاص بالطلب',
                    'show-print-confirmation' => 'إظهار تأكيد الطباعة',
                    'status'                  => 'الحالة',
                    'sub-total-label'         => 'تسمية المجموع الفرعي',
                    'tax-label'               => 'تسمية الضريبة',
                    'title'                   => 'عام',
                ],

                'logo' => [
                    'display-logo' => 'عرض الشعار',
                    'logo-alt'     => 'بديل الشعار',
                    'logo-height'  => 'ارتفاع الشعار (بـ بكسل)',
                    'logo-width'   => 'عرض الشعار (بـ بكسل)',
                    'title'        => 'الشعار',
                    'upload-logo'  => 'تحميل الشعار',
                ],

                'header' => [
                    'header-content' => 'محتوى الرأس',
                    'title'          => 'الرأس',
                ],

                'footer' => [
                    'footer-content' => 'محتوى التذييل',
                    'title'          => 'التذييل',
                ],
            ],

            'edit' => [
                'back-btn'  => 'رجوع',
                'btn-title' => 'حفظ الإيصال',
                'title'     => 'تعديل الإيصال',

                'general' => [
                    'cashier-name-label'      => 'تسمية اسم المحاسب',
                    'change-amount-label'     => 'تسمية مبلغ التغيير',
                    'credit-amount-label'     => 'تسمية مبلغ الائتمان',
                    'discount-amt-label'      => 'تسمية مبلغ الخصم',
                    'display-cashier-name'    => 'عرض اسم المحاسب',
                    'display-change-amount'   => 'عرض مبلغ التغيير',
                    'display-credit-amount'   => 'عرض مبلغ الائتمان',
                    'display-customer-name'   => 'عرض اسم العميل',
                    'display-date'            => 'عرض التاريخ',
                    'display-discount-amt'    => 'عرض مبلغ الخصم',
                    'display-order-id'        => 'عرض رقم الطلب',
                    'display-outlet-address'  => 'عرض عنوان الفرع',
                    'display-outlet-name'     => 'عرض اسم الفرع',
                    'display-sub-total'       => 'عرض المجموع الفرعي',
                    'display-tax'             => 'عرض الضريبة',
                    'grand-total-label'       => 'تسمية الإجمالي',
                    'order-id-label'          => 'تسمية رقم الطلب',
                    'receipt-title'           => 'عنوان الإيصال',
                    'show-order-barcode'      => 'إظهار رمز الباركود الخاص بالطلب',
                    'show-print-confirmation' => 'إظهار تأكيد الطباعة',
                    'status'                  => 'الحالة',
                    'sub-total-label'         => 'تسمية المجموع الفرعي',
                    'tax-label'               => 'تسمية الضريبة',
                    'title'                   => 'عام',
                ],

                'logo' => [
                    'display-logo' => 'عرض الشعار',
                    'logo-alt'     => 'بديل الشعار',
                    'logo-height'  => 'ارتفاع الشعار (بـ بكسل)',
                    'logo-width'   => 'عرض الشعار (بـ بكسل)',
                    'title'        => 'الشعار',
                    'upload-logo'  => 'تحميل الشعار',
                ],

                'header' => [
                    'header-content' => 'محتوى الرأس',
                    'title'          => 'الرأس',
                ],

                'footer' => [
                    'footer-content' => 'محتوى التذييل',
                    'title'          => 'التذييل',
                ],
            ],

            'preview' => [
                'amount'         => 'المبلغ',
                'cashier'        => 'المحاسب',
                'change-amount'  => 'التغيير',
                'customer'       => 'العميل',
                'customer-email' => 'بريد العميل الإلكتروني',
                'customer-name'  => 'اسم العميل',
                'customer-phone' => 'هاتف العميل',
                'date'           => 'التاريخ',
                'discount'       => 'الخصم',
                'email'          => 'البريد الإلكتروني',
                'grand-total'    => 'الإجمالي',
                'order-id'       => 'رقم الطلب',
                'phone'          => 'الهاتف',
                'price'          => 'السعر',
                'product'        => 'المنتج',
                'qty'            => 'الكمية',
                'sub-total'      => 'المجموع الفرعي',
                'tax'            => 'الضريبة',
                'title'          => 'معاينة الإيصال',
                'total-qty'      => 'إجمالي الكمية',
            ],

            'create-success' => 'تم إنشاء الإيصال بنجاح!',
            'delete-failed'  => 'فشل حذف الإيصال!',
            'delete-success' => 'تم حذف الإيصال بنجاح!',
            'update-success' => 'تم تحديث الإيصال بنجاح!',
        ],
    ],

    'emails' => [
        'dear'     => 'عزيزي :name',
        'greeting' => 'تحية طيبة!',

        'registration' => [
            'message' => 'تهانينا! تم إنشاء حسابك بنجاح. يرجى تسجيل الدخول إلى حسابك وبدء استخدام نظام POS.',
            'subject' => 'رسالة تسجيل مستخدم POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'تسجيل الدخول',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
