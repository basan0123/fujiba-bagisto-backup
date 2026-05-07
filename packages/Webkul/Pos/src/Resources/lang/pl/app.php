<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Nieprawidłowe dane logowania.',
                'not-activated'       => 'Twoje konto nie jest aktywowane.',
                'verify-first'        => 'Najpierw zweryfikuj swój e-mail.',
                'success'             => 'Pomyślnie zalogowano.',
            ],

            'logout' => [
                'no-login-agent' => 'Żaden agent nie jest zalogowany.',
                'success'        => 'Pomyślnie wylogowano.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'Wprowadzone hasło jest nieprawidłowe.',
                    'success'          => 'Twoje konto zostało pomyślnie zaktualizowane.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Klient został pomyślnie utworzony!',
            'update-success' => 'Klient został pomyślnie zaktualizowany!',
            'delete-success' => 'Klient został pomyślnie usunięty!',
            'delete-failed'  => 'Usunięcie klienta nie powiodło się!',
            'pending-orders' => 'Klient ma zaległe zamówienia!',
        ],

        'cart' => [
            'already-applied'     => 'Kupon został już zastosowany!',
            'coupon-applied'      => 'Kupon został pomyślnie zastosowany!',
            'coupon-removed'      => 'Kupon został pomyślnie usunięty!',
            'create-success'      => 'Koszyk został pomyślnie utworzony!',
            'invalid-coupon'      => 'Nieprawidłowy kod kuponu!',
            'item-add-success'    => 'Produkt został pomyślnie dodany do koszyka!',
            'item-remove-success' => 'Produkt został pomyślnie usunięty z koszyka!',
            'item-update-success' => 'Produkt został pomyślnie zaktualizowany!',
            'not-found'           => 'Koszyk nie został znaleziony!',
        ],

        'payment' => [
            'title' => 'Płatność Pos',

            'options' => [
                'cash' => [
                    'title'       => 'Płatność Gotówkowa Pos',
                    'description' => 'To jest płatność gotówkowa Pos.',
                ],

                'card' => [
                    'title'       => 'Płatność Kartą Pos',
                    'description' => 'To jest płatność kartą Pos.',
                ],

                'split' => [
                    'title'       => 'Płatność Podzielona Pos',
                    'description' => 'To jest płatność podzielona Pos.',
                ],
            ],

            'no-items' => 'Brak przedmiotów w koszyku do realizacji płatności.',
            'success'  => 'Płatność zakończona pomyślnie!',
        ],

        'shipping' => [
            'title'       => 'Wysyłka Pos',
            'description' => 'To jest darmowa wysyłka Pos.',
        ],

        'order' => [
            'sync-success' => 'Zamówienie zostało pomyślnie zsynchronizowane!',
        ],

        'return' => [
            'create-success'              => 'Zwrot został pomyślnie utworzony',
            'creation-error'              => 'Tworzenie zwrotu nie jest dozwolone.',
            'invalid-qty'                 => 'Znaleziono nieprawidłową ilość do fakturowania przedmiotów.',
            'invalid-refund-amount-error' => 'Kwota zwrotu musi być większa niż zero.',
            'refund-limit-error'          => 'Kwota zwrotu :amount nie może zostać przetworzona.',
        ],

        'drawer' => [
            'create-success' => 'Szuflada otwarta pomyślnie!',
            'not-opened'     => 'Szuflada nie jest otwarta.',
            'close-success'  => 'Szuflada została pomyślnie zamknięta!',
        ],

        'products' => [
            'request-success' => 'Prośba o produkt została pomyślnie wysłana.',
            'create-success'  => 'Produkt został pomyślnie utworzony!',
        ],

        'reports' => [
            'orders'                  => 'Zamówienia',
            'average-order-value'     => 'Średnia Wartość Zamówienia',
            'average-items-per-order' => 'Średnia Liczba Przedmiotów na Zamówienie',
            'discounted-offers'       => 'Oferty z Rabatem',
            'cash-payments'           => 'Płatności Gotówkowe',
            'other-payments'          => 'Inne Płatności',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Notatka do zamówienia',
                    'note-not-available' => 'Notatka niedostępna!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'Rozszerzenie Punktu Sprzedaży (POS) Bagisto.',
                    'title' => 'Punkt Sprzedaży',

                    'settings' => [
                        'info'  => 'Włącz POS, Ustaw konfigurację ogólną, Produkt POS i Paragon.',
                        'title' => 'Ustawienia',

                        'general' => [
                            'footer-content'       => 'Treść Stopki',
                            'footer-note'          => 'Notatka Stopki',
                            'frontend-url'         => 'URL Frontend',
                            'heading-on-login'     => 'Nagłówek przy logowaniu',
                            'info'                 => 'Ustawienia ogólne umożliwiają konfigurację strony użytkownika POS, poprzez dodanie logo, nagłówków, treści stopki, notatek stopki itp.',
                            'pos-logo'             => 'Logo POS',
                            'status'               => 'Status',
                            'sub-heading-on-login' => 'Podnagłówek przy logowaniu',
                            'title'                => 'Ogólne',
                        ],

                        'barcode' => [
                            'height'             => 'Wysokość',
                            'hide-barcode'       => 'Ukryj Kod Kreskowy',
                            'info'               => 'Ustawienia kodu kreskowego umożliwiają konfigurację generowania kodów kreskowych, wysokości kodu kreskowego, szerokości kodu kreskowego, typu kodu kreskowego itp.',
                            'prefix'             => 'Prefiks',
                            'print-product-name' => 'Drukuj Nazwę Produktu',
                            'title'              => 'Kod Kreskowy',
                            'width'              => 'Szerokość',

                            'generate-with' => [
                                'title' => 'Generuj Kod Kreskowy Z',

                                'options' => [
                                    'product-id' => 'ID Produktu',
                                    'sku'        => 'SKU Produktu',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'Zezwalaj na SKU dla produktu niestandardowego',
                            'info'      => 'Ustawienia produktów umożliwiają konfigurację SKU produktu.',
                            'title'     => 'Produkty',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Przypisz Produkty',
            'banks'            => 'Banki',
            'barcode-products' => 'Produkty z Kodem Kreskowym',
            'create'           => 'Utwórz',
            'delete'           => 'Usuń',
            'edit'             => 'Edytuj',
            'generate-barcode' => 'Generuj Kod Kreskowy',
            'orders'           => 'Zamówienia',
            'outlets'          => 'Punkty Sprzedaży',
            'pos'              => 'Punkt Sprzedaży (POS)',
            'preview'          => 'Podgląd',
            'print-barcode'    => 'Drukuj Kod Kreskowy',
            'receipts'         => 'Paragon',
            'requests'         => 'Żądania',
            'sales-report'     => 'Raport sprzedaży',
            'users'            => 'Agenci',
            'view'             => 'Podgląd',
        ],

        'layouts' => [
            'banks'            => 'Banki',
            'barcode-products' => 'Produkty z Kodem Kreskowym',
            'orders'           => 'Zamówienia',
            'pos'              => 'Punkt Sprzedaży (POS)',
            'receipts'         => 'Paragon',
            'requests'         => 'Żądania',
            'sales-report'     => 'Raport sprzedaży',

            'users' => [
                'agents'   => 'Agenci',
                'outlets'  => 'Punkty Sprzedaży',
                'title'    => 'Agenci',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Utwórz Agentów',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Agenci',

                    'datagrid' => [
                        'action'              => 'Akcja',
                        'delete'              => 'Usuń',
                        'edit'                => 'Edytuj',
                        'email'               => 'E-mail',
                        'full-name'           => 'Pełne imię i nazwisko',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'Wybrani agenci zostali pomyślnie usunięci!',
                        'mass-update-success' => 'Wybrani agenci zostali pomyślnie zaktualizowani!',
                        'outlet-name'         => 'Nazwa punktu',
                        'profile-image'       => 'Zdjęcie profilowe',
                        'update-status'       => 'Aktualizuj status',
                        'username'            => 'Nazwa użytkownika',

                        'status' => [
                            'title' => 'Status',

                            'options' => [
                                'active'  => 'Aktywny',
                                'disable' => 'Nieaktywny',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Wstecz',
                    'confirm-password'  => 'Potwierdź Hasło',
                    'email'             => 'Email',
                    'first-name'        => 'Imię',
                    'general'           => 'Ogólne',
                    'image'             => 'Obrazek',
                    'last-name'         => 'Nazwisko',
                    'outlet'            => 'Punkt',
                    'outlet-and-status' => 'Punkt & Status',
                    'password'          => 'Hasło',
                    'save-btn'          => 'Zapisz Agenta',
                    'select-outlet'     => 'Wybierz Punkt',
                    'status'            => 'Status',
                    'title'             => 'Dodaj Agenta',
                    'user-image'        => 'Prześlij Obrazek Agenta',
                    'username'          => 'Nazwa Użytkownika',
                ],

                'edit' => [
                    'back-btn'          => 'Wstecz',
                    'confirm-password'  => 'Potwierdź Hasło',
                    'email'             => 'Email',
                    'first-name'        => 'Imię',
                    'general'           => 'Ogólne',
                    'image'             => 'Obrazek',
                    'last-name'         => 'Nazwisko',
                    'outlet'            => 'Punkt',
                    'outlet-and-status' => 'Punkt & Status',
                    'password'          => 'Hasło',
                    'save-btn'          => 'Zapisz Agenta',
                    'select-outlet'     => 'Wybierz Punkt',
                    'status'            => 'Status',
                    'title'             => 'Edytuj Agenta',
                    'user-image'        => 'Prześlij Obrazek Agenta',
                    'username'          => 'Nazwa Użytkownika',
                ],

                'create-success' => 'Agent został pomyślnie utworzony!',
                'delete-failed'  => 'Usunięcie Agenta nie powiodło się!',
                'delete-success' => 'Agent został pomyślnie usunięty!',
                'update-success' => 'Agent został pomyślnie zaktualizowany!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Utwórz Punkt',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Punkty',

                    'datagrid' => [
                        'action'              => 'Akcja',
                        'active'              => 'Aktywny',
                        'assign'              => 'Przypisz Produkt',
                        'delete'              => 'Usuń',
                        'edit'                => 'Edytuj',
                        'id'                  => 'ID',
                        'inactive'            => 'Nieaktywny',
                        'inventory-source'    => 'Źródło Inwentaryzacji',
                        'mass-delete-success' => 'Wybrane sklepy zostały pomyślnie usunięte!',
                        'mass-update-success' => 'Wybrane sklepy zostały pomyślnie zaktualizowane!',
                        'name'                => 'Nazwa',
                        'receipt-title'       => 'Tytuł Paragonu',
                        'status'              => 'Status',
                        'title'               => 'Lista Sklepów POS',
                        'update-status'       => 'Aktualizuj Status',
                    ],
                ],

                'create' => [
                    'address'                 => 'Adres',
                    'back-btn'                => 'Wstecz',
                    'btn-title'               => 'Zapisz Punkt',
                    'city'                    => 'Miasto',
                    'country'                 => 'Kraj',
                    'customer-care-number'    => 'Numer Obsługi Klienta',
                    'email'                   => 'Email',
                    'general'                 => 'Ogólne',
                    'gst-number'              => 'Numer GST',
                    'inventory'               => 'Inwentarz',
                    'inventory-source'        => 'Źródło Inwentarza',
                    'low-stock-qty'           => 'Niska Ilość Zapasów',
                    'name'                    => 'Nazwa Punktu',
                    'phone'                   => 'Telefon',
                    'postcode'                => 'Kod Pocztowy',
                    'receipt'                 => 'Paragon',
                    'select-country'          => 'Wybierz Kraj',
                    'select-inventory-source' => 'Wybierz Źródło Inwentarza',
                    'select-receipt'          => 'Wybierz Paragon',
                    'state'                   => 'Stan',
                    'status'                  => 'Status',
                    'store-address'           => 'Adres Punktu',
                    'title'                   => 'Dodaj Punkt',
                    'website'                 => 'Strona Internetowa',
                ],

                'edit' => [
                    'address'                 => 'Adres',
                    'back-btn'                => 'Wstecz',
                    'btn-title'               => 'Zapisz Punkt',
                    'city'                    => 'Miasto',
                    'country'                 => 'Kraj',
                    'customer-care-number'    => 'Numer Obsługi Klienta',
                    'email'                   => 'Email',
                    'general'                 => 'Ogólne',
                    'gst-number'              => 'Numer GST',
                    'inventory'               => 'Inwentarz',
                    'inventory-source'        => 'Źródło Inwentarza',
                    'low-stock-qty'           => 'Niska Ilość Zapasów',
                    'name'                    => 'Nazwa Punktu',
                    'phone'                   => 'Telefon',
                    'postcode'                => 'Kod Pocztowy',
                    'receipt'                 => 'Paragon',
                    'select-country'          => 'Wybierz Kraj',
                    'select-inventory-source' => 'Wybierz Źródło Inwentarza',
                    'select-receipt'          => 'Wybierz Paragon',
                    'state'                   => 'Stan',
                    'status'                  => 'Status',
                    'store-address'           => 'Adres Punktu',
                    'title'                   => 'Edytuj Punkt',
                    'website'                 => 'Strona Internetowa',
                ],

                'assign' => [
                    'back-btn' => 'Wstecz',
                    'title'    => 'Zarządzaj Produktami Punktów',

                    'datagrid' => [
                        'active'              => 'Aktywny',
                        'assign'              => 'Przypisz',
                        'disable'             => 'Dezaktywuj',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'image'               => 'Obrazek',
                        'mass-assign-success' => 'Przypisanie produktu zostało pomyślnie zaktualizowane!',
                        'name'                => 'Nazwa',
                        'out-of-stock'        => 'Brak w Magazynie',
                        'pos-status'          => 'Status POS',
                        'price'               => 'Cena',
                        'product-image'       => 'Obrazek Produktu',
                        'qty'                 => 'Ilość',
                        'qty-value'           => ':qty Dostępne',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Status',
                        'type'                => 'Typ',
                        'unassign'            => 'Odejmij',
                        'update-assign'       => 'Aktualizuj Przypisanie',
                    ],
                ],

                'create-success' => 'Punkt został pomyślnie utworzony!',
                'delete-failed'  => 'Usunięcie Punktu nie powiodło się!',
                'delete-success' => 'Punkt został pomyślnie usunięty!',
                'update-success' => 'Punkt został pomyślnie zaktualizowany!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Produkty z Kodem Kreskowym',

                'datagrid' => [
                    'barcode'               => 'Kod Kreskowy',
                    'generate-barcode'      => 'Generuj Kod Kreskowy',
                    'print-barcode'         => 'Drukuj Kod Kreskowy',
                    'id'                    => 'ID',
                    'id-value'              => 'ID - :id',
                    'image'                 => 'Obrazek',
                    'mass-generate-success' => 'Kody kreskowe dla wybranych produktów zostały pomyślnie wygenerowane!',
                    'name'                  => 'Nazwa',
                    'out-of-stock'          => 'Brak w Magazynie',
                    'price'                 => 'Cena',
                    'product-image'         => 'Obrazek Produktu',
                    'qty'                   => 'Ilość',
                    'qty-value'             => ':qty Dostępne',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'active'  => 'Aktywny',
                            'disable' => 'Nieaktywny',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'Wstecz',
                'btn-title' => 'Drukuj',
                'qty'       => 'Ilość',
                'title'     => 'Drukuj Kod Kreskowy',
            ],

            'generate-failed'  => 'Generowanie kodu kreskowego nie powiodło się!',
            'generate-success' => 'Kod kreskowy został pomyślnie wygenerowany!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Zamówienia',

                'datagrid' => [
                    'customer-name' => 'Imię Klienta',
                    'grand-total'   => 'Kwota Całkowita',
                    'order-date'    => 'Data Zamówienia',
                    'order-id'      => 'ID Zamówienia',
                    'order-ref-id'  => 'Ref. ID Zamówienia',
                    'view'          => 'Zobacz',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'canceled'        => 'Anulowane',
                            'closed'          => 'Zamknięte',
                            'completed'       => 'Zakończone',
                            'fraud'           => 'Oszustwo',
                            'pending'         => 'Oczekujące',
                            'pending-payment' => 'Oczekująca Płatność',
                            'processing'      => 'W Trakcie Realizacji',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Wnioski',

                'datagrid' => [
                    'id'                  => 'ID',
                    'product-image'       => 'Obrazek Produktu',
                    'mass-update-error'   => 'Aktualizacja wniosku nie powiodła się!',
                    'mass-update-success' => 'Wybrane wnioski zostały pomyślnie zaktualizowane!',
                    'product-name'        => 'Nazwa Produktu',
                    'outlet-name'         => 'Nazwa Punktu',
                    'qty-value'           => 'Ilość - :qty',
                    'request-date'        => 'Data Wniosku',
                    'requested-qty'       => 'Żądana Ilość',
                    'update-status'       => 'Aktualizuj Status',
                    'user-name'           => 'Imię Użytkownika',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'complete' => 'Zrealizowane',
                            'decline'  => 'Odrzucone',
                            'pending'  => 'Oczekujące',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Wstecz',
                'btn-title' => 'Zapisz',
                'title'     => 'Szczegóły Żądanego Produktu #:id',

                'user-info' => [
                    'email'            => 'Email',
                    'name'             => 'Imię',
                    'outlet-address'   => 'Adres Punktu',
                    'outlet-inventory' => 'Źródło Inwentaryzacji Punktu',
                    'outlet-name'      => 'Nazwa Punktu',
                    'title'            => 'Informacje o Użytkowniku',
                ],

                'request-info' => [
                    'comment'       => 'Komentarz',
                    'product-name'  => 'Nazwa Produktu',
                    'qty-value'     => 'Ilość - :qty',
                    'request-date'  => 'Data Wniosku',
                    'requested-qty' => 'Żądana Ilość',
                    'title'         => 'Informacje o Wniosku',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'complete' => 'Zrealizowane',
                            'decline'  => 'Odrzucone',
                            'pending'  => 'Oczekujące',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Aktualizacja wniosku nie powiodła się!',
            'update-success' => 'Wniosek został pomyślnie zaktualizowany!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Utwórz Bank',
                'title'     => 'Banki',

                'datagrid' => [
                    'active'              => 'Aktywny',
                    'address'             => 'Adres banku',
                    'agent-name'          => 'Agent',
                    'delete'              => 'Usuń',
                    'disable'             => 'Wyłącz',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Wybrane banki zostały pomyślnie usunięte!',
                    'name'                => 'Nazwa banku',
                    'status'              => 'Status',
                ],
            ],

            'create' => [
                'back-btn'  => 'Wstecz',
                'btn-title' => 'Zapisz Bank',
                'title'     => 'Utwórz Nowy Bank',

                'general' => [
                    'address' => 'Adres',
                    'email'   => 'Email',
                    'name'    => 'Nazwa',
                    'phone'   => 'Telefon',
                    'title'   => 'Ogólne',
                ],

                'agent-and-status' => [
                    'agent'        => 'Przypisz Agenta POS',
                    'bank-status'  => 'Status Banku',
                    'select-agent' => 'Wybierz Agenta',
                    'title'        => 'Agent POS & Status Banku',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Wstecz',
                'btn-title' => 'Zapisz Bank',
                'title'     => 'Edytuj Bank',

                'general' => [
                    'address' => 'Adres',
                    'email'   => 'Email',
                    'name'    => 'Nazwa',
                    'phone'   => 'Telefon',
                    'title'   => 'Ogólne',
                ],

                'agent-and-status' => [
                    'agent'        => 'Przypisz Agenta POS',
                    'bank-status'  => 'Status Banku',
                    'select-agent' => 'Wybierz Agenta',
                    'title'        => 'Agent POS & Status Banku',
                ],
            ],

            'create-success' => 'Bank został pomyślnie utworzony!',
            'delete-failed'  => 'Usuwanie banku nie powiodło się!',
            'delete-success' => 'Bank został pomyślnie usunięty!',
            'update-success' => 'Bank został pomyślnie zaktualizowany!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Raport sprzedaży',

                'datagrid' => [
                    'bank-name'      => 'Nazwa Banku',
                    'grand-total'    => 'Kwota Całkowita',
                    'order-date'     => 'Data Zamówienia',
                    'order-id'       => 'ID Zamówienia',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Notatka Zamówienia',
                    'outlet-name'    => 'Nazwa Punktu',
                    'payment-method' => 'Metoda Płatności',
                    'view'           => 'Zobacz',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'canceled'        => 'Anulowane',
                            'closed'          => 'Zamknięte',
                            'completed'       => 'Zakończone',
                            'fraud'           => 'Oszustwo',
                            'pending'         => 'Oczekujące',
                            'pending-payment' => 'Oczekująca Płatność',
                            'processing'      => 'W Trakcie Realizacji',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Utwórz Paragon',
                'title'      => 'Paragony',

                'datagrid' => [
                    'delete'              => 'Usuń',
                    'edit'                => 'Edytuj',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Wybrane paragony zostały pomyślnie usunięte!',
                    'preview'             => 'Podgląd',
                    'title'               => 'Tytuł',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'active'   => 'Aktywny',
                            'inactive' => 'Nieaktywny',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Wstecz',
                'btn-title' => 'Zapisz Paragon',
                'title'     => 'Utwórz Nowy Paragon',

                'general' => [
                    'cashier-name-label'      => 'Etykieta Nazwy Kasjera',
                    'change-amount-label'     => 'Etykieta Kwoty Wydania',
                    'credit-amount-label'     => 'Etykieta Kwoty Kredytu',
                    'discount-amt-label'      => 'Etykieta Kwoty Rabatu',
                    'display-cashier-name'    => 'Wyświetl Imię Kasjera',
                    'display-change-amount'   => 'Wyświetl Kwotę Wydania',
                    'display-credit-amount'   => 'Wyświetl Kwotę Kredytu',
                    'display-customer-name'   => 'Wyświetl Imię Klienta',
                    'display-date'            => 'Wyświetl Datę',
                    'display-discount-amt'    => 'Wyświetl Kwotę Rabatu',
                    'display-order-id'        => 'Wyświetl ID Zamówienia',
                    'display-outlet-address'  => 'Wyświetl Adres Punktu',
                    'display-outlet-name'     => 'Wyświetl Nazwę Punktu',
                    'display-sub-total'       => 'Wyświetl Suma Częściowa',
                    'display-tax'             => 'Wyświetl Podatek',
                    'grand-total-label'       => 'Etykieta Kwoty Całkowitej',
                    'order-id-label'          => 'Etykieta ID Zamówienia',
                    'receipt-title'           => 'Tytuł Paragonu',
                    'show-order-barcode'      => 'Pokaż kod kreskowy zamówienia',
                    'show-print-confirmation' => 'Pokaż potwierdzenie wydruku',
                    'status'                  => 'Status',
                    'sub-total-label'         => 'Etykieta Suma Częściowa',
                    'tax-label'               => 'Etykieta Podatek',
                    'title'                   => 'Ogólne',
                ],

                'logo' => [
                    'display-logo' => 'Wyświetl Logo',
                    'logo-alt'     => 'Alternatywna Nazwa Logo',
                    'logo-height'  => 'Wysokość Logo (w px)',
                    'logo-width'   => 'Szerokość Logo (w px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Prześlij Logo',
                ],

                'header' => [
                    'header-content' => 'Zawartość Nagłówka',
                    'title'          => 'Nagłówek',
                ],

                'footer' => [
                    'footer-content' => 'Zawartość Stopki',
                    'title'          => 'Stopka',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Wstecz',
                'btn-title' => 'Zapisz Paragon',
                'title'     => 'Edytuj Paragon',

                'general' => [
                    'cashier-name-label'      => 'Etykieta Nazwy Kasjera',
                    'change-amount-label'     => 'Etykieta Kwoty Wydania',
                    'credit-amount-label'     => 'Etykieta Kwoty Kredytu',
                    'discount-amt-label'      => 'Etykieta Kwoty Rabatu',
                    'display-cashier-name'    => 'Wyświetl Imię Kasjera',
                    'display-change-amount'   => 'Wyświetl Kwotę Wydania',
                    'display-credit-amount'   => 'Wyświetl Kwotę Kredytu',
                    'display-customer-name'   => 'Wyświetl Imię Klienta',
                    'display-date'            => 'Wyświetl Datę',
                    'display-discount-amt'    => 'Wyświetl Kwotę Rabatu',
                    'display-order-id'        => 'Wyświetl ID Zamówienia',
                    'display-outlet-address'  => 'Wyświetl Adres Punktu',
                    'display-outlet-name'     => 'Wyświetl Nazwę Punktu',
                    'display-sub-total'       => 'Wyświetl Suma Częściowa',
                    'display-tax'             => 'Wyświetl Podatek',
                    'grand-total-label'       => 'Etykieta Kwoty Całkowitej',
                    'order-id-label'          => 'Etykieta ID Zamówienia',
                    'receipt-title'           => 'Tytuł Paragonu',
                    'show-order-barcode'      => 'Pokaż kod kreskowy zamówienia',
                    'show-print-confirmation' => 'Pokaż potwierdzenie wydruku',
                    'status'                  => 'Status',
                    'sub-total-label'         => 'Etykieta Suma Częściowa',
                    'tax-label'               => 'Etykieta Podatek',
                    'title'                   => 'Ogólne',
                ],

                'logo' => [
                    'display-logo' => 'Wyświetl Logo',
                    'logo-alt'     => 'Alternatywna Nazwa Logo',
                    'logo-height'  => 'Wysokość Logo (w px)',
                    'logo-width'   => 'Szerokość Logo (w px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Prześlij Logo',
                ],

                'header' => [
                    'header-content' => 'Zawartość Nagłówka',
                    'title'          => 'Nagłówek',
                ],

                'footer' => [
                    'footer-content' => 'Zawartość Stopki',
                    'title'          => 'Stopka',
                ],
            ],

            'preview' => [
                'amount'         => 'Kwota',
                'cashier'        => 'Kasjer',
                'change-amount'  => 'Kwota Wydania',
                'customer'       => 'Klient',
                'customer-email' => 'Email Klienta',
                'customer-name'  => 'Imię Klienta',
                'customer-phone' => 'Telefon Klienta',
                'date'           => 'Data',
                'discount'       => 'Rabatt',
                'email'          => 'Email',
                'grand-total'    => 'Kwota Całkowita',
                'order-id'       => 'ID Zamówienia',
                'phone'          => 'Telefon',
                'price'          => 'Cena',
                'product'        => 'Produkt',
                'qty'            => 'Ilość',
                'sub-total'      => 'Suma Częściowa',
                'tax'            => 'Podatek',
                'title'          => 'Podgląd Paragonu',
                'total-qty'      => 'Łączna Ilość',
            ],

            'create-success' => 'Paragon został pomyślnie utworzony!',
            'delete-failed'  => 'Usuwanie paragonu nie powiodło się!',
            'delete-success' => 'Paragon został pomyślnie usunięty!',
            'update-success' => 'Paragon został pomyślnie zaktualizowany!',
        ],
    ],

    'emails' => [
        'dear'     => 'Drogi :name',
        'greeting' => 'Witaj!',

        'registration' => [
            'message' => 'Gratulacje! Twoje konto zostało pomyślnie utworzone. Zaloguj się do swojego konta i zacznij korzystać z systemu POS.',
            'subject' => 'Mail Rejestracyjny Użytkownika POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Zaloguj się',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
