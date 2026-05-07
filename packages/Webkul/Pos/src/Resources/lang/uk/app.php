<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Невірні облікові дані.',
                'not-activated'       => 'Ваш обліковий запис не активований.',
                'verify-first'        => 'Будь ласка, спочатку підтверджте вашу електронну пошту.',
                'success'             => 'Ви успішно увійшли в систему.',
            ],

            'logout' => [
                'no-login-agent' => 'Ніхто не увійшов у систему.',
                'success'        => 'Ви успішно вийшли з системи.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'Введений вами пароль невірний.',
                    'success'          => 'Ваш обліковий запис успішно оновлено.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Клієнт успішно створений!',
            'update-success' => 'Клієнт успішно оновлений!',
            'delete-success' => 'Клієнт успішно видалений!',
            'delete-failed'  => 'Не вдалося видалити клієнта!',
            'pending-orders' => 'У клієнта є невиконані замовлення!',
        ],

        'cart' => [
            'already-applied'     => 'Купон вже застосовано!',
            'coupon-applied'      => 'Купон успішно застосовано!',
            'coupon-removed'      => 'Купон успішно видалено!',
            'create-success'      => 'Кошик успішно створено!',
            'invalid-coupon'      => 'Недійсний код купона!',
            'item-add-success'    => 'Товар успішно додано до кошика!',
            'item-remove-success' => 'Товар успішно видалено з кошика!',
            'item-update-success' => 'Товар успішно оновлено!',
            'not-found'           => 'Кошик не знайдено!',
        ],

        'payment' => [
            'title' => 'Pos Оплата',

            'options' => [
                'cash' => [
                    'title'       => 'Pos Оплата Готівкою',
                    'description' => 'Це оплата готівкою Pos.',
                ],

                'card' => [
                    'title'       => 'Pos Оплата Карткою',
                    'description' => 'Це оплата карткою Pos.',
                ],

                'split' => [
                    'title'       => 'Pos Розділена Оплата',
                    'description' => 'Це розділена оплата Pos.',
                ],
            ],

            'no-items' => 'У кошику немає товарів для продовження оплати.',
            'success'  => 'Оплата успішно завершена!',
        ],

        'shipping' => [
            'title'       => 'Pos Доставка',
            'description' => 'Це безкоштовна доставка Pos.',
        ],

        'order' => [
            'sync-success' => 'Замовлення успішно синхронізовано!',
        ],

        'return' => [
            'create-success'              => 'Повернення успішно створено',
            'creation-error'              => 'Створення повернення заборонено.',
            'invalid-qty'                 => 'Знайдено недійсну кількість для виставлення рахунку.',
            'invalid-refund-amount-error' => 'Сума повернення повинна бути більшою за нуль.',
            'refund-limit-error'          => 'Сума повернення :amount не може бути оброблена.',
        ],

        'drawer' => [
            'create-success' => 'Шухляда успішно відкрита!',
            'not-opened'     => 'Шухляда не відкрита.',
            'close-success'  => 'Шухляда успішно закрита!',
        ],

        'products' => [
            'request-success' => 'Запит на продукт успішно надіслано.',
            'create-success'  => 'Продукт успішно створено!',
        ],

        'reports' => [
            'orders'                  => 'Замовлення',
            'average-order-value'     => 'Середнє Значення Замовлення',
            'average-items-per-order' => 'Середня Кількість Товарів на Замовлення',
            'discounted-offers'       => 'Знижкові Пропозиції',
            'cash-payments'           => 'Готівкові Оплати',
            'other-payments'          => 'Інші Оплати',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Примітка до замовлення',
                    'note-not-available' => 'Примітка недоступна!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'Розширення точки продажу (POS) Bagisto.',
                    'title' => 'Точка Продажу',

                    'settings' => [
                        'info'  => 'Увімкнути POS, Налаштувати Загальні Налаштування, POS Продукти та Квитанцію.',
                        'title' => 'Налаштування',

                        'general' => [
                            'footer-content'       => 'Вміст підвалу',
                            'footer-note'          => 'Примітка підвалу',
                            'frontend-url'         => 'URL інтерфейсу',
                            'heading-on-login'     => 'Заголовок при вході',
                            'info'                 => 'Загальні налаштування дозволяють налаштувати сторінку користувача POS, додавши логотип, заголовки, вміст підвалу, примітки підвалу тощо.',
                            'pos-logo'             => 'Логотип POS',
                            'status'               => 'Статус',
                            'sub-heading-on-login' => 'Підзаголовок при вході',
                            'title'                => 'Загальні',
                        ],

                        'barcode' => [
                            'height'             => 'Висота',
                            'hide-barcode'       => 'Сховати Штрихкод',
                            'info'               => 'Налаштування штрихкоду дозволяють налаштувати генерацію штрихкодів, висоту штрихкоду, ширину штрихкоду, тип штрихкоду тощо.',
                            'prefix'             => 'Префікс',
                            'print-product-name' => 'Друкувати Назву Продукта',
                            'title'              => 'Штрихкод',
                            'width'              => 'Ширина',

                            'generate-with' => [
                                'title' => 'Генерувати Штрихкод З',

                                'options' => [
                                    'product-id' => 'ID Продукта',
                                    'sku'        => 'SKU Продукта',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'Дозволити SKU для користувацького продукту',
                            'info'      => 'Налаштування продукту дозволяють налаштувати SKU продукту.',
                            'title'     => 'Продукти',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Призначити продукти',
            'banks'            => 'Банки',
            'barcode-products' => 'Продукти з Штрихкодом',
            'create'           => 'Створити',
            'delete'           => 'Видалити',
            'edit'             => 'Редагувати',
            'generate-barcode' => 'Створити Штрихкод',
            'orders'           => 'Замовлення',
            'outlets'          => 'Торгові точки',
            'pos'              => 'Торговий пункт (POS)',
            'preview'          => 'Попередній перегляд',
            'print-barcode'    => 'Друкувати Штрихкод',
            'receipts'         => 'Чеки',
            'requests'         => 'Запити',
            'sales-report'     => 'Звіт про продажі',
            'users'            => 'Агенти',
            'view'             => 'Переглянути',
        ],

        'layouts' => [
            'banks'            => 'Банки',
            'barcode-products' => 'Продукти з Штрихкодом',
            'orders'           => 'Замовлення',
            'pos'              => 'Торговий пункт (POS)',
            'receipts'         => 'Чеки',
            'requests'         => 'Запити',
            'sales-report'     => 'Звіт про продажі',

            'users' => [
                'agents'   => 'Агенти',
                'outlets'  => 'Торгові точки',
                'title'    => 'Агенти',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Створити агентів',
                    'pos-front'  => 'Фронт POS',
                    'title'      => 'Агенти',

                    'datagrid' => [
                        'action'              => 'Дія',
                        'delete'              => 'Видалити',
                        'edit'                => 'Редагувати',
                        'email'               => 'Електронна пошта',
                        'full-name'           => 'Повне ім\'я',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'Вибрані агенти успішно видалені!',
                        'mass-update-success' => 'Вибрані агенти успішно оновлені!',
                        'outlet-name'         => 'Назва торгової точки',
                        'profile-image'       => 'Зображення профілю',
                        'update-status'       => 'Оновити статус',
                        'username'            => 'Ім\'я користувача',

                        'status' => [
                            'title' => 'Статус',

                            'options' => [
                                'active'  => 'Активний',
                                'disable' => 'Вимкнено',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Назад',
                    'confirm-password'  => 'Підтвердження пароля',
                    'email'             => 'Електронна пошта',
                    'first-name'        => 'Ім\'я',
                    'general'           => 'Основні дані',
                    'image'             => 'Зображення',
                    'last-name'         => 'Прізвище',
                    'outlet'            => 'Пункт продажу',
                    'outlet-and-status' => 'Пункт продажу та статус',
                    'password'          => 'Пароль',
                    'save-btn'          => 'Зберегти агента',
                    'select-outlet'     => 'Вибрати пункт продажу',
                    'status'            => 'Статус',
                    'title'             => 'Додати агента',
                    'user-image'        => 'Завантажити зображення агента',
                    'username'          => 'Користувацьке ім\'я',
                ],

                'edit' => [
                    'back-btn'          => 'Назад',
                    'confirm-password'  => 'Підтвердження пароля',
                    'email'             => 'Електронна пошта',
                    'first-name'        => 'Ім\'я',
                    'general'           => 'Основні дані',
                    'image'             => 'Зображення',
                    'last-name'         => 'Прізвище',
                    'outlet'            => 'Пункт продажу',
                    'outlet-and-status' => 'Пункт продажу та статус',
                    'password'          => 'Пароль',
                    'save-btn'          => 'Зберегти агента',
                    'select-outlet'     => 'Вибрати пункт продажу',
                    'status'            => 'Статус',
                    'title'             => 'Редагувати агента',
                    'user-image'        => 'Завантажити зображення агента',
                    'username'          => 'Користувацьке ім\'я',
                ],

                'create-success' => 'Агента успішно створено!',
                'delete-failed'  => 'Не вдалося видалити агента!',
                'delete-success' => 'Агента успішно видалено!',
                'update-success' => 'Агента успішно оновлено!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Створити пункт продажу',
                    'pos-front'  => 'Фронт POS',
                    'title'      => 'Пункти продажу',

                    'datagrid' => [
                        'action'              => 'Дія',
                        'active'              => 'Активний',
                        'assign'              => 'Призначити продукт',
                        'delete'              => 'Видалити',
                        'edit'                => 'Редагувати',
                        'id'                  => 'ID',
                        'inactive'            => 'Неактивний',
                        'inventory-source'    => 'Джерело інвентаризації',
                        'mass-delete-success' => 'Вибрані магазини успішно видалені!',
                        'mass-update-success' => 'Вибрані магазини успішно оновлені!',
                        'name'                => 'Назва',
                        'receipt-title'       => 'Назва чека',
                        'status'              => 'Статус',
                        'title'               => 'Список POS магазинів',
                        'update-status'       => 'Оновити статус',
                    ],
                ],

                'create' => [
                    'address'                 => 'Адреса',
                    'back-btn'                => 'Назад',
                    'btn-title'               => 'Зберегти пункт продажу',
                    'city'                    => 'Місто',
                    'country'                 => 'Країна',
                    'customer-care-number'    => 'Номер служби підтримки клієнтів',
                    'email'                   => 'Електронна пошта',
                    'general'                 => 'Загальні',
                    'gst-number'              => 'Номер GST',
                    'inventory'               => 'Інвентаризація',
                    'inventory-source'        => 'Джерело інвентаризації',
                    'low-stock-qty'           => 'Кількість низького запасу',
                    'name'                    => 'Назва пункту продажу',
                    'phone'                   => 'Телефон',
                    'postcode'                => 'Поштовий індекс',
                    'receipt'                 => 'Квитанція',
                    'select-country'          => 'Вибрати країну',
                    'select-inventory-source' => 'Вибрати джерело інвентаризації',
                    'select-receipt'          => 'Вибрати квитанцію',
                    'state'                   => 'Штат',
                    'status'                  => 'Статус',
                    'store-address'           => 'Адреса пункту продажу',
                    'title'                   => 'Додати пункт продажу',
                    'website'                 => 'Вебсайт',
                ],

                'edit' => [
                    'address'                 => 'Адреса',
                    'back-btn'                => 'Назад',
                    'btn-title'               => 'Зберегти пункт продажу',
                    'city'                    => 'Місто',
                    'country'                 => 'Країна',
                    'customer-care-number'    => 'Номер служби підтримки клієнтів',
                    'email'                   => 'Електронна пошта',
                    'general'                 => 'Загальні',
                    'gst-number'              => 'Номер GST',
                    'inventory'               => 'Інвентаризація',
                    'inventory-source'        => 'Джерело інвентаризації',
                    'low-stock-qty'           => 'Кількість низького запасу',
                    'name'                    => 'Назва пункту продажу',
                    'phone'                   => 'Телефон',
                    'postcode'                => 'Поштовий індекс',
                    'receipt'                 => 'Квитанція',
                    'select-country'          => 'Вибрати країну',
                    'select-inventory-source' => 'Вибрати джерело інвентаризації',
                    'select-receipt'          => 'Вибрати квитанцію',
                    'state'                   => 'Штат',
                    'status'                  => 'Статус',
                    'store-address'           => 'Адреса пункту продажу',
                    'title'                   => 'Редагувати пункт продажу',
                    'website'                 => 'Вебсайт',
                ],

                'assign' => [
                    'back-btn' => 'Назад',
                    'title'    => 'Управління продуктами пункту продажу',

                    'datagrid' => [
                        'active'              => 'Активний',
                        'assign'              => 'Призначити',
                        'disable'             => 'Вимкнути',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'image'               => 'Зображення',
                        'mass-assign-success' => 'Призначення продуктів успішно оновлено!',
                        'name'                => 'Назва',
                        'out-of-stock'        => 'Немає в наявності',
                        'pos-status'          => 'Статус POS',
                        'price'               => 'Ціна',
                        'product-image'       => 'Зображення продукту',
                        'qty'                 => 'Кількість',
                        'qty-value'           => ':qty Доступно',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Статус',
                        'type'                => 'Тип',
                        'unassign'            => 'Зняти призначення',
                        'update-assign'       => 'Оновити призначення',
                    ],
                ],

                'create-success' => 'Пункт продажу успішно створено!',
                'delete-failed'  => 'Не вдалося видалити пункт продажу!',
                'delete-success' => 'Пункт продажу успішно видалено!',
                'update-success' => 'Пункт продажу успішно оновлено!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Продукти з Штрихкодом',

                'datagrid' => [
                    'barcode'               => 'Штрих-код',
                    'generate-barcode'      => 'Сформувати штрих-код',
                    'print-barcode'         => 'Роздрукувати штрих-код',
                    'id'                    => 'ID',
                    'id-value'              => 'ID - :id',
                    'image'                 => 'Зображення',
                    'mass-generate-success' => 'Штрих-коди для вибраних продуктів успішно створені!',
                    'name'                  => 'Назва',
                    'out-of-stock'          => 'Немає в наявності',
                    'price'                 => 'Ціна',
                    'product-image'         => 'Зображення продукту',
                    'qty'                   => 'Кількість',
                    'qty-value'             => ':qty Доступно',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'active'  => 'Активний',
                            'disable' => 'Вимкнено',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Друк',
                'qty'       => 'Кількість',
                'title'     => 'Друк штрих-коду',
            ],

            'generate-failed'  => 'Не вдалося створити штрих-код!',
            'generate-success' => 'Штрих-код успішно створено!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Замовлення',

                'datagrid' => [
                    'customer-name' => 'Ім\'я клієнта',
                    'grand-total'   => 'Загальна сума',
                    'order-date'    => 'Дата замовлення',
                    'order-id'      => 'ID замовлення',
                    'order-ref-id'  => 'Посилання на ID замовлення',
                    'view'          => 'Переглянути',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'canceled'        => 'Скасовано',
                            'closed'          => 'Закрито',
                            'completed'       => 'Завершено',
                            'fraud'           => 'Шахрайство',
                            'pending'         => 'Очікує',
                            'pending-payment' => 'Очікує на оплату',
                            'processing'      => 'Обробка',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Запити',

                'datagrid' => [
                    'id'                  => 'ID',
                    'product-image'       => 'Зображення продукту',
                    'mass-update-error'   => 'Не вдалося оновити запити!',
                    'mass-update-success' => 'Вибрані запити успішно оновлені!',
                    'product-name'        => 'Назва продукту',
                    'outlet-name'         => 'Назва пункту продажу',
                    'qty-value'           => 'Кількість - :qty',
                    'request-date'        => 'Дата запиту',
                    'requested-qty'       => 'Запитувана кількість',
                    'update-status'       => 'Оновити статус',
                    'user-name'           => 'Ім\'я користувача',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'complete' => 'Завершено',
                            'decline'  => 'Відхилено',
                            'pending'  => 'Очікує',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Зберегти',
                'title'     => 'Деталі запиту продукту #:id',

                'user-info' => [
                    'email'            => 'Електронна пошта',
                    'name'             => 'Ім\'я',
                    'outlet-address'   => 'Адреса пункту продажу',
                    'outlet-inventory' => 'Джерело інвентаризації пункту продажу',
                    'outlet-name'      => 'Назва пункту продажу',
                    'title'            => 'Інформація про користувача',
                ],

                'request-info' => [
                    'comment'       => 'Коментар',
                    'product-name'  => 'Назва продукту',
                    'qty-value'     => 'Кількість - :qty',
                    'request-date'  => 'Дата запиту',
                    'requested-qty' => 'Запитувана кількість',
                    'title'         => 'Інформація про запит',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'complete' => 'Завершено',
                            'decline'  => 'Відхилено',
                            'pending'  => 'Очікує',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Не вдалося оновити запит!',
            'update-success' => 'Запит успішно оновлено!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Створити банк',
                'title'     => 'Банки',

                'datagrid' => [
                    'active'              => 'Активний',
                    'address'             => 'Адреса банку',
                    'agent-name'          => 'Агент',
                    'delete'              => 'Видалити',
                    'disable'             => 'Вимкнути',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Вибрані банки успішно видалені!',
                    'name'                => 'Назва банку',
                    'status'              => 'Статус',
                ],
            ],

            'create' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Зберегти банк',
                'title'     => 'Створити новий банк',

                'general' => [
                    'address' => 'Адреса',
                    'email'   => 'Електронна пошта',
                    'name'    => 'Назва',
                    'phone'   => 'Телефон',
                    'title'   => 'Загальна інформація',
                ],

                'agent-and-status' => [
                    'agent'        => 'Призначити агента POS',
                    'bank-status'  => 'Статус банку',
                    'select-agent' => 'Вибрати агента',
                    'title'        => 'Агент POS & Статус банку',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Зберегти банк',
                'title'     => 'Редагувати банк',

                'general' => [
                    'address' => 'Адреса',
                    'email'   => 'Електронна пошта',
                    'name'    => 'Назва',
                    'phone'   => 'Телефон',
                    'title'   => 'Загальна інформація',
                ],

                'agent-and-status' => [
                    'agent'        => 'Призначити агента POS',
                    'bank-status'  => 'Статус банку',
                    'select-agent' => 'Вибрати агента',
                    'title'        => 'Агент POS & Статус банку',
                ],
            ],

            'create-success' => 'Банк успішно створено!',
            'delete-failed'  => 'Не вдалося видалити банк!',
            'delete-success' => 'Банк успішно видалено!',
            'update-success' => 'Банк успішно оновлено!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Звіт про продажі',

                'datagrid' => [
                    'bank-name'      => 'Назва банку',
                    'grand-total'    => 'Загальна сума',
                    'order-date'     => 'Дата замовлення',
                    'order-id'       => 'ID замовлення',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Примітка до замовлення',
                    'outlet-name'    => 'Назва пункту продажу',
                    'payment-method' => 'Метод оплати',
                    'view'           => 'Переглянути',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'canceled'        => 'Скасовано',
                            'closed'          => 'Закрито',
                            'completed'       => 'Завершено',
                            'fraud'           => 'Шахрайство',
                            'pending'         => 'Очікує',
                            'pending-payment' => 'Очікує на оплату',
                            'processing'      => 'Обробка',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Створити квитанцію',
                'title'      => 'Квитанції',

                'datagrid' => [
                    'delete'              => 'Видалити',
                    'edit'                => 'Редагувати',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Вибрані квитанції успішно видалені!',
                    'preview'             => 'Попередній перегляд',
                    'title'               => 'Назва',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'active'   => 'Активний',
                            'inactive' => 'Неактивний',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Зберегти квитанцію',
                'title'     => 'Створити нову квитанцію',

                'general' => [
                    'cashier-name-label'      => 'Мітка імені касира',
                    'change-amount-label'     => 'Мітка суми решти',
                    'credit-amount-label'     => 'Мітка суми кредиту',
                    'discount-amt-label'      => 'Мітка суми знижки',
                    'display-cashier-name'    => 'Відображати ім\'я касира',
                    'display-change-amount'   => 'Відображати суму решти',
                    'display-credit-amount'   => 'Відображати суму кредиту',
                    'display-customer-name'   => 'Відображати ім\'я клієнта',
                    'display-date'            => 'Відображати дату',
                    'display-discount-amt'    => 'Відображати суму знижки',
                    'display-order-id'        => 'Відображати ID замовлення',
                    'display-outlet-address'  => 'Відображати адресу пункту продажу',
                    'display-outlet-name'     => 'Відображати назву пункту продажу',
                    'display-sub-total'       => 'Відображати підсумок',
                    'display-tax'             => 'Відображати податок',
                    'grand-total-label'       => 'Мітка загальної суми',
                    'order-id-label'          => 'Мітка ID замовлення',
                    'receipt-title'           => 'Назва квитанції',
                    'show-order-barcode'      => 'Показати штрих-код замовлення',
                    'show-print-confirmation' => 'Показати підтвердження друку',
                    'status'                  => 'Статус',
                    'sub-total-label'         => 'Мітка підсумку',
                    'tax-label'               => 'Мітка податку',
                    'title'                   => 'Загальна інформація',
                ],

                'logo' => [
                    'display-logo' => 'Відображати логотип',
                    'logo-alt'     => 'Альт текст логотипу',
                    'logo-height'  => 'Висота логотипу (в px)',
                    'logo-width'   => 'Ширина логотипу (в px)',
                    'title'        => 'Логотип',
                    'upload-logo'  => 'Завантажити логотип',
                ],

                'header' => [
                    'header-content' => 'Зміст заголовка',
                    'title'          => 'Заголовок',
                ],

                'footer' => [
                    'footer-content' => 'Зміст підвалу',
                    'title'          => 'Підвал',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Зберегти квитанцію',
                'title'     => 'Редагувати квитанцію',

                'general' => [
                    'cashier-name-label'      => 'Мітка імені касира',
                    'change-amount-label'     => 'Мітка суми решти',
                    'credit-amount-label'     => 'Мітка суми кредиту',
                    'discount-amt-label'      => 'Мітка суми знижки',
                    'display-cashier-name'    => 'Відображати ім\'я касира',
                    'display-change-amount'   => 'Відображати суму решти',
                    'display-credit-amount'   => 'Відображати суму кредиту',
                    'display-customer-name'   => 'Відображати ім\'я клієнта',
                    'display-date'            => 'Відображати дату',
                    'display-discount-amt'    => 'Відображати суму знижки',
                    'display-order-id'        => 'Відображати ID замовлення',
                    'display-outlet-address'  => 'Відображати адресу пункту продажу',
                    'display-outlet-name'     => 'Відображати назву пункту продажу',
                    'display-sub-total'       => 'Відображати підсумок',
                    'display-tax'             => 'Відображати податок',
                    'grand-total-label'       => 'Мітка загальної суми',
                    'order-id-label'          => 'Мітка ID замовлення',
                    'receipt-title'           => 'Назва квитанції',
                    'show-order-barcode'      => 'Показати штрих-код замовлення',
                    'show-print-confirmation' => 'Показати підтвердження друку',
                    'status'                  => 'Статус',
                    'sub-total-label'         => 'Мітка підсумку',
                    'tax-label'               => 'Мітка податку',
                    'title'                   => 'Загальна інформація',
                ],

                'logo' => [
                    'display-logo' => 'Відображати логотип',
                    'logo-alt'     => 'Альт текст логотипу',
                    'logo-height'  => 'Висота логотипу (в px)',
                    'logo-width'   => 'Ширина логотипу (в px)',
                    'title'        => 'Логотип',
                    'upload-logo'  => 'Завантажити логотип',
                ],

                'header' => [
                    'header-content' => 'Зміст заголовка',
                    'title'          => 'Заголовок',
                ],

                'footer' => [
                    'footer-content' => 'Зміст підвалу',
                    'title'          => 'Підвал',
                ],
            ],

            'preview' => [
                'amount'         => 'Сума',
                'cashier'        => 'Касир',
                'change-amount'  => 'Сума решти',
                'customer'       => 'Клієнт',
                'customer-email' => 'Електронна пошта клієнта',
                'customer-name'  => 'Ім\'я клієнта',
                'customer-phone' => 'Телефон клієнта',
                'date'           => 'Дата',
                'discount'       => 'Знижка',
                'email'          => 'Електронна пошта',
                'grand-total'    => 'Загальна сума',
                'order-id'       => 'ID замовлення',
                'phone'          => 'Телефон',
                'price'          => 'Ціна',
                'product'        => 'Продукт',
                'qty'            => 'Кількість',
                'sub-total'      => 'Підсумок',
                'tax'            => 'Податок',
                'title'          => 'Попередній перегляд квитанції',
                'total-qty'      => 'Загальна кількість',
            ],

            'create-success' => 'Квитанцію успішно створено!',
            'delete-failed'  => 'Не вдалося видалити квитанцію!',
            'delete-success' => 'Квитанцію успішно видалено!',
            'update-success' => 'Квитанцію успішно оновлено!',
        ],
    ],

    'emails' => [
        'dear'     => 'Шановний :name',
        'greeting' => 'Вітаємо!',

        'registration' => [
            'message' => 'Вітаємо! Ваш акаунт успішно створено. Будь ласка, увійдіть до свого акаунту та почніть використовувати систему POS.',
            'subject' => 'Лист реєстрації користувача POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Увійти',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
