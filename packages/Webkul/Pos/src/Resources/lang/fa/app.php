<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'اعتبارنامه نامعتبر است.',
                'not-activated'       => 'حساب کاربری شما فعال نشده است.',
                'verify-first'        => 'لطفاً ابتدا ایمیل خود را تأیید کنید.',
                'success'             => 'شما با موفقیت وارد شدید.',
            ],

            'logout' => [
                'no-login-agent' => 'هیچ نماینده‌ای وارد نشده است.',
                'success'        => 'شما با موفقیت خارج شدید.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'رمز عبور وارد شده اشتباه است.',
                    'success'          => 'حساب شما با موفقیت به‌روزرسانی شد.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'مشتری با موفقیت ایجاد شد!',
            'update-success' => 'مشتری با موفقیت به‌روزرسانی شد!',
            'delete-success' => 'مشتری با موفقیت حذف شد!',
            'delete-failed'  => 'حذف مشتری با شکست مواجه شد!',
            'pending-orders' => 'مشتری دارای سفارشات در حال پردازش است!',
        ],

        'cart' => [
            'already-applied'     => 'کوپن قبلاً اعمال شده است!',
            'coupon-applied'      => 'کوپن با موفقیت اعمال شد!',
            'coupon-removed'      => 'کوپن با موفقیت حذف شد!',
            'create-success'      => 'سبد خرید با موفقیت ایجاد شد!',
            'invalid-coupon'      => 'کد کوپن نامعتبر است!',
            'item-add-success'    => 'محصول با موفقیت به سبد خرید اضافه شد!',
            'item-remove-success' => 'محصول با موفقیت از سبد خرید حذف شد!',
            'item-update-success' => 'محصول با موفقیت به‌روزرسانی شد!',
            'not-found'           => 'سبد خرید یافت نشد!',
        ],

        'payment' => [
            'title' => 'پرداخت پوز',

            'options' => [
                'cash' => [
                    'title'       => 'پرداخت نقدی پوز',
                    'description' => 'این پرداخت نقدی پوز است.',
                ],

                'card' => [
                    'title'       => 'پرداخت کارت پوز',
                    'description' => 'این پرداخت کارت پوز است.',
                ],

                'split' => [
                    'title'       => 'پرداخت چندگانه پوز',
                    'description' => 'این پرداخت چندگانه پوز است.',
                ],
            ],

            'no-items' => 'هیچ موردی در سبد خرید برای پرداخت وجود ندارد.',
            'success'  => 'پرداخت با موفقیت انجام شد!',
        ],

        'shipping' => [
            'title'       => 'حمل و نقل پوز',
            'description' => 'این حمل و نقل رایگان پوز است.',
        ],

        'order' => [
            'sync-success' => 'سفارش با موفقیت همگام‌سازی شد!',
        ],

        'return' => [
            'create-success'              => 'بازپرداخت با موفقیت ایجاد شد',
            'creation-error'              => 'ایجاد بازپرداخت مجاز نیست.',
            'invalid-qty'                 => 'تعداد نامعتبری برای صورتحساب اقلام یافت شد.',
            'invalid-refund-amount-error' => 'مقدار بازپرداخت باید بیشتر از صفر باشد.',
            'refund-limit-error'          => 'مقدار بازپرداخت :amount قابل انجام نیست.',
        ],

        'drawer' => [
            'create-success' => 'کشو با موفقیت باز شد!',
            'not-opened'     => 'کشو باز نشده است.',
            'close-success'  => 'کشو با موفقیت بسته شد!',
        ],

        'products' => [
            'request-success' => 'درخواست محصول با موفقیت ارسال شد.',
            'create-success'  => 'محصول با موفقیت ایجاد شد!',
        ],

        'reports' => [
            'orders'                  => 'سفارشات',
            'average-order-value'     => 'میانگین ارزش سفارش',
            'average-items-per-order' => 'میانگین اقلام در هر سفارش',
            'discounted-offers'       => 'پیشنهادات تخفیفی',
            'cash-payments'           => 'پرداخت‌های نقدی',
            'other-payments'          => 'سایر پرداخت‌ها',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'یادداشت سفارش',
                    'note-not-available' => 'یادداشت در دسترس نیست!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'افزونه فروش نقطه‌ای (POS) باگیستو.',
                    'title' => 'فروش نقطه‌ای',

                    'settings' => [
                        'info'  => 'فعال کردن POS، تنظیم پیکربندی عمومی، محصولات POS و رسید قبض.',
                        'title' => 'تنظیمات',

                        'general' => [
                            'footer-content'       => 'محتوای پاورقی',
                            'footer-note'          => 'یادداشت پاورقی',
                            'frontend-url'         => 'آدرس رابط جلو',
                            'heading-on-login'     => 'عنوان در صفحه ورود',
                            'info'                 => 'تنظیمات عمومی به شما اجازه می‌دهد تنظیمات صفحه کاربر POS مانند افزودن لوگو، عناوین، محتوای پاورقی، یادداشت پاورقی و غیره را پیکربندی کنید.',
                            'pos-logo'             => 'لوگوی POS',
                            'status'               => 'وضعیت',
                            'sub-heading-on-login' => 'زیرعنوان در صفحه ورود',
                            'title'                => 'عمومی',
                        ],

                        'barcode' => [
                            'height'             => 'ارتفاع',
                            'hide-barcode'       => 'مخفی کردن بارکد',
                            'info'               => 'تنظیمات بارکد به شما اجازه می‌دهد پیکربندی‌های مربوط به تولید بارکد، ارتفاع بارکد، عرض بارکد، نوع بارکد و غیره را انجام دهید.',
                            'prefix'             => 'پیشوند',
                            'print-product-name' => 'چاپ نام محصول',
                            'title'              => 'بارکد',
                            'width'              => 'عرض',

                            'generate-with' => [
                                'title' => 'تولید بارکد با',

                                'options' => [
                                    'product-id' => 'شناسه محصول',
                                    'sku'        => 'SKU محصول',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'اجازه استفاده از SKU برای محصول سفارشی',
                            'info'      => 'تنظیمات محصول امکان پیکربندی SKU محصول را فراهم می‌کند.',
                            'title'     => 'محصولات',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'اختصاص محصولات',
            'banks'            => 'بانک‌ها',
            'barcode-products' => 'محصولات بارکد',
            'create'           => 'ایجاد',
            'delete'           => 'حذف',
            'edit'             => 'ویرایش',
            'generate-barcode' => 'تولید بارکد',
            'orders'           => 'سفارشات',
            'outlets'          => 'فروشگاه‌ها',
            'pos'              => 'نقطه فروش (POS)',
            'preview'          => 'پیش‌نمایش',
            'print-barcode'    => 'چاپ بارکد',
            'receipts'         => 'فاکتورها',
            'requests'         => 'درخواست‌ها',
            'sales-report'     => 'گزارش فروش',
            'users'            => 'نمایندگان',
            'view'             => 'مشاهده',
        ],

        'layouts' => [
            'banks'            => 'بانک‌ها',
            'barcode-products' => 'محصولات بارکد',
            'orders'           => 'سفارشات',
            'pos'              => 'نقطه فروش (POS)',
            'receipts'         => 'فاکتورها',
            'requests'         => 'درخواست‌ها',
            'sales-report'     => 'گزارش فروش',

            'users' => [
                'agents'   => 'نمایندگان',
                'outlets'  => 'فروشگاه‌ها',
                'title'    => 'نمایندگان',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'ایجاد نمایندگان',
                    'pos-front'  => 'رابط POS',
                    'title'      => 'نمایندگان',

                    'datagrid' => [
                        'action'              => 'عملیات',
                        'delete'              => 'حذف',
                        'edit'                => 'ویرایش',
                        'email'               => 'ایمیل',
                        'full-name'           => 'نام کامل',
                        'id'                  => 'شناسه',
                        'id-value'            => 'شناسه - :id',
                        'mass-delete-success' => 'نمایندگان انتخاب‌شده با موفقیت حذف شدند!',
                        'mass-update-success' => 'نمایندگان انتخاب‌شده با موفقیت به‌روزرسانی شدند!',
                        'outlet-name'         => 'نام خروجی',
                        'profile-image'       => 'تصویر پروفایل',
                        'update-status'       => 'به‌روزرسانی وضعیت',
                        'username'            => 'نام کاربری',

                        'status' => [
                            'title' => 'وضعیت',

                            'options' => [
                                'active'  => 'فعال',
                                'disable' => 'غیرفعال',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'بازگشت',
                    'confirm-password'  => 'تایید رمز عبور',
                    'email'             => 'ایمیل',
                    'first-name'        => 'نام',
                    'general'           => 'عمومی',
                    'image'             => 'تصویر',
                    'last-name'         => 'نام خانوادگی',
                    'outlet'            => 'خروجی',
                    'outlet-and-status' => 'خروجی و وضعیت',
                    'password'          => 'رمز عبور',
                    'save-btn'          => 'ذخیره نماینده',
                    'select-outlet'     => 'انتخاب خروجی',
                    'status'            => 'وضعیت',
                    'title'             => 'افزودن نماینده',
                    'user-image'        => 'بارگذاری تصویر نماینده',
                    'username'          => 'نام کاربری',
                ],

                'edit' => [
                    'back-btn'          => 'بازگشت',
                    'confirm-password'  => 'تایید رمز عبور',
                    'email'             => 'ایمیل',
                    'first-name'        => 'نام',
                    'general'           => 'عمومی',
                    'image'             => 'تصویر',
                    'last-name'         => 'نام خانوادگی',
                    'outlet'            => 'خروجی',
                    'outlet-and-status' => 'خروجی و وضعیت',
                    'password'          => 'رمز عبور',
                    'save-btn'          => 'ذخیره نماینده',
                    'select-outlet'     => 'انتخاب خروجی',
                    'status'            => 'وضعیت',
                    'title'             => 'ویرایش نماینده',
                    'user-image'        => 'بارگذاری تصویر نماینده',
                    'username'          => 'نام کاربری',
                ],

                'create-success' => 'نماینده با موفقیت ایجاد شد!',
                'delete-failed'  => 'حذف نماینده ناموفق بود!',
                'delete-success' => 'نماینده با موفقیت حذف شد!',
                'update-success' => 'نماینده با موفقیت به‌روزرسانی شد!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'ایجاد خروجی',
                    'pos-front'  => 'رابط POS',
                    'title'      => 'خروجی‌ها',

                    'datagrid' => [
                        'action'              => 'عملیات',
                        'active'              => 'فعال',
                        'assign'              => 'اختصاص محصول',
                        'delete'              => 'حذف',
                        'edit'                => 'ویرایش',
                        'id'                  => 'شناسه',
                        'inactive'            => 'غیرفعال',
                        'inventory-source'    => 'منبع موجودی',
                        'mass-delete-success' => 'فروشگاه‌های انتخاب شده با موفقیت حذف شدند!',
                        'mass-update-success' => 'فروشگاه‌های انتخاب شده با موفقیت به‌روزرسانی شدند!',
                        'name'                => 'نام',
                        'receipt-title'       => 'عنوان رسید',
                        'status'              => 'وضعیت',
                        'title'               => 'لیست فروشگاه‌های POS',
                        'update-status'       => 'به‌روزرسانی وضعیت',
                    ],
                ],

                'create' => [
                    'address'                 => 'آدرس',
                    'back-btn'                => 'بازگشت',
                    'btn-title'               => 'ذخیره فروشگاه',
                    'city'                    => 'شهر',
                    'country'                 => 'کشور',
                    'customer-care-number'    => 'شماره خدمات مشتری',
                    'email'                   => 'ایمیل',
                    'general'                 => 'عمومی',
                    'gst-number'              => 'شماره GST',
                    'inventory'               => 'موجودی',
                    'inventory-source'        => 'منبع موجودی',
                    'low-stock-qty'           => 'مقدار کم موجودی',
                    'name'                    => 'نام فروشگاه',
                    'phone'                   => 'تلفن',
                    'postcode'                => 'کد پستی',
                    'receipt'                 => 'رسید',
                    'select-country'          => 'انتخاب کشور',
                    'select-inventory-source' => 'انتخاب منبع موجودی',
                    'select-receipt'          => 'انتخاب رسید',
                    'state'                   => 'ایالت',
                    'status'                  => 'وضعیت',
                    'store-address'           => 'آدرس فروشگاه',
                    'title'                   => 'اضافه کردن فروشگاه',
                    'website'                 => 'وبسایت',
                ],

                'edit' => [
                    'address'                 => 'آدرس',
                    'back-btn'                => 'بازگشت',
                    'btn-title'               => 'ذخیره فروشگاه',
                    'city'                    => 'شهر',
                    'country'                 => 'کشور',
                    'customer-care-number'    => 'شماره خدمات مشتری',
                    'email'                   => 'ایمیل',
                    'general'                 => 'عمومی',
                    'gst-number'              => 'شماره GST',
                    'inventory'               => 'موجودی',
                    'inventory-source'        => 'منبع موجودی',
                    'low-stock-qty'           => 'مقدار کم موجودی',
                    'name'                    => 'نام فروشگاه',
                    'phone'                   => 'تلفن',
                    'postcode'                => 'کد پستی',
                    'receipt'                 => 'رسید',
                    'select-country'          => 'انتخاب کشور',
                    'select-inventory-source' => 'انتخاب منبع موجودی',
                    'select-receipt'          => 'انتخاب رسید',
                    'state'                   => 'ایالت',
                    'status'                  => 'وضعیت',
                    'store-address'           => 'آدرس فروشگاه',
                    'title'                   => 'ویرایش فروشگاه',
                    'website'                 => 'وبسایت',
                ],

                'assign' => [
                    'back-btn' => 'بازگشت',
                    'title'    => 'مدیریت محصولات خروجی',

                    'datagrid' => [
                        'active'              => 'فعال',
                        'assign'              => 'اختصاص دادن',
                        'disable'             => 'غیرفعال',
                        'id'                  => 'شناسه',
                        'id-value'            => 'شناسه - :id',
                        'image'               => 'تصویر',
                        'mass-assign-success' => 'محصولات با موفقیت اختصاص داده شدند!',
                        'name'                => 'نام',
                        'out-of-stock'        => 'تمام شده',
                        'pos-status'          => 'وضعیت POS',
                        'price'               => 'قیمت',
                        'product-image'       => 'تصویر محصول',
                        'qty'                 => 'تعداد',
                        'qty-value'           => ':qty موجود',
                        'sku'                 => 'شناسه SKU',
                        'sku-value'           => 'شناسه SKU - :sku',
                        'status'              => 'وضعیت',
                        'type'                => 'نوع',
                        'unassign'            => 'لغو اختصاص',
                        'update-assign'       => 'به‌روزرسانی اختصاص',
                    ],
                ],

                'create-success' => 'خروجی با موفقیت ایجاد شد!',
                'delete-failed'  => 'حذف خروجی ناموفق بود!',
                'delete-success' => 'خروجی با موفقیت حذف شد!',
                'update-success' => 'خروجی با موفقیت به‌روزرسانی شد!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'محصولات بارکد',

                'datagrid' => [
                    'barcode'               => 'بارکد',
                    'generate-barcode'      => 'تولید بارکد',
                    'print-barcode'         => 'چاپ بارکد',
                    'id'                    => 'شناسه',
                    'id-value'              => 'شناسه - :id',
                    'image'                 => 'تصویر',
                    'mass-generate-success' => 'بارکد محصولات انتخاب شده با موفقیت تولید شد!',
                    'name'                  => 'نام',
                    'out-of-stock'          => 'تمام شده',
                    'price'                 => 'قیمت',
                    'product-image'         => 'تصویر محصول',
                    'qty'                   => 'تعداد',
                    'qty-value'             => ':qty موجود',
                    'sku'                   => 'شناسه SKU',
                    'sku-value'             => 'شناسه SKU - :sku',

                    'status' => [
                        'title' => 'وضعیت',

                        'options' => [
                            'active'  => 'فعال',
                            'disable' => 'غیرفعال',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'بازگشت',
                'btn-title' => 'چاپ',
                'qty'       => 'تعداد',
                'title'     => 'چاپ بارکد',
            ],

            'generate-failed'  => 'تولید بارکد ناموفق بود!',
            'generate-success' => 'بارکد با موفقیت تولید شد!',
        ],

        'orders' => [
            'index' => [
                'title' => 'سفارشات',

                'datagrid' => [
                    'customer-name' => 'نام مشتری',
                    'grand-total'   => 'جمع کل',
                    'order-date'    => 'تاریخ سفارش',
                    'order-id'      => 'شناسه سفارش',
                    'order-ref-id'  => 'شناسه مرجع سفارش',
                    'view'          => 'مشاهده',

                    'status' => [
                        'title' => 'وضعیت',

                        'options' => [
                            'canceled'        => 'لغو شده',
                            'closed'          => 'بسته شده',
                            'completed'       => 'تکمیل شده',
                            'fraud'           => 'تقلبی',
                            'pending'         => 'در انتظار',
                            'pending-payment' => 'در انتظار پرداخت',
                            'processing'      => 'در حال پردازش',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'درخواست‌ها',

                'datagrid' => [
                    'id'                  => 'شناسه',
                    'product-image'       => 'تصویر محصول',
                    'mass-update-error'   => 'بروزرسانی درخواست ناموفق بود!',
                    'mass-update-success' => 'درخواست‌های انتخاب شده با موفقیت به‌روز شدند!',
                    'product-name'        => 'نام محصول',
                    'outlet-name'         => 'نام فروشگاه',
                    'qty-value'           => 'تعداد - :qty',
                    'request-date'        => 'تاریخ درخواست',
                    'requested-qty'       => 'تعداد درخواست شده',
                    'update-status'       => 'بروزرسانی وضعیت',
                    'user-name'           => 'نام کاربر',

                    'status' => [
                        'title' => 'وضعیت',

                        'options' => [
                            'complete' => 'تکمیل شده',
                            'decline'  => 'رد شده',
                            'pending'  => 'در انتظار',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'بازگشت',
                'btn-title' => 'ذخیره',
                'title'     => 'جزئیات درخواست محصول #:id',

                'user-info' => [
                    'email'            => 'ایمیل',
                    'name'             => 'نام',
                    'outlet-address'   => 'آدرس فروشگاه',
                    'outlet-inventory' => 'منبع موجودی فروشگاه',
                    'outlet-name'      => 'نام فروشگاه',
                    'title'            => 'اطلاعات کاربر',
                ],

                'request-info' => [
                    'comment'       => 'نظر',
                    'product-name'  => 'نام محصول',
                    'qty-value'     => 'تعداد - :qty',
                    'request-date'  => 'تاریخ درخواست',
                    'requested-qty' => 'تعداد درخواست شده',
                    'title'         => 'اطلاعات درخواست',

                    'status' => [
                        'title' => 'وضعیت',

                        'options' => [
                            'complete' => 'تکمیل شده',
                            'decline'  => 'رد شده',
                            'pending'  => 'در انتظار',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'بروزرسانی درخواست ناموفق بود!',
            'update-success' => 'درخواست با موفقیت به‌روز شد!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'ایجاد بانک',
                'title'     => 'بانک‌ها',

                'datagrid' => [
                    'active'              => 'فعال',
                    'address'             => 'آدرس بانک',
                    'agent-name'          => 'نماینده',
                    'delete'              => 'حذف',
                    'disable'             => 'غیرفعال کردن',
                    'id'                  => 'شناسه',
                    'mass-delete-success' => 'بانک‌های انتخاب شده با موفقیت حذف شدند!',
                    'name'                => 'نام بانک',
                    'status'              => 'وضعیت',
                ],
            ],

            'create' => [
                'back-btn'  => 'بازگشت',
                'btn-title' => 'ذخیره بانک',
                'title'     => 'ایجاد بانک جدید',

                'general' => [
                    'address' => 'آدرس',
                    'email'   => 'ایمیل',
                    'name'    => 'نام',
                    'phone'   => 'تلفن',
                    'title'   => 'عمومی',
                ],

                'agent-and-status' => [
                    'agent'        => 'واگذاری نماینده POS',
                    'bank-status'  => 'وضعیت بانک',
                    'select-agent' => 'انتخاب نماینده',
                    'title'        => 'نماینده POS & وضعیت بانک',
                ],
            ],

            'edit' => [
                'back-btn'  => 'بازگشت',
                'btn-title' => 'ذخیره بانک',
                'title'     => 'ویرایش بانک',

                'general' => [
                    'address' => 'آدرس',
                    'email'   => 'ایمیل',
                    'name'    => 'نام',
                    'phone'   => 'تلفن',
                    'title'   => 'عمومی',
                ],

                'agent-and-status' => [
                    'agent'        => 'واگذاری نماینده POS',
                    'bank-status'  => 'وضعیت بانک',
                    'select-agent' => 'انتخاب نماینده',
                    'title'        => 'نماینده POS & وضعیت بانک',
                ],
            ],

            'create-success' => 'بانک با موفقیت ایجاد شد!',
            'delete-failed'  => 'حذف بانک ناموفق بود!',
            'delete-success' => 'بانک با موفقیت حذف شد!',
            'update-success' => 'بانک با موفقیت به‌روز شد!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'گزارش فروش',

                'datagrid' => [
                    'bank-name'      => 'نام بانک',
                    'grand-total'    => 'مجموع کل',
                    'order-date'     => 'تاریخ سفارش',
                    'order-id'       => 'شناسه سفارش',
                    'order-id-value' => 'شناسه - :id',
                    'order-note'     => 'یادداشت سفارش',
                    'outlet-name'    => 'نام فروشگاه',
                    'payment-method' => 'روش پرداخت',
                    'view'           => 'مشاهده',

                    'status' => [
                        'title' => 'وضعیت',

                        'options' => [
                            'canceled'        => 'لغو شده',
                            'closed'          => 'بسته شده',
                            'completed'       => 'تکمیل شده',
                            'fraud'           => 'تقلب',
                            'pending'         => 'در انتظار',
                            'pending-payment' => 'در انتظار پرداخت',
                            'processing'      => 'در حال پردازش',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'ایجاد رسید',
                'title'      => 'رسیدها',

                'datagrid' => [
                    'delete'              => 'حذف',
                    'edit'                => 'ویرایش',
                    'id'                  => 'شناسه',
                    'mass-delete-success' => 'رسیدهای انتخاب شده با موفقیت حذف شدند!',
                    'preview'             => 'پیش‌نمایش',
                    'title'               => 'عنوان',

                    'status' => [
                        'title' => 'وضعیت',

                        'options' => [
                            'active'   => 'فعال',
                            'inactive' => 'غیرفعال',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'بازگشت',
                'btn-title' => 'ذخیره رسید',
                'title'     => 'ایجاد رسید جدید',

                'general' => [
                    'cashier-name-label'      => 'برچسب نام صندوقدار',
                    'change-amount-label'     => 'برچسب مبلغ تغییر',
                    'credit-amount-label'     => 'برچسب مبلغ اعتبار',
                    'discount-amt-label'      => 'برچسب مبلغ تخفیف',
                    'display-cashier-name'    => 'نمایش نام صندوقدار',
                    'display-change-amount'   => 'نمایش مبلغ تغییر',
                    'display-credit-amount'   => 'نمایش مبلغ اعتبار',
                    'display-customer-name'   => 'نمایش نام مشتری',
                    'display-date'            => 'نمایش تاریخ',
                    'display-discount-amt'    => 'نمایش مبلغ تخفیف',
                    'display-order-id'        => 'نمایش شناسه سفارش',
                    'display-outlet-address'  => 'نمایش آدرس فروشگاه',
                    'display-outlet-name'     => 'نمایش نام فروشگاه',
                    'display-sub-total'       => 'نمایش مجموع جزئی',
                    'display-tax'             => 'نمایش مالیات',
                    'grand-total-label'       => 'برچسب مجموع کل',
                    'order-id-label'          => 'برچسب شناسه سفارش',
                    'receipt-title'           => 'عنوان رسید',
                    'show-order-barcode'      => 'نمایش بارکد سفارش',
                    'show-print-confirmation' => 'نمایش تأیید چاپ',
                    'status'                  => 'وضعیت',
                    'sub-total-label'         => 'برچسب مجموع جزئی',
                    'tax-label'               => 'برچسب مالیات',
                    'title'                   => 'عمومی',
                ],

                'logo' => [
                    'display-logo' => 'نمایش لوگو',
                    'logo-alt'     => 'متن جایگزین لوگو',
                    'logo-height'  => 'ارتفاع لوگو (به پیکسل)',
                    'logo-width'   => 'عرض لوگو (به پیکسل)',
                    'title'        => 'لوگو',
                    'upload-logo'  => 'بارگذاری لوگو',
                ],

                'header' => [
                    'header-content' => 'محتوای سرصفحه',
                    'title'          => 'سرصفحه',
                ],

                'footer' => [
                    'footer-content' => 'محتوای پاورقی',
                    'title'          => 'پاورقی',
                ],
            ],

            'edit' => [
                'back-btn'  => 'بازگشت',
                'btn-title' => 'ذخیره رسید',
                'title'     => 'ویرایش رسید',

                'general' => [
                    'cashier-name-label'      => 'برچسب نام صندوقدار',
                    'change-amount-label'     => 'برچسب مبلغ تغییر',
                    'credit-amount-label'     => 'برچسب مبلغ اعتبار',
                    'discount-amt-label'      => 'برچسب مبلغ تخفیف',
                    'display-cashier-name'    => 'نمایش نام صندوقدار',
                    'display-change-amount'   => 'نمایش مبلغ تغییر',
                    'display-credit-amount'   => 'نمایش مبلغ اعتبار',
                    'display-customer-name'   => 'نمایش نام مشتری',
                    'display-date'            => 'نمایش تاریخ',
                    'display-discount-amt'    => 'نمایش مبلغ تخفیف',
                    'display-order-id'        => 'نمایش شناسه سفارش',
                    'display-outlet-address'  => 'نمایش آدرس فروشگاه',
                    'display-outlet-name'     => 'نمایش نام فروشگاه',
                    'display-sub-total'       => 'نمایش مجموع جزئی',
                    'display-tax'             => 'نمایش مالیات',
                    'grand-total-label'       => 'برچسب مجموع کل',
                    'order-id-label'          => 'برچسب شناسه سفارش',
                    'receipt-title'           => 'عنوان رسید',
                    'show-order-barcode'      => 'نمایش بارکد سفارش',
                    'show-print-confirmation' => 'نمایش تأیید چاپ',
                    'status'                  => 'وضعیت',
                    'sub-total-label'         => 'برچسب مجموع جزئی',
                    'tax-label'               => 'برچسب مالیات',
                    'title'                   => 'عمومی',
                ],

                'logo' => [
                    'display-logo' => 'نمایش لوگو',
                    'logo-alt'     => 'متن جایگزین لوگو',
                    'logo-height'  => 'ارتفاع لوگو (به پیکسل)',
                    'logo-width'   => 'عرض لوگو (به پیکسل)',
                    'title'        => 'لوگو',
                    'upload-logo'  => 'بارگذاری لوگو',
                ],

                'header' => [
                    'header-content' => 'محتوای سرصفحه',
                    'title'          => 'سرصفحه',
                ],

                'footer' => [
                    'footer-content' => 'محتوای پاورقی',
                    'title'          => 'پاورقی',
                ],
            ],

            'preview' => [
                'amount'         => 'مقدار',
                'cashier'        => 'صندوقدار',
                'change-amount'  => 'تغییر',
                'customer'       => 'مشتری',
                'customer-email' => 'ایمیل مشتری',
                'customer-name'  => 'نام مشتری',
                'customer-phone' => 'تلفن مشتری',
                'date'           => 'تاریخ',
                'discount'       => 'تخفیف',
                'email'          => 'ایمیل',
                'grand-total'    => 'مجموع کل',
                'order-id'       => 'شناسه سفارش',
                'phone'          => 'تلفن',
                'price'          => 'قیمت',
                'product'        => 'محصول',
                'qty'            => 'تعداد',
                'sub-total'      => 'مجموع جزئی',
                'tax'            => 'مالیات',
                'title'          => 'پیش‌نمایش رسید',
                'total-qty'      => 'مجموع تعداد',
            ],

            'create-success' => 'رسید با موفقیت ایجاد شد!',
            'delete-failed'  => 'حذف رسید ناموفق بود!',
            'delete-success' => 'رسید با موفقیت حذف شد!',
            'update-success' => 'رسید با موفقیت به‌روز شد!',
        ],
    ],

    'emails' => [
        'dear'     => 'عزیز :name',
        'greeting' => 'سلام!',

        'registration' => [
            'message' => 'تبریک! حساب کاربری شما با موفقیت ایجاد شد. لطفاً وارد حساب کاربری خود شوید و استفاده از سیستم POS را آغاز کنید.',
            'subject' => 'ایمیل ثبت‌نام کاربر POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'ورود',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
