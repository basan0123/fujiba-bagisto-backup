<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Неверные учетные данные.',
                'not-activated'       => 'Ваша учетная запись не активирована.',
                'verify-first'        => 'Пожалуйста, сначала подтвердите ваш адрес электронной почты.',
                'success'             => 'Вы успешно вошли в систему.',
            ],

            'logout' => [
                'no-login-agent' => 'Нет активного агента.',
                'success'        => 'Вы успешно вышли из системы.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'Введенный пароль неверен.',
                    'success'          => 'Ваша учетная запись успешно обновлена.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Клиент успешно создан!',
            'update-success' => 'Клиент успешно обновлен!',
            'delete-success' => 'Клиент успешно удален!',
            'delete-failed'  => 'Не удалось удалить клиента!',
            'pending-orders' => 'У клиента есть незавершенные заказы!',
        ],

        'cart' => [
            'already-applied'     => 'Купон уже применен!',
            'coupon-applied'      => 'Купон успешно применен!',
            'coupon-removed'      => 'Купон успешно удален!',
            'create-success'      => 'Корзина успешно создана!',
            'invalid-coupon'      => 'Неверный код купона!',
            'item-add-success'    => 'Товар успешно добавлен в корзину!',
            'item-remove-success' => 'Товар успешно удален из корзины!',
            'item-update-success' => 'Товар успешно обновлен!',
            'not-found'           => 'Корзина не найдена!',
        ],

        'payment' => [
            'title' => 'Оплата Pos',

            'options' => [
                'cash' => [
                    'title'       => 'Оплата наличными Pos',
                    'description' => 'Это оплата наличными Pos.',
                ],

                'card' => [
                    'title'       => 'Оплата картой Pos',
                    'description' => 'Это оплата картой Pos.',
                ],

                'split' => [
                    'title'       => 'Разделенная оплата Pos',
                    'description' => 'Это разделенная оплата Pos.',
                ],
            ],

            'no-items' => 'Нет товаров в корзине для продолжения оплаты.',
            'success'  => 'Оплата успешно завершена!',
        ],

        'shipping' => [
            'title'       => 'Доставка Pos',
            'description' => 'Это бесплатная доставка Pos.',
        ],

        'order' => [
            'sync-success' => 'Заказ успешно синхронизирован!',
        ],

        'return' => [
            'create-success'              => 'Возврат успешно создан',
            'creation-error'              => 'Создание возврата не разрешено.',
            'invalid-qty'                 => 'Обнаружено недопустимое количество для выставления счета.',
            'invalid-refund-amount-error' => 'Сумма возврата должна быть больше нуля.',
            'refund-limit-error'          => 'Сумма возврата :amount не может быть обработана.',
        ],

        'drawer' => [
            'create-success' => 'Выдвижной ящик успешно открыт!',
            'not-opened'     => 'Выдвижной ящик не открыт.',
            'close-success'  => 'Выдвижной ящик успешно закрыт!',
        ],

        'products' => [
            'request-success' => 'Запрос на товар успешно отправлен.',
            'create-success'  => 'Товар успешно создан!',
        ],

        'reports' => [
            'orders'                  => 'Заказы',
            'average-order-value'     => 'Среднее значение заказа',
            'average-items-per-order' => 'Среднее количество товаров на заказ',
            'discounted-offers'       => 'Скидочные предложения',
            'cash-payments'           => 'Наличные платежи',
            'other-payments'          => 'Другие платежи',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Примечание к заказу',
                    'note-not-available' => 'Примечание недоступно!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'Расширение точки продажи (POS) от Bagisto.',
                    'title' => 'Точка Продажи',

                    'settings' => [
                        'info'  => 'Включите POS, Настройте Общие Параметры, Продукты POS и Квитанцию.',
                        'title' => 'Настройки',

                        'general' => [
                            'footer-content'       => 'Контент Подвала',
                            'footer-note'          => 'Примечание Подвала',
                            'frontend-url'         => 'URL фронтенда',
                            'heading-on-login'     => 'Заголовок при Входе',
                            'info'                 => 'Общие настройки позволяют настроить страницу пользователя POS, добавив логотип, заголовки, контент подвала, примечания подвала и т.д.',
                            'pos-logo'             => 'Логотип POS',
                            'status'               => 'Статус',
                            'sub-heading-on-login' => 'Подзаголовок при Входе',
                            'title'                => 'Общее',
                        ],

                        'barcode' => [
                            'height'             => 'Высота',
                            'hide-barcode'       => 'Скрыть Штрихкод',
                            'info'               => 'Настройки штрихкода позволяют настроить генерацию штрихкодов, высоту штрихкода, ширину штрихкода, тип штрихкода и т.д.',
                            'prefix'             => 'Префикс',
                            'print-product-name' => 'Печать Названия Продукта',
                            'title'              => 'Штрихкод',
                            'width'              => 'Ширина',

                            'generate-with' => [
                                'title' => 'Генерировать Штрихкод С',

                                'options' => [
                                    'product-id' => 'ID Продукта',
                                    'sku'        => 'SKU Продукта',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'Разрешить SKU',
                            'info'      => 'Настройки продукта позволяют настроить SKU продукта.',
                            'title'     => 'Продукты',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Назначить продукты',
            'banks'            => 'Банки',
            'barcode-products' => 'Продукты с Штрихкодом',
            'create'           => 'Создать',
            'delete'           => 'Удалить',
            'edit'             => 'Редактировать',
            'generate-barcode' => 'Сгенерировать Штрихкод',
            'orders'           => 'Заказы',
            'outlets'          => 'Торговые Точки',
            'pos'              => 'Торговая Точка (POS)',
            'preview'          => 'Предпросмотр',
            'print-barcode'    => 'Распечатать Штрихкод',
            'receipts'         => 'Чеки',
            'requests'         => 'Запросы',
            'sales-report'     => 'Отчет о продажах',
            'users'            => 'Агенты',
            'view'             => 'Просмотр',
        ],

        'layouts' => [
            'banks'            => 'Банки',
            'barcode-products' => 'Продукты с Штрихкодом',
            'orders'           => 'Заказы',
            'pos'              => 'Торговая Точка (POS)',
            'receipts'         => 'Чеки',
            'requests'         => 'Запросы',
            'sales-report'     => 'Отчет о продажах',

            'users' => [
                'agents'   => 'Агенты',
                'outlets'  => 'Торговые Точки',
                'title'    => 'Агенты',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Создать агентов',
                    'pos-front'  => 'POS фронт',
                    'title'      => 'Агенты',

                    'datagrid' => [
                        'action'              => 'Действие',
                        'delete'              => 'Удалить',
                        'edit'                => 'Редактировать',
                        'email'               => 'Электронная почта',
                        'full-name'           => 'Полное имя',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'Выбранные агенты успешно удалены!',
                        'mass-update-success' => 'Выбранные агенты успешно обновлены!',
                        'outlet-name'         => 'Название точки',
                        'profile-image'       => 'Изображение профиля',
                        'update-status'       => 'Обновить статус',
                        'username'            => 'Имя пользователя',

                        'status' => [
                            'title' => 'Статус',

                            'options' => [
                                'active'  => 'Активен',
                                'disable' => 'Отключен',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Назад',
                    'confirm-password'  => 'Подтвердите пароль',
                    'email'             => 'Электронная почта',
                    'first-name'        => 'Имя',
                    'general'           => 'Общие',
                    'image'             => 'Изображение',
                    'last-name'         => 'Фамилия',
                    'outlet'            => 'Точка продажи',
                    'outlet-and-status' => 'Точка продажи и статус',
                    'password'          => 'Пароль',
                    'save-btn'          => 'Сохранить агента',
                    'select-outlet'     => 'Выберите точку продажи',
                    'status'            => 'Статус',
                    'title'             => 'Добавить агента',
                    'user-image'        => 'Загрузить изображение агента',
                    'username'          => 'Имя пользователя',
                ],

                'edit' => [
                    'back-btn'          => 'Назад',
                    'confirm-password'  => 'Подтвердите пароль',
                    'email'             => 'Электронная почта',
                    'first-name'        => 'Имя',
                    'general'           => 'Общие',
                    'image'             => 'Изображение',
                    'last-name'         => 'Фамилия',
                    'outlet'            => 'Точка продажи',
                    'outlet-and-status' => 'Точка продажи и статус',
                    'password'          => 'Пароль',
                    'save-btn'          => 'Сохранить агента',
                    'select-outlet'     => 'Выберите точку продажи',
                    'status'            => 'Статус',
                    'title'             => 'Редактировать агента',
                    'user-image'        => 'Загрузить изображение агента',
                    'username'          => 'Имя пользователя',
                ],

                'create-success' => 'Агент успешно создан!',
                'delete-failed'  => 'Не удалось удалить агента!',
                'delete-success' => 'Агент успешно удален!',
                'update-success' => 'Агент успешно обновлен!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Создать точку продажи',
                    'pos-front'  => 'POS фронт',
                    'title'      => 'Точки продажи',

                    'datagrid' => [
                        'action'              => 'Действие',
                        'active'              => 'Активен',
                        'assign'              => 'Назначить продукт',
                        'delete'              => 'Удалить',
                        'edit'                => 'Редактировать',
                        'id'                  => 'ID',
                        'inactive'            => 'Неактивен',
                        'inventory-source'    => 'Источник инвентаря',
                        'mass-delete-success' => 'Выбранные магазины успешно удалены!',
                        'mass-update-success' => 'Выбранные магазины успешно обновлены!',
                        'name'                => 'Название',
                        'receipt-title'       => 'Название чека',
                        'status'              => 'Статус',
                        'title'               => 'Список POS магазинов',
                        'update-status'       => 'Обновить статус',
                    ],
                ],

                'create' => [
                    'address'                 => 'Адрес',
                    'back-btn'                => 'Назад',
                    'btn-title'               => 'Сохранить точку продажи',
                    'city'                    => 'Город',
                    'country'                 => 'Страна',
                    'customer-care-number'    => 'Номер службы поддержки клиентов',
                    'email'                   => 'Электронная почта',
                    'general'                 => 'Общие',
                    'gst-number'              => 'Номер GST',
                    'inventory'               => 'Инвентарь',
                    'inventory-source'        => 'Источник инвентаря',
                    'low-stock-qty'           => 'Количество при низком запасе',
                    'name'                    => 'Название точки продажи',
                    'phone'                   => 'Телефон',
                    'postcode'                => 'Почтовый индекс',
                    'receipt'                 => 'Квитанция',
                    'select-country'          => 'Выберите страну',
                    'select-inventory-source' => 'Выберите источник инвентаря',
                    'select-receipt'          => 'Выберите квитанцию',
                    'state'                   => 'Штат',
                    'status'                  => 'Статус',
                    'store-address'           => 'Адрес точки продажи',
                    'title'                   => 'Добавить точку продажи',
                    'website'                 => 'Веб-сайт',
                ],

                'edit' => [
                    'address'                 => 'Адрес',
                    'back-btn'                => 'Назад',
                    'btn-title'               => 'Сохранить точку продажи',
                    'city'                    => 'Город',
                    'country'                 => 'Страна',
                    'customer-care-number'    => 'Номер службы поддержки клиентов',
                    'email'                   => 'Электронная почта',
                    'general'                 => 'Общие',
                    'gst-number'              => 'Номер GST',
                    'inventory'               => 'Инвентарь',
                    'inventory-source'        => 'Источник инвентаря',
                    'low-stock-qty'           => 'Количество при низком запасе',
                    'name'                    => 'Название точки продажи',
                    'phone'                   => 'Телефон',
                    'postcode'                => 'Почтовый индекс',
                    'receipt'                 => 'Квитанция',
                    'select-country'          => 'Выберите страну',
                    'select-inventory-source' => 'Выберите источник инвентаря',
                    'select-receipt'          => 'Выберите квитанцию',
                    'state'                   => 'Штат',
                    'status'                  => 'Статус',
                    'store-address'           => 'Адрес точки продажи',
                    'title'                   => 'Редактировать точку продажи',
                    'website'                 => 'Веб-сайт',
                ],

                'assign' => [
                    'back-btn' => 'Назад',
                    'title'    => 'Управление продуктами точки продажи',

                    'datagrid' => [
                        'active'              => 'Активен',
                        'assign'              => 'Назначить',
                        'disable'             => 'Отключить',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'image'               => 'Изображение',
                        'mass-assign-success' => 'Назначение продуктов обновлено успешно!',
                        'name'                => 'Название',
                        'out-of-stock'        => 'Нет в наличии',
                        'pos-status'          => 'Статус POS',
                        'price'               => 'Цена',
                        'product-image'       => 'Изображение продукта',
                        'qty'                 => 'Количество',
                        'qty-value'           => ':qty доступно',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Статус',
                        'type'                => 'Тип',
                        'unassign'            => 'Убрать назначение',
                        'update-assign'       => 'Обновить назначение',
                    ],
                ],

                'create-success' => 'Точка продажи успешно создана!',
                'delete-failed'  => 'Не удалось удалить точку продажи!',
                'delete-success' => 'Точка продажи успешно удалена!',
                'update-success' => 'Точка продажи успешно обновлена!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Продукты с Штрихкодом',

                'datagrid' => [
                    'barcode'               => 'Штрих-код',
                    'generate-barcode'      => 'Сгенерировать штрих-код',
                    'print-barcode'         => 'Распечатать штрих-код',
                    'id'                    => 'ID',
                    'id-value'              => 'ID - :id',
                    'image'                 => 'Изображение',
                    'mass-generate-success' => 'Штрих-коды для выбранных продуктов успешно сгенерированы!',
                    'name'                  => 'Название',
                    'out-of-stock'          => 'Нет в наличии',
                    'price'                 => 'Цена',
                    'product-image'         => 'Изображение продукта',
                    'qty'                   => 'Количество',
                    'qty-value'             => ':qty доступно',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'active'  => 'Активен',
                            'disable' => 'Отключен',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Распечатать',
                'qty'       => 'Количество',
                'title'     => 'Распечатать штрих-код',
            ],

            'generate-failed'  => 'Ошибка генерации штрих-кода!',
            'generate-success' => 'Штрих-код успешно сгенерирован!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Заказы',

                'datagrid' => [
                    'customer-name' => 'Имя клиента',
                    'grand-total'   => 'Итоговая сумма',
                    'order-date'    => 'Дата заказа',
                    'order-id'      => 'ID заказа',
                    'order-ref-id'  => 'ID ссылки заказа',
                    'view'          => 'Просмотр',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'canceled'        => 'Отменен',
                            'closed'          => 'Закрыт',
                            'completed'       => 'Завершен',
                            'fraud'           => 'Мошенничество',
                            'pending'         => 'В ожидании',
                            'pending-payment' => 'В ожидании оплаты',
                            'processing'      => 'В обработке',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Запросы',

                'datagrid' => [
                    'id'                  => 'ID',
                    'product-image'       => 'Изображение продукта',
                    'mass-update-error'   => 'Ошибка обновления запроса!',
                    'mass-update-success' => 'Выбранные запросы успешно обновлены!',
                    'product-name'        => 'Название продукта',
                    'outlet-name'         => 'Название точки продажи',
                    'qty-value'           => 'Количество - :qty',
                    'request-date'        => 'Дата запроса',
                    'requested-qty'       => 'Запрашиваемое количество',
                    'update-status'       => 'Обновить статус',
                    'user-name'           => 'Имя пользователя',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'complete' => 'Завершен',
                            'decline'  => 'Отклонен',
                            'pending'  => 'В ожидании',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Сохранить',
                'title'     => 'Детали запрашиваемого продукта #:id',

                'user-info' => [
                    'email'            => 'Электронная почта',
                    'name'             => 'Имя',
                    'outlet-address'   => 'Адрес точки продажи',
                    'outlet-inventory' => 'Источник инвентаря точки продажи',
                    'outlet-name'      => 'Название точки продажи',
                    'title'            => 'Информация о пользователе',
                ],

                'request-info' => [
                    'comment'       => 'Комментарий',
                    'product-name'  => 'Название продукта',
                    'qty-value'     => 'Количество - :qty',
                    'request-date'  => 'Дата запроса',
                    'requested-qty' => 'Запрашиваемое количество',
                    'title'         => 'Информация о запросе',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'complete' => 'Завершен',
                            'decline'  => 'Отклонен',
                            'pending'  => 'В ожидании',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Ошибка обновления запроса!',
            'update-success' => 'Запрос успешно обновлен!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Создать банк',
                'title'     => 'Банки',

                'datagrid' => [
                    'active'              => 'Активный',
                    'address'             => 'Адрес банка',
                    'agent-name'          => 'Агент',
                    'delete'              => 'Удалить',
                    'disable'             => 'Отключить',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Выбранные банки успешно удалены!',
                    'name'                => 'Название банка',
                    'status'              => 'Статус',
                ],
            ],

            'create' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Сохранить банк',
                'title'     => 'Создать новый банк',

                'general' => [
                    'address' => 'Адрес',
                    'email'   => 'Электронная почта',
                    'name'    => 'Название',
                    'phone'   => 'Телефон',
                    'title'   => 'Общие',
                ],

                'agent-and-status' => [
                    'agent'        => 'Назначить агента POS',
                    'bank-status'  => 'Статус банка',
                    'select-agent' => 'Выбрать агента',
                    'title'        => 'Агент POS и статус банка',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Сохранить банк',
                'title'     => 'Редактировать банк',

                'general' => [
                    'address' => 'Адрес',
                    'email'   => 'Электронная почта',
                    'name'    => 'Название',
                    'phone'   => 'Телефон',
                    'title'   => 'Общие',
                ],

                'agent-and-status' => [
                    'agent'        => 'Назначить агента POS',
                    'bank-status'  => 'Статус банка',
                    'select-agent' => 'Выбрать агента',
                    'title'        => 'Агент POS и статус банка',
                ],
            ],

            'create-success' => 'Банк успешно создан!',
            'delete-failed'  => 'Ошибка удаления банка!',
            'delete-success' => 'Банк успешно удален!',
            'update-success' => 'Банк успешно обновлен!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Отчет о продажах',

                'datagrid' => [
                    'bank-name'      => 'Название банка',
                    'grand-total'    => 'Итоговая сумма',
                    'order-date'     => 'Дата заказа',
                    'order-id'       => 'ID заказа',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Примечание к заказу',
                    'outlet-name'    => 'Название точки продажи',
                    'payment-method' => 'Метод оплаты',
                    'view'           => 'Просмотр',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'canceled'        => 'Отменен',
                            'closed'          => 'Закрыт',
                            'completed'       => 'Завершен',
                            'fraud'           => 'Мошенничество',
                            'pending'         => 'В ожидании',
                            'pending-payment' => 'В ожидании оплаты',
                            'processing'      => 'В обработке',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Создать квитанцию',
                'title'      => 'Квитанции',

                'datagrid' => [
                    'delete'              => 'Удалить',
                    'edit'                => 'Редактировать',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Выбранные квитанции успешно удалены!',
                    'preview'             => 'Предварительный просмотр',
                    'title'               => 'Название',

                    'status' => [
                        'title' => 'Статус',

                        'options' => [
                            'active'   => 'Активен',
                            'inactive' => 'Неактивен',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Сохранить квитанцию',
                'title'     => 'Создать новую квитанцию',

                'general' => [
                    'cashier-name-label'      => 'Метка имени кассира',
                    'change-amount-label'     => 'Метка суммы сдачи',
                    'credit-amount-label'     => 'Метка суммы кредита',
                    'discount-amt-label'      => 'Метка суммы скидки',
                    'display-cashier-name'    => 'Отображать имя кассира',
                    'display-change-amount'   => 'Отображать сумму сдачи',
                    'display-credit-amount'   => 'Отображать сумму кредита',
                    'display-customer-name'   => 'Отображать имя клиента',
                    'display-date'            => 'Отображать дату',
                    'display-discount-amt'    => 'Отображать сумму скидки',
                    'display-order-id'        => 'Отображать ID заказа',
                    'display-outlet-address'  => 'Отображать адрес точки продажи',
                    'display-outlet-name'     => 'Отображать название точки продажи',
                    'display-sub-total'       => 'Отображать промежуточный итог',
                    'display-tax'             => 'Отображать налог',
                    'grand-total-label'       => 'Метка итоговой суммы',
                    'order-id-label'          => 'Метка ID заказа',
                    'receipt-title'           => 'Название квитанции',
                    'show-order-barcode'      => 'Показать штрих-код заказа',
                    'show-print-confirmation' => 'Показать подтверждение печати',
                    'status'                  => 'Статус',
                    'sub-total-label'         => 'Метка промежуточного итога',
                    'tax-label'               => 'Метка налога',
                    'title'                   => 'Общие',
                ],

                'logo' => [
                    'display-logo' => 'Отображать логотип',
                    'logo-alt'     => 'Альтернативный текст логотипа',
                    'logo-height'  => 'Высота логотипа (в пикселях)',
                    'logo-width'   => 'Ширина логотипа (в пикселях)',
                    'title'        => 'Логотип',
                    'upload-logo'  => 'Загрузить логотип',
                ],

                'header' => [
                    'header-content' => 'Содержимое заголовка',
                    'title'          => 'Заголовок',
                ],

                'footer' => [
                    'footer-content' => 'Содержимое подвала',
                    'title'          => 'Подвал',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Назад',
                'btn-title' => 'Сохранить квитанцию',
                'title'     => 'Редактировать квитанцию',

                'general' => [
                    'cashier-name-label'      => 'Метка имени кассира',
                    'change-amount-label'     => 'Метка суммы сдачи',
                    'credit-amount-label'     => 'Метка суммы кредита',
                    'discount-amt-label'      => 'Метка суммы скидки',
                    'display-cashier-name'    => 'Отображать имя кассира',
                    'display-change-amount'   => 'Отображать сумму сдачи',
                    'display-credit-amount'   => 'Отображать сумму кредита',
                    'display-customer-name'   => 'Отображать имя клиента',
                    'display-date'            => 'Отображать дату',
                    'display-discount-amt'    => 'Отображать сумму скидки',
                    'display-order-id'        => 'Отображать ID заказа',
                    'display-outlet-address'  => 'Отображать адрес точки продажи',
                    'display-outlet-name'     => 'Отображать название точки продажи',
                    'display-sub-total'       => 'Отображать промежуточный итог',
                    'display-tax'             => 'Отображать налог',
                    'grand-total-label'       => 'Метка итоговой суммы',
                    'order-id-label'          => 'Метка ID заказа',
                    'receipt-title'           => 'Название квитанции',
                    'show-order-barcode'      => 'Показать штрих-код заказа',
                    'show-print-confirmation' => 'Показать подтверждение печати',
                    'status'                  => 'Статус',
                    'sub-total-label'         => 'Метка промежуточного итога',
                    'tax-label'               => 'Метка налога',
                    'title'                   => 'Общие',
                ],

                'logo' => [
                    'display-logo' => 'Отображать логотип',
                    'logo-alt'     => 'Альтернативный текст логотипа',
                    'logo-height'  => 'Высота логотипа (в пикселях)',
                    'logo-width'   => 'Ширина логотипа (в пикселях)',
                    'title'        => 'Логотип',
                    'upload-logo'  => 'Загрузить логотип',
                ],

                'header' => [
                    'header-content' => 'Содержимое заголовка',
                    'title'          => 'Заголовок',
                ],

                'footer' => [
                    'footer-content' => 'Содержимое подвала',
                    'title'          => 'Подвал',
                ],
            ],

            'preview' => [
                'amount'         => 'Сумма',
                'cashier'        => 'Кассир',
                'change-amount'  => 'Сдача',
                'customer'       => 'Клиент',
                'customer-email' => 'Электронная почта клиента',
                'customer-name'  => 'Имя клиента',
                'customer-phone' => 'Телефон клиента',
                'date'           => 'Дата',
                'discount'       => 'Скидка',
                'email'          => 'Электронная почта',
                'grand-total'    => 'Итоговая сумма',
                'order-id'       => 'ID заказа',
                'phone'          => 'Телефон',
                'price'          => 'Цена',
                'product'        => 'Продукт',
                'qty'            => 'Кол-во',
                'sub-total'      => 'Промежуточный итог',
                'tax'            => 'Налог',
                'title'          => 'Предварительный просмотр квитанции',
                'total-qty'      => 'Общее количество',
            ],

            'create-success' => 'Квитанция успешно создана!',
            'delete-failed'  => 'Ошибка удаления квитанции!',
            'delete-success' => 'Квитанция успешно удалена!',
            'update-success' => 'Квитанция успешно обновлена!',
        ],
    ],

    'emails' => [
        'dear'     => 'Уважаемый :name',
        'greeting' => 'Здравствуйте!',

        'registration' => [
            'message' => 'Поздравляем! Ваша учетная запись успешно создана. Пожалуйста, войдите в свою учетную запись и начните использовать систему POS.',
            'subject' => 'Письмо о регистрации пользователя POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Войти',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
