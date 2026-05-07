<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Credenziali non valide.',
                'not-activated'       => 'Il tuo account non è attivato.',
                'verify-first'        => 'Per favore verifica prima la tua email.',
                'success'             => 'Hai effettuato l\'accesso con successo.',
            ],

            'logout' => [
                'no-login-agent' => 'Nessun agente è connesso.',
                'success'        => 'Hai effettuato il logout con successo.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'La password inserita è errata.',
                    'success'          => 'Il tuo account è stato aggiornato con successo.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Cliente creato con successo!',
            'update-success' => 'Cliente aggiornato con successo!',
            'delete-success' => 'Cliente eliminato con successo!',
            'delete-failed'  => 'Eliminazione del cliente fallita!',
            'pending-orders' => 'Il cliente ha ordini in sospeso!',
        ],

        'cart' => [
            'already-applied'     => 'Coupon già applicato!',
            'coupon-applied'      => 'Coupon applicato con successo!',
            'coupon-removed'      => 'Coupon rimosso con successo!',
            'create-success'      => 'Carrello creato con successo!',
            'invalid-coupon'      => 'Codice coupon non valido!',
            'item-add-success'    => 'Prodotto aggiunto al carrello con successo!',
            'item-remove-success' => 'Prodotto rimosso dal carrello con successo!',
            'item-update-success' => 'Prodotto aggiornato con successo!',
            'not-found'           => 'Carrello non trovato!',
        ],

        'payment' => [
            'title' => 'Pagamento Pos',

            'options' => [
                'cash' => [
                    'title'       => 'Pagamento in Contante Pos',
                    'description' => 'Questo è un pagamento in contante Pos.',
                ],

                'card' => [
                    'title'       => 'Pagamento con Carta Pos',
                    'description' => 'Questo è un pagamento con carta Pos.',
                ],

                'split' => [
                    'title'       => 'Pagamento Diviso Pos',
                    'description' => 'Questo è un pagamento diviso Pos.',
                ],
            ],

            'no-items' => 'Nessun articolo nel carrello per procedere al pagamento.',
            'success'  => 'Pagamento completato con successo!',
        ],

        'shipping' => [
            'title'       => 'Spedizione Pos',
            'description' => 'Questa è una spedizione Pos gratuita.',
        ],

        'order' => [
            'sync-success' => 'Ordine sincronizzato con successo!',
        ],

        'return' => [
            'create-success'              => 'Rimborso creato con successo',
            'creation-error'              => 'La creazione del rimborso non è consentita.',
            'invalid-qty'                 => 'Abbiamo trovato una quantità non valida per fatturare gli articoli.',
            'invalid-refund-amount-error' => 'L’importo del rimborso deve essere maggiore di zero.',
            'refund-limit-error'          => 'L’importo del rimborso :amount non può essere elaborato.',
        ],

        'drawer' => [
            'create-success' => 'Cassetto aperto con successo!',
            'not-opened'     => 'Cassetto non aperto.',
            'close-success'  => 'Cassetto chiuso con successo!',
        ],

        'products' => [
            'request-success' => 'Richiesta prodotto inviata con successo.',
            'create-success'  => 'Prodotto creato con successo!',
        ],

        'reports' => [
            'orders'                  => 'Ordini',
            'average-order-value'     => 'Valore Medio Ordine',
            'average-items-per-order' => 'Articoli Medi per Ordine',
            'discounted-offers'       => 'Offerte Scontate',
            'cash-payments'           => 'Pagamenti in Contante',
            'other-payments'          => 'Altri Pagamenti',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Nota dell\'ordine',
                    'note-not-available' => 'Nota non disponibile!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'L\'Estensione del Punto Vendita (POS) di Bagisto.',
                    'title' => 'Punto Vendita',

                    'settings' => [
                        'info'  => 'Abilita il POS, Imposta la Configurazione Generale, Prodotto POS e Ricevuta.',
                        'title' => 'Impostazioni',

                        'general' => [
                            'footer-content'       => 'Contenuto del Piè di Pagina',
                            'footer-note'          => 'Nota del Piè di Pagina',
                            'frontend-url'         => 'URL del frontend',
                            'heading-on-login'     => 'Intestazione al Login',
                            'info'                 => 'Le impostazioni generali permettono di configurare la pagina utente del POS, aggiungendo logo, intestazioni, contenuti del piè di pagina, nota del piè di pagina, ecc.',
                            'pos-logo'             => 'Logo POS',
                            'status'               => 'Stato',
                            'sub-heading-on-login' => 'Sotto-Intestazione al Login',
                            'title'                => 'Generale',
                        ],

                        'barcode' => [
                            'height'             => 'Altezza',
                            'hide-barcode'       => 'Nascondi Codice a Barre',
                            'info'               => 'Le impostazioni del codice a barre permettono di configurare la generazione di codici a barre, l\'altezza del codice a barre, la larghezza del codice a barre, il tipo di codice a barre, ecc.',
                            'prefix'             => 'Prefisso',
                            'print-product-name' => 'Stampa Nome Prodotto',
                            'title'              => 'Codice a Barre',
                            'width'              => 'Larghezza',

                            'generate-with' => [
                                'title' => 'Genera Codice a Barre Con',

                                'options' => [
                                    'product-id' => 'ID Prodotto',
                                    'sku'        => 'SKU Prodotto',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'Consenti SKU per prodotto personalizzato',
                            'info'      => 'Le impostazioni del prodotto consentono le configurazioni per SKU del prodotto.',
                            'title'     => 'Prodotti',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Assegna Prodotti',
            'banks'            => 'Banche',
            'barcode-products' => 'Prodotti con Codice a Barre',
            'create'           => 'Crea',
            'delete'           => 'Elimina',
            'edit'             => 'Modifica',
            'generate-barcode' => 'Genera Codice a Barre',
            'orders'           => 'Ordini',
            'outlets'          => 'Punti Vendita',
            'pos'              => 'Punto Vendita (POS)',
            'preview'          => 'Anteprima',
            'print-barcode'    => 'Stampa Codice a Barre',
            'receipts'         => 'Ricevute',
            'requests'         => 'Richieste',
            'sales-report'     => 'Rapporto di Vendita',
            'users'            => 'Agenti',
            'view'             => 'Visualizza',
        ],

        'layouts' => [
            'banks'            => 'Banche',
            'barcode-products' => 'Prodotti con Codice a Barre',
            'orders'           => 'Ordini',
            'pos'              => 'Punto Vendita (POS)',
            'receipts'         => 'Ricevute',
            'requests'         => 'Richieste',
            'sales-report'     => 'Rapporto di vendita',

            'users' => [
                'agents'   => 'Agenti',
                'outlets'  => 'Punti Vendita',
                'title'    => 'Agenti',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Crea Agenti',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Agenti',

                    'datagrid' => [
                        'action'              => 'Azione',
                        'delete'              => 'Elimina',
                        'edit'                => 'Modifica',
                        'email'               => 'Email',
                        'full-name'           => 'Nome completo',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'Agenti selezionati eliminati con successo!',
                        'mass-update-success' => 'Agenti selezionati aggiornati con successo!',
                        'outlet-name'         => 'Nome del punto vendita',
                        'profile-image'       => 'Immagine del profilo',
                        'update-status'       => 'Aggiorna stato',
                        'username'            => 'Nome utente',

                        'status' => [
                            'title' => 'Stato',

                            'options' => [
                                'active'  => 'Attivo',
                                'disable' => 'Disabilita',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Indietro',
                    'confirm-password'  => 'Conferma Password',
                    'email'             => 'Email',
                    'first-name'        => 'Nome',
                    'general'           => 'Generale',
                    'image'             => 'Immagine',
                    'last-name'         => 'Cognome',
                    'outlet'            => 'Punto Vendita',
                    'outlet-and-status' => 'Punto Vendita & Stato',
                    'password'          => 'Password',
                    'save-btn'          => 'Salva Agente',
                    'select-outlet'     => 'Seleziona Punto Vendita',
                    'status'            => 'Stato',
                    'title'             => 'Aggiungi Agente',
                    'user-image'        => 'Carica Immagine Agente',
                    'username'          => 'Nome Utente',
                ],

                'edit' => [
                    'back-btn'          => 'Indietro',
                    'confirm-password'  => 'Conferma Password',
                    'email'             => 'Email',
                    'first-name'        => 'Nome',
                    'general'           => 'Generale',
                    'image'             => 'Immagine',
                    'last-name'         => 'Cognome',
                    'outlet'            => 'Punto Vendita',
                    'outlet-and-status' => 'Punto Vendita & Stato',
                    'password'          => 'Password',
                    'save-btn'          => 'Salva Agente',
                    'select-outlet'     => 'Seleziona Punto Vendita',
                    'status'            => 'Stato',
                    'title'             => 'Modifica Agente',
                    'user-image'        => 'Carica Immagine Agente',
                    'username'          => 'Nome Utente',
                ],

                'create-success' => 'Agente creato con successo!',
                'delete-failed'  => 'Eliminazione agente fallita!',
                'delete-success' => 'Agente eliminato con successo!',
                'update-success' => 'Agente aggiornato con successo!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Crea Punto Vendita',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Punti Vendita',

                    'datagrid' => [
                        'action'              => 'Azione',
                        'active'              => 'Attivo',
                        'assign'              => 'Assegna Prodotto',
                        'delete'              => 'Elimina',
                        'edit'                => 'Modifica',
                        'id'                  => 'Id',
                        'inactive'            => 'Non Attivo',
                        'inventory-source'    => 'Fonte Inventario',
                        'mass-delete-success' => 'I negozi selezionati sono stati eliminati con successo!',
                        'mass-update-success' => 'I negozi selezionati sono stati aggiornati con successo!',
                        'name'                => 'Nome',
                        'receipt-title'       => 'Titolo Ricevuta',
                        'status'              => 'Stato',
                        'title'               => 'Lista Negozi POS',
                        'update-status'       => 'Aggiorna Stato',
                    ],
                ],

                'create' => [
                    'address'                 => 'Indirizzo',
                    'back-btn'                => 'Indietro',
                    'btn-title'               => 'Salva Punto Vendita',
                    'city'                    => 'Città',
                    'country'                 => 'Paese',
                    'customer-care-number'    => 'Numero Assistenza Clienti',
                    'email'                   => 'Email',
                    'general'                 => 'Generale',
                    'gst-number'              => 'Numero GST',
                    'inventory'               => 'Inventario',
                    'inventory-source'        => 'Fonte Inventario',
                    'low-stock-qty'           => 'Quantità Bassa di Stock',
                    'name'                    => 'Nome Punto Vendita',
                    'phone'                   => 'Telefono',
                    'postcode'                => 'CAP',
                    'receipt'                 => 'Ricevuta',
                    'select-country'          => 'Seleziona Paese',
                    'select-inventory-source' => 'Seleziona Fonte Inventario',
                    'select-receipt'          => 'Seleziona Ricevuta',
                    'state'                   => 'Stato',
                    'status'                  => 'Stato',
                    'store-address'           => 'Indirizzo Punto Vendita',
                    'title'                   => 'Aggiungi Punto Vendita',
                    'website'                 => 'Sito Web',
                ],

                'edit' => [
                    'address'                 => 'Indirizzo',
                    'back-btn'                => 'Indietro',
                    'btn-title'               => 'Salva Punto Vendita',
                    'city'                    => 'Città',
                    'country'                 => 'Paese',
                    'customer-care-number'    => 'Numero Assistenza Clienti',
                    'email'                   => 'Email',
                    'general'                 => 'Generale',
                    'gst-number'              => 'Numero GST',
                    'inventory'               => 'Inventario',
                    'inventory-source'        => 'Fonte Inventario',
                    'low-stock-qty'           => 'Quantità Bassa di Stock',
                    'name'                    => 'Nome Punto Vendita',
                    'phone'                   => 'Telefono',
                    'postcode'                => 'CAP',
                    'receipt'                 => 'Ricevuta',
                    'select-country'          => 'Seleziona Paese',
                    'select-inventory-source' => 'Seleziona Fonte Inventario',
                    'select-receipt'          => 'Seleziona Ricevuta',
                    'state'                   => 'Stato',
                    'status'                  => 'Stato',
                    'store-address'           => 'Indirizzo Punto Vendita',
                    'title'                   => 'Modifica Punto Vendita',
                    'website'                 => 'Sito Web',
                ],

                'assign' => [
                    'back-btn' => 'Indietro',
                    'title'    => 'Gestisci Prodotti Punto Vendita',

                    'datagrid' => [
                        'active'              => 'Attivo',
                        'assign'              => 'Assegna',
                        'disable'             => 'Disabilita',
                        'id'                  => 'Id',
                        'id-value'            => 'Id - :id',
                        'image'               => 'Immagine',
                        'mass-assign-success' => 'Assegnazione prodotto aggiornata con successo!',
                        'name'                => 'Nome',
                        'out-of-stock'        => 'Esaurito',
                        'pos-status'          => 'Stato POS',
                        'price'               => 'Prezzo',
                        'product-image'       => 'Immagine Prodotto',
                        'qty'                 => 'Quantità',
                        'qty-value'           => ':qty Disponibile',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Stato',
                        'type'                => 'Tipo',
                        'unassign'            => 'Rimuovi Assegnazione',
                        'update-assign'       => 'Aggiorna Assegnazione',
                    ],
                ],

                'create-success' => 'Punto Vendita creato con successo!',
                'delete-failed'  => 'Eliminazione punto vendita fallita!',
                'delete-success' => 'Punto Vendita eliminato con successo!',
                'update-success' => 'Punto Vendita aggiornato con successo!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Prodotti con Codice a Barre',

                'datagrid' => [
                    'barcode'               => 'Codice a Barre',
                    'generate-barcode'      => 'Genera Codice a Barre',
                    'print-barcode'         => 'Stampa Codice a Barre',
                    'id'                    => 'Id',
                    'id-value'              => 'Id - :id',
                    'image'                 => 'Immagine',
                    'mass-generate-success' => 'Codici a barre dei prodotti selezionati generati con successo!',
                    'name'                  => 'Nome',
                    'out-of-stock'          => 'Esaurito',
                    'price'                 => 'Prezzo',
                    'product-image'         => 'Immagine Prodotto',
                    'qty'                   => 'Quantità',
                    'qty-value'             => ':qty Disponibile',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Stato',

                        'options' => [
                            'active'  => 'Attivo',
                            'disable' => 'Disabilita',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'Indietro',
                'btn-title' => 'Stampa',
                'qty'       => 'Quantità',
                'title'     => 'Stampa Codice a Barre',
            ],

            'generate-failed'  => 'Generazione del codice a barre fallita!',
            'generate-success' => 'Codice a barre generato con successo!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Ordini',

                'datagrid' => [
                    'customer-name' => 'Nome Cliente',
                    'grand-total'   => 'Totale',
                    'order-date'    => 'Data Ordine',
                    'order-id'      => 'ID Ordine',
                    'order-ref-id'  => 'ID Riferimento Ordine',
                    'view'          => 'Visualizza',

                    'status' => [
                        'title' => 'Stato',

                        'options' => [
                            'canceled'        => 'Annullato',
                            'closed'          => 'Chiuso',
                            'completed'       => 'Completato',
                            'fraud'           => 'Frode',
                            'pending'         => 'In Attesa',
                            'pending-payment' => 'In Attesa di Pagamento',
                            'processing'      => 'In Elaborazione',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Richieste',

                'datagrid' => [
                    'id'                  => 'Id',
                    'product-image'       => 'Immagine Prodotto',
                    'mass-update-error'   => 'Aggiornamento richiesta fallito!',
                    'mass-update-success' => 'Richieste selezionate aggiornate con successo!',
                    'product-name'        => 'Nome Prodotto',
                    'outlet-name'         => 'Nome Punto Vendita',
                    'qty-value'           => 'QTY - :qty',
                    'request-date'        => 'Data Richiesta',
                    'requested-qty'       => 'Quantità Richiesta',
                    'update-status'       => 'Aggiorna Stato',
                    'user-name'           => 'Nome Utente',

                    'status' => [
                        'title' => 'Stato',

                        'options' => [
                            'complete' => 'Completa',
                            'decline'  => 'Rifiuta',
                            'pending'  => 'In Attesa',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Indietro',
                'btn-title' => 'Salva',
                'title'     => 'Dettagli Prodotto Richiesto #:id',

                'user-info' => [
                    'email'            => 'Email',
                    'name'             => 'Nome',
                    'outlet-address'   => 'Indirizzo Punto Vendita',
                    'outlet-inventory' => 'Fonte Inventario Punto Vendita',
                    'outlet-name'      => 'Nome Punto Vendita',
                    'title'            => 'Informazioni Utente',
                ],

                'request-info' => [
                    'comment'       => 'Commento',
                    'product-name'  => 'Nome Prodotto',
                    'qty-value'     => 'Quantità - :qty',
                    'request-date'  => 'Data Richiesta',
                    'requested-qty' => 'Quantità Richiesta',
                    'title'         => 'Informazioni Richiesta',

                    'status' => [
                        'title' => 'Stato',

                        'options' => [
                            'complete' => 'Completa',
                            'decline'  => 'Rifiuta',
                            'pending'  => 'In Attesa',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Aggiornamento richiesta fallito!',
            'update-success' => 'Richiesta aggiornata con successo!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Crea Banca',
                'title'     => 'Banche',

                'datagrid' => [
                    'active'              => 'Attivo',
                    'address'             => 'Indirizzo della banca',
                    'agent-name'          => 'Agente',
                    'delete'              => 'Elimina',
                    'disable'             => 'Disabilita',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Banche selezionate eliminate con successo!',
                    'name'                => 'Nome della banca',
                    'status'              => 'Stato',
                ],
            ],

            'create' => [
                'back-btn'  => 'Indietro',
                'btn-title' => 'Salva Banca',
                'title'     => 'Crea Nuova Banca',

                'general' => [
                    'address' => 'Indirizzo',
                    'email'   => 'Email',
                    'name'    => 'Nome',
                    'phone'   => 'Telefono',
                    'title'   => 'Generale',
                ],

                'agent-and-status' => [
                    'agent'        => 'Assegna Agente POS',
                    'bank-status'  => 'Stato Banca',
                    'select-agent' => 'Seleziona Agente',
                    'title'        => 'Agente POS & Stato Banca',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Indietro',
                'btn-title' => 'Salva Banca',
                'title'     => 'Modifica Banca',

                'general' => [
                    'address' => 'Indirizzo',
                    'email'   => 'Email',
                    'name'    => 'Nome',
                    'phone'   => 'Telefono',
                    'title'   => 'Generale',
                ],

                'agent-and-status' => [
                    'agent'        => 'Assegna Agente POS',
                    'bank-status'  => 'Stato Banca',
                    'select-agent' => 'Seleziona Agente',
                    'title'        => 'Agente POS & Stato Banca',
                ],
            ],

            'create-success' => 'Banca creata con successo!',
            'delete-failed'  => 'Eliminazione banca fallita!',
            'delete-success' => 'Banca eliminata con successo!',
            'update-success' => 'Banca aggiornata con successo!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Rapporto di vendita',

                'datagrid' => [
                    'bank-name'      => 'Nome Banca',
                    'grand-total'    => 'Totale',
                    'order-date'     => 'Data Ordine',
                    'order-id'       => 'ID Ordine',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Nota Ordine',
                    'outlet-name'    => 'Nome Punto Vendita',
                    'payment-method' => 'Metodo di Pagamento',
                    'view'           => 'Visualizza',

                    'status' => [
                        'title' => 'Stato',

                        'options' => [
                            'canceled'        => 'Annullato',
                            'closed'          => 'Chiuso',
                            'completed'       => 'Completato',
                            'fraud'           => 'Frode',
                            'pending'         => 'In Attesa',
                            'pending-payment' => 'In Attesa di Pagamento',
                            'processing'      => 'In Elaborazione',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Crea Ricevuta',
                'title'      => 'Ricevute',

                'datagrid' => [
                    'delete'              => 'Elimina',
                    'edit'                => 'Modifica',
                    'id'                  => 'Id',
                    'mass-delete-success' => 'Ricevute selezionate eliminate con successo!',
                    'preview'             => 'Anteprima',
                    'title'               => 'Titolo',

                    'status' => [
                        'title' => 'Stato',

                        'options' => [
                            'active'   => 'Attivo',
                            'inactive' => 'Inattivo',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Indietro',
                'btn-title' => 'Salva Ricevuta',
                'title'     => 'Crea Nuova Ricevuta',

                'general' => [
                    'cashier-name-label'      => 'Etichetta Nome Cassiere',
                    'change-amount-label'     => 'Etichetta Importo Cambio',
                    'credit-amount-label'     => 'Etichetta Importo Credito',
                    'discount-amt-label'      => 'Etichetta Importo Sconto',
                    'display-cashier-name'    => 'Visualizza Nome Cassiere',
                    'display-change-amount'   => 'Visualizza Importo Cambio',
                    'display-credit-amount'   => 'Visualizza Importo Credito',
                    'display-customer-name'   => 'Visualizza Nome Cliente',
                    'display-date'            => 'Visualizza Data',
                    'display-discount-amt'    => 'Visualizza Importo Sconto',
                    'display-order-id'        => 'Visualizza ID Ordine',
                    'display-outlet-address'  => 'Visualizza Indirizzo Punto Vendita',
                    'display-outlet-name'     => 'Visualizza Nome Punto Vendita',
                    'display-sub-total'       => 'Visualizza Sub Totale',
                    'display-tax'             => 'Visualizza Tassa',
                    'grand-total-label'       => 'Etichetta Totale',
                    'order-id-label'          => 'Etichetta ID Ordine',
                    'receipt-title'           => 'Titolo Ricevuta',
                    'show-order-barcode'      => 'Mostra il codice a barre dell\'ordine',
                    'show-print-confirmation' => 'Mostra la conferma di stampa',
                    'status'                  => 'Stato',
                    'sub-total-label'         => 'Etichetta Sub Totale',
                    'tax-label'               => 'Etichetta Tassa',
                    'title'                   => 'Generale',
                ],

                'logo' => [
                    'display-logo' => 'Visualizza Logo',
                    'logo-alt'     => 'Testo Alternativo Logo',
                    'logo-height'  => 'Altezza Logo (in px)',
                    'logo-width'   => 'Larghezza Logo (in px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Carica Logo',
                ],

                'header' => [
                    'header-content' => 'Contenuto Intestazione',
                    'title'          => 'Intestazione',
                ],

                'footer' => [
                    'footer-content' => 'Contenuto Piè di Pagina',
                    'title'          => 'Piè di Pagina',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Indietro',
                'btn-title' => 'Salva Ricevuta',
                'title'     => 'Modifica Ricevuta',

                'general' => [
                    'cashier-name-label'      => 'Etichetta Nome Cassiere',
                    'change-amount-label'     => 'Etichetta Importo Cambio',
                    'credit-amount-label'     => 'Etichetta Importo Credito',
                    'discount-amt-label'      => 'Etichetta Importo Sconto',
                    'display-cashier-name'    => 'Visualizza Nome Cassiere',
                    'display-change-amount'   => 'Visualizza Importo Cambio',
                    'display-credit-amount'   => 'Visualizza Importo Credito',
                    'display-customer-name'   => 'Visualizza Nome Cliente',
                    'display-date'            => 'Visualizza Data',
                    'display-discount-amt'    => 'Visualizza Importo Sconto',
                    'display-order-id'        => 'Visualizza ID Ordine',
                    'display-outlet-address'  => 'Visualizza Indirizzo Punto Vendita',
                    'display-outlet-name'     => 'Visualizza Nome Punto Vendita',
                    'display-sub-total'       => 'Visualizza Sub Totale',
                    'display-tax'             => 'Visualizza Tassa',
                    'grand-total-label'       => 'Etichetta Totale',
                    'order-id-label'          => 'Etichetta ID Ordine',
                    'receipt-title'           => 'Titolo Ricevuta',
                    'show-order-barcode'      => 'Mostra il codice a barre dell\'ordine',
                    'show-print-confirmation' => 'Mostra la conferma di stampa',
                    'status'                  => 'Stato',
                    'sub-total-label'         => 'Etichetta Sub Totale',
                    'tax-label'               => 'Etichetta Tassa',
                    'title'                   => 'Generale',
                ],

                'logo' => [
                    'display-logo' => 'Visualizza Logo',
                    'logo-alt'     => 'Testo Alternativo Logo',
                    'logo-height'  => 'Altezza Logo (in px)',
                    'logo-width'   => 'Larghezza Logo (in px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Carica Logo',
                ],

                'header' => [
                    'header-content' => 'Contenuto Intestazione',
                    'title'          => 'Intestazione',
                ],

                'footer' => [
                    'footer-content' => 'Contenuto Piè di Pagina',
                    'title'          => 'Piè di Pagina',
                ],
            ],

            'preview' => [
                'amount'         => 'Importo',
                'cashier'        => 'Cassiere',
                'change-amount'  => 'Cambio',
                'customer'       => 'Cliente',
                'customer-email' => 'Email Cliente',
                'customer-name'  => 'Nome Cliente',
                'customer-phone' => 'Telefono Cliente',
                'date'           => 'Data',
                'discount'       => 'Sconto',
                'email'          => 'Email',
                'grand-total'    => 'Totale',
                'order-id'       => 'ID Ordine',
                'phone'          => 'Telefono',
                'price'          => 'Prezzo',
                'product'        => 'Prodotto',
                'qty'            => 'Quantità',
                'sub-total'      => 'Sub Totale',
                'tax'            => 'Tassa',
                'title'          => 'Anteprima Ricevuta',
                'total-qty'      => 'Quantità Totale',
            ],

            'create-success' => 'Ricevuta creata con successo!',
            'delete-failed'  => 'Eliminazione ricevuta fallita!',
            'delete-success' => 'Ricevuta eliminata con successo!',
            'update-success' => 'Ricevuta aggiornata con successo!',
        ],
    ],

    'emails' => [
        'dear'     => 'Caro/a :name',
        'greeting' => 'Saluti!',

        'registration' => [
            'message' => 'Congratulazioni! Il tuo account è stato creato con successo. Per favore, accedi al tuo account e inizia a usare il sistema POS.',
            'subject' => 'Email di Registrazione Utente POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Accesso',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
