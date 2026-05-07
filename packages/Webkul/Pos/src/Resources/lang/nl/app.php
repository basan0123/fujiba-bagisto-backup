<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Ongeldige inloggegevens.',
                'not-activated'       => 'Je account is niet geactiveerd.',
                'verify-first'        => 'Verifieer eerst je e-mail.',
                'success'             => 'Je bent succesvol ingelogd.',
            ],

            'logout' => [
                'no-login-agent' => 'Geen agent is ingelogd.',
                'success'        => 'Je bent succesvol uitgelogd.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'Het ingevoerde wachtwoord is onjuist.',
                    'success'          => 'Je account is succesvol bijgewerkt.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Klant succesvol aangemaakt!',
            'update-success' => 'Klant succesvol bijgewerkt!',
            'delete-success' => 'Klant succesvol verwijderd!',
            'delete-failed'  => 'Verwijderen van klant mislukt!',
            'pending-orders' => 'Klant heeft openstaande bestellingen!',
        ],

        'cart' => [
            'already-applied'     => 'Coupon is al toegepast!',
            'coupon-applied'      => 'Coupon succesvol toegepast!',
            'coupon-removed'      => 'Coupon succesvol verwijderd!',
            'create-success'      => 'Winkelwagen succesvol aangemaakt!',
            'invalid-coupon'      => 'Ongeldige couponcode!',
            'item-add-success'    => 'Product succesvol aan winkelwagen toegevoegd!',
            'item-remove-success' => 'Product succesvol uit winkelwagen verwijderd!',
            'item-update-success' => 'Product succesvol bijgewerkt!',
            'not-found'           => 'Winkelwagen niet gevonden!',
        ],

        'payment' => [
            'title' => 'Pos Betaling',

            'options' => [
                'cash' => [
                    'title'       => 'Pos Contante Betaling',
                    'description' => 'Dit is een Pos contante betaling.',
                ],

                'card' => [
                    'title'       => 'Pos Kaart Betaling',
                    'description' => 'Dit is een Pos kaartbetaling.',
                ],

                'split' => [
                    'title'       => 'Pos Gesplitste Betaling',
                    'description' => 'Dit is een Pos gesplitste betaling.',
                ],
            ],

            'no-items' => 'Geen artikelen in het winkelwagentje om mee door te gaan met betalen.',
            'success'  => 'Betaling succesvol voltooid!',
        ],

        'shipping' => [
            'title'       => 'Pos Verzendkosten',
            'description' => 'Dit is gratis Pos verzending.',
        ],

        'order' => [
            'sync-success' => 'Bestelling succesvol gesynchroniseerd!',
        ],

        'return' => [
            'create-success'              => 'Terugbetaling succesvol aangemaakt',
            'creation-error'              => 'Terugbetaling aanmaken is niet toegestaan.',
            'invalid-qty'                 => 'Ongeldig aantal voor facturatie van artikelen gevonden.',
            'invalid-refund-amount-error' => 'Het terug te betalen bedrag moet groter dan nul zijn.',
            'refund-limit-error'          => 'Terugbetalingsbedrag :amount kan niet worden verwerkt.',
        ],

        'drawer' => [
            'create-success' => 'Lade succesvol geopend!',
            'not-opened'     => 'Lade is niet geopend.',
            'close-success'  => 'Lade succesvol gesloten!',
        ],

        'products' => [
            'request-success' => 'Productaanvraag succesvol verzonden.',
            'create-success'  => 'Product succesvol aangemaakt!',
        ],

        'reports' => [
            'orders'                  => 'Bestellingen',
            'average-order-value'     => 'Gemiddelde Bestelwaarde',
            'average-items-per-order' => 'Gemiddeld Aantal Artikelen per Bestelling',
            'discounted-offers'       => 'Korting Aanbiedingen',
            'cash-payments'           => 'Contante Betalingen',
            'other-payments'          => 'Andere Betalingen',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Bestelnotitie',
                    'note-not-available' => 'Notitie niet beschikbaar!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'De Bagisto Point of Sale (POS) extensie.',
                    'title' => 'Point of Sale',

                    'settings' => [
                        'info'  => 'POS inschakelen, Algemene configuratie instellen, POS-product en factuurontvangst.',
                        'title' => 'Instellingen',

                        'general' => [
                            'footer-content'       => 'Voettekst',
                            'footer-note'          => 'Voettekst Notitie',
                            'frontend-url'         => 'Frontend-URL',
                            'heading-on-login'     => 'Titel bij Inloggen',
                            'info'                 => 'Algemene instellingen maken configuraties mogelijk voor de POS-gebruikerspagina, door logo, kopteksten, voettekstinhoud, voettekstnotitie, enz. toe te voegen.',
                            'pos-logo'             => 'POS Logo',
                            'status'               => 'Status',
                            'sub-heading-on-login' => 'Subtitel bij Inloggen',
                            'title'                => 'Algemeen',
                        ],

                        'barcode' => [
                            'height'             => 'Hoogte',
                            'hide-barcode'       => 'Verberg Barcode',
                            'info'               => 'Barcode-instellingen maken configuraties mogelijk voor barcodegeneratie, barcodehoogte, barcodebreedte, barcodetype, enz.',
                            'prefix'             => 'Voorvoegsel',
                            'print-product-name' => 'Productnaam Afdrukken',
                            'title'              => 'Barcode',
                            'width'              => 'Breedte',

                            'generate-with' => [
                                'title' => 'Genereer Barcode Met',

                                'options' => [
                                    'product-id' => 'Product-ID',
                                    'sku'        => 'Product SKU',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'SKU toestaan voor aangepast product',
                            'info'      => 'Productinstellingen stellen configuraties voor de SKU van het product in.',
                            'title'     => 'Producten',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Producten toewijzen',
            'banks'            => 'Banken',
            'barcode-products' => 'Barcode producten',
            'create'           => 'Aanmaken',
            'delete'           => 'Verwijderen',
            'edit'             => 'Bewerken',
            'generate-barcode' => 'Barcode genereren',
            'orders'           => 'Bestellingen',
            'outlets'          => 'Verkooppunten',
            'pos'              => 'Verkooppunt (POS)',
            'preview'          => 'Voorbeeld',
            'print-barcode'    => 'Barcode afdrukken',
            'receipts'         => 'Bonnen',
            'requests'         => 'Verzoeken',
            'sales-report'     => 'Verkooprapport',
            'users'            => 'Agents',
            'view'             => 'Bekijken',
        ],

        'layouts' => [
            'banks'            => 'Banken',
            'barcode-products' => 'Barcode Producten',
            'orders'           => 'Bestellingen',
            'pos'              => 'Verkooppunt (POS)',
            'receipts'         => 'Bonnen',
            'requests'         => 'Verzoeken',
            'sales-report'     => 'Verkooprapport',

            'users' => [
                'agents'   => 'Agents',
                'outlets'  => 'Verkooppunten',
                'title'    => 'Agents',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Maak Agenten Aan',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Agenten',

                    'datagrid' => [
                        'action'              => 'Actie',
                        'delete'              => 'Verwijderen',
                        'edit'                => 'Bewerken',
                        'email'               => 'E-mail',
                        'full-name'           => 'Volledige naam',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'Geselecteerde agenten succesvol verwijderd!',
                        'mass-update-success' => 'Geselecteerde agenten succesvol bijgewerkt!',
                        'outlet-name'         => 'Outletnaam',
                        'profile-image'       => 'Profielfoto',
                        'update-status'       => 'Status bijwerken',
                        'username'            => 'Gebruikersnaam',

                        'status' => [
                            'title' => 'Status',

                            'options' => [
                                'active'  => 'Actief',
                                'disable' => 'Deactiveer',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Terug',
                    'confirm-password'  => 'Bevestig Wachtwoord',
                    'email'             => 'E-mail',
                    'first-name'        => 'Voornaam',
                    'general'           => 'Algemeen',
                    'image'             => 'Afbeelding',
                    'last-name'         => 'Achternaam',
                    'outlet'            => 'Outlet',
                    'outlet-and-status' => 'Outlet & Status',
                    'password'          => 'Wachtwoord',
                    'save-btn'          => 'Sla Agent Op',
                    'select-outlet'     => 'Selecteer Outlet',
                    'status'            => 'Status',
                    'title'             => 'Voeg Agent Toe',
                    'user-image'        => 'Upload Agent Afbeelding',
                    'username'          => 'Gebruikersnaam',
                ],

                'edit' => [
                    'back-btn'          => 'Terug',
                    'confirm-password'  => 'Bevestig Wachtwoord',
                    'email'             => 'E-mail',
                    'first-name'        => 'Voornaam',
                    'general'           => 'Algemeen',
                    'image'             => 'Afbeelding',
                    'last-name'         => 'Achternaam',
                    'outlet'            => 'Outlet',
                    'outlet-and-status' => 'Outlet & Status',
                    'password'          => 'Wachtwoord',
                    'save-btn'          => 'Sla Agent Op',
                    'select-outlet'     => 'Selecteer Outlet',
                    'status'            => 'Status',
                    'title'             => 'Bewerk Agent',
                    'user-image'        => 'Upload Agent Afbeelding',
                    'username'          => 'Gebruikersnaam',
                ],

                'create-success' => 'Agent succesvol aangemaakt!',
                'delete-failed'  => 'Verwijderen van agent mislukt!',
                'delete-success' => 'Agent succesvol verwijderd!',
                'update-success' => 'Agent succesvol bijgewerkt!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Maak Outlet Aan',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Outlets',

                    'datagrid' => [
                        'action'              => 'Actie',
                        'active'              => 'Actief',
                        'assign'              => 'Wijs Product Toe',
                        'delete'              => 'Verwijder',
                        'edit'                => 'Bewerk',
                        'id'                  => 'ID',
                        'inactive'            => 'Inactief',
                        'inventory-source'    => 'Voorraadbron',
                        'mass-delete-success' => 'Geselecteerde winkels succesvol verwijderd!',
                        'mass-update-success' => 'Geselecteerde winkels succesvol bijgewerkt!',
                        'name'                => 'Naam',
                        'receipt-title'       => 'Ontvangst Titel',
                        'status'              => 'Status',
                        'title'               => 'Pos Winkel Lijst',
                        'update-status'       => 'Status Bijwerken',
                    ],
                ],

                'create' => [
                    'address'                 => 'Adres',
                    'back-btn'                => 'Terug',
                    'btn-title'               => 'Sla Outlet Op',
                    'city'                    => 'Stad',
                    'country'                 => 'Land',
                    'customer-care-number'    => 'Klantenservice Nummer',
                    'email'                   => 'E-mail',
                    'general'                 => 'Algemeen',
                    'gst-number'              => 'BTW Nummer',
                    'inventory'               => 'Voorraad',
                    'inventory-source'        => 'Voorraadbron',
                    'low-stock-qty'           => 'Lage Voorraad Hoeveelheid',
                    'name'                    => 'Outlet Naam',
                    'phone'                   => 'Telefoon',
                    'postcode'                => 'Postcode',
                    'receipt'                 => 'Ontvangstbewijs',
                    'select-country'          => 'Selecteer Land',
                    'select-inventory-source' => 'Selecteer Voorraadbron',
                    'select-receipt'          => 'Selecteer Ontvangstbewijs',
                    'state'                   => 'Staat',
                    'status'                  => 'Status',
                    'store-address'           => 'Outlet Adres',
                    'title'                   => 'Voeg Outlet Toe',
                    'website'                 => 'Website',
                ],

                'edit' => [
                    'address'                 => 'Adres',
                    'back-btn'                => 'Terug',
                    'btn-title'               => 'Sla Outlet Op',
                    'city'                    => 'Stad',
                    'country'                 => 'Land',
                    'customer-care-number'    => 'Klantenservice Nummer',
                    'email'                   => 'E-mail',
                    'general'                 => 'Algemeen',
                    'gst-number'              => 'BTW Nummer',
                    'inventory'               => 'Voorraad',
                    'inventory-source'        => 'Voorraadbron',
                    'low-stock-qty'           => 'Lage Voorraad Hoeveelheid',
                    'name'                    => 'Outlet Naam',
                    'phone'                   => 'Telefoon',
                    'postcode'                => 'Postcode',
                    'receipt'                 => 'Ontvangstbewijs',
                    'select-country'          => 'Selecteer Land',
                    'select-inventory-source' => 'Selecteer Voorraadbron',
                    'select-receipt'          => 'Selecteer Ontvangstbewijs',
                    'state'                   => 'Staat',
                    'status'                  => 'Status',
                    'store-address'           => 'Outlet Adres',
                    'title'                   => 'Bewerk Outlet',
                    'website'                 => 'Website',
                ],

                'assign' => [
                    'back-btn' => 'Terug',
                    'title'    => 'Beheer Outlet Producten',

                    'datagrid' => [
                        'active'              => 'Actief',
                        'assign'              => 'Wijs Toe',
                        'disable'             => 'Deactiveer',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'image'               => 'Afbeelding',
                        'mass-assign-success' => 'Producttoewijzing succesvol bijgewerkt!',
                        'name'                => 'Naam',
                        'out-of-stock'        => 'Niet Op Voorraad',
                        'pos-status'          => 'POS Status',
                        'price'               => 'Prijs',
                        'product-image'       => 'Product Afbeelding',
                        'qty'                 => 'Hoeveelheid',
                        'qty-value'           => ':qty Beschikbaar',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Status',
                        'type'                => 'Type',
                        'unassign'            => 'Verwijder Toewijzing',
                        'update-assign'       => 'Bijwerken Toewijzing',
                    ],
                ],

                'create-success' => 'Outlet succesvol aangemaakt!',
                'delete-failed'  => 'Verwijderen van outlet mislukt!',
                'delete-success' => 'Outlet succesvol verwijderd!',
                'update-success' => 'Outlet succesvol bijgewerkt!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Barcode Producten',

                'datagrid' => [
                    'barcode'               => 'Barcode',
                    'generate-barcode'      => 'Genereer Barcode',
                    'print-barcode'         => 'Print Barcode',
                    'id'                    => 'ID',
                    'id-value'              => 'ID - :id',
                    'image'                 => 'Afbeelding',
                    'mass-generate-success' => 'Barcodes voor geselecteerde producten succesvol gegenereerd!',
                    'name'                  => 'Naam',
                    'out-of-stock'          => 'Niet Op Voorraad',
                    'price'                 => 'Prijs',
                    'product-image'         => 'Product Afbeelding',
                    'qty'                   => 'Hoeveelheid',
                    'qty-value'             => ':qty Beschikbaar',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'active'  => 'Actief',
                            'disable' => 'Deactiveer',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'Terug',
                'btn-title' => 'Print',
                'qty'       => 'Hoeveelheid',
                'title'     => 'Print Barcode',
            ],

            'generate-failed'  => 'Barcode generatie mislukt!',
            'generate-success' => 'Barcode succesvol gegenereerd!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Bestellingen',

                'datagrid' => [
                    'customer-name' => 'Klantnaam',
                    'grand-total'   => 'Totaalbedrag',
                    'order-date'    => 'Besteldatum',
                    'order-id'      => 'Bestel-ID',
                    'order-ref-id'  => 'Bestel Referentie-ID',
                    'view'          => 'Bekijken',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'canceled'        => 'Geannuleerd',
                            'closed'          => 'Gesloten',
                            'completed'       => 'Voltooid',
                            'fraud'           => 'Fraude',
                            'pending'         => 'In Afwachting',
                            'pending-payment' => 'In Afwachting van Betaling',
                            'processing'      => 'Verwerken',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Verzoeken',

                'datagrid' => [
                    'id'                  => 'ID',
                    'product-image'       => 'Product Afbeelding',
                    'mass-update-error'   => 'Bijwerken van verzoek mislukt!',
                    'mass-update-success' => 'Geselecteerde verzoeken succesvol bijgewerkt!',
                    'product-name'        => 'Product Naam',
                    'outlet-name'         => 'Outlet Naam',
                    'qty-value'           => 'Hoeveelheid - :qty',
                    'request-date'        => 'Verzoekdatum',
                    'requested-qty'       => 'Gevraagde Hoeveelheid',
                    'update-status'       => 'Status Bijwerken',
                    'user-name'           => 'Gebruikersnaam',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'complete' => 'Voltooid',
                            'decline'  => 'Afwijzen',
                            'pending'  => 'In Afwachting',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Terug',
                'btn-title' => 'Opslaan',
                'title'     => 'Verzoek Product Details #:id',

                'user-info' => [
                    'email'            => 'E-mail',
                    'name'             => 'Naam',
                    'outlet-address'   => 'Outlet Adres',
                    'outlet-inventory' => 'Outlet Voorraadbron',
                    'outlet-name'      => 'Outlet Naam',
                    'title'            => 'Gebruikersinformatie',
                ],

                'request-info' => [
                    'comment'       => 'Opmerking',
                    'product-name'  => 'Product Naam',
                    'qty-value'     => 'Hoeveelheid - :qty',
                    'request-date'  => 'Verzoekdatum',
                    'requested-qty' => 'Gevraagde Hoeveelheid',
                    'title'         => 'Verzoek Informatie',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'complete' => 'Voltooid',
                            'decline'  => 'Afwijzen',
                            'pending'  => 'In Afwachting',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Bijwerken van verzoek mislukt!',
            'update-success' => 'Verzoek succesvol bijgewerkt!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Bank Aanmaken',
                'title'     => 'Banken',

                'datagrid' => [
                    'active'              => 'Actief',
                    'address'             => 'Bankadres',
                    'agent-name'          => 'Agent',
                    'delete'              => 'Verwijderen',
                    'disable'             => 'Uitschakelen',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Geselecteerde banken zijn succesvol verwijderd!',
                    'name'                => 'Banknaam',
                    'status'              => 'Status',
                ],
            ],

            'create' => [
                'back-btn'  => 'Terug',
                'btn-title' => 'Sla Bank Op',
                'title'     => 'Nieuwe Bank Aanmaken',

                'general' => [
                    'address' => 'Adres',
                    'email'   => 'E-mail',
                    'name'    => 'Naam',
                    'phone'   => 'Telefoon',
                    'title'   => 'Algemeen',
                ],

                'agent-and-status' => [
                    'agent'        => 'Wijs POS Agent Toe',
                    'bank-status'  => 'Bankstatus',
                    'select-agent' => 'Selecteer Agent',
                    'title'        => 'POS Agent & Bankstatus',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Terug',
                'btn-title' => 'Sla Bank Op',
                'title'     => 'Bewerk Bank',

                'general' => [
                    'address' => 'Adres',
                    'email'   => 'E-mail',
                    'name'    => 'Naam',
                    'phone'   => 'Telefoon',
                    'title'   => 'Algemeen',
                ],

                'agent-and-status' => [
                    'agent'        => 'Wijs POS Agent Toe',
                    'bank-status'  => 'Bankstatus',
                    'select-agent' => 'Selecteer Agent',
                    'title'        => 'POS Agent & Bankstatus',
                ],
            ],

            'create-success' => 'Bank succesvol aangemaakt!',
            'delete-failed'  => 'Verwijderen van bank mislukt!',
            'delete-success' => 'Bank succesvol verwijderd!',
            'update-success' => 'Bank succesvol bijgewerkt!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Verkooprapport',

                'datagrid' => [
                    'bank-name'      => 'Banknaam',
                    'grand-total'    => 'Totaalbedrag',
                    'order-date'     => 'Besteldatum',
                    'order-id'       => 'Bestel-ID',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Bestelopmerking',
                    'outlet-name'    => 'Outletnaam',
                    'payment-method' => 'Betaalmethode',
                    'view'           => 'Bekijken',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'canceled'        => 'Geannuleerd',
                            'closed'          => 'Gesloten',
                            'completed'       => 'Voltooid',
                            'fraud'           => 'Fraude',
                            'pending'         => 'In Afwachting',
                            'pending-payment' => 'In Afwachting van Betaling',
                            'processing'      => 'Verwerken',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Maak Ontvangstbewijs Aan',
                'title'      => 'Ontvangstbewijzen',

                'datagrid' => [
                    'delete'              => 'Verwijderen',
                    'edit'                => 'Bewerken',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Geselecteerde ontvangstbewijzen succesvol verwijderd!',
                    'preview'             => 'Voorvertoning',
                    'title'               => 'Titel',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'active'   => 'Actief',
                            'inactive' => 'Inactief',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Terug',
                'btn-title' => 'Sla Ontvangstbewijs Op',
                'title'     => 'Nieuw Ontvangstbewijs Aanmaken',

                'general' => [
                    'cashier-name-label'      => 'Naam Kassier Label',
                    'change-amount-label'     => 'Veranderbedrag Label',
                    'credit-amount-label'     => 'Kredietbedrag Label',
                    'discount-amt-label'      => 'Kortingbedrag Label',
                    'display-cashier-name'    => 'Toon Naam Kassier',
                    'display-change-amount'   => 'Toon Veranderbedrag',
                    'display-credit-amount'   => 'Toon Kredietbedrag',
                    'display-customer-name'   => 'Toon Klantnaam',
                    'display-date'            => 'Toon Datum',
                    'display-discount-amt'    => 'Toon Kortingbedrag',
                    'display-order-id'        => 'Toon Bestel-ID',
                    'display-outlet-address'  => 'Toon Outletadres',
                    'display-outlet-name'     => 'Toon Outletnaam',
                    'display-sub-total'       => 'Toon Subtotaal',
                    'display-tax'             => 'Toon Belasting',
                    'grand-total-label'       => 'Totaalbedrag Label',
                    'order-id-label'          => 'Bestel-ID Label',
                    'receipt-title'           => 'Titel Ontvangstbewijs',
                    'show-order-barcode'      => 'Bestelbarcode weergeven',
                    'show-print-confirmation' => 'Afdrukbevestiging weergeven',
                    'status'                  => 'Status',
                    'sub-total-label'         => 'Subtotaal Label',
                    'tax-label'               => 'Belasting Label',
                    'title'                   => 'Algemeen',
                ],

                'logo' => [
                    'display-logo' => 'Toon Logo',
                    'logo-alt'     => 'Logo Alt',
                    'logo-height'  => 'Logo Hoogte (in px)',
                    'logo-width'   => 'Logo Breedte (in px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Upload Logo',
                ],

                'header' => [
                    'header-content' => 'Koptekst Inhoud',
                    'title'          => 'Koptekst',
                ],

                'footer' => [
                    'footer-content' => 'Voettekst Inhoud',
                    'title'          => 'Voettekst',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Terug',
                'btn-title' => 'Sla Ontvangstbewijs Op',
                'title'     => 'Bewerk Ontvangstbewijs',

                'general' => [
                    'cashier-name-label'      => 'Naam Kassier Label',
                    'change-amount-label'     => 'Veranderbedrag Label',
                    'credit-amount-label'     => 'Kredietbedrag Label',
                    'discount-amt-label'      => 'Kortingbedrag Label',
                    'display-cashier-name'    => 'Toon Naam Kassier',
                    'display-change-amount'   => 'Toon Veranderbedrag',
                    'display-credit-amount'   => 'Toon Kredietbedrag',
                    'display-customer-name'   => 'Toon Klantnaam',
                    'display-date'            => 'Toon Datum',
                    'display-discount-amt'    => 'Toon Kortingbedrag',
                    'display-order-id'        => 'Toon Bestel-ID',
                    'display-outlet-address'  => 'Toon Outletadres',
                    'display-outlet-name'     => 'Toon Outletnaam',
                    'display-sub-total'       => 'Toon Subtotaal',
                    'display-tax'             => 'Toon Belasting',
                    'grand-total-label'       => 'Totaalbedrag Label',
                    'order-id-label'          => 'Bestel-ID Label',
                    'receipt-title'           => 'Titel Ontvangstbewijs',
                    'show-order-barcode'      => 'Bestelbarcode weergeven',
                    'show-print-confirmation' => 'Afdrukbevestiging weergeven',
                    'status'                  => 'Status',
                    'sub-total-label'         => 'Subtotaal Label',
                    'tax-label'               => 'Belasting Label',
                    'title'                   => 'Algemeen',
                ],

                'logo' => [
                    'display-logo' => 'Toon Logo',
                    'logo-alt'     => 'Logo Alt',
                    'logo-height'  => 'Logo Hoogte (in px)',
                    'logo-width'   => 'Logo Breedte (in px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Upload Logo',
                ],

                'header' => [
                    'header-content' => 'Koptekst Inhoud',
                    'title'          => 'Koptekst',
                ],

                'footer' => [
                    'footer-content' => 'Voettekst Inhoud',
                    'title'          => 'Voettekst',
                ],
            ],

            'preview' => [
                'amount'         => 'Bedrag',
                'cashier'        => 'Kassier',
                'change-amount'  => 'Veranderbedrag',
                'customer'       => 'Klant',
                'customer-email' => 'Klant E-mail',
                'customer-name'  => 'Klantnaam',
                'customer-phone' => 'Klant Telefoon',
                'date'           => 'Datum',
                'discount'       => 'Korting',
                'email'          => 'E-mail',
                'grand-total'    => 'Totaalbedrag',
                'order-id'       => 'Bestel-ID',
                'phone'          => 'Telefoon',
                'price'          => 'Prijs',
                'product'        => 'Product',
                'qty'            => 'Hoeveelheid',
                'sub-total'      => 'Subtotaal',
                'tax'            => 'Belasting',
                'title'          => 'Voorvertoning Ontvangstbewijs',
                'total-qty'      => 'Totaal Hoeveelheid',
            ],

            'create-success' => 'Ontvangstbewijs succesvol aangemaakt!',
            'delete-failed'  => 'Verwijderen van ontvangstbewijs mislukt!',
            'delete-success' => 'Ontvangstbewijs succesvol verwijderd!',
            'update-success' => 'Ontvangstbewijs succesvol bijgewerkt!',
        ],
    ],

    'emails' => [
        'dear'     => 'Beste :name',
        'greeting' => 'Hallo!',

        'registration' => [
            'message' => 'Gefeliciteerd! Je account is succesvol aangemaakt. Log in op je account en begin met het gebruiken van het POS-systeem.',
            'subject' => 'POS Gebruiker Registratie E-mail',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Inloggen',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
