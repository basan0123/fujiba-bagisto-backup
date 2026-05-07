<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Ungültige Anmeldeinformationen.',
                'not-activated'       => 'Ihr Konto ist noch nicht aktiviert.',
                'verify-first'        => 'Bitte verifizieren Sie zuerst Ihre E-Mail-Adresse.',
                'success'             => 'Sie haben sich erfolgreich eingeloggt.',
            ],

            'logout' => [
                'no-login-agent' => 'Kein Agent ist eingeloggt.',
                'success'        => 'Sie haben sich erfolgreich abgemeldet.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'Das eingegebene Passwort ist falsch.',
                    'success'          => 'Ihr Konto wurde erfolgreich aktualisiert.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Kunde erfolgreich erstellt!',
            'update-success' => 'Kunde erfolgreich aktualisiert!',
            'delete-success' => 'Kunde erfolgreich gelöscht!',
            'delete-failed'  => 'Kunde konnte nicht gelöscht werden!',
            'pending-orders' => 'Kunde hat ausstehende Bestellungen!',
        ],

        'cart' => [
            'already-applied'     => 'Gutschein wurde bereits angewendet!',
            'coupon-applied'      => 'Gutschein erfolgreich angewendet!',
            'coupon-removed'      => 'Gutschein erfolgreich entfernt!',
            'create-success'      => 'Warenkorb erfolgreich erstellt!',
            'invalid-coupon'      => 'Ungültiger Gutscheincode!',
            'item-add-success'    => 'Produkt erfolgreich zum Warenkorb hinzugefügt!',
            'item-remove-success' => 'Produkt erfolgreich aus dem Warenkorb entfernt!',
            'item-update-success' => 'Produkt erfolgreich aktualisiert!',
            'not-found'           => 'Warenkorb nicht gefunden!',
        ],

        'payment' => [
            'title' => 'Pos-Zahlung',

            'options' => [
                'cash' => [
                    'title'       => 'Pos Barzahlung',
                    'description' => 'Dies ist eine Pos-Barzahlung.',
                ],

                'card' => [
                    'title'       => 'Pos Kartenzahlung',
                    'description' => 'Dies ist eine Pos-Kartenzahlung.',
                ],

                'split' => [
                    'title'       => 'Pos Geteilte Zahlung',
                    'description' => 'Dies ist eine Pos-geteilte Zahlung.',
                ],
            ],

            'no-items' => 'Keine Artikel im Warenkorb, um mit der Zahlung fortzufahren.',
            'success'  => 'Zahlung erfolgreich abgeschlossen!',
        ],

        'shipping' => [
            'title'       => 'Pos Versand',
            'description' => 'Dies ist kostenloser Pos-Versand.',
        ],

        'order' => [
            'sync-success' => 'Bestellung erfolgreich synchronisiert!',
        ],

        'return' => [
            'create-success'              => 'Rückerstattung erfolgreich erstellt',
            'creation-error'              => 'Erstellung der Rückerstattung ist nicht erlaubt.',
            'invalid-qty'                 => 'Ungültige Menge zum Fakturieren der Artikel gefunden.',
            'invalid-refund-amount-error' => 'Der Rückerstattungsbetrag muss größer als null sein.',
            'refund-limit-error'          => 'Rückerstattungsbetrag :amount kann nicht verarbeitet werden.',
        ],

        'drawer' => [
            'create-success' => 'Kassenschublade erfolgreich geöffnet!',
            'not-opened'     => 'Kassenschublade wurde nicht geöffnet.',
            'close-success'  => 'Kassenschublade erfolgreich geschlossen!',
        ],

        'products' => [
            'request-success' => 'Produktanfrage erfolgreich eingereicht.',
            'create-success'  => 'Produkt erfolgreich erstellt!',
        ],

        'reports' => [
            'orders'                  => 'Bestellungen',
            'average-order-value'     => 'Durchschnittlicher Bestellwert',
            'average-items-per-order' => 'Durchschnittliche Artikel pro Bestellung',
            'discounted-offers'       => 'Rabattangebote',
            'cash-payments'           => 'Barzahlungen',
            'other-payments'          => 'Andere Zahlungen',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Bestellnotiz',
                    'note-not-available' => 'Notiz nicht verfügbar!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'Die Bagisto Point of Sale (POS) Erweiterung.',
                    'title' => 'Point Of Sale',

                    'settings' => [
                        'info'  => 'POS aktivieren, allgemeine Konfiguration festlegen, POS-Produkt und Rechnungsbeleg.',
                        'title' => 'Einstellungen',

                        'general' => [
                            'footer-content'       => 'Footer-Inhalt',
                            'footer-note'          => 'Footer-Notiz',
                            'frontend-url'         => 'Frontend-URL',
                            'heading-on-login'     => 'Überschrift beim Login',
                            'info'                 => 'Allgemeine Einstellungen ermöglichen die Konfiguration der POS-Benutzerseite, indem Sie Logo, Überschriften, Fußzeileninhalte, Fußzeilennotizen usw. hinzufügen.',
                            'pos-logo'             => 'POS-Logo',
                            'status'               => 'Status',
                            'sub-heading-on-login' => 'Unterüberschrift beim Login',
                            'title'                => 'Allgemein',
                        ],

                        'barcode' => [
                            'height'             => 'Höhe',
                            'hide-barcode'       => 'Barcode ausblenden',
                            'info'               => 'Barcode-Einstellungen ermöglichen die Konfiguration der Barcode-Erstellung, Barcode-Höhe, Barcode-Breite, Barcode-Typ usw.',
                            'prefix'             => 'Präfix',
                            'print-product-name' => 'Produktname drucken',
                            'title'              => 'Barcode',
                            'width'              => 'Breite',

                            'generate-with' => [
                                'title' => 'Barcode erstellen mit',

                                'options' => [
                                    'product-id' => 'Produkt-ID',
                                    'sku'        => 'Produkt-SKU',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'SKU für benutzerdefiniertes Produkt zulassen',
                            'info'      => 'Produkteinstellungen ermöglichen die Konfiguration für Produkt-SKU.',
                            'title'     => 'Produkte',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Produkte zuweisen',
            'banks'            => 'Banken',
            'barcode-products' => 'Barcode-Produkte',
            'create'           => 'Erstellen',
            'delete'           => 'Löschen',
            'edit'             => 'Bearbeiten',
            'generate-barcode' => 'Barcode generieren',
            'orders'           => 'Bestellungen',
            'outlets'          => 'Filialen',
            'pos'              => 'Point Of Sale (POS)',
            'preview'          => 'Vorschau',
            'print-barcode'    => 'Barcode drucken',
            'receipts'         => 'Quittungen',
            'requests'         => 'Anfragen',
            'sales-report'     => 'Verkaufsbericht',
            'users'            => 'Agenten',
            'view'             => 'Anzeigen',
        ],

        'layouts' => [
            'banks'            => 'Banken',
            'barcode-products' => 'Barcode-Produkte',
            'orders'           => 'Bestellungen',
            'pos'              => 'Point Of Sale (POS)',
            'receipts'         => 'Quittungen',
            'requests'         => 'Anfragen',
            'sales-report'     => 'Verkaufsbericht',

            'users' => [
                'agents'   => 'Agenten',
                'outlets'  => 'Filialen',
                'title'    => 'Agenten',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Agenten erstellen',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Agenten',

                    'datagrid' => [
                        'action'              => 'Aktion',
                        'delete'              => 'Löschen',
                        'edit'                => 'Bearbeiten',
                        'email'               => 'E-Mail',
                        'full-name'           => 'Vollständiger Name',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'Ausgewählte Agenten wurden erfolgreich gelöscht!',
                        'mass-update-success' => 'Ausgewählte Agenten wurden erfolgreich aktualisiert!',
                        'outlet-name'         => 'Outlet-Name',
                        'profile-image'       => 'Profilbild',
                        'update-status'       => 'Status aktualisieren',
                        'username'            => 'Benutzername',

                        'status' => [
                            'title' => 'Status',

                            'options' => [
                                'active'  => 'Aktiv',
                                'disable' => 'Deaktivieren',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Zurück',
                    'confirm-password'  => 'Passwort bestätigen',
                    'email'             => 'E-Mail',
                    'first-name'        => 'Vorname',
                    'general'           => 'Allgemein',
                    'image'             => 'Bild',
                    'last-name'         => 'Nachname',
                    'outlet'            => 'Filiale',
                    'outlet-and-status' => 'Filiale & Status',
                    'password'          => 'Passwort',
                    'save-btn'          => 'Agent speichern',
                    'select-outlet'     => 'Filiale auswählen',
                    'status'            => 'Status',
                    'title'             => 'Agent hinzufügen',
                    'user-image'        => 'Agentenbild hochladen',
                    'username'          => 'Benutzername',
                ],

                'edit' => [
                    'back-btn'          => 'Zurück',
                    'confirm-password'  => 'Passwort bestätigen',
                    'email'             => 'E-Mail',
                    'first-name'        => 'Vorname',
                    'general'           => 'Allgemein',
                    'image'             => 'Bild',
                    'last-name'         => 'Nachname',
                    'outlet'            => 'Filiale',
                    'outlet-and-status' => 'Filiale & Status',
                    'password'          => 'Passwort',
                    'save-btn'          => 'Agent speichern',
                    'select-outlet'     => 'Filiale auswählen',
                    'status'            => 'Status',
                    'title'             => 'Agent bearbeiten',
                    'user-image'        => 'Agentenbild hochladen',
                    'username'          => 'Benutzername',
                ],

                'create-success' => 'Agent erfolgreich erstellt!',
                'delete-failed'  => 'Löschen des Agents fehlgeschlagen!',
                'delete-success' => 'Agent erfolgreich gelöscht!',
                'update-success' => 'Agent erfolgreich aktualisiert!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Filiale erstellen',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Filialen',

                    'datagrid' => [
                        'action'              => 'Aktion',
                        'active'              => 'Aktiv',
                        'assign'              => 'Produkt zuweisen',
                        'delete'              => 'Löschen',
                        'edit'                => 'Bearbeiten',
                        'id'                  => 'ID',
                        'inactive'            => 'Inaktiv',
                        'inventory-source'    => 'Bestandsquelle',
                        'mass-delete-success' => 'Ausgewählte Filialen erfolgreich gelöscht!',
                        'mass-update-success' => 'Ausgewählte Filialen erfolgreich aktualisiert!',
                        'name'                => 'Name',
                        'receipt-title'       => 'Belegtitel',
                        'status'              => 'Status',
                        'title'               => 'POS-Filialliste',
                        'update-status'       => 'Status aktualisieren',
                    ],
                ],

                'create' => [
                    'address'                 => 'Adresse',
                    'back-btn'                => 'Zurück',
                    'btn-title'               => 'Outlet speichern',
                    'city'                    => 'Stadt',
                    'country'                 => 'Land',
                    'customer-care-number'    => 'Kundendienstnummer',
                    'email'                   => 'E-Mail',
                    'general'                 => 'Allgemein',
                    'gst-number'              => 'USt-Nummer',
                    'inventory'               => 'Inventar',
                    'inventory-source'        => 'Inventarquelle',
                    'low-stock-qty'           => 'Niedriger Lagerbestand',
                    'name'                    => 'Outlet-Name',
                    'phone'                   => 'Telefon',
                    'postcode'                => 'Postleitzahl',
                    'receipt'                 => 'Beleg',
                    'select-country'          => 'Land auswählen',
                    'select-inventory-source' => 'Inventarquelle auswählen',
                    'select-receipt'          => 'Beleg auswählen',
                    'state'                   => 'Bundesland',
                    'status'                  => 'Status',
                    'store-address'           => 'Outlet-Adresse',
                    'title'                   => 'Outlet hinzufügen',
                    'website'                 => 'Website',
                ],

                'edit' => [
                    'address'                 => 'Adresse',
                    'back-btn'                => 'Zurück',
                    'btn-title'               => 'Outlet speichern',
                    'city'                    => 'Stadt',
                    'country'                 => 'Land',
                    'customer-care-number'    => 'Kundendienstnummer',
                    'email'                   => 'E-Mail',
                    'general'                 => 'Allgemein',
                    'gst-number'              => 'USt-Nummer',
                    'inventory'               => 'Inventar',
                    'inventory-source'        => 'Inventarquelle',
                    'low-stock-qty'           => 'Niedriger Lagerbestand',
                    'name'                    => 'Outlet-Name',
                    'phone'                   => 'Telefon',
                    'postcode'                => 'Postleitzahl',
                    'receipt'                 => 'Beleg',
                    'select-country'          => 'Land auswählen',
                    'select-inventory-source' => 'Inventarquelle auswählen',
                    'select-receipt'          => 'Beleg auswählen',
                    'state'                   => 'Bundesland',
                    'status'                  => 'Status',
                    'store-address'           => 'Outlet-Adresse',
                    'title'                   => 'Outlet bearbeiten',
                    'website'                 => 'Website',
                ],

                'assign' => [
                    'back-btn' => 'Zurück',
                    'title'    => 'Filialprodukte verwalten',

                    'datagrid' => [
                        'active'              => 'Aktiv',
                        'assign'              => 'Zuweisen',
                        'disable'             => 'Deaktivieren',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'image'               => 'Bild',
                        'mass-assign-success' => 'Produktzuweisung erfolgreich aktualisiert!',
                        'name'                => 'Name',
                        'out-of-stock'        => 'Nicht auf Lager',
                        'pos-status'          => 'POS-Status',
                        'price'               => 'Preis',
                        'product-image'       => 'Produktbild',
                        'qty'                 => 'Menge',
                        'qty-value'           => ':qty verfügbar',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Status',
                        'type'                => 'Typ',
                        'unassign'            => 'Entfernen',
                        'update-assign'       => 'Zuweisung aktualisieren',
                    ],
                ],

                'create-success' => 'Filiale erfolgreich erstellt!',
                'delete-failed'  => 'Löschen der Filiale fehlgeschlagen!',
                'delete-success' => 'Filiale erfolgreich gelöscht!',
                'update-success' => 'Filiale erfolgreich aktualisiert!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Barcode-Produkte',

                'datagrid' => [
                    'barcode'               => 'Barcode',
                    'generate-barcode'      => 'Barcode generieren',
                    'print-barcode'         => 'Barcode drucken',
                    'id'                    => 'ID',
                    'id-value'              => 'ID - :id',
                    'image'                 => 'Bild',
                    'mass-generate-success' => 'Barcode für ausgewählte Produkte erfolgreich generiert!',
                    'name'                  => 'Name',
                    'out-of-stock'          => 'Nicht auf Lager',
                    'price'                 => 'Preis',
                    'product-image'         => 'Produktbild',
                    'qty'                   => 'Menge',
                    'qty-value'             => ':qty verfügbar',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'active'  => 'Aktiv',
                            'disable' => 'Deaktivieren',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'Zurück',
                'btn-title' => 'Drucken',
                'qty'       => 'Menge',
                'title'     => 'Barcode drucken',
            ],

            'generate-failed'  => 'Barcode-Generierung fehlgeschlagen!',
            'generate-success' => 'Barcode erfolgreich generiert!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Bestellungen',

                'datagrid' => [
                    'customer-name' => 'Kundenname',
                    'grand-total'   => 'Gesamtbetrag',
                    'order-date'    => 'Bestelldatum',
                    'order-id'      => 'Bestell-ID',
                    'order-ref-id'  => 'Bestell-Ref.-ID',
                    'view'          => 'Anzeigen',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'canceled'        => 'Storniert',
                            'closed'          => 'Geschlossen',
                            'completed'       => 'Abgeschlossen',
                            'fraud'           => 'Betrug',
                            'pending'         => 'Ausstehend',
                            'pending-payment' => 'Ausstehende Zahlung',
                            'processing'      => 'In Bearbeitung',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Anfragen',

                'datagrid' => [
                    'id'                  => 'ID',
                    'product-image'       => 'Produktbild',
                    'mass-update-error'   => 'Aktualisierung der Anfrage fehlgeschlagen!',
                    'mass-update-success' => 'Ausgewählte Anfragen erfolgreich aktualisiert!',
                    'product-name'        => 'Produktname',
                    'outlet-name'         => 'Filialname',
                    'qty-value'           => 'Menge - :qty',
                    'request-date'        => 'Anfragedatum',
                    'requested-qty'       => 'Angeforderte Menge',
                    'update-status'       => 'Status aktualisieren',
                    'user-name'           => 'Benutzername',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'complete' => 'Abgeschlossen',
                            'decline'  => 'Ablehnen',
                            'pending'  => 'Ausstehend',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Zurück',
                'btn-title' => 'Speichern',
                'title'     => 'Details zur angeforderten Produkt #:id',

                'user-info' => [
                    'email'            => 'E-Mail',
                    'name'             => 'Name',
                    'outlet-address'   => 'Filialadresse',
                    'outlet-inventory' => 'Bestandsquelle der Filiale',
                    'outlet-name'      => 'Filialname',
                    'title'            => 'Benutzerinformationen',
                ],

                'request-info' => [
                    'comment'       => 'Kommentar',
                    'product-name'  => 'Produktname',
                    'qty-value'     => 'Menge - :qty',
                    'request-date'  => 'Anfragedatum',
                    'requested-qty' => 'Angeforderte Menge',
                    'title'         => 'Anfrageinformationen',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'complete' => 'Abgeschlossen',
                            'decline'  => 'Ablehnen',
                            'pending'  => 'Ausstehend',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Aktualisierung der Anfrage fehlgeschlagen!',
            'update-success' => 'Anfrage erfolgreich aktualisiert!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Bank erstellen',
                'title'     => 'Banken',

                'datagrid' => [
                    'active'              => 'Aktiv',
                    'address'             => 'Bankadresse',
                    'agent-name'          => 'Agent',
                    'delete'              => 'Löschen',
                    'disable'             => 'Deaktivieren',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Ausgewählte Banken erfolgreich gelöscht!',
                    'name'                => 'Bankname',
                    'status'              => 'Status',
                ],
            ],

            'create' => [
                'back-btn'  => 'Zurück',
                'btn-title' => 'Bank speichern',
                'title'     => 'Neue Bank erstellen',

                'general' => [
                    'address' => 'Adresse',
                    'email'   => 'E-Mail',
                    'name'    => 'Name',
                    'phone'   => 'Telefon',
                    'title'   => 'Allgemein',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS-Agent zuweisen',
                    'bank-status'  => 'Bankstatus',
                    'select-agent' => 'Agent auswählen',
                    'title'        => 'POS-Agent & Bankstatus',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Zurück',
                'btn-title' => 'Bank speichern',
                'title'     => 'Bank bearbeiten',

                'general' => [
                    'address' => 'Adresse',
                    'email'   => 'E-Mail',
                    'name'    => 'Name',
                    'phone'   => 'Telefon',
                    'title'   => 'Allgemein',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS-Agent zuweisen',
                    'bank-status'  => 'Bankstatus',
                    'select-agent' => 'Agent auswählen',
                    'title'        => 'POS-Agent & Bankstatus',
                ],
            ],

            'create-success' => 'Bank erfolgreich erstellt!',
            'delete-failed'  => 'Banklöschung fehlgeschlagen!',
            'delete-success' => 'Bank erfolgreich gelöscht!',
            'update-success' => 'Bank erfolgreich aktualisiert!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Verkaufsbericht',

                'datagrid' => [
                    'bank-name'      => 'Bankname',
                    'grand-total'    => 'Gesamtbetrag',
                    'order-date'     => 'Bestelldatum',
                    'order-id'       => 'Bestell-ID',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Bestellnotiz',
                    'outlet-name'    => 'Filialname',
                    'payment-method' => 'Zahlungsmethode',
                    'view'           => 'Anzeigen',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'canceled'        => 'Storniert',
                            'closed'          => 'Geschlossen',
                            'completed'       => 'Abgeschlossen',
                            'fraud'           => 'Betrug',
                            'pending'         => 'Ausstehend',
                            'pending-payment' => 'Ausstehende Zahlung',
                            'processing'      => 'In Bearbeitung',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Beleg erstellen',
                'title'      => 'Belege',

                'datagrid' => [
                    'delete'              => 'Löschen',
                    'edit'                => 'Bearbeiten',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Ausgewählte Belege erfolgreich gelöscht!',
                    'preview'             => 'Vorschau',
                    'title'               => 'Titel',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'active'   => 'Aktiv',
                            'inactive' => 'Inaktiv',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Zurück',
                'btn-title' => 'Beleg speichern',
                'title'     => 'Neuen Beleg erstellen',

                'general' => [
                    'cashier-name-label'      => 'Kassierer Name Etikett',
                    'change-amount-label'     => 'Wechselgeld Etikett',
                    'credit-amount-label'     => 'Kreditbetrag Etikett',
                    'discount-amt-label'      => 'Rabattbetrag Etikett',
                    'display-cashier-name'    => 'Kassierer Name anzeigen',
                    'display-change-amount'   => 'Wechselgeld anzeigen',
                    'display-credit-amount'   => 'Kreditbetrag anzeigen',
                    'display-customer-name'   => 'Kundenname anzeigen',
                    'display-date'            => 'Datum anzeigen',
                    'display-discount-amt'    => 'Rabattbetrag anzeigen',
                    'display-order-id'        => 'Bestell-ID anzeigen',
                    'display-outlet-address'  => 'Filialadresse anzeigen',
                    'display-outlet-name'     => 'Filialname anzeigen',
                    'display-sub-total'       => 'Zwischensumme anzeigen',
                    'display-tax'             => 'Steuer anzeigen',
                    'grand-total-label'       => 'Gesamtbetrag Etikett',
                    'order-id-label'          => 'Bestell-ID Etikett',
                    'receipt-title'           => 'Belegtitel',
                    'show-order-barcode'      => 'Bestellbarcode anzeigen',
                    'show-print-confirmation' => 'Druckbestätigung anzeigen',
                    'status'                  => 'Status',
                    'sub-total-label'         => 'Zwischensumme Etikett',
                    'tax-label'               => 'Steuer Etikett',
                    'title'                   => 'Allgemein',
                ],

                'logo' => [
                    'display-logo' => 'Logo anzeigen',
                    'logo-alt'     => 'Logo Alt',
                    'logo-height'  => 'Logo Höhe (in px)',
                    'logo-width'   => 'Logo Breite (in px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Logo hochladen',
                ],

                'header' => [
                    'header-content' => 'Kopfzeileninhalt',
                    'title'          => 'Kopfzeile',
                ],

                'footer' => [
                    'footer-content' => 'Fußzeileninhalt',
                    'title'          => 'Fußzeile',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Zurück',
                'btn-title' => 'Beleg speichern',
                'title'     => 'Beleg bearbeiten',

                'general' => [
                    'cashier-name-label'      => 'Kassierer Name Etikett',
                    'change-amount-label'     => 'Wechselgeld Etikett',
                    'credit-amount-label'     => 'Kreditbetrag Etikett',
                    'discount-amt-label'      => 'Rabattbetrag Etikett',
                    'display-cashier-name'    => 'Kassierer Name anzeigen',
                    'display-change-amount'   => 'Wechselgeld anzeigen',
                    'display-credit-amount'   => 'Kreditbetrag anzeigen',
                    'display-customer-name'   => 'Kundenname anzeigen',
                    'display-date'            => 'Datum anzeigen',
                    'display-discount-amt'    => 'Rabattbetrag anzeigen',
                    'display-order-id'        => 'Bestell-ID anzeigen',
                    'display-outlet-address'  => 'Filialadresse anzeigen',
                    'display-outlet-name'     => 'Filialname anzeigen',
                    'display-sub-total'       => 'Zwischensumme anzeigen',
                    'display-tax'             => 'Steuer anzeigen',
                    'grand-total-label'       => 'Gesamtbetrag Etikett',
                    'order-id-label'          => 'Bestell-ID Etikett',
                    'receipt-title'           => 'Belegtitel',
                    'show-order-barcode'      => 'Bestellbarcode anzeigen',
                    'show-print-confirmation' => 'Druckbestätigung anzeigen',
                    'status'                  => 'Status',
                    'sub-total-label'         => 'Zwischensumme Etikett',
                    'tax-label'               => 'Steuer Etikett',
                    'title'                   => 'Allgemein',
                ],

                'logo' => [
                    'display-logo' => 'Logo anzeigen',
                    'logo-alt'     => 'Logo Alt',
                    'logo-height'  => 'Logo Höhe (in px)',
                    'logo-width'   => 'Logo Breite (in px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Logo hochladen',
                ],

                'header' => [
                    'header-content' => 'Kopfzeileninhalt',
                    'title'          => 'Kopfzeile',
                ],

                'footer' => [
                    'footer-content' => 'Fußzeileninhalt',
                    'title'          => 'Fußzeile',
                ],
            ],

            'preview' => [
                'amount'         => 'Betrag',
                'cashier'        => 'Kassierer',
                'change-amount'  => 'Wechselgeld',
                'customer'       => 'Kunde',
                'customer-email' => 'Kunden-E-Mail',
                'customer-name'  => 'Kundenname',
                'customer-phone' => 'Kunden-Telefon',
                'date'           => 'Datum',
                'discount'       => 'Rabatt',
                'email'          => 'E-Mail',
                'grand-total'    => 'Gesamtbetrag',
                'order-id'       => 'Bestell-ID',
                'phone'          => 'Telefon',
                'price'          => 'Preis',
                'product'        => 'Produkt',
                'qty'            => 'Menge',
                'sub-total'      => 'Zwischensumme',
                'tax'            => 'Steuer',
                'title'          => 'Belegvorschau',
                'total-qty'      => 'Gesamtmenge',
            ],

            'create-success' => 'Beleg erfolgreich erstellt!',
            'delete-failed'  => 'Beleglöschung fehlgeschlagen!',
            'delete-success' => 'Beleg erfolgreich gelöscht!',
            'update-success' => 'Beleg erfolgreich aktualisiert!',
        ],
    ],

    'emails' => [
        'dear'     => 'Liebe/r :name',
        'greeting' => 'Grüße!',

        'registration' => [
            'message' => 'Herzlichen Glückwunsch! Ihr Konto wurde erfolgreich erstellt. Bitte melden Sie sich bei Ihrem Konto an und beginnen Sie mit der Nutzung des POS-Systems.',
            'subject' => 'POS Benutzerregistrierungs-Mail',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Anmelden',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
